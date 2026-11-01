# Architecture and implementation guide

## Stack and request flow

Laravel 12 and Blade render the application. Eloquent uses MySQL, including XAMPP's compatible MariaDB. Yajra DataTables 12 provides server-side table JSON. Bootstrap, jQuery, and DataTables' Bootstrap 5 integration are npm dependencies. Vite compiles application assets; Laravel's `@vite` directive loads development or production files. Both dependency lockfiles make installation reproducible.

Requests enter through `public/index.php` and `routes/web.php`. Laravel binds route IDs to models. Form Requests validate input before controllers execute. `BusinessRecordService` handles related writes/upload cleanup; `BranchStatusService` evaluates current opening status. Blade renders pages, and Yajra returns table data. Standard web middleware protects writes with CSRF validation.

## Domain schema

The domain migration is `database/migrations/2026_10_29_000100_create_business_management_tables.php`. Each table includes `id`, `created_at`, and `updated_at`.

| Table / model | Additional columns | Relationships |
| --- | --- | --- |
| `businesses` / `Business` | `name`, `email`, `phone_number`, `logo_path` | Has many branches |
| `branches` / `Branch` | `business_id`, `name` | Business parent; image/timing/exception children |
| `branch_images` / `BranchImage` | `branch_id`, `path` | Belongs to branch |
| `branch_timings` / `BranchTiming` | `branch_id`, `weekday`, `start_time`, `end_time` | One weekly interval for a branch |
| `branch_date_overrides` / `BranchDateOverride` | `branch_id`, `date`, `is_available` | One exception per branch/date |

Foreign keys cascade deletion. `weekday` uses ISO numbering, Monday `1` to Sunday `7`. Weekly times are `TIME` columns; exceptions use a `DATE` and boolean. `(branch_id, date)` is unique. The date model normalizes values to `Y-m-d` for consistent MySQL/SQLite behavior. Standard Laravel scaffold tables are separate from domain data and do not add a login requirement.

## Main classes

| Class | Responsibility |
| --- | --- |
| `BusinessController` | Listing, creation form, create/edit/delete, Yajra data |
| `BranchController` | Listing, creation, details, deletion, Yajra/status JSON |
| `BranchDateOverrideController` | Create/delete exceptions and enforce branch ownership |
| `StoreBusinessRequest` | Contact/logo validation |
| `StoreBranchRequest` | Business selection, branch/images, weekdays, optional date overrides |
| `StoreBranchDateOverrideRequest` | Unique date and boolean availability |
| `ScheduleValidation` | Same-day ordering and overlap checks |
| `BusinessRecordService` | Transactional persistence and file compensation/cleanup |
| `BranchStatusService` | Date precedence and status calculation |
| `DemoSeeder` | Repeatable illustrative data and PDF sample hours |

## Routes

The PDF originally specifies create/delete. The user requested business/branch editing on 2 November 2026; the following additional routes implement that extension.

| Method and path | Route name |
| --- | --- |
| `GET /` | Redirects to `/branches` |
| `GET /businesses` | `businesses.index` |
| `GET /businesses/data` | `businesses.data` |
| `GET /businesses/create` | `businesses.create` |
| `POST /businesses` | `businesses.store` |
| `DELETE /businesses/{business}` | `businesses.destroy` |
| `GET /businesses/{business}/edit` | `businesses.edit` |
| `PUT/PATCH /businesses/{business}` | `businesses.update` |
| `GET /branches/{branch}/edit` | `branches.edit` |
| `PUT/PATCH /branches/{branch}` | `branches.update` |
| `GET /branches` | `branches.index` |
| `GET /branches/data` | `branches.data` |
| `GET /branches/create` | `branches.create` |
| `POST /branches` | `branches.store` |
| `GET /branches/{branch}` | `branches.show` |
| `GET /branches/{branch}/status` | `branches.status` |
| `DELETE /branches/{branch}` | `branches.destroy` |
| `POST /branches/{branch}/date-overrides` | `branches.date-overrides.store` |
| `DELETE /branches/{branch}/date-overrides/{dateOverride}` | `branches.date-overrides.destroy` |

Static data endpoints precede dynamic IDs. Exception deletion checks that the exception belongs to the URL's branch. Replacing an exception uses delete followed by create.

## Form payloads and validation

Business POST fields are `name`, `email`, `phone_number`, and `logo`. Branch fields are `business_id`, `name`, `images[]`, `weekly_hours`, and optional `overrides`.

