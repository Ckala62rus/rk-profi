# Развёртывание Laravel-сайта на VDS Selectel

Инструкция предназначена для чистого VDS с **Ubuntu 24.04 LTS**. Production-стек запускается через Docker Compose: Nginx, Laravel/PHP-FPM, очередь, планировщик, PostgreSQL, Redis и Certbot.

Внешне открыты только SSH, HTTP и HTTPS. PostgreSQL и Redis не публикуют порты и доступны только контейнерам внутри Docker-сети.

> Все команды после раздела «Клонировать проект» выполняются из каталога `backend`. Не запускайте production командой `docker compose up` без `-f docker-compose.production.yml`.

## Как безопасно выполнять production-команды

Во всех примерах ниже используется одна форма Compose-команды:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml ...
```

`--env-file .env.production` передаёт сервисам production-переменные и секреты, а `-f docker-compose.production.yml` выбирает production-стек вместо локального Docker-окружения. Не вставляйте вывод `docker compose ... config` в чаты или тикеты: он может содержать пароли и ключи.

Основные действия Compose:

- `up -d` создаёт и запускает нужные контейнеры в фоне; `--build` перед этим собирает обновлённые образы из исходного кода;
- `ps` показывает состояние контейнеров; `logs --tail=100 SERVICE` выводит последние 100 строк журнала указанного сервиса;
- `exec SERVICE COMMAND` выполняет команду внутри уже работающего контейнера;
- `run --rm SERVICE COMMAND` запускает отдельный одноразовый контейнер и удаляет только его после завершения;
- `down` останавливает и удаляет контейнеры и сеть текущего Compose-проекта, но сохраняет именованные тома PostgreSQL, Redis, загрузок и PgAdmin;
- **никогда не добавляйте `-v` к `down`**: `docker compose down -v` удаляет именованные тома вместе с данными. Также не используйте `docker volume rm` и `docker system prune --volumes` без проверенной резервной копии и отдельного плана восстановления.

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

SSH использует пару связанных ключей:

- **приватный ключ** — секрет; остаётся только на вашем компьютере или в защищённом хранилище Remote Desktop Manager (RDM);
- **публичный ключ** — одна строка, которую разрешено передать серверу и добавить в `authorized_keys`.

Создавайте пару ключей **на своём локальном компьютере**, а не на VDS. Так приватная часть изначально не попадает на сервер и не передаётся по сети. Старый подход — создать ключ на Linux-сервере, а потом забрать приватный файл на Windows — технически работает, но небезопасен: приватный ключ уже находился на сервере и мог остаться в истории, резервной копии или чужом доступе.

#### Windows: создать ключ локально

Откройте PowerShell **на своём компьютере Windows** и выполните:

```powershell
ssh-keygen -t ed25519 -a 100 -f "$HOME\.ssh\rkprofi_deploy" -C "deploy@rkprofi"
Get-Content "$HOME\.ssh\rkprofi_deploy.pub"
```

Первая команда создаёт пару Ed25519-ключей. Параметр `-a 100` усложняет перебор парольной фразы, `-f` задаёт понятное имя файлов, а `-C` добавляет только подпись-комментарий. На запрос `passphrase` задайте надёжную парольную фразу.

После команды в `C:\Users\<ваш-пользователь>\.ssh\` появятся **два файла**:

| Файл | Где хранить | Назначение |
| --- | --- | --- |
| `rkprofi_deploy` | только на вашем компьютере или в защищённом RDM-хранилище | приватный ключ; не копировать на VDS, не отправлять и не показывать |
| `rkprofi_deploy.pub` | на вашем компьютере; его содержимое будет добавлено на VDS | публичный ключ; его можно копировать на сервер |

Вторая команда выводит содержимое публичного файла. Скопируйте всю одну строку, начинающуюся с `ssh-ed25519`.

#### Linux / macOS: создать ключ локально

Откройте терминал **на своём компьютере Linux или macOS** и выполните:

```bash
ssh-keygen -t ed25519 -a 100 -f ~/.ssh/rkprofi_deploy -C "deploy@rkprofi"
cat ~/.ssh/rkprofi_deploy.pub
```

Назначение команд и файлов такое же, как в Windows: `~/.ssh/rkprofi_deploy` остаётся только на локальном компьютере, а строка из `~/.ssh/rkprofi_deploy.pub` копируется на сервер.

#### Что именно копировать на VDS

На сервер через SSH, RDM или буфер обмена передаётся **только текст публичного ключа** из файла `.pub`, например:

```text
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAA... deploy@rkprofi
```

Не передавайте на VDS файл `rkprofi_deploy` и его содержимое. Приватный ключ обычно начинается с `-----BEGIN OPENSSH PRIVATE KEY-----`, а публичный — с `ssh-ed25519`. Если приватный ключ был скопирован на сервер или показан третьим лицам, удалите его оттуда и создайте новую пару ключей.

#### Remote Desktop Manager (RDM)

При создании SSH-сессии в RDM укажите IP VDS в поле «Узел» и `deploy` в поле «Пользователь». На вкладке «Ключ SSH» импортируйте или выберите **локальный приватный** файл `rkprofi_deploy` (без `.pub`). Пароль пользователя сервера оставьте пустым. Запрос RDM на `passphrase` означает, что клиент расшифровывает ваш локальный приватный ключ; это штатная защита и не является запросом пароля пользователя на сервере.

### 2.2 Создать пользователя `deploy` и добавить публичный ключ

Не закрывайте исходный root-сеанс: он останется способом исправить настройку, если вход под `deploy` не получится.

В root-сеансе VDS выполните:

```bash
adduser deploy
usermod -aG sudo deploy
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
nano /home/deploy/.ssh/authorized_keys
```

Команда `adduser` создаёт обычного пользователя, под которым будет выполняться повседневное администрирование. `usermod -aG sudo deploy` разрешает этому пользователю выполнять только необходимые административные команды через `sudo`. `install -d` создаёт каталог `.ssh` с владельцем `deploy` и правами `700`: доступ к нему есть только у владельца. OpenSSH отклоняет ключи при небезопасных правах.

В `authorized_keys` вставьте скопированную строку `ssh-ed25519 ...` из файла `.pub`. В файле должен быть только публичный ключ на одной строке. В `nano` сохраните изменения: `Ctrl+O`, Enter, затем выйдите: `Ctrl+X`.

Задайте владельца и ограниченные права файла, затем проверьте его формат:

```bash
chown deploy:deploy /home/deploy/.ssh/authorized_keys
chmod 600 /home/deploy/.ssh/authorized_keys
awk 'NR == 1 { print $1 }' /home/deploy/.ssh/authorized_keys
stat -c '%U:%G %a %n' /home/deploy /home/deploy/.ssh /home/deploy/.ssh/authorized_keys
```

`chown` назначает владельцем пользователя `deploy`, а `chmod 600` оставляет чтение и запись только владельцу. `awk` должен вывести `ssh-ed25519`, а не `-----BEGIN`. `stat` должен показать права `700` для `.ssh` и `600` для `authorized_keys`.

Подключитесь под `deploy` из **второго** терминала или RDM-сессии. При использовании терминала можно явно указать приватный ключ:

```bash
ssh -i ~/.ssh/rkprofi_deploy -o IdentitiesOnly=yes deploy@SERVER_IP
```

Команда использует приватный ключ только с локального компьютера. `IdentitiesOnly=yes` запрещает клиенту перебирать другие ключи и упрощает диагностику. В RDM используйте тот же приватный ключ в настройках SSH-сессии.

Если клиент запрашивает парольную фразу ключа — введите `passphrase`, заданную при создании ключа. Если он запрашивает пароль пользователя `deploy`, сервер не принял ключ. В root-сеансе запустите `tail -f /var/log/auth.log`, повторите попытку входа и проверьте сообщения: отсутствие строк `publickey` означает, что RDM не предлагает ключ; `Failed publickey` обычно означает несовпадающую публичную часть; сообщение о `bad ownership or modes` означает неверные права.

Только после успешного входа по ключу под `deploy` можно закрыть исходный root-сеанс.

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

### Как выполнять команды от root после отключения root-входа

После применения `PermitRootLogin no` новый прямой вход `root@SERVER_IP` по SSH намеренно не работает. Это снижает риск атаки на наиболее привилегированную учётную запись. Для обычной работы всегда подключайтесь по ключу как `deploy`, а права root получайте только на время административной задачи:

```bash
sudo -i
```

`sudo` запросит пароль **пользователя `deploy`**, заданный командой `adduser deploy`. Это не пароль для SSH: SSH продолжает принимать только ключ. Опция `-i` запускает root-оболочку с окружением root; приглашение терминала изменится на `root@...#`. В этой оболочке выполняйте только команды, которым действительно нужны административные права.

