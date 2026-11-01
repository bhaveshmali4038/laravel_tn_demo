# Executed verification

Verified on 2 November 2026 in `C:\xampp\htdocs\laravel_tn_demo`. These are results from this project, rather than inherited results from the reference application.

## Current editing extension - 2 November 2026

Added business and branch edit/update routes, prefilled forms, directory Edit links, and an Edit branch link on details at the user's request. No migration was required. The refreshed walkthrough includes two edit demonstrations (88 seconds total), and the submission ZIP includes the updated implementation and documentation.

Both complete suites passed after this extension: **33 tests and 271 assertions each**, on SQLite memory and the dedicated MySQL test database. Six edit-specific tests cover optional/replaced uploads, schedule/date replacement, failure rollback, invalid update preservation, failed-validation redisplay, and moving a branch between businesses. All Blade views compiled successfully.

Browser verification confirmed prefilled branch hours/dates/images, successful save with existing images retained, and prefilled business contacts/logo with a successful save. Historical results below describe the preceding create/delete version.

## Latest recheck after the README command revision

On 2 November 2026, reran both database suites: **28 tests and 227 assertions passed on each**. The npm production build and strict Composer validation also passed again. The README now contains direct setup/run/test/export commands and self-contained inline video/ZIP commands, with no custom helper-file invocations. The Composer development shortcut uses Artisan directly as well.

Verified the README's direct `php artisan serve` startup on temporary port 8011 and its PHP server alternative with upload limits on temporary port 8012. Both returned HTTP 200 for `/branches` and valid database-backed DataTables responses. The application on port 8010 remained available throughout; only the temporary verification servers were stopped afterward.

## Automated checks

| Check | Actual result |
| --- | --- |
| PHP syntax for application, routes, database, tests | All 30 checked PHP files passed |
| `composer install --no-interaction` | Passed, including Laravel package discovery; 113 locked package versions retained |
| `composer validate --strict` | Passed |
| `composer check-platform-reqs` | Passed on PHP 8.2.12 |
| `composer audit --locked --no-interaction` | No published security advisories found |
| `npm.cmd ci` | Passed; clean installation from lockfile |
| `npm.cmd run build` | Passed with Vite 8.3.5; production manifest and hashed CSS/JS generated |
| npm audit | 0 reported vulnerabilities |
| JavaScript syntax and `npm ls` | Passed |
| Vite development startup | Served successfully on port 5173, then stopped; stale `public/hot` removed |
| `php artisan test` | **28 tests, 227 assertions passed** against SQLite memory |
| `php vendor/bin/phpunit --configuration phpunit.mysql.xml` | **28 tests, 227 assertions passed** against isolated `laravel_tn_demo_testing` |
| `php artisan migrate --seed --force` | All four migrations and demo seeding passed on `laravel_tn_demo` |
| `php artisan storage:link` | Public upload link created |
| `php artisan route:list --except-vendor` | 15 routes; no business/branch edit or update routes |
| `php artisan view:cache` | All Blade views compiled successfully; generated views cleared afterward |

Both database suites exercise required contacts/uploads, multiple branches/images/weekday slots, invalid and overlapping intervals, exact opening/closing boundaries, split-shift gaps, closed weekdays, date-opening and date-closure precedence, timezone rollover, scoped date deletion, transaction rollback, upload cleanup, cascading deletion, DataTables output/search/paging, and create/delete-only restrictions. Backend tests disable Vite; production assets were checked separately in the browser.

## Browser verification

Used the production Vite build with the PHP server helper at `http://127.0.0.1:8010`, connected to XAMPP MySQL/MariaDB.

- Both directories loaded their actual Yajra DataTables responses. A business search filtered two records down to the expected one.
- Submitting an empty business form displayed required-field feedback. A valid business/logo upload previewed and saved successfully.
- Created a branch with two images, two Monday time slots, and an available-date override. Persistence succeeded and the detail page showed both images and both slots.
- A deliberately overlapping interval produced the browser message `Time slots cannot repeat or overlap.` Correcting it allowed submission.
- The branch showed **Open** at approximately 01:03 Asia/Kolkata because that date was marked available all day, even though its weekly Monday hours began at 09:00.
- Riverside displayed the exact sample weekly schedule from the PDF, including Monday/Tuesday split shifts and Friday/Saturday/Sunday closure.
- Checked desktop and narrow mobile layouts. The mobile detail page had no horizontal document overflow and zero broken images. Navigation collapsed appropriately.
- No browser warning/error logs were recorded during the inspected directory/detail/form flow.
- Removed only the synthetic walkthrough business and its branch/uploads after verification. The submission database retains Harbor Coffee and its two seeded branches.

Screenshots under `docs/screenshots` document the workflow, including a synthetic business used only for the walkthrough. That temporary business is intentionally absent from the final SQL export.

## Export and media checks

Exported `database/laravel_tn_demo.sql` from the final seeded application database with `mysqldump --single-transaction --result-file`.

Restored that export into the disposable testing database successfully and checked the resulting domain record counts:

| Table | Records |
| --- | ---: |
| businesses | 1 |
| branches | 2 |
| branch_timings | 14 |
| branch_images | 3 |
| branch_date_overrides | 2 |

The small captioned walkthrough is **72 seconds**, H.264, **1920 x 1080**, 10 fps, without an audio track. FFmpeg decoded the complete video successfully, and sampled frames were visually reviewed. It explains adding businesses/branches, images, weekday intervals, date overrides, live status, search, and deletion.

The submission packager requires the SQL export, production manifest, and video; includes source, lockfiles, built assets and demo images; and excludes local environment secrets, dependencies, caches and the public upload link. Archive integrity and required contents are checked after generation.

## Remaining external step

The local implementation and deliverables are complete. The user selected `https://github.com/bhaveshmali4038/laravel_tn_demo` for publication. The GitHub handoff includes code, SQL, screenshots, and the walkthrough video. Final upload status is confirmed in the chat after the remote revision is verified.

These checks establish the behavior covered above. They do not claim that software can never contain an undiscovered defect, or that a public deployment has been tested.
