# Feverup Code Challenge — Pokémon CPT (WordPress + Docker)

This repository provides a fully containerized WordPress environment implementing a Pokémon Custom Post Type (CPT) with importer, REST API, AJAX, optional TypeScript filter UI, and automated tests. The goal is that a reviewer can clone, run `docker-compose up`, and evaluate everything locally.

---

## Contents

- WordPress (PHP 8.2, Apache)
- MariaDB 10.6
- Custom plugin: `wp-content/plugins/pokemon-cpt/`
- Optional theme child: `wp-content/themes/understrap-child/`
- REST API routes under `/wp-json/pokemon/v1/`
- Random and generate routes
- PHPUnit test environment with WP core test suite bootstrap
- Optional DB snapshot import on first run (`data/db_data.sql`)

---

## Prerequisites

- Docker and Docker Compose installed
- Ports 8080 (HTTP) and 8081 (phpMyAdmin) available
- Port 3306 free if you expose DB locally

---

## Quick Start

Clone and start the stack:

```bash
git clone https://github.com/YOUR-USERNAME/feverup.git
cd feverup
docker-compose up -d
```

URLs after startup:

- WordPress front end: http://localhost:8080/
- WordPress admin:     http://localhost:8080/wp-admin/
- phpMyAdmin:          http://localhost:8081/

Default admin credentials (development only):

- Username: `admin`
- Password: `admin`

If you see a database error or an empty WordPress install the first time, see the Database section below.

---

## Project Structure

```
feverup/
├─ docker-compose.yml
├─ Dockerfile
├─ phpunit.xml
├─ wp-tests-bootstrap.php
├─ wp-tests-config.php
├─ data/
│  └─ db_data.sql                # optional pre-filled DB dump
├─ tests/
│  ├─ test_cpt_registration.php
│  ├─ test_importer.php
│  └─ test_taxonomy_registration.php
└─ wp-content/
   ├─ plugins/
   │  └─ pokemon-cpt/
   │     └─ pokemon-cpt.php
   └─ themes/
      └─ understrap-child/
```

---

## Database

### Automatic import on first run

If `data/db_data.sql` is present, the DB container will load it automatically on the first initialization (when the MariaDB data directory is empty). This happens when you run `docker-compose up -d` on a clean machine or after removing volumes.

To force a clean re-import:

```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

Verify the import:

```bash
docker logs wordpress-db | grep docker-entrypoint-initdb.d || true
docker exec -it wordpress-db mysql -u wp_user -pwp_pass -e "SHOW DATABASES; USE wp_db; SHOW TABLES;"
```

### Export a fresh snapshot

```bash
docker exec -it wordpress-db mysqldump -u wp_user -pwp_pass wp_db > data/db_data.sql
```

Ensure the dump targets `wp_db`. Typical MariaDB dumps do not include a `CREATE DATABASE` or `USE` mismatch; if needed you can adjust manually.

---

## URLs and Features

### CPT Archive and Single

- Pokémon archive: `http://localhost:8080/pokemon/`
- Single Pokémon: `http://localhost:8080/pokemon/{post-name}/`

If you see 404s after enabling the plugin, go to WP Admin → Settings → Permalinks and click Save to flush rewrite rules.

### REST API

- List stored Pokémon (ID is most recent Pokédex number when available):

  ```
  GET http://localhost:8080/wp-json/pokemon/v1/list/
  ```

- Get a single Pokémon by most recent Pokédex number:

  ```
  GET http://localhost:8080/wp-json/pokemon/v1/pokemon/{id}/
  ```

Example response fields include: name, description, photo (featured image), types, weight, old/new Pokédex numbers and versions, moves with short descriptions.

### Random and Generate Routes

- Redirect to a random stored Pokémon:

  ```
  http://localhost:8080/random
  ```

  This redirects to the permalink of a random published Pokémon in the database.

