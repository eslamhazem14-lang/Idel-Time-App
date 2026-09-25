# Production deployment (Linux VPS, Nginx or Apache, PHP-FPM, MySQL)

## 1. Server requirements

* PHP **8.2+** with extensions: `bcmath` (optional), `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `gd` (optional)
* MySQL 8.0+ or MariaDB 10.6+ (utf8mb4)
* Composer 2, Node.js 20+ (build step only — can run in CI)
* Nginx or Apache, PHP-FPM, Supervisor (queue worker), cron

## 2. Database

```sql
CREATE DATABASE idle_time CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'idle'@'localhost' IDENTIFIED BY 'a-long-random-password';
GRANT ALL PRIVILEGES ON idle_time.* TO 'idle'@'localhost';
```

## 3. Application

```bash
cd /var/www
git clone <repo> idletime && cd idletime
composer install --no-dev --optimize-autoloader
npm ci && npm run build            # or build in CI and upload public/build
cp .env.example .env               # fill DB_*, APP_URL (https), MAIL_*, SESSION_* …
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force      # default task categories (no demo users)
php artisan platform:create-admin --name="Your Name" --email=you@company.com   # prompts for a password (min 12 chars)
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Do **not** run `db:seed` in production — it creates demo users.

Key `.env` values for production: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `QUEUE_CONNECTION=database` (or `redis`), `TRUSTED_PROXIES` if behind a load balancer/Cloudflare, real `MAIL_*` SMTP credentials.

## 4. Web server

The document root must be **`public/`** — never the project root. Attachments live in `storage/app/private` and are never web-accessible.

### Nginx

```nginx
server {
    listen 80;
    server_name idletime.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name idletime.example.com;
    root /var/www/idletime/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/idletime.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/idletime.example.com/privkey.pem;

    client_max_body_size 30M;          # attachments: 5 files × 5 MB
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.svg { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(?:css|js|woff2?|svg|png|jpg|webp)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

### Apache

Enable `mod_rewrite` and point the vhost at `public/` (the shipped `public/.htaccess` handles routing):

```apache
<VirtualHost *:443>
    ServerName idletime.example.com
    DocumentRoot /var/www/idletime/public
    <Directory /var/www/idletime/public>
        AllowOverride All
        Require all granted
    </Directory>
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/idletime.example.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/idletime.example.com/privkey.pem
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

PHP settings: `upload_max_filesize = 8M`, `post_max_size = 32M`, `expose_php = Off`.

## 5. Scheduler (cron)

Claim expiry (every minute), expiry warnings, task deadlines, auto-approval, nightly wallet reconciliation and token pruning:

```cron
* * * * * cd /var/www/idletime && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Queue worker (Supervisor)

Notifications and emails are queued.

```ini
[program:idletime-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/idletime/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
numprocs=2
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/www/idletime/storage/logs/worker.log
```

`supervisorctl reread && supervisorctl update && supervisorctl start idletime-worker:*`

## 7. Deploying updates

```bash
php artisan down --render="errors::503"   # optional
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize        # config, route, view and event caches
php artisan queue:restart
php artisan up
```

## 8. Admin setup checklist

1. `php artisan platform:create-admin` and sign in at `/login` → you land on `/admin`.
2. **Admin → Settings**: commission %, min/max reward, task duration limits, claim multiplier / minimum / grace period, expired-claim policy, auto-approval days, minimum withdrawal/deposit, enabled withdrawal methods, email verification, maintenance mode.
3. **Admin → Categories**: review the default categories created by `CategorySeeder`, add or deactivate as needed.
4. **Admin → Templates**: add task templates requesters can start from.
5. Decide your manual payment process: requesters report deposits in **Billing** (reference `IDLE-D000123`); confirm them in **Admin → Deposits** once money arrives. Pay developers from **Admin → Withdrawals** (Processing → Paid, or Reject which refunds automatically).
6. Watch the daily queues on the Overview page: tasks to approve, pending submissions, withdrawals, disputes, fraud signals.

## 9. Operations

* **Backups**: nightly `mysqldump --single-transaction idle_time` + `storage/app/private` (attachments).
* **Ledger integrity**: `php artisan wallet:reconcile` (also scheduled at 03:15; mismatches are logged as `critical`).
* **Logs**: `storage/logs/laravel-*.log` (daily). Configure log shipping/alerting on `critical`.
* **Health check**: `GET /up`.
