# QuickCart — Magento 2 eCommerce Platform

> **Shop Smarter. Shop Faster.**

QuickCart is a multi-category electronics store built on **Magento Open Source 2.4.9**.
It is a portfolio project that shows real-world Magento development: custom modules
built on service contracts, a custom Luma-based theme, declarative schema, REST APIs,
admin tooling and performance tuning. It changes no Magento core files.

| Status | Phase |
| :----: | ----- |
| ✅ | **Phase 1** — Magento installation & native Windows dev stack |
| ⏳ | Phase 2 — QuickCart branding & responsive theme |
| ⏳ | Phase 3 — Homepage & catalog customization |
| ⏳ | Phase 4 — Product & checkout enhancements |
| ⏳ | Phase 5 — Custom modules (catalog, recommendations, order tracking, checkout) |
| ⏳ | Phase 6 — REST API (`QuickCart_CustomApi`) |
| ⏳ | Phase 7 — Admin sales dashboard, grids, ACL |
| ⏳ | Phase 8 — Testing, optimization & final documentation |

---

## Technology stack

| Layer | Version | Notes |
| ----- | ------- | ----- |
| Magento Open Source | 2.4.9 | Composer project (`magento/project-community-edition`) |
| PHP | 8.4 (NTS x64) | 2.4.9 supports 8.3 – 8.5 |
| Composer | 2.11 | |
| MySQL | 8.4 LTS | |
| OpenSearch | 3.9 | Catalog search engine required by 2.4.9 |
| Nginx | 1.30 | Uses Magento's own `nginx.conf.sample` rules |
| Frontend | LESS, Knockout.js, RequireJS, jQuery UI widgets | Magento Luma stack |

**Cache/session store:** local development uses Magento's file cache and its built-in
full-page cache, because Redis/Valkey publish no native Windows builds. On Linux,
deployments use Valkey/Redis for cache, page cache and sessions (Phase 8 documents
this).

---

## Repository layout

```
.
├── devstack/                   Native Windows development stack (no Docker, no admin rights)
│   ├── qc.cmd / qc.ps1         Developer CLI: install, start/stop, magento, composer, mysql
│   ├── .env.example            Versions, ports, credentials template (.env is git-ignored)
│   ├── lib/                    Stack + Magento installer logic, php-cgi supervisor
│   └── templates/              php.ini, my.ini, opensearch.yml, nginx.conf templates
├── src/                        Magento project root (only project-owned files are committed)
│   ├── composer.json / .lock
│   ├── app/code/QuickCart/     Custom modules
│   ├── app/design/frontend/    QuickCart theme (Phase 2)
│   └── app/etc/config.php      Module list / shared deployment config
└── docs/                       Phase notes, API docs, screenshots
```

`src/vendor`, `generated`, `var`, `pub/static`, `pub/media`, `app/etc/env.php` and every
file that Composer deploys from `magento/magento2-base` are **not committed**: running
`composer install` recreates them. See [.gitignore](.gitignore).

### Runtime architecture (local)

```
Browser ──► Nginx :8080 ──FastCGI──► php-cgi ×3 (:9071-9073, supervised) ──► Magento 2.4.9
                                                                   │
                                          ┌────────────────────────┼─────────────────────┐
                                          ▼                        ▼                     ▼
                                   MySQL 8.4 :3307        OpenSearch 3.9 :9200    var/ file cache
```

All binaries, data and logs live in `STACK_DIR` (default `E:/quickcart-stack`), so the
stack doesn't touch other PHP/MySQL installs on the machine, such as XAMPP.

---

## Custom modules