- Generate and store a random Pokémon from PokéAPI (requires a logged-in user with post creation capability, e.g., admin):

  ```
  http://localhost:8080/generate
  ```

- Admin import by name via `admin-post.php` action (requires admin or editor):

  ```
  http://localhost:8080/wp-admin/admin-post.php?action=pokemon_import&name=pikachu
  ```

### AJAX on Single Pokémon

On the single Pokémon template, a button triggers an AJAX request to fetch the oldest Pokédex number and version, returned by the plugin’s `wp_ajax_*` handler.

### Optional TypeScript Filter Page

The archive page can render a grid of Pokémon with a simple client-side filter by type and pagination limited to 6 per page. The filter options are based on the first types returned by the PokéAPI. This is intentionally lightweight to keep the code review focused on the WordPress parts.

---

## Running Tests

The test service downloads the WordPress core test suite and runs three test files included in `tests/`.

Run all tests:

```bash
docker-compose run --rm test
```

Expected output:

```
PHPUnit 9.6.x by Sebastian Bergmann and contributors.

...  3 / 3 (100%)

OK (3 tests, 8 assertions)
```

If you see bootstrap or wp-tests-config warnings, confirm these files exist at the repository root and are mounted by the `test` service:

- `wp-tests-bootstrap.php`
- `wp-tests-config.php`
- `phpunit.xml`
- `tests/` directory with the test files

---

## Common Commands

Start services:

```bash
docker-compose up -d
```

Stop services:

```bash
docker-compose down
```

Rebuild images from scratch:

```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

Watch container logs:

```bash
docker-compose logs -f
docker-compose logs -f wordpress
docker-compose logs -f db
```

Open a shell in the WordPress container:

```bash
docker exec -it wordpress-dev bash
```

Open MySQL CLI:

```bash
docker exec -it wordpress-db mysql -u wp_user -pwp_pass wp_db
```

Run tests:

```bash
docker-compose run --rm test
```

---

## Troubleshooting

1) Database dump did not load  
- Cause: MariaDB only runs initialization scripts when the data directory is empty.  
- Fix: `docker-compose down -v && docker-compose up -d`

2) REST routes return 404  
- Fix: WP Admin → Settings → Permalinks → Save. Alternatively, in the container:  
  ```bash
  wp rewrite flush --hard --allow-root
  ```

3) Test bootstrap errors (WP_UnitTestCase not found)  
- Cause: WP test library not downloaded or bootstrap not mounted.  
- Fix: Ensure `wp-tests-bootstrap.php`, `wp-tests-config.php`, and `phpunit.xml` exist at repo root. Re-run `docker-compose run --rm test`.

4) Permission issues writing uploads  
- Fix inside WordPress container:  
  ```bash
  chown -R www-data:www-data /var/www/html/wp-content
  ```

5) Plugin or theme code changes not visible  
- Ensure files are edited on the host within `wp-content/` and not only inside the container. The `docker-compose` mounts `./wp-content` into the container; local edits persist.

---

## Review Notes

- Admin user for development:
  - Username: `admin`
  - Password: `admin`
- All features are implemented without ACF or page builders.
- PHP 8 required by base image.
- Code follows WordPress Coding Standards as closely as practical within the time box.
- The repository is self-contained for evaluation with Docker and does not require local PHP or MySQL.
- For a completely fresh run, always use `docker-compose down -v` followed by `docker-compose up -d`.

---

## Pre-commit Checklist (for the author)

- [ ] `wp-content/plugins/pokemon-cpt/pokemon-cpt.php` exists and loads
- [ ] `tests/` contains all three test files and they pass locally
- [ ] `wp-tests-bootstrap.php`, `wp-tests-config.php`, and `phpunit.xml` are present at repo root
- [ ] `data/db_data.sql` exists and restores a working admin user (`admin/admin`) and a few example Pokémon
- [ ] `docker-compose up -d` works on a clean machine (or after `down -v`)
- [ ] README reflects current commands and routes

---

License: MIT
