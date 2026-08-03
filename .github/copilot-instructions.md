# Copilot instructions

Notes for future coding-agent sessions on this repo. These are meant to save exploration time - update this file whenever you learn something that would otherwise require re-discovering. Lines in this file are intentionally not hard-wrapped - rely on your editor's soft-wrap. Keep entries factual and time-insensitive (describe how the codebase behaves, not what was done in any particular session).

**After making code changes, always check whether this file needs updating** - e.g. if a gotcha you just hit isn't documented, a documented behaviour turned out to be stale/wrong, or a mechanism described here was added/removed/renamed.

## When the user reports something broken, check docker logs first

- Before diving into source code to guess at a cause, check the relevant container's logs - the actual PHP fatal/exception, nginx error, or stack trace is usually right there and will save several rounds of speculative code reading.
- `docker compose logs <service> --tail 200` (or `-f` to follow) - e.g. `front-app`, `front-web`, `front-ssl`, `admin-app`, `api-app`, `api-web`. Use `docker compose ps` first if unsure which containers are currently running.
- Common classes of problem and where to look:
  - A PHP fatal error, uncaught exception, or 500 response - check the relevant `*-app` container's logs (`front-app`/`admin-app`/`api-app`/`pdf-app`) - these run php-fpm and log PHP errors/exceptions there, not in the `*-web`/`*-ssl` nginx containers.
  - A connection refused/timeout/502 from the browser - check the corresponding `*-web`/`*-ssl` nginx container's logs, and consider the stale-DNS-after-recreate gotcha (see the Docker Compose section below) as a likely cause before assuming it's an application bug.
  - Something that only breaks after a container was just rebuilt/recreated - check for the stale anonymous-volume config-cache gotcha (see Docker Compose section) before assuming the new code is wrong.
- If logs don't immediately explain it, `docker compose exec <service> sh` to get a shell inside the container and inspect further (e.g. confirm the file on disk matches what you expect, check `/tmp/config-cache.php`'s mtime, etc.) - this is much faster than reasoning about code in the abstract.

## Cypress tests