Weekly controls use `weekly_hours[1][enabled]`, `weekly_hours[1][slots][0][start_time]`, and `[end_time]`. Each day/slot has an independent index. Disabled weekdays submit no intervals; enabled weekdays need at least one complete interval. Rules accept weekday keys `1`-`7`, validate `HH:mm`, and reject unknown nested keys.

`ScheduleValidation` sorts intervals by start and compares each with the greatest preceding end. Equal/reversed times and partial/nested/duplicate overlaps fail. Adjacent boundaries pass. A slot cannot run overnight.

Creation exceptions use `overrides[0][date]` and `overrides[0][is_available]`; the detail form uses flat `date` and `is_available`. Availability is boolean `1` or `0`. Exceptions have no interval fields. Validation and a database unique index prevent repeated branch/date pairs.

Browser constraints and JavaScript provide feedback for contacts, files, slots, and dates. Form Requests remain authoritative when JavaScript is bypassed. Image checks inspect actual content/MIME type. The README documents requiredness and numeric upload/contact limits as implementation policies because the PDF does not define them.

## Opening-status algorithm

`BranchStatusService::status(Branch $branch, ?CarbonImmutable $at = null)` uses an optional instant for deterministic tests or the current server time. It converts the instant to `config('app.timezone')`, defaulting to `Asia/Kolkata` via `APP_TIMEZONE`.

1. Find an override for the current local calendar date.
2. If one exists, use its availability for that entire date.
3. Otherwise select the current ISO weekday's intervals and check `start_time <= current_time < end_time`.
4. Return status, evaluation time, timezone, local date, source, and effective intervals.

Response keys are `is_open`, `label`, `evaluated_at`, `time_zone`, `date`, `source`, and `slots`. Source is `weekly` or `date_override`. An available date reports `00:00-24:00` for display; `24:00` is not accepted in weekly input.

The detail view receives initial status from the server. An uncached JSON endpoint refreshes it while the page stays open. Branch listing data refreshes too. Date exceptions affect only their selected date, leaving weekly rows unchanged.

## npm/Vite frontend

`package.json` declares packages and `dev`/`build` scripts; `package-lock.json` pins versions. Canonical source is `resources/css/app.css` and `resources/js/app.js`, with related modules under `resources/js`. Package imports compile to `public/build` and a manifest.

The layout uses `@vite`, rather than copied `public/vendor` assets or CDN URLs. `npm ci` installs dependencies; `npm run build` produces production assets. Optional `npm run dev` supplies development assets while PHP runs separately. Initialization occurs after imported dependencies are available.

## Persistence and uploads

Logos are stored on the public disk under `business-logos`, gallery files under `branch-images`. Only relative paths are stored in SQL. `public/storage` links to `storage/app/public`; views use public-disk URLs.

Creation tracks stored paths in database transactions. If a write fails, saved uploads are removed and the error is rethrown. Deletion gathers relevant paths, commits record deletion, then removes files. Foreign keys remove child images, intervals, and exceptions. Business deletion collects every branch's image paths; branch deletion collects only its own. Demo branch images have distinct paths so sibling images survive deletion.

## DataTables and output

Business JSON includes `id`, `name`, `email`, `phone_number`, `branches_count`, `logo`, and `actions`. Branch JSON includes `id`, `name`, `business_name`, `status`, and `actions`. Yajra performs server paging/search/order; branch business-name filtering/ordering uses the relationship.

Text output is escaped. Only controlled image/status/action markup is raw, with inserted values escaped. Deletion uses CSRF-protected forms and interface confirmation.

## Tests and deliverables

Feature tests cover business/branch persistence and uploads, contacts/images, intervals/status boundaries/gaps, full-day overrides, timezone conversion, scoped deletion, relational/file cleanup, DataTables, and edit validation and rollback. `phpunit.xml` uses SQLite memory; `phpunit.mysql.xml` targets a dedicated MySQL/MariaDB database.

`database/laravel_tn_demo.sql` is the export. The walkthrough and ZIP live under `output/submission`. The README provides commands and external-sharing steps; `docs/VERIFICATION.md` records checks executed for this project.

## Editing

`UpdateBusinessRequest` and `UpdateBranchRequest` reuse creation validation, making replacement uploads optional. Edit views prefill current fields, weekly slots and dated availability. Failed validation restores submitted values, including removal of all special dates. `BusinessRecordService::updateBusiness/updateBranch` uses transactions, compensates new uploads on failure, and deletes replaced files only after a successful commit. Branch updates replace the submitted schedule/date set and optionally replace the gallery. No schema changes are needed.
