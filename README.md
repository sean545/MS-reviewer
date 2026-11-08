# MS Reviewer

A green-themed learning-module system for MS programs. Professors upload topic PDFs per course;
students read them in the browser, track progress, take page-pinned notes and search inside every PDF.

Built with Laravel 13, Filament 5, MySQL 8.4, Redis and poppler-utils, all running in Docker.

| Area | URL | Who |
|---|---|---|
| Student portal | `/learn` | Everyone (self-registration creates a Student) |
| Staff panel | `/admin` | Admins and Professors |

**Roles:** Admins manage everything, including users and site-wide announcements. Professors
manage the topics, PDFs and announcements of the courses an admin assigns to them. Students read.

## Local development

Requires Docker. PHP, Composer and Node run inside the `app` container.

```bash
docker/setup.sh                       # first time: build, install, migrate, seed
docker compose up -d                  # afterwards
docker compose exec app php artisan test --compact
```

Open http://localhost:8081. Dev accounts (password `password`): `admin@reviewer.test`,
`prof@reviewer.test`, `student@reviewer.test`.

PDF processing (page count, thumbnail, search text) runs on the `queue` container. After changing
job code, run `docker compose restart queue`. After changing the panel theme
(`resources/css/filament/learn/theme.css`) or Blade views, rebuild it with
`docker compose exec app npm run build`. To re-index every PDF, run
`docker compose exec app php artisan materials:reprocess`.

## Production

On a Linux server with Docker and git:

```bash
git clone git@github.com:sean545/MS-reviewer.git && cd MS-reviewer
cp .env.example .env
# edit .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain,
#            APP_PORT=80, and strong DB_PASSWORD / DB_ROOT_PASSWORD values
docker/setup.sh                       # first install (demo data is skipped in production)
docker compose exec app php artisan users:make-admin you@example.com
docker/deploy.sh                      # switch to production mode
```

For later updates, push to `main` and run `docker/deploy.sh` on the server again. Put HTTPS in
front of nginx with a reverse proxy such as Caddy or Cloudflare.

## Backups

```bash
docker/backup.sh                      # writes backups/<timestamp>/ (database + PDFs), keeps 14
docker/restore.sh backups/<timestamp> # replaces the current database and PDFs
```

In production, prefix each command with
`COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"`. For daily backups, add a host
crontab entry, for example `0 2 * * * cd /srv/MS-reviewer && docker/backup.sh >> backups/backup.log 2>&1`.
Copy the `backups/` folder off the server regularly.
