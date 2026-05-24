# Instructions — FeedWatch Test Strategy (Codeception)

## Agent role

> You are a **Symfony Expert**, **Codeception** and **PHP 8.4** specialist.
> You write idiomatic, deterministic, readable tests that exercise the **real logic** of the code (never a double of the class under test). You follow the repository's existing conventions and make every quality gate pass before concluding.

---

## 1. Execution environment

- **No local PHP**: PHP 8.4 lives in the `feedwatch-web-1` Docker container (working dir `/app`, which maps to `symfony/`). The database is **SQLite**, stored at `var/data_<env>.db` inside the container (`DATABASE_URL` resolves to `sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db`). There is no separate DB container anymore.
- Every PHP / Composer / Console command goes through:
  ```bash
  docker exec feedwatch-web-1 sh -c '<command>'
  ```
- Run the suites:
  ```bash
  docker exec feedwatch-web-1 sh -c 'php vendor/bin/codecept run'              # everything
  docker exec feedwatch-web-1 sh -c 'php vendor/bin/codecept run Unit'         # one suite
  docker exec feedwatch-web-1 sh -c 'php vendor/bin/codecept run Functional Repository/ArticleRepositoryCest.php'  # one file
  ```
- **Stale test database** (`Functional` returns 500s with "table … doesn't exist") → recreate the schema:
  ```bash
  docker exec feedwatch-web-1 sh -c 'php bin/console doctrine:schema:drop --force --full-database --env=test \
    && php bin/console doctrine:schema:create --env=test'
  ```

---

## 2. The three suites — when to use which

