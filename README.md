# LAPOR TI

**Aspiration and Complaint Service** — a citizen-facing helpdesk where the public can submit reports, track their status, and staff can handle them from an admin panel.

Built on [HESK](https://www.hesk.com) 3.7.10 (PHP) with a custom theme (`LaporTi`) and Indonesian localization, packaged to run locally with Docker Compose.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Database](#database)
- [Admin Panel](#admin-panel)
- [Customization](#customization)
- [Deployment](#deployment)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [Credits and License](#credits-and-license)

---

## Features

- Public complaint submission form with file attachments
- Report status lookup using a tracking ID
- Staff/admin panel: ticket management, categories, staff accounts and permissions, knowledge base
- Custom theme (`LaporTi`) and Bahasa Indonesia language pack
- Reproducible local environment: PHP + Apache and MariaDB, started with one command

## Tech Stack

| Component | Version / Notes |
|---|---|
| Application | HESK 3.7.10 (PHP, no framework) |
| Web server | Apache with PHP 8.2 (`php:8.2-apache-bookworm` image) |
| Database | MariaDB 10.11 |
| Orchestration | Docker Compose v2 |
| Table prefix | `b2ca_` |

## Project Structure

```
laporti/
├── README.md
├── .env.example              # template for local credentials (copy to .env)
├── .gitignore
├── docker-compose.yml
├── docker/
│   ├── Dockerfile            # PHP + Apache image with required extensions
│   ├── entrypoint.sh         # generates settings on first start, fixes permissions
│   ├── render-settings.php   # builds hesk_settings.inc.php from template + env
│   └── initdb/               # put the SQL dump here (not tracked by git)
└── src/                      # the entire HESK application
```

What lives inside `src/`:

| Group | Contents |
|---|---|
| Public pages | `index.php`, `submit_ticket.php`, `reply_ticket.php`, `my_tickets.php`, `knowledgebase.php`, `login.php`, `register.php`, and other root `*.php` files |
| Admin panel | the directory named by `admin_dir` in the settings file |
| Core | `inc/`, `vendor/` |
| Presentation | `theme/LaporTi/` (custom), `theme/hesk3/` (default), `css/`, `js/`, `img/`, `fonts/` |
| Languages | `language/id/` (Indonesian), `language/en/` |
| Configuration | `hesk_settings.inc.php.example` (tracked template), `hesk_settings.inc.php` (local, not tracked) |
| Runtime data | `attachments/` (uploaded files), `cache/` |
| Misc | `cron/`, `docs/`, `.htaccess`, `robots.txt`, `head.txt`, `header.txt`, `footer.txt` |

> **Important:** HESK locates its files through relative paths (`HESK_PATH`). Do not move individual files out of `src/` or reorganize its internals, or the application will break.

## Quick Start

### Requirements

- Docker Engine with Docker Compose v2
- Git
- A database dump of the project (not included in the repository)

### Steps

1. **Clone the repository**

   ```bash
   git clone https://github.com/dfulndri/laporti.git
   cd laporti
   ```

2. **Create your environment file**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` and set your own passwords (see [Configuration](#configuration)).

3. **Add the database dump**

   ```bash
   cp /path/to/your-dump.sql docker/initdb/laporti.sql
   ```

   Files in `docker/initdb/` are imported automatically, but **only the first time** the database volume is created. See [Database](#database) for importing into an existing database.

4. **Start the stack**

   ```bash
   docker compose up -d --build
   ```

5. **Open the application**

   | Page | URL |
   |---|---|
   | Public site | http://localhost:8080 |
   | Admin panel | http://localhost:8080/021lm3dntgmirqrs/ |

   Find the admin directory name with:

   ```bash
   grep admin_dir src/hesk_settings.inc.php.example
   ```

If the page does not load, check `docker compose logs --tail=30 app` and the [Troubleshooting](#troubleshooting) section.

## Configuration

### Environment variables (`.env`)

| Variable | Purpose | Default in `.env.example` |
|---|---|---|
| `DB_NAME` | Database name | `laporti` |
| `DB_USER` | Application database user | `laporti` |
| `DB_PASSWORD` | Password of the application user | *(set your own)* |
| `DB_ROOT_PASSWORD` | MariaDB root password | *(set your own)* |
| `DB_PREFIX` | Table prefix (must match the dump) | `b2ca_` |
| `SITE_URL` | Base URL used by HESK | `http://localhost:8080` |
| `APP_PORT` | Host port for the web app | `8080` |
| `DB_PORT` | Host port for the database | `3307` |
| `DB_BIND` | Interface the DB port is bound to | `127.0.0.1` |

Avoid the `$` character in passwords, since Docker Compose treats it as variable interpolation.

### How the HESK settings file is created

`src/hesk_settings.inc.php` contains secrets, so it is **not** committed. On the first start, the container entrypoint generates it from `src/hesk_settings.inc.php.example` and the values in `.env`. SMTP is disabled and the URL key is randomly generated.

To regenerate it from scratch:

```bash
rm src/hesk_settings.inc.php
docker compose restart app
```

Saving settings from the HESK admin panel rewrites this file. That is expected and safe, because it is git-ignored.

## Database

### Connect with a database client (Navicat, DBeaver, etc.)

| Field | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `3307` (or your `DB_PORT`) |
| User | value of `DB_USER` |
| Password | value of `DB_PASSWORD` |
| Database | value of `DB_NAME` |

The port is bound to `127.0.0.1` only, so it is not reachable from other machines. If your client cannot connect (for example when Docker runs inside WSL), set `DB_BIND=0.0.0.0` in `.env` and run `docker compose up -d`.

### Import a dump into an existing database

```bash
set -a; . ./.env; set +a
docker exec -i laporti_db mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < backup.sql
```

### Backup

```bash
set -a; . ./.env; set +a
docker exec laporti_db mariadb-dump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > backup_$(date +%F).sql
```

### Reset the local database (destroys all local data)

```bash
docker compose down -v
docker compose up -d
```

> Changing `DB_PASSWORD` in `.env` does not change the password of an existing database user. Either run `ALTER USER` inside the container or recreate the volume as shown above.

## Admin Panel

Log in at `/<admin_dir>/` with a staff account that exists in the `users` table.

### Reset an account password (local or any environment you control)

```bash
set -a; . ./.env; set +a
HASH=$(docker exec laporti_app php -r "echo password_hash('NewPassword123', PASSWORD_DEFAULT);")
docker exec laporti_db mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
  -e "UPDATE ${DB_PREFIX}users SET pass='${HASH}' WHERE user='YOUR_USERNAME';"
```

### Login locked after too many failed attempts

HESK blocks an IP for 60 minutes after repeated failures. Clear the log:

```bash
set -a; . ./.env; set +a
docker exec laporti_db mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "DELETE FROM ${DB_PREFIX}logins;"
```

### Roles

- **Administrator** (`isadmin = 1`): full access to every feature and category.
- **Staff** (`isadmin = 0`): access limited by assigned categories and feature permissions.

## Customization

Make changes only in these places:

| What | Where |
|---|---|
| Look and feel | `src/theme/LaporTi/` |
| Indonesian text | `src/language/id/` |
| Header / footer HTML snippets | `src/head.txt`, `src/header.txt`, `src/footer.txt` |

Leave HESK core files (`src/inc/`, root `*.php`) untouched so that future HESK upgrades stay manageable. When upgrading HESK, follow the official upgrade guide and re-apply the customizations above.

## Deployment

This repository is set up for local development. For a production host:

1. Upload the **contents of `src/`** to the web root (not the repository root; do not upload `docker/`, `.env` or `README.md`).
2. Create `hesk_settings.inc.php` on the server from `hesk_settings.inc.php.example`. Fill in the database credentials, set `site_url` and `hesk_url` to your real domain, set `force_ssl` to `1`, configure SMTP, and generate a new URL key in the admin panel.
3. Import the database dump with the host's database tool.
4. Make sure `attachments/` and `cache/` are writable by the web server.
5. Open the site over HTTPS and verify ticket submission and email delivery.

## Security Notes

- Never commit `.env`, `src/hesk_settings.inc.php`, SQL dumps, or anything under `src/attachments/` (it contains files uploaded by citizens). All of these are covered by `.gitignore`.
- Use different, strong passwords for local and production environments.
- If credentials were ever pushed to a repository, rotate them immediately. Removing a file from git does not remove it from history.
- Keep the database port bound to `127.0.0.1` unless you have a specific reason not to.
- Consider keeping this repository private, since it includes a customized theme and project-specific content.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `403 Forbidden` / "unable to read htaccess file" | `chmod 755 . src && chmod -R a+rX .` then `docker compose restart app` |
| `ERR_SSL_PROTOCOL_ERROR` or redirect to HTTPS | Make sure `force_ssl` is `0` in `src/hesk_settings.inc.php`, use `http://` explicitly, and try an incognito window |
| "Could not load a valid language file" | Check that `src/language/id` and `src/language/en` exist |
| "Cannot connect to database" | `db_host` must be `db` inside Docker. Wait ~20 seconds after the first start, then check `docker compose logs db` |
| `Table '...b2ca_...' doesn't exist` | The dump was not imported. See [Import a dump](#import-a-dump-into-an-existing-database) |
| Tables exist but import fails with "already exists" | The dump was already imported automatically on first start. No action needed |
| Uploads fail | `chmod -R 777 src/attachments src/cache` |
| Database client cannot connect | Verify with `docker compose ps`, then try `DB_BIND=0.0.0.0` in `.env` |
| Emails are not sent locally | Expected: SMTP is disabled in the generated local settings. Configure it under Settings → Email in the admin panel if needed |

## Credits and License

- Based on [HESK](https://www.hesk.com), a third-party helpdesk product with its own license terms. Please review them on the official site before redistributing the software or modifying the "Powered by" footer.
- Custom theme, localization, and Docker packaging: LAPOR TI project.