- Feature files: `cypress/e2e/*.feature`. Step definitions/fixtures: `cypress/e2e/common/*.js`.
- Two fixture mechanisms exist, don't confuse them:
  - `../cypress/e2e/common/fixtures.js` (`@CleanupFixtures` tag) - the **legacy** mechanism. Relies on Python scripts in `tests/python-api-client/*.py` run via `cy.runPythonApiCommand(...)`, which call `service-api` directly. Only works where Cypress has direct network access to the API (i.e. locally, NOT in CI). Skips cleanup in CI (`if (!Cypress.env('CI'))`) because it relies on a pre-seeded/shared LPA that must persist for reuse by later scenarios.
  - `../cypress/e2e/common/user_with_lpas_fixtures.js` (`@CleanupUserFixtures` tag) - creates a brand-new user + N LPAs per scenario by calling a feature-flagged endpoint in `service-front` (`POST`/`DELETE /testing/cypress-fixture`). Works in CI because front-app is reachable there. Cleanup always runs (no CI guard needed - unlike the legacy mechanism, there's no reason to keep the user around).
  - Step phrases: `Given I create a new user with {int} LPAs` / `Given I create a new user with {int} HW LPAs`, then `When I log in as the newly created fixture user`.
  - Example scenario: `../cypress/e2e/SharedSpace.feature`
- **`cy.log()` vs `cy.task('log', ...)`**: `cy.log()` only writes to the Cypress Command Log in the runner UI/video - it is NOT printed to stdout, so it's invisible in CI console output. `cy.task('log', message)` (registered in `../cypress.config.js`) does `console.log(message)` in the Node process, which DOES show up in CI logs. Use `cy.task('log', ...)` (not `cy.log()`) for anything you need to see in CI output, e.g. the fixture user's generated email/password in `user_with_lpas_fixtures.js`.
- **Run a single spec**: `make cypress-run-spec SPEC=<Name>.feature` - runs the `cypress` container against the already-running docker-compose stack. It does NOT start the stack itself - `front-app`/`front-web`/`front-ssl` (and `admin-*` if the spec needs the admin app) must already be up (e.g. via `docker compose up -d` or `make dc-up`), otherwise Cypress fails with "could not verify that this server is running".
- **CI networking constraint** (important, easy to forget): the Cypress container in GitHub Actions only has network access to the front-app and admin-app ALBs. `service-api` is NEVER directly reachable from Cypress in CI. See `../scripts/pipeline/ci_ingress/ci_ingress.py` - it only opens ingress on front/admin security groups. Any fixture mechanism that needs to run in CI must go through front-app or admin-app (e.g. a feature-flagged test-only route), not call the API directly.
- **Which CI job actually runs Cypress**: `.github/workflows/*` has a "Remaining" test suite job (as opposed to "stitched" jobs) that runs Cypress against seeded DB users / UI-driven signup, NOT the Python fixtures - it excludes all tags associated with Python-fixture scenarios.

## service-front feature flags

- Pattern: `App\Feature` enum (`../service-front/src/App/src/Feature.php`), each case's value is an env var name. `isEnabled(): bool { return getenv($this->value) === 'true'; }`.
- Routes gated behind a flag are registered conditionally in `../service-front/config/routes.php` (`if (Feature::X->isEnabled()) {...}`) so the route literally doesn't exist (404, not 403) when disabled.
- Terraform wiring for a new flag: add to `feature_flags` object in `../terraform/environment/variables.tf`, set per-environment value in `../terraform/environment/terraform.tfvars.json`, wire the env var in `../terraform/environment/modules/environment/ecs_front.tf` (or `ecs_admin.tf` for admin-app).
- Local docker-compose: set the env var directly on the relevant service in `../docker-compose.yml` (e.g. `front-app`).

## service-front dependency injection (Laminas ServiceManager)

- Central DI config: `../service-front/config/autoload/dependencies.global.php` (`factories` array maps `SomeClass::class => SomeClassFactory::class`).
- **The app has global autowiring, on by default** - `../service-front/config/config.php` registers `Laminas\Di\ConfigProvider::class` as the FIRST provider in the `ConfigAggregator`. That provider contributes `Laminas\Di\Container\ServiceManager\AutowireFactory` as an `abstract_factory`, which the ServiceManager falls back to for ANY class with no explicit `factories`/`aliases`/`invokables` entry. It autowires constructor params that are container-resolvable typed classes/interfaces - zero explicit registration needed. It does NOT handle scalar constructor params (strings/ints/arrays with no default) - those classes still need an explicit factory to pull values from `config` or elsewhere.
  - A class whose constructor only takes typed classes/interfaces (e.g. `LoggerInterface`, some `App\Service\*` class) does NOT need any `factories` entry at all - it will be autowired automatically. Adding an explicit `ReflectionBasedAbstractFactory::class` mapping (or a bespoke `*Factory` class) for such a class is redundant - it duplicates behaviour the app already gets for free.
- The remaining explicit `*Factory` classes in the codebase are (almost) all there for a real technical reason, not just style preference. Before assuming a factory is redundant, check whether its `__invoke()` does any of the following - if so, it's genuinely needed and autowiring (`Laminas\Di` or `ReflectionBasedAbstractFactory`) cannot replace it:
  - reads a **scalar/array config value** (e.g. `$container->get('config')` then pulls out a string/int/array) and passes it as a constructor arg - e.g. `ApiClientFactory`, `OrdnanceSurveyFactory`, `RedisClientFactory`, `DynamoDbClientFactory`, `FeedbackServiceFactory`, `StatusServiceFactory`.
  - calls a **setter method** after construction (the `ApiClientAwareInterface`/`ApiClientTrait` pattern, or bespoke setters like `setLpaApplicationService()`, `setUrlHelper()`, `setStorage()`) - autowiring only resolves constructor params, never setters - e.g. `ApplicantFactory`, `ReplacementAttorneyCleanupFactory`, `UserDetailsFactory`, `AuthenticationServiceFactory`.
  - constructs an **interface-typed constructor param** where multiple concrete implementations exist and the choice is made at runtime (e.g. `MailTransportFactory` picks `NotifyMailTransport` vs `NullMailTransport`/an inline anonymous class based on whether a Notify API key is configured) - autowiring can't pick between candidates.
  - does **`new`** on a class not managed by the container as an intermediate value (e.g. `AuthenticationServiceFactory` does `new LpaAuthAdapter($container->get(ApiClient::class))` because `AuthenticationService`'s constructor is typed to the `AdapterInterface` interface, not the concrete adapter).
  - has genuine custom logic - fallback/try-catch behaviour (`StatusServiceFactory` swallows exceptions per-optional-dependency), attaching event listeners (the inline `ErrorHandler::class` factory in `dependencies.global.php`), or passing non-trivial closures/arrays (`SaveHandlerFactory`).
- **Watch for dead/broken factory references silently masked by autowiring**: Laminas ServiceManager's `getFactory()` only tries to instantiate a registered factory if `class_exists($factory)` is true; if the referenced factory class doesn't exist (e.g. deleted but a stale `use` import/array entry left behind - PHP's `Foo::class` doesn't validate the class exists at parse time), the ServiceManager silently falls through to the `abstract_factories` list and autowires the target class instead, with no error. This can hide a broken/dead factory reference indefinitely as long as the target class happens to be fully autowirable. If you ever see `$container->get(X)` unexpectedly succeeding despite X's registered factory class looking broken/missing, this is why - worth a quick `class_exists($factoryClass)` sanity check, and cleaning up the dead `factories` entry/alias/`use` import once confirmed.
- `Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory` (laminas-servicemanager's own version, distinct from the `Laminas\Di` one above) also exists in vendor and also works, but functionally overlaps with the autowiring the app already gets via `Laminas\Di` - rarely a reason to add it explicitly. Neither it nor `Laminas\Di`'s autowiring can call setter methods - both only resolve constructor params - so both are incompatible with the `ApiClientAwareInterface`/`ApiClientTrait` setter-injection pattern used elsewhere (see below).
- **ApiClient setter-injection pattern**: several services (`UserDetails`, `Lpa\Application`) implement `App\Service\ApiClient\ApiClientAwareInterface` and `use App\Service\ApiClient\ApiClientTrait`, which adds a `setApiClient(Client $c)` method. Their factories construct the object then call `->setApiClient($container->get(ApiClient::class))` manually - there is NO ServiceManager initializer/delegator handling this automatically, so every factory using this pattern must remember to call the setter itself.
- Running `phpcbf` on `dependencies.global.php` after adding new (longer) array keys will re-align the whitespace of the ENTIRE `factories` array (looks like a huge diff) - this is cosmetic only (PSR alignment rule), not a functional change. Don't be alarmed by a big diff there.

## API field/type gotchas (service-api via service-front's ApiClient)

- `POST /v2/user/{userId}/applications` returns a numeric (int) `id` for the new LPA, not a string - don't assume `string` return types when handling LPA ids from this endpoint.
- A user's own "about you" details (`PUT /v2/user/{userId}`) are a SEPARATE resource from LPA donor details (`PUT /v2/user/{userId}/applications/{lpaId}/donor`). If you create a user and only set LPA donor details, `UserDetailsMiddleware` will redirect to `/user/about-you/new` - you must PUT `/v2/user/{userId}` too.

## Docker Compose local dev gotchas (Makefile)

- `front-app`, `admin-app`, `api-app` all declare `VOLUME ["/tmp"]` in their Dockerfiles (anonymous volume). Mezzio's config cache lives at `/tmp/config-cache.php`. **`docker compose up -d --force-recreate <service>` alone does NOT clear anonymous volumes** - a stale config cache can survive container recreation and silently hide newly-added DI/route config, producing confusing runtime errors (e.g. "Typed property ...must not be accessed before initialization" for a dependency that IS correctly wired in source). You must also pass `--renew-anon-volumes`.
- nginx (`front-web`, `admin-web`, and their SSL-terminating counterparts `front-ssl`, `admin-ssl`) resolves its upstream hostname (`fastcgi_pass`/`proxy_pass`) ONCE at nginx startup - no dynamic `resolver` directive. If you recreate `front-app` (new container IP), `front-web` (and therefore `front-ssl`, since it proxies to front-web) will keep pointing at the dead IP until restarted.
- **Use these Makefile targets instead of manual docker compose commands**:
  - `make reset-front-app` - force-recreates front-app with `--renew-anon-volumes`, then restarts the web/ssl layer. Use this after any change to front-app's PHP dependency/route config that isn't picked up automatically.
  - `make reset-admin` - same, for admin-app.
  - `make dc-restart-web` - restarts front-web/api-web/admin-web/front-ssl/admin-ssl (the whole nginx layer) to refresh DNS after any app container is recreated.
  - `make dc-clear-cache` - clears the Mezzio config cache file paths directly (front-app's is `/tmp/config-cache.php`, not `/app/data/cache/config-cache.php`).

## Fixture-handler-in-front-app architecture

- `../service-front/src/App/src/Service/Fixture/CypressFixtureService.php` - scaffolds a user + N LPAs server-side (create -> activate -> authenticate -> set-about-you -> create-LPA(s)), replaying the same API calls the real UI/JS fixtures would make. Also has `deleteUser()` for cleanup.
- `../service-front/src/App/src/Handler/Testing/CypressFixtureHandler.php` - HTTP entrypoint, `POST`/`DELETE /testing/cypress-fixture`, gated behind `Feature::CypressFixtures` (env var `CYPRESS_FIXTURES_ENABLED`).
- Excluded from PHPUnit coverage in `../service-front/phpunit.xml` (`<exclude>` block covers `src/App/src/Service/Fixture` and `src/App/src/Handler/Testing`) - deliberately has no unit tests (test-only scaffolding code, not worth the coverage/maintenance cost).
- Consumed from Cypress via `../cypress/e2e/common/user_with_lpas_fixtures.js` (two `cy.request` calls, no direct API access needed).

## Verification commands worth knowing

- **Prefer `make`/dockerised commands over running PHP/composer/etc directly on the host.** This repo's PHP dependencies, extensions, and versions are only guaranteed to match what CI/production use inside the docker containers - running `vendor/bin/phpunit`/`phpcs` etc. directly on the host works if you happen to have a compatible local PHP, but is not guaranteed to and isn't how this is meant to be run. Always reach for the `make` target first:
  - `make dc-front-unit-tests` - runs service-front's phpunit suite inside `front-app-test` (no coverage). Equivalent targets exist for the other services: `dc-admin-unit-tests`, `dc-api-unit-tests`, `dc-pdf-unit-tests`, `dc-shared-unit-tests`, or `dc-unit-tests` to run all of them.
  - `make dc-phpcs-check` - runs phpcs (style check only) for all services inside a dedicated `phpcs` container, writing a checkstyle report to `phpcs/output/phpcs-report.xml`.
  - `make dc-phpcs-fix` - runs phpcbf (auto-fix) the same way.
  - `make cypress-run-spec SPEC=<Name>.feature` - runs a single Cypress spec against the dockerised stack (see Cypress section above).
  - `make reset-front-app` / `make reset-admin` - recreate front-app/admin-app after a PHP/DI/route config change (see Docker Compose section above) - use this instead of manually running `docker compose up -d --force-recreate ...`.
  - `make dc-clear-cache` - clears Mezzio config caches for admin-app/front-app/api-app directly, without recreating containers.
  - `make psql` - opens a `psql` shell against the dockerised Postgres, instead of installing/using a host `psql` client.
- If a `make` target doesn't exist for what you need, prefer `docker compose exec <service> <command>` (or `docker compose run --rm --no-deps <service> <command>` for one-off commands against a fresh container) over installing/using host tooling - keeps behaviour consistent with CI and avoids host PHP-version/extension mismatches.
- `cd service-front && vendor/bin/phpcs <path>` / `phpcbf` - style check/fix. Prefer `make dc-phpcs-check`/`dc-phpcs-fix` (above); only fall back to running these directly on the host for a quick single-file check.
- `cd service-front && vendor/bin/phpunit` - full suite (~1500 tests; takes well over 30s, run with a longer timeout/async). Prefer `make dc-front-unit-tests` (above); only fall back to running this directly on the host if you specifically need to target a single test file/filter that the make target doesn't expose.
- `terraform fmt -check -recursive` (from `../terraform/environment`) - format check for Terraform changes.
- `npm run lint:check` (repo root) - lints `../service-front/assets/js` and `../cypress/e2e/common`.
- `make cypress-run-spec SPEC=<Name>.feature` - the fastest way to verify a single Cypress scenario end-to-end locally, including any front-app changes (make sure to `make reset-front-app` first if you changed PHP config/DI and the container wasn't already recreated).
