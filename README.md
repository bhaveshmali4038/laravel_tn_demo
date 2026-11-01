# Business Timing - Laravel / npm

A Laravel application for managing businesses, branches, weekly operating hours, and date-specific availability. It implements the supplied three-page **Business Timing - Open or Closed status** assignment with business and branch editing added at the user's request on 2 November 2026.

Bootstrap, jQuery, and DataTables are installed through **npm** and compiled with **Vite**. Yajra provides server-side DataTables responses. Browser assets have no CDN dependencies.

All setup, run, build, test, database, video, and packaging commands are written directly in this README. No custom helper file is required to follow these instructions.

The complete implementation is already in this project. Follow the commands below; there is no need to create another Laravel application or paste code into individual files. See [the requirement checklist](docs/REQUIREMENTS.md), [the architecture guide](docs/ARCHITECTURE.md), and [executed verification results](docs/VERIFICATION.md).

## 1. Prerequisites

- PHP **8.2+** compatible with the locked Laravel 12 dependencies; Composer 2.
- MySQL, or XAMPP's MySQL-compatible MariaDB using Laravel's `mysql` driver.
- Node.js **22.12+**, or **20.19+ within Node 20.x**; npm. The exact engine range is `^20.19.0 || >=22.12.0`. This installation uses Node 22.17.1.
- PHP extensions: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, and `zip`. The default isolated tests additionally require `pdo_sqlite`.
- Writable `storage` and `bootstrap/cache` directories.

The commands use PowerShell, XAMPP's local `root` account without a password, database **`laravel_tn_demo`**, and application port **8010**. Adjust database credentials in `.env` to match your installation. For a password-protected MySQL account, add `-p` to the MySQL commands and enter the password when prompted.

## 2. Complete setup commands - Windows / XAMPP

Extract or clone this project into `C:\xampp\htdocs\laravel_tn_demo`. Start **MySQL** in the XAMPP Control Panel. Apache is unnecessary when using Laravel's development server.

Run this block for a **fresh local installation**:

```powershell
Set-Location 'C:\xampp\htdocs\laravel_tn_demo'
$env:Path = 'C:\xampp\php;C:\xampp\mysql\bin;' + $env:Path

php --version
composer --version
node --version
npm.cmd --version

# Install the exact dependencies recorded in the lockfiles.
composer install
npm.cmd ci
npm.cmd run build

# Preserve an existing local configuration.
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
}

# Generate a key once for this fresh local installation.
php artisan key:generate
php artisan optimize:clear

# Start XAMPP MySQL before this command.
& 'C:\xampp\mysql\bin\mysql.exe' -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS laravel_tn_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create tables and illustrative sample records.
php artisan migrate --seed
php artisan storage:link

# Keep this terminal open while using the application.
php artisan serve --host=127.0.0.1 --port=8010
```