Чтобы немедленно вернуться к обычному пользователю `deploy`, выполните:

```bash
exit
```

Не включайте `PermitRootLogin yes` ради удобства. Если доступ к `deploy` когда-либо утрачен, используйте консоль VDS в панели Selectel для восстановления, а не открывайте прямой root-вход по SSH.

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
| `APP_URL`, `FRONTEND_URL` | базовый URL Laravel: на первом шаге `http://example.com` |
| `SANCTUM_STATEFUL_DOMAINS` | оба домена через запятую и без протоколов: `example.com,xn--e1afmkfd.xn--p1ai` |
| `DB_PASSWORD` | длинный уникальный случайный пароль |
| `PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD` | учётная запись для локального PgAdmin; пароль должен отличаться от пароля БД |
| `MAIL_*` | SMTP-данные провайдера почты |
| `LEAD_NOTIFY_EMAIL` | адрес получателя заявок |
| `TURNSTILE_*` | ключи Turnstile или пустые значения |

`APP_URL` и `FRONTEND_URL` содержат основной URL Laravel — в примере англоязычный. Они не задают междоменный редирект: оба домена обслуживают один и тот же сайт. Если домены должны показывать разные языковые версии, этого недостаточно: потребуется отдельная логика определения языка и URL в приложении.

Создайте пароль БД:

