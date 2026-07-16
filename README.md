# Patient Breeze

Patient Breeze is a practice-management / electronic health record (EHR) web application for a medical clinic. It gives staff a patient chart with clinical tracking — **vitals**, **vaccines**, **allergies**, **medications**, **encounter notes**, **lab orders**, and **documents** — alongside appointment scheduling, a patient portal, real-time notifications, and a full audit log.

## Tech Stack

- **Backend:** PHP 8.4, [Laravel 13](https://laravel.com)
- **Frontend:** [Vue 3](https://vuejs.org) + [Inertia.js 3](https://inertiajs.com), built with [Vite 8](https://vitejs.dev) and [Tailwind CSS 4](https://tailwindcss.com)
- **Database:** MariaDB / MySQL
- **Real-time:** [Laravel Reverb](https://reverb.laravel.com) (WebSockets) + Laravel Echo
- **Auth:** Session auth with opt-in TOTP two-factor (`pragmarx/google2fa`)
- **PDF export:** `spatie/laravel-pdf` via Browsershot (requires a Chrome/Chromium binary)
- **Testing:** [Pest 4](https://pestphp.com)

Notable packages: `spatie/laravel-permission` (RBAC), `spatie/laravel-activitylog` (audit log), `spatie/laravel-medialibrary` (documents), `lorisleiva/laravel-actions`, `laravel-vue-i18n`.

## Prerequisites

Install these before you start. Versions below are the minimums this project targets.

| Tool | Version | Notes |
|------|---------|-------|
| **PHP** | 8.4+ | with extensions: `mbstring`, `pdo`, `pdo_mysql`, `gd`, `zip`, `bcmath`, `intl`, `xml` |
| **Composer** | 2.x | PHP dependency manager |
| **Node.js** | 20+ | (22/24 recommended) — ships with npm |
| **MariaDB** or **MySQL** | 10.6+ / 8.0+ | any MySQL-compatible server |
| **Google Chrome / Chromium** | current | only needed for PDF export |
<repository-url> 
Platform-specific ways to get these:

- **macOS** — [Homebrew](https://brew.sh): `brew install php@8.4 composer node mariadb` plus Google Chrome (or use [Laravel Herd](https://herd.laravel.com), which bundles PHP, Composer, and Node).
- **Windows** — Use [Laravel Herd for Windows](https://herd.laravel.com/windows) (bundles PHP, Composer, Node) or [XAMPP](https://www.apachefriends.org) + [Composer](https://getcomposer.org/download/) + [Node.js](https://nodejs.org). **WSL2 (Ubuntu) is strongly recommended** and lets you follow the Linux steps below.
- **Linux (Debian/Ubuntu)** — `sudo apt install php8.4 php8.4-{mbstring,mysql,gd,zip,bcmath,intl,xml,curl} composer mariadb-server nodejs npm` (add [ppa:ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php) if 8.4 isn't in your repos).

## Getting Started

### 1. Clone the repository

```bash
git clone git@github.com:charles-chadwick/Patient-Breeze.git PB
cd PB
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Create your environment file

```bash
cp .env.example .env       # macOS / Linux / WSL
# Windows (PowerShell):  copy .env.example .env
php artisan key:generate
```

### 4. Configure the database

Create an empty database, then point `.env` at it. Defaults in `.env.example`:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pb
DB_USERNAME=
DB_PASSWORD=
```

Create the database (adjust user/password to match your `.env`):

```bash
mysql -u root -p -e "CREATE DATABASE pb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Use `DB_CONNECTION=mysql` instead if you're running MySQL rather than MariaDB.

### 5. Run migrations and seed sample data

```bash
php artisan migrate --seed
```

This creates the schema and loads demo staff, patients, appointments, and clinical reference data (medications, vaccines, allergens, lab panels, etc.).

### 6. (Optional) Configure PDF export

PDF export needs a Chrome/Chromium binary. Point `.env` at it:

```env
LARAVEL_PDF_CHROME_PATH=/usr/bin/google-chrome    # Linux
# macOS:   /Applications/Google Chrome.app/Contents/MacOS/Google Chrome
# Windows: C:\Program Files\Google\Chrome\Application\chrome.exe
```

### 7. Run the app

The quickest way is the bundled `dev` script, which runs the web server, queue worker, log tailer, Reverb WebSocket server, and Vite together:

```bash
composer run dev
```

Then open **http://localhost:8000**.

<details>
<summary>Prefer separate terminals?</summary>

```bash
php artisan serve          # web server        → http://localhost:8000
npm run dev                # Vite dev server (hot reload)
php artisan reverb:start   # WebSocket server (real-time features)
php artisan queue:listen   # background jobs (notifications, etc.)
```
</details>

### 8. Log in

Seeded staff accounts all use the password **`password`**. For example:

```
Email:    slow.rick@example.com
Password: password
```

(Users are seeded from a list of characters; check `database/seeders/UserSeeder.php` for the full roster and roles.)

## Common Commands

| Command | Purpose |
|---------|---------|
| `composer run dev` | Run the full local stack (server, queue, logs, Reverb, Vite) |
| `npm run build` | Build front-end assets for production |
| `php artisan test` | Run the Pest test suite |
| `php artisan test --compact --filter=SomeTest` | Run a subset of tests |
| `php artisan migrate:fresh --seed` | Rebuild the database from scratch |
| `vendor/bin/pint` | Format PHP to project style |

## Troubleshooting

- **`Unable to locate file in Vite manifest`** — run `npm run dev` (or `npm run build`).
- **Real-time features not updating** — make sure `php artisan reverb:start` is running and the `REVERB_*` / `VITE_REVERB_*` values in `.env` are set.
- **Migration / DB connection errors** — confirm your database server is running and the `DB_*` credentials in `.env` match it.
- **Windows line-ending or permission oddities** — develop inside **WSL2** for the smoothest experience.
