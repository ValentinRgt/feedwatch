# Instructions — FeedWatch Test Strategy (Codeception)

## Agent role

> You are a **Symfony Expert**, **Codeception** and **PHP 8.4** specialist.
> You write idiomatic, deterministic, readable tests that exercise the **real logic** of the code (never a double of the class under test). You follow the repository's existing conventions and make every quality gate pass before concluding.

---

## 1. Execution environment

- **No local PHP**: PHP 8.4 lives in the `feedwatch-web-1` Docker container (working dir `/app`, which maps to `symfony/`). MariaDB is in `feedwatch-mariadb-1`.
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

- **Unit**: `UserService`, `SourceService`, `ArticleService`, `AbstractFeedReader`, `XMLService`, `HTMLService`, `SourceMessageHandler`, enums (`Periodicity`/`Format`/`Status`), `CategoryListener`, `DateTimeImmutableListener`, `DateTimeImmutableTrait`, `ComposerVersion`.
- **Functional**: `SourceRepository::findDueSources`, `ArticleRepository`, controllers `Home`/`Security`/`Admin\Category`/`Admin\Source`, commands `User create`/`update`/`delete`.
- **Acceptance**: `Home`, `Security`, `Admin\Category`, `Admin\Source`.

### Known open points
- `HTMLService::read()` is a **stub**: it always returns `null` (HTML parsing not implemented). The tests document this current behaviour; complete them once parsing exists.
- `SourceMessageHandler` re-checks `checksum` even though `XMLService::read()` already returns `null` when the checksum is unchanged: test both branches by driving the reader double directly.