```bash
openssl rand -base64 32
```

Пароль задаётся **один раз** в `DB_PASSWORD`; Compose передаёт его PostgreSQL и Laravel без дублирующих `POSTGRES_*` переменных.

#### Создать и сохранить `APP_KEY`

`APP_KEY` — секрет Laravel, которым шифруются cookies, сессии и другие данные приложения. Его генерируют один раз для production и не меняют без необходимости: после смены ключа существующие сессии и зашифрованные данные перестанут читаться.

Выполните команду:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml run --rm --no-deps --build app php artisan key:generate --show
```

Параметр `--show` **только выводит** новый ключ в терминал; `.env.production` команда не изменяет. Успешный результат заканчивается строкой формата:

```text
base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=
```

Скопируйте эту строку целиком, откройте production-файл и вручную замените значение `APP_KEY`:

```bash
nano .env.production
```

Было:

```dotenv
APP_KEY=base64:REPLACE_WITH_OUTPUT_OF_ARTISAN_KEY_GENERATE
```

Должно стать:

```dotenv
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=
```

Сохраните файл (`Ctrl+O`, Enter, `Ctrl+X`). Не добавляйте `.env.production` в Git и не публикуйте фактическое значение `APP_KEY`. Если команда не вывела строку `base64:...`, не переходите к следующему шагу: сохраните полный текст ошибки из терминала, но не присылайте секреты.

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

Назначение команд:

- `up -d --build` собирает образы и запускает сервисы в фоне. Одноразовый сервис `migrate` применяет миграции, создаёт отсутствующие CMS-страницы (главную, «О компании», «Услуги», «Контакты» и политику конфиденциальности) без изменения существующего контента, затем подготавливает кэш Laravel;
- `ps` показывает состояние. У `migrate` ожидается `Exited (0)`: он завершился штатно после одноразовой задачи;
- `logs --tail=100 migrate` показывает последние 100 строк именно инициализации БД и кэша;
- `curl -I http://127.0.0.1/up` запрашивает health endpoint на самом VDS. Он должен вернуть `HTTP/1.1 200 OK`; эта проверка не зависит от DNS домена.

После успешной проверки сайт должен открываться по HTTP на каждом из двух доменов.

При ошибке сначала посмотрите логи:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 app web-http migrate
```

`logs` ничего не меняет: команда выводит последние 100 строк журналов PHP-приложения, HTTP-Nginx и одноразовой миграции.

### 7.1 Первый вход в админку и управление паролем

**Как попасть в админку:** после включения HTTPS откройте `https://rkprofi.ru/admin/login`, введите email и пароль созданного ниже администратора. Учётных данных по умолчанию нет.

Production не запускает `DatabaseSeeder` и намеренно не создаёт учётную запись с известным паролем. Не используйте `php artisan db:seed` или `migrate:fresh` для создания администратора: первый может перезаписать стартовый CMS-контент, второй удалит таблицы базы данных.