| Suite | Modules | Target | Speed |
|---|---|---|---|
| **Unit** | `Asserts` | Pure isolated logic: services, feed readers, enums, message handler. Dependencies are **doubled**. | Very fast |
| **Functional** | `Symfony` + `Doctrine` (cleanup) | Anything touching the DI container or the database: repositories (queries), controllers, commands. | Medium |
| **Acceptance** | `PhpBrowser` (https://localhost) | End-to-end browser journeys, a few key scenarios. Runs against the **dev** database. | Slow |

**Placement rule**: test each component at the lowest layer that covers its logic. Move up a layer only when the dependency (DB, real HTTP, security) is part of what you want to prove.

**Out of scope by default**: entities (getters/setters) and forms (covered indirectly by functional controller tests).

---

## 3. Code conventions

- **Cest format** only (`*Cest.php`), one class per subject, public methods = one test each.
- **Method naming**: a sentence describing the expected behaviour (`readReturnsNullWhenTheChecksumHasNotChanged`), never `testXxx`.
- **Namespace**: `App\Tests\<Suite>\...`; the tester is injected (`UnitTester`, `FunctionalTester`, `AcceptanceTester`).
- `declare(strict_types=1);` at the top of every file.
- Tests are **not linted** (`phpcs`/`phpstan` only target `src/`). Still keep a style consistent with the existing tests (`_before`, private helpers at the bottom of the class).
- **Controllers live under `App\Http\…`**: public controllers in `App\Http\Controller` (e.g. `HomeController`, `SecurityController`) and admin controllers in `App\Http\AdminController` (e.g. `IndexController`, `CategoryController`, `SourceController`, `MonitoringController`). Test namespaces stay flat (`App\Tests\Functional`, `App\Tests\Acceptance`) but the SUT imports reflect the new layout.

---

## 4. Codeception patterns to apply

### Double a dependency (Unit)
```php
use Codeception\Stub;
$dep = Stub::makeEmpty(SomeInterface::class, [
    'method' => fn (Arg $a): bool => /* scripted behaviour */,
]);
```
- **Capture calls**: a closure that pushes into an array passed by reference (`use (&$calls)`).
- **Expected exception**: `$I->expectThrowable(RuntimeException::class, fn () => $sut->call());`.

### Pitfall — magic `@method` methods
`UserRepository::findOneByEmail()` is a `@method` (routed through `__call`). **`Stub` CANNOT stub it** (returns `null`). To test a service that calls it, use an **in-memory subclass** with a neutralised constructor:
```php
new class ($found) extends UserRepository {
    public array $saved = [];
    public function __construct(private readonly ?User $found = null) {}   // does not call parent
    public function findOneByEmail(string $email): ?User { return $this->found; }
    public function save(User $user, bool $flush = false): void { $this->saved[] = $user; }
};
```

### HTTP client (feed readers, message handler)
Always **network-free**: `Symfony\Component\HttpClient\MockHttpClient` + `MockResponse`.
```php
new XMLService(new MockHttpClient(new MockResponse($xmlFixture)));
```

### Targeted value resolvers (controllers)
`PageSizeResolver` is a `ValueResolverInterface` exposed under the `pageSize` target, fed by the `%items_per_page%` parameter (`[10, 20, 50, 100]`). Controllers receive it via `#[ValueResolver('pageSize')] int $pageSize`.
- Test it in **Unit**: instantiate `new PageSizeResolver([10, 20, …])`, drive `resolve(Request, ArgumentMetadata)` and collect the generator with `iterator_to_array()`. Cover: value missing, value in the whitelist, value outside the whitelist (must fall back to `options[0]`).
- For controllers, a Functional test that hits a route with `?pageSize=` is enough to prove the wiring; do not double the resolver.

### Database (Functional)
- Create precise data: `$id = $I->haveInRepository(Source::class, [...]);` (typed setters accept enums).
- Grab a service: `$I->grabService(SourceRepository::class);`
- Grab an entity: `$I->grabEntityFromRepository(Source::class, ['id' => $id]);`
- DB assertions: `seeInRepository` / `dontSeeInRepository`.
- `cleanup: true` wraps each test in a rolled-back transaction → no cross-test pollution.

### Fixtures
- For stable datasets: a `test` group fixture in `src/Fixture/Test/`, loaded via `$I->loadFixtures([$I->grabService(XxxFixture::class)])` in `_before`.
- For fine control (e.g. a relative `lastFetchedAt`), prefer inline `haveInRepository`.

### Acceptance
- Authenticate through the real form: `$I->loginAsAUser(UserFixture::ADMIN_EMAIL)` (`CommonTrait`).
- The dev DB is **persistent**: anything a test creates must be **deleted** at the end (grab the delete form's action via XPath, then `submitForm`).

---

## 5. Quality gates (mandatory before concluding)

```bash
docker exec feedwatch-web-1 sh -c './vendor/bin/phpcs src'                              # PSR12, src only
docker exec feedwatch-web-1 sh -c './vendor/bin/phpstan analyse --no-progress --memory-limit=-1'  # level 6, phpstan.dist.neon, src only
docker exec feedwatch-web-1 sh -c 'php vendor/bin/codecept run'                          # all suites green
```
Any new file under `src/` (e.g. a test fixture) **must** pass phpcs + phpstan.

---

## 6. Procedure to cover a new component

1. **Read** the target source and its dependencies; identify the branches (conditions, exceptions, null values).
2. **Pick the suite** per the table in §2.
3. **Write** a `Cest`: one test per branch/behaviour, minimal data, doubled dependencies (§4).
4. **Run** the file alone, fix until green.
5. **Run the full suite** + the quality gates (§5).
6. If a stable dataset is reusable, turn it into a `Test` **fixture**; otherwise inline `haveInRepository`.

---

## 7. What is already covered (reference)

- **Unit**: `UserService`, `SourceService` (`getReader`, `updateSource`), `ArticleService`, `AbstractFeedReader`, `XMLService`, `HTMLService`, `SourceMessageHandler` (happy path + reader-returns-null), enums (`Periodicity`/`Format`/`Status`), `CategoryListener`, `DateTimeImmutableListener`, `DateTimeImmutableTrait`, `ComposerVersion`.
- **Functional**: `SourceRepository::findDueSources`, `ArticleRepository`, controllers `Home`/`Security`/`Admin\Category`/`Admin\Source`, commands `User create`/`update`/`delete`.
- **Acceptance**: `Home`, `Security`, `Admin\Category`, `Admin\Source`.

### Known open points

#### Tests currently failing — must be fixed
- **`SourceServiceCest` — 5 errors**: `SourceService::__construct` now requires a third argument `SourceErrorRepository` (see [Service/SourceService.php](src/Service/SourceService.php)). Every `new SourceService([...], $this->repository())` in [tests/Unit/Service/SourceServiceCest.php](symfony/tests/Unit/Service/SourceServiceCest.php) must pass an `SourceErrorRepository` double (`Stub::makeEmpty(SourceErrorRepository::class)`).
- **`SourceMessageHandlerCest::skipsSourcesWhoseChecksumHasNotChanged` — 1 failure**: `SourceMessageHandler::__invoke` no longer pre-checks `Source::getChecksum()` against the reader's payload; checksum gating lives entirely in the feed reader (`read()` returns `null` when unchanged). The two valid branches are now (a) reader returns `null` → skip, (b) reader returns content → update + create articles. Drop or rewrite this "same-checksum" scenario — it duplicates `skipsSourcesWhenTheReaderReturnsNull` once the unchanged-checksum logic is read from the reader double directly.
- **`Acceptance\CategoryControllerCest::adminCanCreateAndRemoveACategory` — 1 failure**: the new category appears in `<th>` cells in the listing table, and `$I->see('Acceptance E2E Category')` after submit is checking for the text *anywhere*, but the listing now renders the create button label `Add category` and a `<select>` with the page-size options before showing the new row. Likely a real bug in either the redirect target or a missing assertion — investigate before changing the test.

#### Missing coverage (to add)
- **`PageSizeResolver`** ([src/Resolver/PageSizeResolver.php](src/Resolver/PageSizeResolver.php)) — no unit test yet. Cover the three branches: missing param → default (first option), allowed value → echoed, not-in-whitelist → fallback to default. Use the **Unit** suite.
- **`SourceService::recordFailure()`** — new method that persists a `SourceError` and flips the `Source` to `StatusEnum::IN_ERROR`. Unit-test it with in-memory subclasses of `SourceRepository` / `SourceErrorRepository` (capture saved entities in public arrays), then assert exception class/message/file/line are copied and the source status is updated.
- **`SourceMessageHandler` failure branch** — when the reader (or any collaborator) throws, the handler logs the error and calls `sourceService->recordFailure($source, $throwable)`. Add a test that drives the reader stub to throw, doubles `SourceService::recordFailure` to capture calls (`use (&$failures)`), and asserts the throwable + source are forwarded.
- **`SourceErrorRepository::findRecent($limit)`** ([src/Repository/SourceErrorRepository.php](src/Repository/SourceErrorRepository.php)) — Functional test in `tests/Functional/Repository/`: insert several `SourceError` rows with controlled `createdAt`, assert ordering (DESC) and `$limit`.
- **`SourceRepository::findMostActive($days, $limit)`** and **`CategoryRepository::findMostActive($days, $limit)`** — Functional repository tests: seed sources/categories with articles whose `createdAt` straddles the `$days` window, assert ordering by `articleCount DESC` and the cutoff.
- **`Admin\IndexController`** ([src/Http/AdminController/IndexController.php](src/Http/AdminController/IndexController.php)) — dashboard rendering counts and the four "most active" arrays. Functional test: seed minimal data, log in as admin, assert the response is 200 and the counts appear; access control mirrors the other admin controllers (anonymous → login, regular user → 403).
- **`Admin\MonitoringController`** ([src/Http/AdminController/MonitoringController.php](src/Http/AdminController/MonitoringController.php)) — `index` (paginated list of `SourceError` rows, joined with `Source`) and `delete` (CSRF-protected POST). Functional + Acceptance tests symmetric to `Admin\Source`. Will need a `Test` `SourceErrorFixture` (or inline `haveInRepository`) to seed errors.

#### Documented current behaviour (not a bug)
- `HTMLService::read()` is a **stub**: it always returns `null` (HTML parsing not implemented). The tests document this current behaviour; complete them once parsing exists.
