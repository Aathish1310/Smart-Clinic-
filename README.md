# Smart Clinic Appointment Management System

Web-based appointment system for small and medium clinics (final-year project proposal by K. Athiththyan, ESOFT Metro Campus / London Metropolitan University).

| Technology (as in the proposal) | Used for |
|---|---|
| HTML, CSS, JavaScript, Bootstrap 5 | Responsive user interface (Bootstrap is bundled in `assets/vendor`, no internet needed) |
| PHP 8 (plain PHP + PDO) | Back-end logic |
| MySQL / MariaDB | Database (10 tables, InnoDB) |
| XAMPP, Visual Studio Code, GitHub | Local server, editor, version control |

## Who can do what

| Role | Features |
|---|---|
| **Patient** | Register, log in, search doctors by name/speciality, see free times, book, receive confirmation + reminder notifications, cancel or reschedule, view history, edit profile |
| **Doctor** | Daily schedule dashboard, appointment list with filters, patient details, mark completed / no-show, write notes, manage weekly working hours and leave days |
| **Clinic staff** | Confirm / cancel / reschedule any booking, book for walk-in or phone patients, register and edit patients, manage doctor schedules, booking reports with charts and CSV export |
| **Administrator** | Everything staff can do plus add/edit/deactivate doctors, create staff accounts, e-mail outbox, activity (audit) log |

Key rules built in: no double booking (checked in PHP **and** by a database UNIQUE constraint), patients can only cancel/reschedule online until 2 hours before the visit, up to 30 days ahead booking window, max 5 upcoming bookings per patient, doctor leave cancels and notifies affected patients, one reminder per confirmed visit 24 hours before. All limits are in `includes/config.php`.

## Install on Windows (XAMPP)

1. Install XAMPP (PHP 8.x and MySQL) from https://www.apachefriends.org
2. Copy this folder anywhere (for example `C:\clinic`).
3. Double-click **`setup.bat`** - it starts MySQL, creates the `smart_clinic` database, imports `database/schema.sql` and offers demo data.
4. Double-click **`start.bat`** - the browser opens at http://localhost:8080
5. Log in with a demo account (password **demo1234**):
   - Administrator `admin@clinic.test`
   - Staff `priya.jayasuriya@clinic.test`
   - Doctor `kavitha.ramanathan@clinic.test`
   - Patient `nimal.fernando@clinic.test`

Alternatively copy the folder into `C:\xampp\htdocs\clinic`, start Apache and MySQL in the XAMPP Control Panel and open http://localhost/clinic (the URL prefix is detected automatically).

If your MySQL root user has a password, `setup.bat` asks for it and saves it in `includes/config.local.php`.

### Reminders
Press **Send reminders** on the staff dashboard, or schedule `reminders.bat` in Windows Task Scheduler to run every hour. E-mails are always recorded in the outbox (Administrator > System > E-mail outbox). To really send them set `MAIL_ENABLED` to `true` in `includes/config.local.php` and configure PHP `mail()` / SMTP.

## Install on Linux / macOS

```bash
mysql -u root -e "CREATE DATABASE smart_clinic CHARACTER SET utf8mb4"
mysql -u root smart_clinic < database/schema.sql
php scripts/seed.php                 # optional demo data
php -S localhost:8080 -t .
```

## Automated tests

```bash
mysql -u root -e "CREATE DATABASE smart_clinic_test CHARACTER SET utf8mb4"
mysql -u root smart_clinic_test < database/schema.sql
CLINIC_DB_NAME=smart_clinic_test php tests/run_tests.php            # 43 booking-rule tests

CLINIC_DB_NAME=smart_clinic_test php scripts/seed.php
CLINIC_DB_NAME=smart_clinic_test php -S 127.0.0.1:8001 -t . &
python3 tests/http_tests.py http://127.0.0.1:8001/                  # 30 end-to-end web tests
```
The test runner refuses to run on any database whose name does not end in `_test`.

## Folder map

```
index.php login.php register.php doctors.php   public pages
appointment.php appointment_action.php          shared appointment page + all state changes
reschedule.php notifications.php profile.php api.php
patient/   doctor/   admin/                     role dashboards and tools
includes/  config, db, functions, booking (all rules), header/footer, schedule editor
database/schema.sql                             10-table schema
scripts/   seed.php, send_reminders.php, .bat helpers
tests/     run_tests.php (logic), http_tests.py (web)
assets/    css, js, bundled Bootstrap + icons
```

## Security measures
Prepared statements everywhere (SQL injection), `password_hash` (bcrypt), CSRF token on every form, output escaping (XSS), session id regenerated on login, HttpOnly + SameSite cookies, role checks on every page and action, login lock-out after 5 failures, open-redirect protection, CSV formula-injection guard, generic login errors.

## Known limits (honest list)
- Reminders are in-app plus a logged e-mail; real SMTP/SMS delivery needs configuration or a gateway.
- Online payments, prescriptions/lab records and multi-branch support are outside this project's scope.
- The Windows `.bat` scripts need XAMPP; they could not be run inside the Linux build environment, but the PHP code, database and tests were run on MariaDB 10.11 with PHP 8.3.
