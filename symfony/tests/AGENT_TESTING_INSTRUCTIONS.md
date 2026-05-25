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
The app uses two `ValueResolverInterface` resolvers, exposed via `#[AsTargetedValueResolver]` and consumed in controllers with `#[ValueResolver('…')]`:
- **`PageSizeResolver`** (target `pageSize`, fed by the `%items_per_page%` parameter — `[10, 20, 50, 100]`). Controllers receive it via `#[ValueResolver('pageSize')] int $pageSize`.
- **`QueryResolver`** (target `query`, yields `$request->query->getString('q')`). Controllers receive it via `#[ValueResolver('query')] string $query`.

How to test them:
- **Unit**: instantiate the resolver directly (e.g. `new PageSizeResolver([10, 20, …])` or `new QueryResolver()`), drive `resolve(Request, ArgumentMetadata)`, and collect the generator with `iterator_to_array()`. Cover every branch: for `PageSizeResolver` — missing param, value in whitelist, value outside whitelist (must fall back to `options[0]`); for `QueryResolver` — missing param (→ `''`), present param, non-string param (coerced by `getString`).
- **Functional** (controllers): hit the route with `?pageSize=` and `?q=` to prove the wiring; **do not double the resolver** — it is already covered in Unit.

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

- **Unit**: `UserService`, `SourceService` (`getReader`, `updateSource`, `recordFailure`), `ArticleService`, `AbstractFeedReader`, `XMLService`, `HTMLService`, `SourceMessageHandler` (happy path + reader-returns-null + failure branch via `recordFailure`), `PageSizeResolver`, enums (`Periodicity`/`Format`/`Status`), `CategoryListener`, `DateTimeImmutableListener`, `DateTimeImmutableTrait`, `ComposerVersion`.
- **Functional**: `SourceRepository::findDueSources`, `ArticleRepository` (incl. `findByCategoryQuery` without `$search`), controllers `Home`/`Security`/`Admin\Index`/`Admin\Category`/`Admin\Source`/`Admin\Monitoring`, commands `User create`/`update`/`delete`.
- **Acceptance**: `Home`, `Security`, `Admin\Index`, `Admin\Category`, `Admin\Source`, `Admin\Monitoring`.

### Known open points

#### Missing coverage (to add)

Driven by the recent search/listing additions (see [src/Resolver/QueryResolver.php](src/Resolver/QueryResolver.php), [src/Http/AdminController/ArticleController.php](src/Http/AdminController/ArticleController.php), and the new `findByQuery` methods on `Article`/`Category`/`Source` repositories):

- **`QueryResolver`** ([src/Resolver/QueryResolver.php](src/Resolver/QueryResolver.php)) — targeted value resolver (`query` target) that yields `$request->query->getString('q')`. Unit test in `tests/Unit/Resolver/` covering: `?q=foo` → `'foo'`, missing param → `''`, non-scalar/array param → coerced to `''` (the resolver always yields a `string`). Same pattern as `PageSizeResolverCest`: instantiate directly, drive `resolve(Request, ArgumentMetadata)`, collect with `iterator_to_array()`.
- **Repository search methods** — three new methods, one functional Cest each (or extra tests in the existing `ArticleRepositoryCest` / a new `CategoryRepositoryCest`/`SourceRepositoryCest`):
  - **`ArticleRepository::findByQuery(string $search)`** ([src/Repository/ArticleRepository.php](src/Repository/ArticleRepository.php)) — searches `LOWER(a.title) / LOWER(s.name) / LOWER(c.name)` with a `LIKE` and orders by `publishedAt DESC, createdAt DESC`. Seed three articles (match by title, match by source name, match by category name) plus one non-matching, assert exactly the three matchers are returned in the right order. Also cover case-insensitivity and the leading/trailing whitespace trim.
  - **`ArticleRepository::findByCategoryQuery(?Category $category, ?string $search)`** — the `$search` branch is new. Add a test covering category + search combined (intersection), and `null` category + search (matches across all categories).
  - **`CategoryRepository::findByQuery(string $search)`** and **`SourceRepository::findByQuery(string $search)`** — simpler `LIKE` on the `name` column. Seed three rows, assert only the matching ones are returned and that the search is case-insensitive.
- **Search wiring on existing controllers** — `HomeController`, `Admin\CategoryController`, `Admin\SourceController` now inject `#[ValueResolver('query')] string $query` and switch the paginated query when `$query` is non-empty. One functional test per controller hitting `?q=…` is enough to prove the wiring; do not double `QueryResolver` (it is exercised in Unit). Reuse the seeded fixtures and assert the response shows the matching row and hides the others.
- **`Admin\ArticleController`** ([src/Http/AdminController/ArticleController.php](src/Http/AdminController/ArticleController.php)) — entirely new controller. Symmetric to `Admin\Source`:
  - Functional: access control (anonymous → login, regular user → 403, admin → 200), default listing ordered by `publishedAt DESC, createdAt DESC`, `?q=` filtering, `?pageSize=` wiring, `POST /article/{id}/delete` with valid/invalid CSRF token.
  - Acceptance: admin-driven create-via-feed scenario is not in scope (articles are produced by the feed pipeline), so cover at minimum: listing renders, search filters the table, delete removes a seeded article. Seed via `haveInRepository` since there is no `ArticleFixture` yet.
- **`SourceRepository::findMostActive($days, $limit)`** and **`CategoryRepository::findMostActive($days, $limit)`** ([src/Repository/SourceRepository.php](src/Repository/SourceRepository.php), [src/Repository/CategoryRepository.php](src/Repository/CategoryRepository.php)) — exercised end-to-end through `IndexControllerCest`, but no direct repository test. Add functional Cests that seed sources/categories with articles whose `createdAt` straddles the `$days` window, then assert ordering by `articleCount DESC` and the `$limit` cutoff. Lowest-layer coverage per §2.

#### Documented current behaviour (not a bug)
- `HTMLService::read()` is a **stub**: it always returns `null` (HTML parsing not implemented). The tests document this current behaviour; complete them once parsing exists.
