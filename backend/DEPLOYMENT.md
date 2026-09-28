# Развёртывание Laravel-сайта на VDS Selectel

Инструкция предназначена для чистого VDS с **Ubuntu 24.04 LTS**. Production-стек запускается через Docker Compose: Nginx, Laravel/PHP-FPM, очередь, планировщик, PostgreSQL, Redis и Certbot.

Внешне открыты только SSH, HTTP и HTTPS. PostgreSQL и Redis не публикуют порты и доступны только контейнерам внутри Docker-сети.

> Все команды после раздела «Клонировать проект» выполняются из каталога `backend`. Не запускайте production командой `docker compose up` без `-f docker-compose.production.yml`.

## 0. Что подготовить заранее

1. VDS с публичным IPv4: для небольшого сайта достаточно 2 vCPU, 4 ГБ RAM и 50 ГБ SSD.
2. Домен и доступ к его DNS-зоне.
3. SSH-ключ на локальном компьютере.
4. SMTP-реквизиты для отправки заявок.
5. Доступ к консоли VDS в панели Selectel. Он нужен на случай ошибки в настройке SSH.

## 1. Направить два домена на сервер

Сайт работает на двух равноправных доменах: англоязычном (ASCII) и русскоязычном (IDN). В DNS-зоне **каждого** домена создайте запись:

| Тип | Имя | Значение |
| --- | --- | --- |
| `A` | `@` | публичный IPv4 VDS |

`www` добавляйте только если он действительно нужен: для него также требуется отдельная `A`/`CNAME`-запись и имя в сертификате.

Для Nginx, Certbot и `SANCTUM_STATEFUL_DOMAINS` русский домен используйте в Punycode. На сервере это можно получить так:

```bash
sudo apt install -y idn2
idn2 ваш-домен.рф
```

Например, результат вида `xn--...` и есть форма, которую нужно записать в `.env.production`. Дождитесь распространения DNS и проверьте оба домена:

```bash
getent ahostsv4 example.com
getent ahostsv4 xn--e1afmkfd.xn--p1ai
```

Оба имени должны возвращать IP вашего VDS. Let’s Encrypt не выпустит сертификат, пока DNS не указывает на этот сервер и снаружи не доступен TCP-порт 80.

## 2. Первичный доступ и обновление ОС

Подключитесь к серверу под пользователем, выданным Selectel (обычно `root`):

```bash
ssh root@SERVER_IP
apt update && apt -y full-upgrade
reboot
```

После перезагрузки подключитесь снова.

### 2.1 Создать SSH-ключ на своём компьютере

Ключ создаётся **на вашем компьютере**, а не на VDS. Если файл ключа уже есть, новый не создавайте: перейдите к выводу публичной части.

**Windows (PowerShell):**

```powershell
ssh-keygen -t ed25519 -a 100 -C "your-email@example.com"
Get-Content "$HOME\.ssh\id_ed25519.pub"
```

**macOS / Linux:**

```bash
ssh-keygen -t ed25519 -a 100 -C "your-email@example.com"
cat ~/.ssh/id_ed25519.pub
```

На вопрос о пути сохранения нажмите Enter, чтобы использовать путь по умолчанию. На вопрос `passphrase` задайте отдельную надёжную парольную фразу. В результате в терминале появится одна строка, начинающаяся с `ssh-ed25519` — это **публичный** ключ. Скопируйте строку целиком.

Никому не передавайте и не добавляйте на сервер файл без расширения `.pub` (`id_ed25519`): это закрытая часть ключа.

### 2.2 Создать пользователя `deploy` и добавить публичный ключ

В root-сеансе VDS выполните:

```bash
adduser deploy
usermod -aG sudo deploy
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
nano /home/deploy/.ssh/authorized_keys
```

Вставьте в открытый файл скопированную строку `ssh-ed25519 ...`. В `nano` сохраните изменения: `Ctrl+O`, Enter, затем выйдите: `Ctrl+X`. После этого задайте права:

```bash
chmod 600 /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys
```

На локальном компьютере откройте **второе** окно терминала и убедитесь, что вход ключом работает:

```bash
ssh deploy@SERVER_IP
```

Не закрывайте исходный root-сеанс до успешной проверки.

## 3. Закрыть лишние порты и усилить SSH

### 3.1 UFW

В root-сеансе разрешите только нужные соединения, затем включите firewall:

```bash
apt install -y ufw
ufw default deny incoming
ufw default allow outgoing
ufw limit 22/tcp comment 'SSH'
ufw allow 80/tcp comment 'HTTP / Lets Encrypt'
ufw allow 443/tcp comment 'HTTPS'
ufw enable
ufw status numbered
```