| Module | Phase | Purpose |
| ------ | ----- | ------- |
| `QuickCart_WindowsCompat` | 1 ✅ | Native-Windows adapters with no core edits: separator-safe template validation (DI preference), GD2 image adapter that accepts drive-letter paths (DI preference), Copy instead of Symlink static-asset materialization (plugin). No-op on Linux. Unit-tested. |
| `QuickCart_CatalogEnhancement` | 5 | Custom product attributes, product labels, featured products |
| `QuickCart_ProductRecommendation` | 5 | Frequently bought together, recently viewed, related recommendations |
| `QuickCart_OrderTracking` | 5 | Shipment tracking, custom status display, customer tracking page |
| `QuickCart_CheckoutEnhancement` | 4/5 | Checkout UI improvements, custom fields, extra validation |
| `QuickCart_SalesDashboard` | 7 | Sales analytics, revenue and best-seller reports |
| `QuickCart_CustomApi` | 6 | REST endpoints for products, categories, orders, customers |

---

## Installation (Windows 10/11, native)

**Requirements:** Windows 10/11 x64, Git, about 6 GB free disk for `STACK_DIR`, 8 GB+ RAM, and the
Microsoft Visual C++ 2015-2022 x64 runtime (needed by PHP).

```bat
git clone <repo-url> quickcart
cd quickcart

:: 1. Download + configure PHP, Composer, MySQL, OpenSearch, Nginx into STACK_DIR
devstack\qc install-stack

:: 2. composer install + bin/magento setup:install + developer configuration
devstack\qc install-magento
```

The first run of `install-stack` creates `devstack/.env` and fills every empty
`*_PASSWORD` with a random secret. Keep this file local; it is git-ignored.

| URL | |
| --- | --- |
| Storefront | http://quickcart.localhost:8080/ |
| Admin | http://quickcart.localhost:8080/qc_admin — user `qcadmin`, password = `ADMIN_PASSWORD` in `devstack/.env` |

`*.localhost` resolves to `127.0.0.1` in Chrome, Edge and Firefox, so you don't need to
edit the hosts file.

### Daily use

```bat
devstack\qc start                  :: MySQL, OpenSearch, PHP-CGI workers, Nginx
devstack\qc status                 :: service health + storefront/admin HTTP codes
devstack\qc stop

devstack\qc magento cache:flush    :: any bin/magento command
devstack\qc magento setup:upgrade
devstack\qc composer show magento/framework
devstack\qc mysql                  :: MySQL client on the quickcart database
```

### Database setup

`install-stack` initializes MySQL 8.4 with its own data directory (`STACK_DIR/data/mysql`)
and creates:

* database `quickcart` (`utf8mb4`),
* user `quickcart@127.0.0.1` with privileges on `quickcart.*` only, which Magento uses
  through `app/etc/env.php`,
* a root password used only by the CLI (`STACK_DIR/conf/mysql-root.cnf`).

Magento then creates its schema with `setup:install`. Custom tables are always added
with declarative schema (`etc/db_schema.xml`) plus data/schema patches, never through
direct changes to core tables.

### Using Adobe's Composer repository instead of the mirror

By default, packages come from `https://mirror.mage-os.org/`, a public mirror of the
Magento Open Source packages that needs no keys. To use Adobe's repository instead:
set `MAGENTO_COMPOSER_REPO=https://repo.magento.com/` in `devstack/.env`, run
`devstack\qc composer config repositories.magento composer https://repo.magento.com/`,
and put your Commerce Marketplace keys in `src/auth.json` (git-ignored).

### Local-only settings

`install-magento` applies these for development only. Revert them in any shared or
production environment:

* Admin 2FA modules (`Magento_TwoFactorAuth`, `Magento_AdminAdobeImsTwoFactorAuth`) are
  disabled, because 2FA needs a mail server or authenticator setup.
* Email sending is off (`system/smtp/disable = 1`), because there is no local mail server.
* OpenSearch runs on loopback with its security plugin disabled.
* Deploy mode is `developer`.

---

## Screenshots

Screenshots will be added with the QuickCart theme in Phase 2.

## API documentation

REST endpoints and a Postman collection will be documented here in Phase 6.

## License

Proprietary. Portfolio project. Magento Open Source is licensed under OSL-3.0 / AFL-3.0.
