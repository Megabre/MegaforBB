<p align="center">
  <img src="https://raw.githubusercontent.com/Megabre/MegaforBB/refs/heads/main/megabb.png" alt="MegaforBB" width="600">
</p>

<h1 align="center">MegaforBB</h1>

<p align="center">
  <strong>Powerful, Secure, and Fast Community Forum</strong><br>
  An open-source community forum that combines a classic forum experience with modern enterprise-grade infrastructure.
</p>

<p align="center">
  <a href="https://www.megaforbb.org">Website</a> ·
  <a href="https://github.com/Megabre/MegaforBB/releases">Download</a> ·
  <a href="https://www.megaforbb.org/news">News</a> ·
  <a href="https://github.com/Megabre/MegaforBB/issues">Report a Bug</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.1.3-blue" alt="Version 1.1.3">
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.0-777BB4?logo=php&logoColor=white" alt="PHP 8.0+">
  <img src="https://img.shields.io/badge/license-open%20source-green" alt="Open Source">
</p>

---

## About

**MegaforBB** is a community forum platform built on the enterprise-grade **Forecor** hybrid kernel. It combines Symfony Dependency Injection and Event Dispatcher with Laravel Eloquent ORM, with a strong focus on security, performance, and extensibility.

| Link | URL |
|------|-----|
| Website | [megaforbb.org](https://www.megaforbb.org) |
| GitHub repository | [github.com/Megabre/MegaforBB](https://github.com/Megabre/MegaforBB) |
| Downloads | [Releases](https://github.com/Megabre/MegaforBB/releases) |
| News & announcements | [megaforbb.org/news](https://www.megaforbb.org/news) |

---

## Key Features

### Forum & Community
- Category and subforum structure, topic prefixes, tags
- Private topics, scheduled publishing, Q&A mode
- Article / blog categories and portal homepage
- Private messaging (PM), inbox, and quota management
- Member profiles, reputation system, profile comments, profile viewers
- Online members, member list, activity feed
- Announcements, ad slots, widget system
- Multi-language support (Turkish and English included)

### Security
- **Attack Mode** — activates the strictest security presets in RAM during an attack
- Early-layer global rate limiting and **RTBH** (IP blocklist)
- NDJSON file-based security audit logs (no DB load, automatic rotation)
- Security headers (X-Frame-Options, nosniff, XSS protection, HSTS)
- CSRF protection, captcha, Stop Forum Spam integration
- Admin 2FA, invitation system, role and permission management
- Censorship / word filtering, file verification manifest

### Performance & Infrastructure
- File or **Redis** cache (APP_URL-based prefix isolation)
- HTML/CSS/JS minification (HtmlMinifier)
- PHP gzip compression, Varnish-compatible setup
- **Meilisearch** full-text search integration
- S3-compatible file storage (Flysystem)

### Extensibility
- Plugin architecture via Event Dispatcher and **PluginLoader**
- Twig-based theme engine with template inheritance
- **Idelist** module — idea / suggestion list with voting
- Documentation module (tree-structured pages)
- RSS feed import
- Data import from XenForo, MyBB, and MegaforBB

### Admin Panel
- Forum, user, role, permission, and moderation tools
- Theme management, PWA settings, SEO / Open Graph
- Backups, cron jobs, error logs
- Contact messages, email templates
- Critical error alerts via Telegram webhook

---

## Architecture

MegaforBB runs on the **Forecor** hybrid kernel:

```
Request → public/index.php (Front Controller)
        → Forecor\Core\Application
        → Symfony DI (ContainerBuilder + auto-wiring)
        → Symfony Router & Event Dispatcher
        → Illuminate Eloquent ORM (database)
        → Twig (themes) + App layer (controllers / services / models)
```

| Layer | Technology |
|-------|------------|
| Core DI | Symfony DependencyInjection |
| Routing & Events | Symfony Routing, Event Dispatcher |
| Database | Illuminate Database (Eloquent) |
| Templates | Twig 3 |
| Search | Meilisearch (optional) |
| Cache | File / Redis |
| Storage | Local / AWS S3 (Flysystem) |

---

## Requirements

| Component | Minimum |
|-----------|---------|
| PHP | 8.0+ (recommended: 8.3) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Web server | Apache (mod_rewrite) or Nginx |
| PHP extensions | PDO, mbstring, json, openssl, curl, gd or imagick |

**Optional (recommended for production):**

- Redis — cache and sessions
- Meilisearch — full-text search
- Varnish — HTTP cache layer

---

## Installation

**MegaforBB v1.1.3 — the first stable release — is now available and ready to use.**

Download the latest package from [GitHub Releases](https://github.com/Megabre/MegaforBB/releases).

### 1. Upload the files

Extract `megaforbb-upload.zip` from the release archive and upload it to your hosting account.

### 2. Import the database

Import the database from `megaforbb-database.zip` using phpMyAdmin or a similar tool.

> **No migrations required.** The release includes a fully prepared database — just import it and you are good to go.

### 3. Configure the environment file

On your hosting, rename `.env-simple` to `.env` and edit its contents to match your website:

```env
APP_NAME=MegaforBB
APP_ENV=Production
APP_DEBUG=false
APP_URL=https://forum.example.com
APP_KEY=                    # A strong random key
APP_LOCALE=en
APP_TIMEZONE=UTC

DB_HOST=127.0.0.1
DB_DATABASE=forum_db
DB_USERNAME=forum_user
DB_PASSWORD=your_password

CACHE_DRIVER=file           # or redis
SESSION_DRIVER=file
MEILISEARCH_HOST=http://127.0.0.1:7700
```

### 4. Default admin credentials

| Field | Value |
|-------|-------|
| Username | `Megadmin` |
| Email | `hello@megaforbb.org` |
| Password | `125478!!` |

> **Change the password immediately after your first login.**

The admin panel is available at `/admin` by default (configurable via `ADMIN_PATH`).

### 5. Clear the cache

Clear the application cache and your server's opcode cache after installation. You can also use **Performance → Clear Cache** in the admin panel.

---

That's it — **MegaforBB is ready to use. Enjoy!**

### Optional: Cron job

For scheduled tasks (RSS imports, scheduled topics, etc.), set up a cron job to run every 5 minutes:

```bash
*/5 * * * * php /path/to/forum/public/cron.php
```

You can also trigger cron from the **Cronjobs** section in the admin panel.

---

## Directory Structure

```
MegaforBB/
├── App/                  # Application layer (controllers, models, services, migrations)
├── Forecor/              # Hybrid kernel (DI, router, bootstrap)
├── Inc/
│   ├── Lang/             # Language files (tr, en)
│   ├── Plugin/           # Plugins
│   └── Template/         # Themes (frontend + admin)
├── Route/                # Web, admin, and API route definitions
├── public/               # Front controller and static assets
├── Content/              # Cache, logs, backups, build
├── Library/vendor/       # Composer dependencies
├── .env                  # Environment configuration
└── .htaccess             # Apache rules (nginx.vhost example included)
```

---

## Themes

MegaforBB ships with three ready-made frontend themes:

| Theme | Description |
|-------|-------------|
| **Default** | Modern default theme powered by Tailwind |
| **RetroDSG** | 2010 nostalgia with glass effects and dynamic typography |
| **Humanizer** | Classic forum look from the 2006–2015 era |

Themes can be activated and customized from the admin panel.

---

## Plugins

Plugins live in the `Inc/Plugin/` directory. Each plugin follows this structure:

- `plugin.json` — metadata
- `plugin.php` — event listener definitions
- `routes.php` — optional routes
- `views/` — Twig templates

Built-in example: **AI Auto Reply** — automatically generates replies to newly opened topics via an AI assistant user.

---

## Data Import

From the admin panel, you can import data from:

- **XenForo**
- **MyBB**
- **MegaforBB** (from another installation)

Import runs step by step; progress is tracked in the panel.

---

## Development

Install dependencies for a development environment:

```bash
composer install
```

Optional dev tools:

```bash
composer require --dev squizlabs/php_codesniffer friendsofphp/php-cs-fixer phpstan/phpstan
```

With `APP_DEBUG=true`, errors are shown in detail via **Whoops**. In production, use `APP_DEBUG=false`; errors are written to `Content/storage/logs/`.

For upgrades or custom setups, migrations can be run manually:

```bash
php Forecor/bin/migrate.php
php Forecor/bin/migrate.php --status
```

---

## Update Check

MegaforBB checks [version.json](https://raw.githubusercontent.com/Megabre/MegaforBB/refs/heads/main/version.json) daily via cron. The current version and file manifest are read from this URL.

---

## Support & Contributing

MegaforBB is completely **free and open source**.

| | |
|---|---|
| Professional support | [megabre.com](https://megabre.com) |
| Bug reports | [GitHub Issues](https://github.com/Megabre/MegaforBB/issues) |
| News | [megaforbb.org/news](https://www.megaforbb.org/news) |

Feel free to open an issue or submit a pull request to contribute.

---

<p align="center">
  <sub>© 2026 MegaforBB — Powerful, Secure, and Fast Community Forum</sub>
</p>
