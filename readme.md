# StudentHub Portal

A secure, role-based student management portal built with PHP and MySQL (XAMPP).
Students register, log in, and sign up for campus events. Administrators manage
student records and events.

**Author:** YOUR-NAME (ENROLLMENT-NO)
**Repository:** https://github.com/25cs065-ux/STUDENTHUB

## Objective

To design and develop a secure, role-based student management web application
using PHP and MySQL. The system supports student registration and login,
administrator management of student records (view, add, edit, deactivate,
export) and events, and student registration for events, with secure
authentication, session management, input validation, audit logging and a
responsive, accessible interface.

## Features

**Guest:** home page, register, login.
**Student:** dashboard, browse events, register/cancel event registration, timetable, logout.
**Admin:** dashboard with live counts, manage students (search, filter, add, edit,
deactivate, CSV export), manage events (create/edit/delete with poster upload),
Fetch API based events page, audit log.

## Tech Stack

PHP 8, MySQL/MariaDB, HTML5, CSS3, JavaScript (Fetch API), Bootstrap 5 (admin dashboard), XAMPP.

## Setup

1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy this project folder to `C:\xampp\htdocs\STUDENTHUB`.
3. Open http://localhost/phpmyadmin, click **New**, create a database named `studenthub`
   (collation `utf8mb4_general_ci`).
4. Select `studenthub` → **Import** → choose `studenthub.sql` → **Import**.
5. Check `db.php`: host `localhost`, user `root`, empty password, database `studenthub`.
6. In `C:\xampp\php\php.ini` set these and restart Apache:
   ```
   session.cookie_httponly = 1
   session.cookie_samesite = "Lax"
   session.use_strict_mode = 1
   session.use_only_cookies = 1
   ```
7. Open http://localhost/STUDENTHUB/

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@studenthub.test | Admin@12345 |
| Student | student@studenthub.test | Student@12345 |

## Main URLs

| Page | URL |
|---|---|
| Login | /STUDENTHUB/login.php |
| Register | /STUDENTHUB/register.html |
| Student dashboard | /STUDENTHUB/student_dashboard.php |
| Student events | /STUDENTHUB/student_events.php |
| Admin dashboard | /STUDENTHUB/admin_dashboard.php |
| Manage students | /STUDENTHUB/manage_students.php |
| Manage events | /STUDENTHUB/events.php |
| Fetch API events | /STUDENTHUB/events_fetch.html |

## Folder Structure

```
STUDENTHUB/
├── index.php                  entry point (redirects to login/dashboard)
├── db.php                     database connection
├── audit.php                  audit log helper
├── login.php, authenticate.php, logout.php, timeout.php
├── register.html, process_register.php, script.js
├── dashboard.php, student_dashboard.php, student_events.php
├── admin_dashboard.php, manage_students.php, edit_student.php,
│   delete_student.php, export_students.php
├── events.php, events_db.php, save_event.php, delete_event.php
├── api.php, events_fetch.html, practical13.js   (JSON API + Fetch CRUD)
├── session_test.php           session ID regeneration demo
├── style.css, flexbox/        styles and home page
├── uploads/events/            event posters (.htaccess blocks script execution)
└── studenthub.sql             database dump
```

## Security Features

- Passwords hashed with `password_hash` / `password_verify`
- Prepared statements (MySQLi) for all database queries (SQL injection)
- Output escaping with `htmlspecialchars` (XSS)
- CSRF tokens on all state-changing forms and on the JSON API
- Role-based access control on every protected page (Admin / Student)
- Session ID regeneration on login, 15-minute inactivity timeout, full session destroy on logout
- Login lock after 5 failed attempts (5 minutes) and inactive accounts blocked
- Server-side validation of every form (client-side validation is only a convenience)
- Poster upload checks: size limit, MIME type, `getimagesize`, random file names,
  PHP execution blocked in `uploads/`
- Audit log of logins, logouts, student/event changes, exports and event registrations
- Soft delete for students (status set to inactive, record kept)

## Testing

See `Test_and_Security_Report` for the full test cases, security checklist and
accessibility checklist. Quick manual test:

1. Register a new student, log in, register for an event.
2. Log in as admin, check the dashboard counts and Latest Registrations.
3. Add, edit and deactivate a student; export the CSV.
4. Try a student opening `admin_dashboard.php` (expect 403).
5. Try SQL injection text in the login password and search box (expect no effect).

## Known Limitations

- Runs on HTTP in local XAMPP; production use needs HTTPS and the `Secure` cookie flag.
- Login lock is session-based, so a production system should also rate-limit by IP/account.
- No email verification or password reset flow.
- `readme` pages for attendance, results and notices are static HTML placeholders from earlier practicals.

## Proofs

- A file is attached in the pdf format