Создайте первого администратора после успешного старта `app`. Замените `admin@example.com` и `Имя администратора` на свои значения. Команда не показывает вводимый пароль и не помещает его в shell history:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan admin:user create admin@example.com --name="Имя администратора"
```

`exec app` запускает Artisan внутри уже работающего PHP-контейнера. `admin:user create` создаёт только одного пользователя с указанным email; если такой email уже существует, команда завершается без изменений. Она запросит подтверждение, затем пароль дважды. Ввод пароля в терминале не отображается — это нормально. Пароль должен состоять минимум из 16 символов и включать строчные и прописные **латинские** буквы, цифру и обычный ASCII-символ, например `!`, `@`, `#`, `$`, `%`, `&` или `*`. Если показаны `validation.min.string` или `validation.password.symbols`, пользователь не создан: повторите команду с более длинным паролем и одним из этих символов. Сохраните пароль в корпоративном менеджере паролей.

Учётную запись можно создать уже на HTTP-этапе, но **не входите в админку до завершения раздела 8.2**: в этот момент HTTPS ещё не включён, а `SESSION_SECURE_COOKIE=true` намеренно не даёт браузеру передавать сессионную cookie по HTTP.

После успешного включения HTTPS откройте:

```text
https://rkprofi.ru/admin/login
```

Войдите с созданным email и паролем. Оба домена обслуживают тот же сайт, но для постоянной закладки выберите один основной адрес. На общем компьютере используйте кнопку выхода после работы.

Если пароль утрачен или есть подозрение на компрометацию, выполните на VDS:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan admin:user reset admin@example.com
```

`admin:user reset` находит только существующего пользователя, запрашивает новый пароль скрытым вводом и отзывает **все** его активные Sanctum API-токены. После этого потребуется войти заново на всех устройствах. Команда изменяет только пароль и токены выбранного пользователя; она не удаляет сайт, загрузки, контейнеры или Docker-тома.

## 8. Выпустить сертификат Let’s Encrypt и включить HTTPS

Перед командой убедитесь, что **каждый** домен из параметров `-d` уже указывает на этот VDS и что порт 80 доступен извне. Открытие сайта по IP не подтверждает готовность: Let’s Encrypt обращается к адресам доменов, а не к IP, введённому вручную.

Проверьте DNS с VDS. Обе команды должны вернуть публичный IPv4 именно этого VDS:

```bash
getent ahostsv4 rkprofi.ru
getent ahostsv4 xn--h1admddc3a.xn--p1ai
```

### 8.1 Выпуск сертификата

Выпускается один сертификат для обоих имён сайта: `rkprofi.ru` и `ркпрофи.рф`. Для кириллического домена Let’s Encrypt принимает только Punycode-форму `xn--h1admddc3a.xn--p1ai`.

Выполните команду из каталога `backend`:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot certonly --webroot --webroot-path /var/www/certbot --email "$(sed -n 's/^LETSENCRYPT_EMAIL=//p' .env.production)" --agree-tos --no-eff-email --cert-name rkprofi.ru -d rkprofi.ru -d xn--h1admddc3a.xn--p1ai
```

Подстановка `$(sed ...)` читает почтовый адрес из строки `LETSENCRYPT_EMAIL` файла `.env.production` и передаёт его Certbot для уведомлений о сертификате. Не нужно публиковать или вставлять адрес в команду вручную. `--cert-name rkprofi.ru` задаёт имя каталога сертификата; оно уже соответствует `LETSENCRYPT_CERT_NAME=rkprofi.ru` в `.env.production`.

Если нужен `www` у любого домена, добавьте его отдельным параметром `-d` и предварительно создайте DNS-запись. Не выпускайте сертификат для имён, которые не направлены на этот VDS.

Certbot создаст сертификат в `deploy/letsencrypt/`. Эта папка исключена из Git; не копируйте и не редактируйте ключи вручную.

### 8.2 Включение HTTPS и перенаправления с HTTP

Откройте `.env.production` и измените следующие значения:

```dotenv
COMPOSE_PROFILES=https
APP_URL=https://rkprofi.ru
FRONTEND_URL=https://rkprofi.ru
# После включения HTTPS отправляйте сессионные cookie только по защищённому соединению.
SESSION_SECURE_COOKIE=true
```