Не открывайте 5432 (PostgreSQL), 6379 (Redis), 8080, 8081, 5050 (PgAdmin), MailHog или PHP-FPM. PostgreSQL и Redis в production не имеют опубликованных портов, а PgAdmin привязан только к `127.0.0.1` и открывается через SSH-туннель.

> Docker может обходить часть обычных правил UFW для опубликованных портов. Поэтому безопасная модель здесь — **не публиковать** лишние порты в `docker-compose.production.yml`. Проверяйте результат командами `ss -lntup` и `docker ps`.

### 3.2 Отключить вход root и пароли

Только после проверки входа ключом создайте конфигурацию SSH:

```bash
nano /etc/ssh/sshd_config.d/99-hardening.conf
```

Вставьте:

```text
PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
AllowUsers deploy
```

Проверьте синтаксис и примените изменения:

```bash
sshd -t && systemctl restart ssh
```

В отдельном терминале ещё раз выполните `ssh deploy@SERVER_IP`. Если войти не удаётся, не закрывайте текущий сеанс и исправьте конфигурацию.

## 4. Установить Fail2ban

Fail2ban блокирует IP после серии неудачных попыток входа по SSH:

```bash
apt install -y fail2ban
nano /etc/fail2ban/jail.d/sshd.local
```

Содержимое файла:

```ini
[sshd]
enabled = true
backend = systemd
port = ssh
maxretry = 5
findtime = 10m
bantime = 1h
```

Проверьте и включите службу:

```bash
systemctl enable --now fail2ban
fail2ban-client status
fail2ban-client status sshd
```

Для разблокировки ошибочно заблокированного IP:

```bash
fail2ban-client set sshd unbanip IP_ADDRESS
```

Nginx работает в контейнере, поэтому стандартный SSH-jail — единственный Fail2ban-jail, который настраивается этой инструкцией. Не включайте nginx-jail без отдельного доступа Fail2ban к логам контейнера.

## 5. Установить Docker Engine и Docker Compose

Выполните под `deploy` через `sudo`:

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
sudo sh -c 'echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" > /etc/apt/sources.list.d/docker.list'
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker deploy
```

Выйдите и зайдите под `deploy` снова, чтобы применилась группа `docker`, затем проверьте:

```bash
docker --version
docker compose version
docker run --rm hello-world
```

Членство в группе `docker` фактически равно привилегированному доступу к серверу. Не добавляйте в неё других пользователей без необходимости.

## 6. Клонировать проект и подготовить единый ENV

```bash
sudo install -d -o deploy -g deploy /srv/rk-profi
cd /srv/rk-profi
git clone https://github.com/Ckala62rus/rk-profi.git .
cd backend
cp deploy/.env.production.example .env.production
chmod 600 .env.production
nano .env.production
```

`deploy/.env.production.example` — единственный production-шаблон переменных для Docker Compose **и** Laravel. Compose использует из него порты, профили и имена сервисов, Laravel получает те же переменные через `env_file`. В Git хранится только пример, файл `.env.production` содержит секреты и не должен попадать в репозиторий.

Заполните в `.env.production` как минимум:

| Переменная | Что указать |
| --- | --- |
| `SERVER_NAME` | оба домена через пробел: `example.com xn--e1afmkfd.xn--p1ai` |
| `LETSENCRYPT_EMAIL` | рабочий адрес для уведомлений о сертификате |
| `LETSENCRYPT_CERT_NAME` | основной англоязычный домен, например `example.com` |
| `APP_URL`, `FRONTEND_URL` | канонический URL: на первом шаге `http://example.com` |
| `SANCTUM_STATEFUL_DOMAINS` | оба домена через запятую и без протоколов: `example.com,xn--e1afmkfd.xn--p1ai` |
| `DB_PASSWORD` | длинный уникальный случайный пароль |
| `PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD` | учётная запись для локального PgAdmin; пароль должен отличаться от пароля БД |
| `MAIL_*` | SMTP-данные провайдера почты |
| `LEAD_NOTIFY_EMAIL` | адрес получателя заявок |
| `TURNSTILE_*` | ключи Turnstile или пустые значения |

`APP_URL` и `FRONTEND_URL` намеренно содержат только один канонический домен — в примере англоязычный. Второй домен обслуживает тот же сайт. Если домены должны показывать разные языковые версии, этого недостаточно: потребуется отдельная логика определения языка и URL в приложении.

Создайте пароль БД:

```bash
openssl rand -base64 32
```

