# Lab 01: Development Environment Standardization

**Source lesson:** VIP Learn – Development, Build, Test, and Deployment › Development environment standardization ([my notes](../../notes/01-development-environment-standardization.md))

## Goal

Build a local WordPress environment that every developer can recreate with
the same versions and settings, and show that the **same code** behaves
differently in `local` and `production` because only the configuration changes.

| Concern | How this lab handles it |
|---|---|
| Same versions for everyone | WordPress `7.1.2` and PHP `8.3` pinned in `.wp-env.json`; `@wordpress/env` pinned to an exact version; Node pinned in `.nvmrc`; Composer resolves packages for PHP 8.3 via `config.platform` |
| Config per environment | Two config files: `.wp-env.json` (`local`) and `.wp-env.production.json` (`production`) |
| Behaviour per environment | Code checks `wp_get_environment_type()`: non-production sites are hidden from search engines and show an environment badge in the admin bar |
| No secrets in code | `Lab01\Secrets::get()` reads VIP env vars, then real environment variables, then `.env` (local/development only) |
| `.env` cannot leak | App code, `vendor/` and `.env` are mounted at `/var/www/lab01`, outside the web root. Only a small loader sits in `mu-plugins/` |
| Same code style | Root `.editorconfig` |

## Run it

Requirements: Docker running, Node 20, Composer 2.

```bash
npm install --include=dev   # --include=dev in case NODE_ENV=production is set in your shell
npm run setup               # composer install + creates .env from .env.example
# put any value in .env, e.g. WEATHER_API_KEY=demo-local-key-1234

npm run start               # local site:            http://localhost:8888
npm run report

npm run start:prod          # production-like site:  http://localhost:8890
npm run report:prod
```

Login: `admin` / `password`.

### Expected result

| Setting | `npm run report` | `npm run report:prod` |
|---|---|---|
| Environment type | `local` | `production` |
| WP_DEBUG | `true` | `false` |
| Search engines allowed | `no` | `yes` |
| WEATHER_API_KEY | `***…1234` | `(missing)` + warning |

The production-like site reports the key as missing even though `.env` exists
on disk. That is on purpose: production must get secrets from the platform,
never from a file in the repository folder.

Stop everything with `npm run stop && npm run stop:prod`.

### Optional: Docker Compose with a pinned database

```bash
docker compose up -d
docker compose run --rm cli wp core install --url=http://localhost:8080 \
  --title="Lab 01 Compose" --admin_user=admin --admin_password=password \
  --admin_email=admin@example.com --skip-email
docker compose run --rm cli wp lab01 report   # Database server: 8.0.32
docker compose down
```

Here the site is `production`, but the secrets are found: Compose passes them
into the container as real environment variables, which is how hosting
platforms deliver secrets.

## Project structure

```
.wp-env.json               local environment
.wp-env.production.json    production-like environment
.wp-env.staging.json       staging environment (challenge 2)
docker-compose.yml         production-like stack with pinned MySQL (challenge 4)
.env.example               list of required secrets (no values)
mu-plugins/
  lab01-environment.php    loader, mounted into wp-content/mu-plugins
src/
  Environment.php          behaviour based on wp_get_environment_type()
  Secrets.php              secret lookup order
  Cli/ReportCommand.php    `wp lab01 report`
```

## Challenges

1. **Add a secret.** Add `MAPS_API_KEY` to `Environment::REQUIRED_SECRETS` and `.env.example`. Check the admin notice on both sites.
2. **Add staging.** Create `.wp-env.staging.json` on port `8891` with `WP_ENVIRONMENT_TYPE` set to `staging`. Predict the report before you run it: is the site indexable? Is `.env` read?
3. **See why the web root matters.** Copy `.env` into `mu-plugins/` and open `http://localhost:8888/wp-content/mu-plugins/.env`. Then delete the copy.
4. **Pin the database.** The report shows wp-env uses MariaDB, and you cannot choose its version. Write a `docker-compose.yml` with `wordpress:php8.3-apache` and `mysql:8.0.32` and compare the report.

## What I learned

- Required secrets are declared by name in `Environment::REQUIRED_SECRETS`. The code only lists the names. The values come from the environment: `.env` on my machine, the hosting platform in staging and production. If a value is missing, administrators see an admin notice: *"Missing secrets: WEATHER_API_KEY, MAPS_API_KEY. Set them in the hosting environment, not in code."*
- Adding a new environment, such as staging, only needs a new config file (`.wp-env.staging.json`) copied from an existing one, with its own port and `WP_ENVIRONMENT_TYPE`. The code does not change.
- Where `.env` lives matters. Inside the web root, anyone can download it in a browser. I tested this: the file returned HTTP 200 with the API key in plain text. It must stay outside the web root and out of Git.

## How this is used in real projects

- WordPress VIP sets `WP_ENVIRONMENT_TYPE` for each environment and provides `vip_get_env_var()` for secrets set with `vip config envvar set`.
- Teams commit `.wp-env.json` (or `docker-compose.yml`) so a new developer runs one command and gets the same stack.
- `.wp-env.override.json` (gitignored) is for personal local changes that should not affect the team.
