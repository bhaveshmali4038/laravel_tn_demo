# Assignment requirement mapping

Source: the supplied three-page **Business Timing - Open Close - 3 to 5 yrs 3.pdf**. The application requirements are mapped below; the external upload/share instruction remains a separate submission step. Actual executed results belong in `docs/VERIFICATION.md`.

| PDF requirement | Implementation | Verification |
| --- | --- | --- |
| New Laravel project and MySQL | Laravel 12 application, `.env.example`, domain migration | Composer install, MySQL migrations/tests |
| Add businesses | `BusinessController::create/store`, request, separate form | Persistence/upload tests |
| Name, Email, Phone Number, Logo | `Business`, `businesses`, business form | Required/invalid contacts and logo tests |
| Multiple branches per business | Business/Branch relationships and foreign key | Multiple branch persistence/cascade tests |
| Create/delete records only (original PDF) | Extended by the user on 2 November 2026 to include business/branch edit/update | Edit persistence, validation, replacement and rollback tests |
| Separate branch form | `BranchController::create/store`, creation view | Creation test and browser form check |
| Branch Business, Name, Multiple Images | `Branch`, `BranchImage`, request/form | Business selection and multi-image tests |
| Different start/end times per weekday | `BranchTiming`, independent weekday rows | Weekday persistence/status tests |
| Multiple slots on one day | Repeating controls; one timing row per interval | Multiple-slot, gap, overlap, boundary tests |
| Unavailable weekdays | Disabled controls, no timing rows | Closed-day status/validation tests |
| Specific-date availability/unavailability | `BranchDateOverride`, creation/detail forms | Full-day opening and closure precedence |
| Invert normal date availability | Date overrides replace weekly schedule | Closed date opened; open date closed |
| Dynamic status on page visit | Show action and `BranchStatusService` | Current-time tests, browser details |
| Operating hours on branch details | Weekly/effective-hours tables, status badge | Compare seeded hours to PDF |
| Sample timing input interaction | Weekday toggles, add/remove slots | Browser interaction check |
| Sample status presentation | Visible Timings and Open/Closed | Browser details check |
| Backend validation | Form Requests, `ScheduleValidation` | Invalid contacts/files/slots/dates |
| Frontend validation | Browser constraints and JavaScript | Invalid input/overlap/date/file checks |
| Bootstrap appearance | npm Bootstrap, custom CSS, Blade | Desktop/mobile visual review |
| jQuery DataTables for both lists | npm jQuery/DataTables, list initializers | Search/paging/output tests, browser tables |
| Yajra DataTables | Composer Yajra dependency, data actions | Server JSON tests |
| Routes/controllers/migrations/models | Laravel structure | Boot/routes/migration/feature checks |
| Document code and setup | README, architecture, inline comments | Reproduce setup and inspect source map |
| Small usage video | `output/submission/business-timing-walkthrough.mp4` | Play/review the tutorial |
| Latest code and database | Source ZIP, SQL export, demo uploads | Archive and fresh restore checks |
| GitHub/Google Drive upload and URL | README external submission steps | Pending destination, upload, accessible link |

## User clarifications

The user explicitly selected these answers to cases the PDF does not define:

1. An **Available** date is open for the full date, `00:00` until the following midnight, replacing weekly hours. An **Unavailable** date is closed for the full date.
2. Weekly intervals are **same-day only**, with closing strictly after opening. Overnight intervals are rejected.

## Policies for unspecified details

- Default timezone `Asia/Kolkata`, matching the environment; configurable with `APP_TIMEZONE`.
- Starts inclusive, ends exclusive. Adjacent slots pass; equal/reversed, incomplete, invalid, duplicate, and overlapping slots fail.
- Listed identity/upload fields required. Names up to 255 characters; contact rules in README.
- Actual JPG/JPEG, PNG, WebP images up to 4 MiB each; 1-10 branch images. These formats/limits are not numeric PDF requirements.
- One exception per branch/date; replace by delete/create.
- A branch may have all weekdays disabled and open only on available dates.
- Business deletion cascades to branches/data and removes uploads; branch deletion preserves siblings.
- Authentication, roles, and public hosting are outside the specified application behavior.

## Naming and sample fidelity

The PDF gives field labels and example values but prescribes no identifiers for functions, classes, routes, tables, columns, or filenames. Consistent Laravel identifiers appear in `docs/ARCHITECTURE.md`.

Riverside's seeded schedule matches the sample:

| Day | Hours |
| --- | --- |
| Monday | `12:00-14:00`, `19:00-22:00` |
| Tuesday | `09:00-11:00`, `12:45-14:30`, `19:00-22:00` |
| Wednesday | `09:00-17:00` |
| Thursday | `09:00-17:00` |
| Friday | Closed |
| Saturday | Closed |
| Sunday | Closed |

## Additional npm request

The user requested npm packages and all commands in the README, using the reference project's documentation style. This project uses npm-managed Bootstrap/jQuery/DataTables with Vite. Main setup requires `npm ci` and `npm run build`; optional `npm run dev` runs in another terminal alongside PHP.