Переключите Nginx. Не используйте `-v`: этот параметр удалит тома базы данных и загруженных файлов.

```bash
docker compose --env-file .env.production -f docker-compose.production.yml down
docker compose --env-file .env.production -f docker-compose.production.yml config
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
docker compose --env-file .env.production -f docker-compose.production.yml exec web-https nginx -t
curl -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' http://127.0.0.1/up -H 'Host: rkprofi.ru'
curl -k -sS -o /dev/null -w '%{http_code}\n' https://127.0.0.1/up -H 'Host: rkprofi.ru'
```

Назначение команд:

- `down` кратковременно останавливает стек и удаляет только его контейнеры и сеть; именованные тома с PostgreSQL, Redis и загрузками остаются на сервере;
- `config` проверяет подстановку переменных и выбранный профиль до запуска; не публикуйте его полный вывод, потому что в нём могут быть секреты;
- `up -d --build` собирает актуальные образы и поднимает HTTPS-Nginx вместе с остальными сервисами в фоне;
- `exec web-https nginx -t` проверяет синтаксис фактически загруженной конфигурации Nginx;
- первый `curl` должен вывести `301 https://rkprofi.ru/up`, второй — `200`; они проверяют HTTP-перенаправление и HTTPS-ответ локально без вывода cookie.

После этого сайт доступен по HTTPS на обоих доменах. Любой HTTP-запрос, кроме пути ACME-проверки, перенаправляется на HTTPS с тем же доменным именем. Проверить сертификат с внешнего компьютера можно так:

```bash
curl -I https://rkprofi.ru
curl -I https://xn--h1admddc3a.xn--p1ai
```

### 8.3 Аварийный переход с HTTPS на HTTP при проблеме с сертификатом

Используйте этот режим только если `web-https` не запускается или не обслуживает сайт из-за отсутствующего, повреждённого либо недоступного сертификата. При HTTP трафик, включая вход в админку, передаётся без шифрования. Не входите в админку и не меняйте пароли через HTTP без крайней необходимости. Учтите, что браузер с ранее сохранённой HSTS-политикой может вообще не позволить открыть HTTP-версию.

Перед переключением убедитесь, что порт 80 снова доступен извне. Если ранее вы закрывали его по разделу 10, в `.env.production` должно быть `HTTP_PORT=80`, а правило UFW нужно вернуть:

```bash
sudo ufw allow 80/tcp comment 'HTTP / Lets Encrypt'
```

Команда открывает только TCP-порт 80 в UFW. Она нужна для временного HTTP-доступа и для проверки Let’s Encrypt по HTTP-01; не открывает PostgreSQL, Redis или PgAdmin.

Откройте `.env.production` и временно задайте:

```dotenv
COMPOSE_PROFILES=http
HTTP_PORT=80
APP_URL=http://rkprofi.ru
FRONTEND_URL=http://rkprofi.ru
SESSION_SECURE_COOKIE=false
```

`COMPOSE_PROFILES=http` выбирает контейнер `web-http`, который не читает TLS-ключи. `SESSION_SECURE_COOKIE=false` необходимо только на время HTTP: иначе браузер не отправит Laravel session-cookie. Не меняйте `SERVER_NAME`, `SANCTUM_STATEFUL_DOMAINS`, пароли или `APP_KEY`.

Затем переключите контейнеры без удаления томов:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml config
docker compose --env-file .env.production -f docker-compose.production.yml down
docker compose --env-file .env.production -f docker-compose.production.yml up -d
docker compose --env-file .env.production -f docker-compose.production.yml ps
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100 migrate web-http
curl -sS -o /dev/null -w '%{http_code}\n' http://127.0.0.1/up -H 'Host: rkprofi.ru'
```

`config` проверяет, что выбран HTTP-профиль; `down` останавливает контейнеры, но сохраняет именованные тома; `up -d` запускает их в фоне с `web-http`; `ps` показывает состояние; `logs` должен показать у `migrate` статус `Exited (0)`, а `curl` — `200`. **Не добавляйте `-v` к `down`**. Не выполняйте в аварийном режиме `git pull` или обновление образов: сначала восстановите работоспособность, затем обновляйте проект отдельным шагом.

Проверьте HTTP с внешнего компьютера на обоих именах:

```bash
curl -I http://rkprofi.ru
curl -I http://xn--h1admddc3a.xn--p1ai
```

После восстановления сертификата не оставляйте сайт в HTTP. Если каталог сертификата существует, сначала выполните реальное продление:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot renew
```

