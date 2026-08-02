# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Symfony 7.4 + API Platform 3.4 backend for "Gestion Travaux" (a construction/works management PWA). It exposes a REST/JSON-LD API auto-generated from Doctrine entity attributes, with JWT authentication (LexikJWTAuthenticationBundle). MySQL via Doctrine ORM 3. Consumed by a separate Framework7 frontend. Swagger UI is served at `/api` when the server is running.

## Commands

All PHP commands run either through the Symfony CLI (`symfony console ...`) or directly via `php bin/console ...`; both are used in this repo. Inside Docker, run them from within the container (`docker exec -it gestion-travaux-api bash`).

### Running the app

- `symfony server:start` — start the dev server (README flow)
- `yarn start` — start on port 8001
- `symfony console doctrine:migrations:migrate` — apply migrations
- `symfony console make:migration` — generate a migration from entity changes
- `yarn truncate-database` — drop, recreate, apply full schema, and load fixtures (dev DB)

### Tests

Functional/API tests use **Codeception**, not bare PHPUnit, even though PHPUnit is present as a dependency for the test runner underneath.

- `yarn test` — stops any running server, resets the **test** database (`yarn truncate-database-test`), then runs `vendor/bin/codecept run`. This is the standard way to run the whole suite; it manages the test DB and local server itself.
- `vendor/bin/codecept run Api` — run only the API suite (`tests/Api/**/*Cest.php`)
- `vendor/bin/codecept run Unit` — run only unit tests (`tests/Unit/**/*Test.php`)
- `vendor/bin/codecept run Functional` — run only functional/command tests
- `vendor/bin/codecept run Api ClientCest` — run a single Cest class
- `vendor/bin/codecept run Api ClientCest:testCreateClient` — run a single test method
- Codeception suite configs are `tests/{Acceptance,Api,Functional,Unit}.suite.yml`. The `Api` suite starts its own local Symfony server on port 8000 in the `test` environment (via `Codeception\Extension\RunProcess`) and talks to it over HTTP through the REST/PhpBrowser modules — it is not testing the kernel in-process. The `Doctrine` module wraps each test in a transaction (`cleanup: true`).
- Test data comes from fixtures in `src/DataFixtures/` (loaded by `yarn truncate-database-test`), not per-test factories. `App\Tests\Enum\UserFixturesEnum` holds well-known fixture identifiers (e.g. default test user email) used across Cest tests.
- Cest tests commonly chain scenario steps with `#[Codeception\Attribute\Depends('testX')]` to express ordering/state reuse between test methods in the same class.

### Static analysis & style

- `vendor/bin/phpstan analyse` — level 6, scoped to `src` and `tests` (`phpstan.dist.neon`); Symfony/Doctrine extensions enabled, requires a warmed dev container (`var/cache/dev/App_KernelDevDebugContainer.xml`) — run `symfony console cache:warmup` first if analysis complains about the container.
- `vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php` — auto-fix style; runs automatically on staged PHP files via Husky/`lint-staged` on commit.
- `vendor/bin/rector process` — apply configured Rector sets (PHP 8.3, code quality, dead code, type declarations) to `src`/`tests`; `--dry-run` to preview.
- `yarn prettier` — check formatting of JS/CSS/SCSS/Markdown (also runs on commit via `lint-staged`).

### Other

- `php bin/console lexik:jwt:generate-keypair` — generate JWT RSA keys (required before auth works)
- `composer generate-openapi-bundle` — runs `openapi/openapi-generator.php` to emit an OpenAPI spec

## Architecture

### API Platform resource pattern

Entities under `src/Entity/` double as API Platform resources: the `#[ApiResource]` attribute and its `operations` array live directly on the Doctrine entity class, alongside `#[ORM\...]` mapping attributes, serializer `#[Groups]`, and `#[Assert\...]` validation constraints. There is no separate resource/DTO layer for standard CRUD — read `src/Entity/Work.php` as the canonical example:

- Per-operation `security` expressions (`is_granted('ROLE_USER')`, `is_granted('VIEW', object)`, `is_granted('EDIT', object)`) gate access at the operation level, not in controllers.
- Serialization groups follow a `{resource}:read` / `{resource}:write` convention passed via `normalizationContext`/`denormalizationContext`, with group constants declared on the entity itself.
- Custom business logic on writes/deletes is delegated to a `processor:` on the specific operation (see below), not stuffed into the entity or a controller.

### Processors (`src/Processor/`)

Implement `ApiPlatform\State\ProcessorInterface` and are wired per-operation via `processor: SomeProcessor::class` on the `#[ApiResource]` operation attribute. Convention: check `$data instanceof <ExpectedEntity>` and delegate to the decorated built-in processor (commonly `ApiPlatform\Doctrine\Common\State\PersistProcessor` injected via `#[Autowire]`) when the processor doesn't need to act, otherwise run extra logic (cascading deletes, cross-entity linking, DTO → entity mapping) before/after persisting. Processors that receive a DTO input (e.g. `SupplierReturnInvoiceProcessor` consuming `SupplierReturnInvoiceUpdateInput`) look up the target entity from `$uriVariables` themselves — API Platform does not auto-hydrate the entity for them.

### Providers (`src/State/`)

Implement `ApiPlatform\State\ProviderInterface` for custom read/query logic that doesn't fit the standard Doctrine collection/item providers (e.g. `MonthlyProvider` resolves a repository via `context['resource_class']`, requiring it to implement a marker interface like `MonthlyProviderInterface`/`MonthlyProviderRepositoryInterface` from `src/Interface/`).

### Access control layers

Three distinct mechanisms compose together, each with a specific job — don't conflate them:

1. **Operation `security` expressions** on `#[ApiResource]` operations — coarse-grained, e.g. `ROLE_USER` vs per-object `VIEW`/`EDIT` voter checks.
2. **Voters** (`src/Security/Voter/`) — extend `Symfony\...\Voter<string, SubjectType>`, implement fine-grained per-object authorization (e.g. `WorkImageVoter` checks the current user owns the parent `Work`). Referenced from `security` expressions via `is_granted('ATTRIBUTE', object)`.
3. **Doctrine query extensions** (`src/Doctrine/CurrentUserExtension.php`) — implement `QueryCollectionExtensionInterface`/`QueryItemExtensionInterface` to transparently scope _every_ query (collection and item) to `current_user`, so a user never even sees other users' rows in a `GetCollection`/`Get`. It reflects on the resource class for a `getUser()` method and has resource-specific branches (e.g. `WorkImage` is scoped through its parent `Work`).

When adding a new user-owned entity, all three layers typically need updating together: operation security, a voter if per-object edit/view checks are needed, and `CurrentUserExtension` if collection queries must be scoped.

### DTOs and file-upload flows (`src/Dto/`)

Used as `input`/`output` classes on API Platform operations for non-trivial payloads — especially multipart file uploads (e.g. `ProductInvoiceCreationInput` holds `UploadedFile[]` with `Assert\File`/`Assert\Count` validation) and zip-download requests. These pair with dedicated `src/Controller/` actions (e.g. `ProductInvoiceFileController`, `*DownloadController`, `*ZipController`) for behavior that isn't plain entity CRUD — download/zip endpoints are implemented as invokable controllers rather than API Platform operations.

### Domain structure

