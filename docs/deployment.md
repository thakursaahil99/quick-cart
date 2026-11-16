# Deploying QuickCart to production (Linux VPS)

The Windows `devstack` is for development only. A production QuickCart runs the standard
Magento 2.4.9 stack on Linux. This guide targets **Ubuntu 24.04 LTS** on one VPS (4 vCPU,
8 GB RAM minimum, SSD).

```
                 ┌──────────── HTTPS (Let's Encrypt) ────────────┐
Browser ──► Nginx :443 ──► Varnish :6081 (optional FPC) ──► Nginx :8080 ──► PHP-FPM 8.4
                                                                 │
                       ┌──────────────────┬──────────────────────┼──────────────────┐
                       ▼                  ▼                      ▼                  ▼
                  MySQL 8.4          OpenSearch            Valkey / Redis        cron (bin/magento cron:run)
                                     (catalog search)      cache, page cache,    indexers, emails, reports,
                                                           sessions              QuickCart recommendations
```

## 1. Packages

```bash
sudo apt update && sudo apt install -y nginx mysql-server-8.4 valkey-server unzip git \
  php8.4-fpm php8.4-cli php8.4-bcmath php8.4-curl php8.4-gd php8.4-intl php8.4-mbstring \
  php8.4-mysql php8.4-soap php8.4-xml php8.4-xsl php8.4-zip php8.4-sockets php8.4-opcache
# OpenSearch: follow https://opensearch.org/docs/latest/install-and-configure/install-opensearch/debian/
# Composer 2: https://getcomposer.org/download/
```

Recommended PHP settings (`/etc/php/8.4/fpm/conf.d/99-magento.ini`):

```ini
memory_limit = 2G
max_execution_time = 1800
realpath_cache_size = 10M
realpath_cache_ttl = 7200
opcache.enable = 1
opcache.memory_consumption = 512
opcache.max_accelerated_files = 130000
opcache.validate_timestamps = 0   ; reload php-fpm after each deploy
opcache.save_comments = 1
```

MySQL: set `innodb_buffer_pool_size` to about 50 % of RAM, and keep the `sales` and catalog
tables on SSD.

## 2. Code and dependencies

```bash
sudo mkdir -p /var/www/quickcart && sudo chown $USER:www-data /var/www/quickcart
git clone https://github.com/thakursaahil99/quick-cart.git /var/www/quickcart
cd /var/www/quickcart/src
composer install --no-dev --optimize-autoloader
```

Packages come from the Mage-OS mirror configured in `src/composer.json`. To use Adobe's
repository instead, see the README ("Using Adobe's Composer repository").

## 3. Install

```bash
bin/magento setup:install \
  --base-url=https://shop.example.com/ --base-url-secure=https://shop.example.com/ \
  --use-secure=1 --use-secure-admin=1 \
  --db-host=127.0.0.1 --db-name=quickcart --db-user=quickcart --db-password='***' \
  --search-engine=opensearch --opensearch-host=127.0.0.1 --opensearch-port=9200 \
  --cache-backend=redis --cache-backend-redis-server=127.0.0.1 --cache-backend-redis-db=0 \
  --page-cache=redis --page-cache-redis-server=127.0.0.1 --page-cache-redis-db=1 \
  --session-save=redis --session-save-redis-host=127.0.0.1 --session-save-redis-db=2 \
  --backend-frontname=<unique-admin-path> \
  --admin-firstname=Store --admin-lastname=Owner --admin-email=owner@example.com \
  --admin-user=<admin-user> --admin-password='***' \
  --language=en_US --currency=INR --timezone=Asia/Kolkata
```

Valkey is a drop-in replacement for Redis; Magento's Redis backend works unchanged.
The QuickCart data patches run during install and create the demo catalog. For a real
catalog, disable the demo module before going live:
`bin/magento module:disable QuickCart_DemoData` (data it already installed stays).

Generate a demo order history only on demo or staging stores:
`bin/magento quickcart:demo:orders --count=150 --days=120`.

## 4. Production mode

```bash
bin/magento deploy:mode:set production --skip-compilation
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f en_US --jobs=4
bin/magento indexer:set-mode schedule
bin/magento cache:enable
bin/magento cache:flush
```

Recommended settings:

```bash
bin/magento config:set dev/js/minify_files 1
bin/magento config:set dev/css/minify_files 1
bin/magento config:set dev/template/minify_html 1
bin/magento config:set dev/static/sign 1
bin/magento config:set catalog/frontend/flat_catalog_product 0
```

Leave JS bundling off when Nginx serves HTTP/2. Magento's built-in bundler makes pages
heavier.

## 5. Cron

```bash
sudo -u www-data bin/magento cron:install
```

This runs indexers (in "update by schedule" mode), emails, sales report aggregation (the
best sellers rail) and `quickcart_recommendations_rebuild` (02:30 daily). Check that it runs:
`bin/magento cron:run && tail var/log/cron.log`.

## 6. Nginx and TLS

Use Magento's own rules (`src/nginx.conf.sample`) in the PHP-FPM upstream server block, then
add TLS with `certbot --nginx -d shop.example.com`. In front of it:

- Enable `http2`, gzip (or brotli) and long cache headers for `/static/` and `/media/`.
  The sample config already sets the cache headers.
- Restrict the admin path to known IPs (`location /<unique-admin-path> { allow 203.0.113.0/24; deny all; }`).
- Keep `/setup` closed.

### Varnish (optional, faster full-page cache)

```bash
sudo apt install -y varnish
bin/magento config:set system/full_page_cache/caching_application 2
bin/magento varnish:vcl:generate --export-version=6 > /etc/varnish/default.vcl
```

QuickCart is FPC-safe. Recently viewed products come from localStorage plus an AJAX render.
The tracking page and customer data are private or non-cacheable. Label and recommendation
changes clean the right cache tags.

## 7. Security checklist

- [ ] **Two-factor authentication on** for the admin (`Magento_TwoFactorAuth`; it is disabled
      only in the local devstack).
- [ ] Admin URL secret keys **on** (default), a unique admin path, and an admin IP allow-list.
- [ ] HTTPS everywhere (`web/secure/use_in_frontend` and `use_in_adminhtml` = 1) and HSTS.
- [ ] `app/etc/env.php` mode 640, owned by the deploy user, group `www-data`, never in git.
- [ ] Files 644 and directories 755; only `var/`, `generated/`, `pub/static/` and `pub/media/`
      are writable by PHP.
- [ ] Database user with privileges on the Magento schema only; MySQL, OpenSearch and Valkey
      bound to 127.0.0.1.
- [ ] Automatic security updates (`unattended-upgrades`) and fail2ban for SSH.
- [ ] Nightly database and `pub/media` backups, copied off the server.
- [ ] Apply Magento security patches promptly (`composer update magento/*` on a staging
      copy first).

## 8. Deploying updates

```bash
cd /var/www/quickcart
bin/magento maintenance:enable
git pull
cd src && composer install --no-dev --optimize-autoloader
bin/magento setup:upgrade --keep-generated
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f en_US --jobs=4
bin/magento cache:flush
sudo systemctl reload php8.4-fpm
bin/magento maintenance:disable
```

Then run the smoke test against the live store:

```bash
QC_BASE_URL=https://shop.example.com QC_HOST=shop.example.com node tools/qa/api-smoke.mjs
```