`run --rm` запускает одноразовый Certbot и удаляет только его контейнер после завершения; `renew` обновляет лишь сертификаты, которым уже требуется продление. Если сертификат или его каталог отсутствует, повторите команду `certbot certonly` из раздела 8.1 с теми же доменами.

Когда Certbot подтвердит наличие сертификата, верните в `.env.production` значения:

```dotenv
COMPOSE_PROFILES=https
HTTP_PORT=80
APP_URL=https://rkprofi.ru
FRONTEND_URL=https://rkprofi.ru
SESSION_SECURE_COOKIE=true
```

И вернитесь к HTTPS безопасной последовательностью:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml config
docker compose --env-file .env.production -f docker-compose.production.yml down
docker compose --env-file .env.production -f docker-compose.production.yml up -d
docker compose --env-file .env.production -f docker-compose.production.yml exec web-https nginx -t
curl -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' http://127.0.0.1/up -H 'Host: rkprofi.ru'
curl -k -sS -o /dev/null -w '%{http_code}\n' https://127.0.0.1/up -H 'Host: rkprofi.ru'
```

Команды снова выбирают TLS-контейнер, проверяют его конфигурацию и ожидают `301` для HTTP и `200` для HTTPS. Если во время аварийного HTTP-режима выполнялся вход в админку, сразу после возврата на HTTPS сбросьте пароль администратора командой из раздела 7.1: она отзовёт все старые токены.

## 9. Автопродление сертификата

Let’s Encrypt использует HTTP-01, поэтому во время продления TCP-порт 80 должен быть доступен из интернета. Сначала выполните безопасную проверку:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot renew --dry-run
```

`renew --dry-run` запрашивает тестовое продление и не изменяет боевой сертификат. `run --rm` удаляет только одноразовый Certbot-контейнер после завершения. Переходите к cron только если вывод содержит сообщение об успешных simulated renewals.

Откройте root-crontab:

```bash
sudo crontab -e
```

`sudo crontab -e` открывает расписание задач root. Это требуется, чтобы cron мог записывать в `/var/log/`; редактируйте только строку этого проекта.

Добавьте строку, заменив путь, если проект находится не в `/srv/rk-profi/backend`:

```cron
17 3,15 * * * cd /srv/rk-profi/backend && /usr/bin/docker compose --env-file .env.production -f docker-compose.production.yml --profile certbot run --rm certbot && /usr/bin/docker compose --env-file .env.production -f docker-compose.production.yml exec -T web-https nginx -s reload >> /var/log/rkprofi-certbot.log 2>&1
```

Задача запускается дважды в сутки. Certbot продлевает сертификат только когда это необходимо, затем `nginx -s reload` перечитывает сертификат без остановки сайта. Эта строка предполагает, что запущен `web-https`; во время аварийного HTTP-режима из раздела 8.3 перезагрузка HTTPS-Nginx завершится ошибкой. Сначала восстановите сертификат и вернитесь к HTTPS, затем cron снова будет работать штатно.

Проверьте путь Docker командой `command -v docker`; она выводит абсолютный путь к исполняемому файлу. Если это не `/usr/bin/docker`, подставьте фактический путь в cron.

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

`up -d` пересоздаёт только изменившиеся контейнеры, а `ps` подтверждает опубликованные порты. После этого порт 80 будет привязан только к `127.0.0.1` и перестанет быть доступным из интернета; HTTPS на 443 продолжит работать. Не рассчитывайте для этого на одно лишь правило `ufw deny 80`, потому что Docker публикует порты через собственные правила NAT.

После закрытия порта 80 **автопродление HTTP-01 перестанет работать**. При проблеме с сертификатом сначала верните `HTTP_PORT=80` и правило UFW из раздела 8.3. До закрытия выберите один из вариантов:

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
# Состояние и непрерывный вывод журналов
docker compose --env-file .env.production -f docker-compose.production.yml ps
docker compose --env-file .env.production -f docker-compose.production.yml logs -f app queue scheduler web-https pgadmin

# Сведения о версии Laravel, maintenance mode и его отключение
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan about
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan down
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan up

# Обновление: сначала резервная копия, затем получение fast-forward изменений,
# пересборка/перезапуск сервисов и просмотр инициализации
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