Open **[http://127.0.0.1:8010](http://127.0.0.1:8010)**. Stop the server with `Ctrl+C`.

Before running migrations, verify these values in `.env`, especially if it already existed:

```dotenv
APP_NAME="Business Timing"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8010
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_tn_demo
DB_USERNAME=root
DB_PASSWORD=
```

Run `php artisan config:clear` after changing `.env`. Generate the key once for a fresh installation; do not regenerate an existing deployed application's key. The `.env` and application key are excluded from the submission.

`composer install` already installs Yajra. No separate `composer require` command is needed. `npm ci` installs the locked packages, and `npm run build` creates `public/build` and its Vite manifest. Both npm commands are part of the setup. `npm.cmd` avoids PowerShell's `npm.ps1` execution-policy issue without changing system policy.

The pinned frontend package versions are:

| npm package | Version |
| --- | --- |
| `bootstrap` | `5.3.8` |
| `@popperjs/core` | `2.11.8` |
| `jquery` | `3.7.1` |
| `datatables.net` | `2.3.8` |
| `datatables.net-bs5` | `2.3.8` |
| `vite` | `8.3.5` |
| `laravel-vite-plugin` | `3.2.0` |

For an empty application, use `php artisan migrate` instead of `php artisan migrate --seed`. Add the repeatable demo later with:

```powershell
php artisan db:seed
```

The seeder creates Harbor Coffee, a Riverside branch matching the PDF sample, and a City Centre branch. It generates illustrative images locally and sample date exceptions relative to the day Riverside is first created.

## 3. Subsequent runs and frontend development

After installation, start MySQL and run:

```powershell
Set-Location 'C:\xampp\htdocs\laravel_tn_demo'
php artisan serve --host=127.0.0.1 --port=8010
```

The production build works without a Vite server. After changing CSS or JavaScript, rebuild:

```powershell
npm.cmd run build
```

For Vite development refresh, leave the PHP server running and open a **second terminal**:

```powershell
Set-Location 'C:\xampp\htdocs\laravel_tn_demo'
npm.cmd run dev
```

Continue opening `http://127.0.0.1:8010`; Vite supplies development assets. Stop Vite with `Ctrl+C`. If an interrupted session leaves `public/hot` pointing at a stopped server, remove that generated file and run `npm.cmd run build`.

### Upload limits for the complete 10-image workflow

The standard `php artisan serve` command uses your loaded PHP configuration. Check its location with:

```powershell
php --ini
notepad 'C:\xampp\php\php.ini'
```

For ten images of up to 4 MiB each, set these values in the loaded `php.ini`, save it, then restart the PHP server:

```ini
upload_max_filesize = 4M
post_max_size = 50M
max_file_uploads = 10
max_input_vars = 50000
```

Alternatively, these direct commands apply the same limits only to this server process, without editing `php.ini` or running a custom helper file. Stop any existing server on port 8010 first. Run from the project root:

```powershell
Push-Location '.\public'
php -d upload_max_filesize=4M -d post_max_size=50M -d max_file_uploads=10 -d max_input_vars=50000 -S 127.0.0.1:8010 -t . '..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
# After stopping the server with Ctrl+C, return to the project root.
Pop-Location
```

The router above is part of Laravel itself. If using Apache, apply the limits to Apache's loaded PHP configuration and restart Apache. The web server's document root must be this project's **`public`** directory.

## 4. Using the application

1. Open **Businesses > Add business**. Enter the name, email, phone number, and logo; save.
2. Open **Branches > Add branch**. Choose the business, enter a branch name, and select one or more branch images.
3. Enable available weekdays and enter start/end pairs. Use **Add time slot** for further intervals. Leave closed weekdays disabled.
4. Optionally add specific dates and select **Available** or **Unavailable**. Each exception replaces the weekly schedule for that entire date.
5. Save the branch. Its detail page shows images, weekly hours, today's effective availability, and current **Open/Closed** status.
6. Create or delete date exceptions from branch details. Replace an exception by deleting it and creating another record for that date.
7. Search, sort, and paginate both listings. Select **Delete** and confirm to remove a record.

Status is calculated on the server on page visit and refreshed while the page remains open. Branch listings refresh status as well. Deleting a business removes its branches, dependent data, and uploaded files. Deleting one branch preserves its business and siblings.

The PDF originally specifies create/delete only. At the user's request on 2 November 2026, this project also supports editing businesses and branches.

8. Use **Edit** in either directory (or **Edit branch** on branch details) to open the prefilled form, change the information, and save.
9. On business edits, leaving the logo empty retains it; uploading a new logo replaces the old file.
10. On branch edits, leaving images empty retains the gallery; selecting new images replaces the entire gallery. Update the business/name, enable or disable weekdays, add/remove slots, and change or remove special dates as needed. Saving replaces the weekly schedule and special-date records with the submitted form.

No new migration or package installation is required for editing. Refresh the running application's directory page to see the Edit buttons.

## 5. Timing rules and clarifications

The user clarified two cases left unspecified by the PDF:

- **Available date:** open for the full local date, `00:00` until the following midnight, overriding weekly hours. An **Unavailable** date is closed for the full date.
- **Weekly intervals:** same-day only; the closing time must follow opening time. Overnight intervals are rejected.

The default timezone is **Asia/Kolkata**, matching the project environment. Set `APP_TIMEZONE` to another valid IANA timezone before entering schedules if needed. Weekday selection, status, and date boundaries use this setting.

Other documented implementation policies:

- Weekdays use ISO numbers internally: Monday `1` through Sunday `7`.
- Weekly inputs are 24-hour `HH:mm`. Starts are inclusive; ends are exclusive. `09:00-12:00` is open at `09:00` and closed at `12:00`.
- Adjacent intervals are allowed. Duplicate/overlapping intervals are rejected, and gaps are closed.
- An enabled weekday needs at least one complete interval. A disabled weekday has no intervals and is closed.
- One exception is allowed per branch/date. It replaces the weekly schedule only on that date.
- A branch may have every weekday disabled and open only on available dates.
- `24:00` is the display boundary for all-day availability, not an accepted weekly input.

The PDF gives field labels and sample times but no mandated function, class, route, table, column, or file identifiers. This project follows Laravel naming consistently; exact identifiers appear in the architecture guide.

## 6. Validation and uploads

Both frontend and backend validate forms. Listed identity fields and uploads are required. Names allow up to 255 characters; email uses Laravel email validation. Phone numbers accept an optional leading `+` and common formatting punctuation, with 7-15 digits and at most 30 characters overall. A branch must reference an existing business.

Logos/gallery images must be actual JPG/JPEG, PNG, or WebP files up to **4 MiB each**. Branches accept **1-10 images**. These numeric limits/formats are implementation choices because the PDF provides none. Re-select uploads after validation errors because browsers do not restore file inputs.

Related writes use transactions. Files are removed if creation fails after upload. Foreign keys cascade child deletion, and the application removes associated files. User text is escaped in pages/DataTables; writes use Laravel CSRF protection. Authentication and roles are outside the specified assignment.

## 7. Source map

| Location | Purpose |
| --- | --- |
| `routes/web.php` | Business, branch, status, and date-exception routes |
| `app/Http/Controllers` | Pages, create/edit/delete actions, DataTables, and status |
| `app/Http/Requests` | Server validation |
| `app/Models` | Business, Branch, BranchImage, BranchTiming, BranchDateOverride |
| `app/Services/BranchStatusService.php` | Current status and date precedence |
| `app/Services/ScheduleValidation.php` | Ordering/overlap validation |
| `app/Services/BusinessRecordService.php` | Transactional writes and upload cleanup |
| `database/migrations` | Schema, relationships, and indices |
| `database/seeders/DemoSeeder.php` | Optional PDF sample data/images |
| `resources/views` | Layout, listings, separate forms, branch details |
| `resources/css/app.css` | Canonical application CSS compiled by Vite |
| `resources/js/app.js` | npm imports and frontend entry point |
| `vite.config.js` | Laravel/Vite build configuration |
| `tests/Feature` | Domain/validation/persistence/status tests |
| `docs` | Requirement mapping, architecture, verification |

## 8. Verification commands

```powershell
php artisan test
php artisan route:list --except-vendor
php artisan view:cache
php artisan view:clear
npm.cmd run build
```

The default suite uses an in-memory SQLite database from `phpunit.xml`, separate from application data. Coverage includes contacts/uploads, create/delete persistence, intervals/boundaries/gaps, full-day override precedence, local-date conversion, scoped exception deletion, relational/file cleanup, and DataTables responses.

Run the feature suite separately against MySQL/MariaDB:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS laravel_tn_demo_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php vendor/bin/phpunit --configuration phpunit.mysql.xml
```

`phpunit.mysql.xml` targets **`laravel_tn_demo_testing`**. Configure the test account there or in the environment if different from `.env`. Tests recreate tables in this dedicated database; never point it at data you want to retain. SQLite and MySQL results are distinct checks. Actual current results/limits are in [docs/VERIFICATION.md](docs/VERIFICATION.md).

## 9. Database export and restore

**`database/laravel_tn_demo.sql`** is an alternative to migrations/seeding when restoring the supplied sample database. Create the empty database and configure `.env` as above, then import **instead of** running `migrate --seed`:

```powershell
Set-Location 'C:\xampp\htdocs\laravel_tn_demo'
& 'C:\xampp\mysql\bin\mysql.exe' --default-character-set=utf8mb4 -h 127.0.0.1 -u root laravel_tn_demo --execute="source database/laravel_tn_demo.sql"
php artisan storage:link
```

Keep the supplied `storage/app/public` demo files with the export so image paths resolve. Do not import into a database containing work to preserve. Migrations plus the optional seeder can always reproduce the sample instead.

Export the latest local database after changes:

```powershell
& 'C:\xampp\mysql\bin\mysqldump.exe' --single-transaction --default-character-set=utf8mb4 -h 127.0.0.1 -u root --result-file=database/laravel_tn_demo.sql laravel_tn_demo
```

`--result-file` avoids PowerShell redirect encoding differences.

## 10. Submission deliverables

| File | Purpose |
| --- | --- |
| `database/laravel_tn_demo.sql` | Database export |
| `output/submission/business-timing-walkthrough.mp4` | Short application usage video |
| `output/submission/laravel-tn-demo-submission.zip` | Source, database, demo files, video, and built assets |

The ZIP excludes `.env`, keys, `vendor`, `node_modules`, runtime logs/caches, and the generated `public/storage` link. It includes `.env.example`, both lockfiles, source, production frontend assets, demo uploads, and database/video deliverables. Follow setup after extraction.

The PDF also asks for a **GitHub or Google Drive sharing link**. A local package does not complete that external step. The selected repository is [bhaveshmali4038/laravel_tn_demo](https://github.com/bhaveshmali4038/laravel_tn_demo). The code, SQL export, screenshots, and walkthrough video are published on its `main` branch.

1. Upload the ZIP to Google Drive with the intended evaluator access; or upload source, database, and video to a GitHub repository while preserving `.gitignore`.
2. The walkthrough video is included at `output/submission/business-timing-walkthrough.mp4`. The generated ZIP remains ignored; optionally attach it to a GitHub release or share it through Drive.
3. Verify evaluator access to the actual URL and submit that URL with the assignment.

### Optional: regenerate the video and submission ZIP

The video is already supplied. The following commands are fully included here and run directly in PowerShell from the project root. They do not call custom script files. Video generation requires Python, the included screenshots under `docs/screenshots`, and the Windows Segoe UI font. Capture fresh screenshots before regenerating a video after UI changes.

```powershell
Set-Location 'C:\xampp\htdocs\laravel_tn_demo'
python -m pip install --target tmp/video-deps Pillow imageio-ffmpeg
@'
"""Create a captioned usage video from verified application screenshots."""
from pathlib import Path
import subprocess
import sys

ROOT = Path.cwd()
sys.path.insert(0, str(ROOT / 'tmp/video-deps'))
from PIL import Image, ImageDraw, ImageFont
import imageio_ffmpeg

out = ROOT / 'output/submission'
out.mkdir(parents=True, exist_ok=True)
slides = [
    ('02-businesses.jpg', '1. Add your business', 'Open Businesses > Add business. Enter name, email, phone and a logo.'),
    ('03-create-business.jpg', '2. Save the business', 'All fields are required. Use JPG, PNG or WebP images up to 4 MB.'),
    ('04-branches.jpg', '3. Add a branch', 'Open Branches > Add branch. Select its business and add branch images.'),
    ('05-weekly-slots.jpg', '4. Set weekly hours', 'Enable a weekday. Add separate slots for split shifts; leave other days closed.'),
    ('05-weekly-slots.jpg', '5. Check time slots', 'End must follow start. Duplicate and overlapping slots are rejected.'),
    ('06-special-date.jpg', '6. Add special dates', 'Available means open all day. Unavailable means closed all day in Asia/Kolkata.'),
    ('01-branch-details.jpg', '7. View hours and current status', 'Weekly hours and date exceptions determine status. Status refreshes every 30 seconds.'),
    ('07-date-opening.jpg', '8. A date overrides the weekly routine', 'This branch is open at night because today is explicitly marked available all day.'),
    ('04-branches.jpg', '9. Find or delete records', 'Search and sort either directory. Delete a business to remove its branches and files.'),
    ('11-edit-business.jpg', '10. Edit a business', 'Use Edit in Businesses. Update contacts and optionally replace the logo, then save.'),
    ('10-edit-branch.jpg', '11. Edit a branch', 'Update its business, name, weekly slots and dates. An empty upload retains existing images.'),
]
font_path = 'C:/Windows/Fonts/segoeui.ttf'
font = ImageFont.truetype(font_path, 36)
title_font = ImageFont.truetype(font_path, 48)
cmd = [imageio_ffmpeg.get_ffmpeg_exe(), '-y', '-loglevel', 'error', '-f', 'rawvideo',
       '-vcodec', 'rawvideo', '-pix_fmt', 'rgb24', '-s', '1920x1080', '-r', '10',
       '-i', '-', '-an', '-vcodec', 'libx264', '-preset', 'fast', '-crf', '21',
       '-pix_fmt', 'yuv420p', '-movflags', '+faststart', str(out / 'business-timing-walkthrough.mp4')]
proc = subprocess.Popen(cmd, stdin=subprocess.PIPE)
for filename, title, caption in slides:
    canvas = Image.new('RGB', (1920, 1080), '#182742')
    draw = ImageDraw.Draw(canvas)
    draw.text((55, 20), title, font=title_font, fill='white')
    source = Image.open(ROOT / 'docs/screenshots' / filename).convert('RGB')
    # Display the relevant section without shrinking a full long form to illegibility.
    if filename == '06-special-date.jpg':
        source = source.crop((0, 1320, source.width, min(source.height, 2050)))
    elif filename == '05-weekly-slots.jpg':
        source = source.crop((0, 450, source.width, min(source.height, 1400)))
    elif filename == '01-branch-details.jpg':
        source = source.crop((0, 250, source.width, 910))
    elif filename == '07-date-opening.jpg':
        source = source.crop((130, 350, 1300, 1010))
    else:
        source = source.crop((0, 0, source.width, min(source.height, 1050)))
    source.thumbnail((1810, 820))
    canvas.paste(source, ((1920 - source.width)//2, 100))
    words, lines, line = caption.split(), [], ''
    for word in words:
        test = (line + ' ' + word).strip()
        if draw.textlength(test, font=font) > 1810:
            lines.append(line)
            line = word
        else:
            line = test
    lines.append(line)
    for index, line in enumerate(lines):
        draw.text((55, 960 + index * 45), line, font=font, fill='white')
    frame = canvas.tobytes()
    for _ in range(80):
        proc.stdin.write(frame)
proc.stdin.close()
if proc.wait() != 0:
    raise RuntimeError('Video encoding failed')
print(out / 'business-timing-walkthrough.mp4')
'@ | python -
```

After exporting SQL and building npm assets, create the ZIP directly:

```powershell
@'
"""Package reproducible source and deliverables without local secrets/runtime data."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path.cwd()
destination = root / 'output/submission/laravel-tn-demo-submission.zip'
destination.parent.mkdir(parents=True, exist_ok=True)
required = [
    root / 'public/build/manifest.json',
    root / 'database/laravel_tn_demo.sql',
    root / 'output/submission/business-timing-walkthrough.mp4',
]
for item in required:
    if not item.is_file() or item.stat().st_size == 0:
        raise FileNotFoundError(f'Generate the required deliverable before packaging: {item}')
excluded = {'vendor', 'node_modules', 'tmp', 'output', '.git', '.agents', '.codex', '.aws', '.phpunit.cache'}
with ZipFile(destination, 'w', ZIP_DEFLATED) as archive:
    for item in root.rglob('*'):
        rel = item.relative_to(root)
        if any(part in excluded for part in rel.parts) or item.is_symlink() or not item.is_file():
            continue
        if item.name.startswith('.env') and item.name != '.env.example':
            continue
        if rel.parts[:2] == ('bootstrap', 'cache') and item.name != '.gitignore':
            continue
        if rel.parts[0] == 'storage' and rel.parts[:3] != ('storage', 'app', 'public') and item.name != '.gitignore':
            continue
        if rel.parts[:2] == ('public', 'storage') or item.name in {'.phpunit.result.cache', 'hot', '.DS_Store'}:
            continue
        archive.write(item, str(Path('laravel_tn_demo') / rel))
    archive.write(root / 'output/submission/business-timing-walkthrough.mp4', 'laravel_tn_demo/output/submission/business-timing-walkthrough.mp4')
print(destination)
'@ | python -
```

## 11. Troubleshooting

| Symptom | Resolution |
| --- | --- |
| Database connection refused | Start XAMPP MySQL; check host/port/credentials; run `php artisan config:clear`. |
| Unknown database | Run the database-creation command before migrations. |
| Application key missing | Run `php artisan key:generate` for the fresh local `.env`. |
| Vite manifest missing / unstyled page | Run `npm.cmd ci` and `npm.cmd run build`. |
| Requests go to a stopped Vite server | Remove stale `public/hot` and run `npm.cmd run build`. |
| PowerShell blocks npm | Use `npm.cmd` instead of `npm.ps1`. |
| Image 404 | Run `php artisan storage:link`; check `storage/app/public` and `APP_URL`. Windows may need Developer Mode or an administrator terminal to create the link. |
| Upload fails before validation | Apply the PHP limits or use the direct per-process server command in section 3. |
| Empty business selection | Create a business first or run `php artisan db:seed`. |
| Status differs from weekly row | Check today's exception, `APP_TIMEZONE`, and system clock. |
| Port 8010 occupied | Use another local port and update `APP_URL`. |