Пароль задаётся **один раз** в `DB_PASSWORD`; Compose передаёт его PostgreSQL и Laravel без дублирующих `POSTGRES_*` переменных.

Сгенерируйте ключ Laravel, скопируйте выведенную строку `base64:...` и вставьте её как значение `APP_KEY`:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml run --rm --no-deps --build app php artisan key:generate --show
```

Проверьте готовую конфигурацию. В выводе не должно быть `REPLACE_WITH_`:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml config
```

## 7. Первый запуск по HTTP

Первый запуск нужен, чтобы Certbot смог пройти проверку домена. В шаблоне уже установлено `COMPOSE_PROFILES=http`.

```bash
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
docker compose --env-file .env.production -f docker-compose.production.yml ps
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 migrate
curl -I http://127.0.0.1/up
```

У `migrate` ожидается статус `Exited (0)`: он один раз применяет миграции и подготавливает кэш. Сайт должен открываться по HTTP на каждом из двух доменов.

При ошибке сначала посмотрите логи:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 app web-http migrate
```

## 8. Выпустить сертификат Let’s Encrypt и включить HTTPS

Перед командой убедитесь, что **каждый** домен из параметров `-d` уже указывает на этот VDS и что порт 80 доступен извне.

### 8.1 Выпуск сертификата

Замените примеры на значения из `.env.production`. Один сертификат должен содержать **оба** домена. Русский домен в команде — только в Punycode, как и в `SERVER_NAME`:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot certonly --webroot --webroot-path /var/www/certbot --email admin@example.com --agree-tos --no-eff-email --cert-name example.com -d example.com -d xn--e1afmkfd.xn--p1ai
```

Если нужен `www` у любого домена, добавьте его отдельным параметром `-d` и предварительно создайте DNS-запись. Не выпускайте сертификат для имён, которые не направлены на этот VDS.

Certbot создаст сертификат в `deploy/letsencrypt/`. Эта папка исключена из Git; не копируйте и не редактируйте ключи вручную.

### 8.2 Переключение на одновременный HTTP и HTTPS

Откройте `.env.production` и измените следующие значения:

```dotenv
COMPOSE_PROFILES=https
APP_URL=https://example.com
FRONTEND_URL=https://example.com
# Пока HTTP должен оставаться рабочим, оставьте false:
SESSION_SECURE_COOKIE=false
```

Переключите Nginx. Не используйте `-v`: этот параметр удалит тома базы данных и загруженных файлов.

```bash
docker compose --env-file .env.production -f docker-compose.production.yml down
docker compose --env-file .env.production -f docker-compose.production.yml config
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
docker compose --env-file .env.production -f docker-compose.production.yml exec web-https nginx -t
curl -I http://127.0.0.1/up
curl -k -I https://127.0.0.1/up -H 'Host: example.com'
```

После этого сайт доступен и по HTTP, и по HTTPS на обоих доменах. HTTP не перенаправляется специально, как требуется для текущего режима. Проверить сертификат с внешнего компьютера можно так:

```bash
curl -I https://example.com
curl -I https://xn--e1afmkfd.xn--p1ai
```

## 9. Автопродление сертификата

Let’s Encrypt использует HTTP-01, поэтому во время продления TCP-порт 80 должен быть доступен из интернета. Сначала выполните безопасную проверку:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot renew --dry-run
```

Откройте root-crontab:

```bash
sudo crontab -e
```

Добавьте строку, заменив путь, если проект находится не в `/srv/rk-profi/backend`:

```cron
17 3,15 * * * cd /srv/rk-profi/backend && /usr/bin/docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot && /usr/bin/docker compose --env-file .env.production -f docker-compose.production.yml exec -T web-https nginx -s reload >> /var/log/rkprofi-certbot.log 2>&1
```

Проверьте путь Docker командой `command -v docker`; если это не `/usr/bin/docker`, подставьте фактический путь в cron.

## 10. Как позднее закрыть HTTP

Когда HTTP перестанет быть нужен, в `.env.production` измените:

```dotenv
HTTP_PORT=127.0.0.1:80
SESSION_SECURE_COOKIE=true
APP_URL=https://example.com
FRONTEND_URL=https://example.com
```

Затем пересоздайте контейнеры:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml up -d
docker compose --env-file .env.production -f docker-compose.production.yml ps
```

Порт 80 будет привязан только к `127.0.0.1` и перестанет быть доступным из интернета; HTTPS на 443 продолжит работать. Не рассчитывайте для этого на одно лишь правило `ufw deny 80`, потому что Docker публикует порты через собственные правила NAT.

После закрытия порта 80 **автопродление HTTP-01 перестанет работать**. До закрытия выберите один из вариантов:

1. настроить DNS-01 в Certbot через API DNS-провайдера;
2. временно возвращать `HTTP_PORT=80` для продления и затем снова закрывать порт;
3. оставить порт 80 открытым только для renew.

Также можно удалить правило UFW для внешнего HTTP после проверки, что Docker пересоздан с loopback-привязкой:

```bash
sudo ufw delete allow 80/tcp
```

## 11. PgAdmin: безопасный просмотр PostgreSQL

PgAdmin запускается вместе с production-стеком, но его порт по умолчанию привязан только к `127.0.0.1` VDS. Он **не доступен из интернета** и не требует нового правила UFW.

Перед первым запуском заполните в `.env.production`:

```dotenv
PGADMIN_PORT=127.0.0.1:5050
PGADMIN_DEFAULT_EMAIL=ваш-email@example.com
PGADMIN_DEFAULT_PASSWORD=длинный-уникальный-пароль
```

После `docker compose ... up -d` создайте SSH-туннель на своём компьютере. Команду держите запущенной, пока работаете с PgAdmin:

```bash
ssh -N -L 127.0.0.1:5050:127.0.0.1:5050 deploy@SERVER_IP
```

Откройте в браузере `http://127.0.0.1:5050` и войдите с `PGADMIN_DEFAULT_EMAIL` и `PGADMIN_DEFAULT_PASSWORD`.

В PgAdmin добавьте подключение **Register → Server** со значениями:

| Поле PgAdmin | Значение из `.env.production` |
| --- | --- |
| Name | любое, например `РК ПРОФИ production` |
| Host name/address | `postgres` |
| Port | `5432` |
| Maintenance database | значение `DB_DATABASE` |
| Username | значение `DB_USERNAME` |
| Password | значение `DB_PASSWORD` |
| SSL mode | `Prefer` |

`postgres` — внутреннее имя Docker-сервиса, поэтому оно разрешается из контейнера PgAdmin, но не с вашего компьютера. Не используйте публичный IP VDS и не публикуйте PostgreSQL наружу.

## 12. Повседневное управление и обновление

```bash
# Статус и логи
docker compose --env-file .env.production -f docker-compose.production.yml ps
docker compose --env-file .env.production -f docker-compose.production.yml logs -f app queue scheduler web-https pgadmin

# Laravel
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan about
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan down
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan up

# Обновление: сначала резервная копия, затем
git pull --ff-only
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 migrate
```

Не выполняйте `docker compose down -v` в production: флаг `-v` удалит PostgreSQL, PgAdmin и другие именованные тома.

## 13. Резервное копирование

Нужны две независимые части: PostgreSQL и том `laravel-storage` с загрузками. Восстановление только базы оставит записи о медиафайлах без самих файлов.

```bash
mkdir -p /srv/rk-profi/backups

# PostgreSQL
docker compose --env-file .env.production -f docker-compose.production.yml exec -T postgres sh -c 'pg_dump -Fc -Z 6 -U "$POSTGRES_USER" "$POSTGRES_DB"' > /srv/rk-profi/backups/rkprofi-$(date +%F).dump

# Загруженные Laravel-файлы
docker run --rm -v rkprofi_laravel-storage:/source:ro -v /srv/rk-profi/backups:/backup alpine:3.20 sh -c 'tar -czf /backup/rkprofi-storage-$(date +%F).tar.gz -C /source .'

# Проверка дампа базы
docker compose --env-file .env.production -f docker-compose.production.yml exec -T postgres pg_restore --list < /srv/rk-profi/backups/rkprofi-YYYY-MM-DD.dump
```

Копируйте резервные копии за пределы VDS: в Object Storage, другой сервер или защищённое внешнее хранилище. Регулярно проверяйте восстановление на тестовом сервере.

## 14. Финальная проверка безопасности

```bash
sudo ufw status verbose
sudo ss -lntup
sudo fail2ban-client status sshd
docker compose --env-file .env.production -f docker-compose.production.yml ps
docker compose --env-file .env.production -f docker-compose.production.yml exec web-https nginx -t
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=50 pgadmin
```

Проверьте, что:

- доступны только 22, 80 и 443 TCP (или только 22 и 443 после закрытия HTTP);
- PostgreSQL, Redis и PgAdmin не слушают публичный интерфейс;
- `APP_DEBUG=false`;
- `.env.production`, `deploy/letsencrypt/` и резервные копии не попадают в Git;
- сертификат открывается по всем доменам;
- формы отправляют письмо на `LEAD_NOTIFY_EMAIL`;
- выполнена хотя бы одна проверка восстановления резервной копии.