- `src/Entity/` — Doctrine entities doubling as API resources (Client, Supplier, Work, Invoice, ProductInvoiceFile, SupplierReturnInvoiceFile, WorkImage, WorkEventDay, TokenNotificationPush, User, ...). Shared field groups extracted to `src/Entity/Traits/`.
- `src/Repository/` — Doctrine repositories; some implement marker interfaces from `src/Interface/` (e.g. `MonthlyProviderRepositoryInterface`) so generic providers can depend on the interface rather than a concrete repository.
- `src/Naming/` — file/directory naming strategies for VichUploaderBundle-managed uploads (invoice files, etc.).
- `src/Formatter/` — output shaping for non-entity read models (e.g. `WorkEventDaysFormatter`).
- `src/Service/` — cross-cutting business logic invoked from processors/controllers (PDF extraction, invoice link validation/resolution, image/token services under their own subdirectories).
- `src/Factory/` — object construction that doesn't belong on an entity/DTO constructor (e.g. `FirebaseServiceAccountFactory`).
- `src/EventSubscriber/` — Symfony kernel-level hooks (JWT customization, logout).
- `src/EventListener/` — Doctrine/ORM-level listeners (e.g. unique constraint violation → clean API error).
- Push notifications: `src/Service/TokenNotificationPush/`, `src/Command/SendNotificationPushCommand.php`, Firebase integration via `src/Interface/Firebase/` and `kreait/firebase-php`.
- PDF generation/parsing uses `mpdf`/`fpdf`/`fpdi`/`smalot/pdfparser`, spreadsheet export uses `phpoffice/phpspreadsheet`.

### Ownership convention

User-owned entities implement `App\Interface\UserOwnerInterface` (`getUser()`/`setUser()`). This interface is the hook point `CurrentUserExtension` checks (`reflectionClass->hasMethod('getUser')`) to decide whether to scope a query to the current user — implementing it is what opts an entity into automatic per-user query scoping.

## SOLID & REST principles as applied in this codebase

The existing architecture already encodes these principles structurally — follow the same separation when extending it rather than putting logic back on entities/controllers:

- **Single Responsibility**: entities hold mapping/serialization/validation metadata only; write-time business logic goes in a `Processor`, read-time custom query logic goes in a `Provider` or a Doctrine query extension, cross-cutting logic goes in a `Service`. Don't grow a processor to handle multiple unrelated entities — the existing ones each guard `$data instanceof <Entity>` and delegate otherwise.
- **Open/Closed**: new operation behavior is added by attaching a new `processor`/`provider` class to an operation, not by branching inside a shared one. New user-owned resources opt into query scoping by implementing `UserOwnerInterface`, not by editing `CurrentUserExtension`'s branching logic (extend it only when an entity's ownership isn't a direct `user` column, as it already does for `WorkImage` via its parent `Work`).
- **Liskov Substitution**: `Processor`/`Provider` implementations must honor `ProcessorInterface`/`ProviderInterface` contracts fully (return the right shape for the operation, delegate untouched cases to the decorated processor) so API Platform can call any of them interchangeably per-operation.
- **Interface Segregation**: narrow marker interfaces (`UserOwnerInterface`, `MonthlyProviderInterface`, `MonthlyProviderRepositoryInterface`) exist so generic Processors/Providers/extensions can depend on a small contract instead of a concrete entity/repository type. Prefer adding a new narrow interface over widening an existing one.
- **Dependency Inversion**: Processors/Providers/Voters/Services depend on interfaces and injected services (`EntityManagerInterface`, `Security`, decorated `ProcessorInterface` via `#[Autowire]`) — never `new` up an `EntityManager` or reach for static/global state.
- **REST via API Platform**: model new endpoints as resource operations (`Get`, `GetCollection`, `Post`, `Put`, `Delete`) on the entity's `#[ApiResource]` rather than ad-hoc controller routes; reserve invokable `src/Controller/` actions for genuinely non-CRUD behavior (file download, zip export, auth). Keep resource state transitions expressed through HTTP verbs + JSON-LD, not custom RPC-style endpoints, and scope read/write shape through serialization `Groups` rather than exposing/accepting full entities.

## Coding conventions observed in this codebase

- `declare(strict_types=1);` at the top of new PHP files (not universally present in older files, but standard for anything new).
- Entities/processors/voters are typically `final` (or `final readonly` for stateless services), with fluent `set*()` methods returning `self`/`static`.
- One class per file; import statements are alphabetized/grouped by php-cs-fixer (`blank_line_between_import_groups`) — let the fixer handle ordering, don't hand-order imports.
- Constants for serialization group names and voter attribute names are declared as class constants (e.g. `Work::GROUP_WORK_READ`, `WorkImageVoter::EDIT_WORK_IMAGE`) rather than inlined string literals, and reused across the entity's own attributes and any voter/security expression referencing them.
