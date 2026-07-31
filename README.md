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

### 6. Configure Reverb (real-time)

Real-time features — the staff notification bell, the portal queue, and live discussion posts — are pushed over WebSockets by [Laravel Reverb](https://reverb.laravel.com). Reverb ships with the project (`laravel/reverb`), so there is nothing extra to install; you only need credentials.

Generate a set of app credentials into your `.env`:

```bash
php artisan reverb:install
```

That fills in `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` (which are empty in `.env.example`). If you'd rather set them by hand, any random values work locally — for example:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

How the two halves of the config differ:

| Variable | Used by | Meaning |
|----------|---------|---------|
| `REVERB_SERVER_HOST` / `REVERB_SERVER_PORT` | the Reverb process | the interface and port the WebSocket server **binds** to |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | Laravel (server-side broadcasting) | where the app **connects** to publish events |
| `VITE_REVERB_*` | the browser (Laravel Echo) | where the client **connects**; baked in at build time |

Because the `VITE_*` values are compiled into the front-end bundle, **restart `npm run dev` (or re-run `npm run build`) after changing any `REVERB_*` value**.

Two more things matter for events to actually arrive:

- `BROADCAST_CONNECTION=reverb` must be set (`.env.example` already does this; the framework default is `null`, which silently drops everything).
- Notifications are queued, so `php artisan queue:listen` needs to be running with `QUEUE_CONNECTION=database`.

Start the server with:

```bash
php artisan reverb:start
```

`composer run dev` already starts it for you. Add `--debug` to watch connections and messages as they happen:

```bash
php artisan reverb:start --debug
```

<details>
<summary>Running Reverb behind a domain or over HTTPS</summary>

For anything other than plain `localhost`, keep the bind address and the public address separate — Reverb still binds locally while the browser talks to your domain over TLS (typically via an Nginx/Caddy reverse proxy that upgrades WebSocket connections):

```env
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

REVERB_HOST=pb.example.com
REVERB_PORT=443
REVERB_SCHEME=https
```

Echo reads `VITE_REVERB_SCHEME` to decide whether to force TLS, so it must match. In production run Reverb under a process supervisor (Supervisor, systemd, or `php artisan reverb:restart` after deploys) rather than in a bare terminal.
</details>

### 7. (Optional) Configure PDF export

PDF export needs a Chrome/Chromium binary. Point `.env` at it:

```env
LARAVEL_PDF_CHROME_PATH=/usr/bin/google-chrome    # Linux
# macOS:   /Applications/Google Chrome.app/Contents/MacOS/Google Chrome
# Windows: C:\Program Files\Google\Chrome\Application\chrome.exe
```

### 8. Run the app

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

### 9. Log in

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
- **Real-time features not updating** — work through these in order:
  1. Is `php artisan reverb:start` running? Re-run it with `--debug` to see whether events reach the server.
  2. Is `BROADCAST_CONNECTION=reverb` set? The framework default is `null`, which discards every event silently.
  3. Are `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` filled in? They're blank in `.env.example` — run `php artisan reverb:install`.
  4. Did you restart Vite after editing `REVERB_*`? The `VITE_REVERB_*` values are compiled into the bundle at build time.
  5. Is `php artisan queue:listen` running? Notifications are queued, so the bell stays quiet without a worker.
  6. Still stuck? `php artisan config:clear` — a cached config will keep serving old broadcast settings.
- **Browser console shows `WebSocket connection to 'ws://localhost:8080' failed`** — the Reverb server isn't running, or `VITE_REVERB_PORT` doesn't match `REVERB_SERVER_PORT`.
- **403 from `/broadcasting/auth`** — you're not logged in, or the channel authorization in `routes/channels.php` rejected the user.
- **Migration / DB connection errors** — confirm your database server is running and the `DB_*` credentials in `.env` match it.
- **Windows line-ending or permission oddities** — develop inside **WSL2** for the smoothest experience.
