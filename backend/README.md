# Backend РК ПРОФИ (Laravel API)

Laravel application code is in `src/`. The Vue source is in the sibling `../frontend/src/` directory and is built by Vite from `src/`.

## Local development Docker stack

The existing development stack is intentionally separate from production.

```bash
cd backend
copy .env.example .env
docker compose up -d --build
```

After the first clone:

```bash
docker exec backend-rkprofi composer install
docker exec backend-rkprofi php artisan key:generate
docker exec backend-rkprofi php artisan migrate
```

| Service | URL / port |
|---|---|
| API (Nginx) | `http://localhost:8090` |
| PostgreSQL | `localhost:5433` (`rkprofi` / `rkprofi`) |
| Redis | `localhost:6380` |
| pgAdmin | `http://localhost:8082` |
| MailHog UI | `http://localhost:8026` |

Useful commands:

```bash
docker exec backend-rkprofi php artisan ...
docker exec backend-rkprofi composer ...
docker compose logs -f backend-rkprofi
```

## Production Docker deployment

Use `docker-compose.production.yml`, not the local Compose file. The production image is self-contained: it installs Composer dependencies, builds the Vue/Vite assets, has no Xdebug, and exposes only Nginx ports.

See [DEPLOYMENT.md](DEPLOYMENT.md) for first deployment, selecting HTTP or HTTPS, manual certificate installation and renewal, updates, rollback, and backups.
