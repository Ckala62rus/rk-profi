# Backend РК ПРОФИ (Laravel API)

Структура как у `D:\OSPanel\domains\etp\backend`: Docker + Laravel в `src/`.

## Запуск

```bash
cd backend
copy .env.example .env
docker compose up -d --build
```

Laravel уже лежит в `src/`. После первого клона:

```bash
docker exec backend-rkprofi composer install
docker exec backend-rkprofi php artisan key:generate
docker exec backend-rkprofi php artisan migrate
```

## Сервисы

| Сервис | URL / порт |
|--------|------------|
| API (Nginx) | http://localhost:8090 |
| PostgreSQL | localhost:5433 (`rkprofi` / `rkprofi`) |
| Redis | localhost:6380 |
| pgAdmin | http://localhost:8082 |
| MailHog UI | http://localhost:8026 |

Демо-статика (`grabber`) остаётся на **8080**.

## Команды

```bash
docker exec backend-rkprofi php artisan ...
docker exec backend-rkprofi composer ...
docker compose logs -f backend-rkprofi
```

## Архитектура кода

REST API, слои как в skill `laravel-api-backend` и правилах `.cursor/rules/`.  
Фронт — отдельный Vue SPA (шаблон позже).
