# Questra: AI-Powered Quiz Generator

Questra is a plain PHP + XAMPP quiz platform for the case-study brief in `development-guide/project.md`. It supports secure authentication, Gemini-ready quiz generation, protected file uploads, quiz ownership, dashboard summaries, activity logging, and a MySQL schema with stored procedures and triggers.

## Current Build Scope

- Session-based registration, login, and logout
- CSRF protection on state-changing forms
- PDO-based database access
- User dashboard summary powered by stored procedures
- Quiz creation from topic and optional source file upload
- Quiz listing and quiz detail views
- Share-link resolution page for taking quizzes
- Admin dashboard summary entry point
- Pusher-ready backend event service with realtime log fallback
- `database/questra_db.sql` with required tables, procedures, triggers, and default admin account

## Tech Stack

- PHP
- MySQL
- XAMPP / Apache
- Tailwind CDN + Bootstrap modal support
- PHPMailer
- Pusher PHP SDK

## Setup

1. Configure your hosts file so `casestudy` points to `127.0.0.1`.
2. Point your Apache virtual host or XAMPP document root to this project.
3. Copy `.env.example` to `.env` and fill in the real values.
4. Import [database/questra_db.sql](/Applications/XAMPP/xamppfiles/htdocs/QG/web/database/questra_db.sql).
5. Run `composer install`.
6. Open `http://casestudy`.

## Environment Notes

Use `casestudy` as the DB host and app URL per the case-study requirement:

```env
DB_HOST=casestudy
DB_PORT=3306
DB_NAME=questra_db
DB_USER=root
DB_PASS=
APP_URL=http://casestudy
```

Gemini, Pusher, Google OAuth, and mail settings are all defined in `.env.example`.

## Default Admin Account

- Email: `admin@questra.local`
- Password: `Admin123!`

Change this immediately after importing the database.

## Security Features

- Password hashing with `password_hash()` / Bcrypt
- `password_verify()` authentication checks
- CSRF token validation for form submissions
- PDO prepared statements
- Escaped output using `htmlspecialchars()`
- Protected `.env` access via `.htaccess`
- Upload extension and size validation
- Upload execution blocked via `uploads/.htaccess`

## Repository Contents

- `classes/` shared app, auth, security, Gemini, Pusher, and quiz services
- `submissions/` POST handlers
- `pages/` route-like templates
- `components/` shared layout parts
- `database/questra_db.sql` full schema, procedures, and triggers
- `development-guide/` project instructions and build brief
