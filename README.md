# Tiba Quip standalone ecommerce

Store: `http://localhost/tiba_quip/`  
Admin: `http://localhost/tiba_quip/admin/`

## Requirements and configuration

PHP 8.1+ with mysqli/mysqlnd, mbstring, DOM, fileinfo and sessions; MySQL 8 or compatible MariaDB; Apache with `.htaccess` enabled. The storefront retains Medion/Bootstrap/jQuery styles and responsive components. Administration retains its Remos styles, with ApexCharts and a locally bundled TinyMCE editor (license in `admin/js/tinymce/`).

The native database is `tiba_new`. Credentials and the application base path are in HTTP-protected `app/config.local.php`. Environment overrides are `TIBA_DB_HOST`, `TIBA_DB_USER`, `TIBA_DB_PASSWORD`, and `TIBA_DB_NAME`. The local runtime account `tiba_quip_runtime` has only SELECT/INSERT/UPDATE/DELETE access to the native database and cannot query WordPress tables. Schema maintenance uses separate protected `app/migration.local.php` credentials through the CLI setup command.

For deployment, configure `base_path`, set `environment` to `production`, configure HTTPS and the absolute site URL in Admin > Website settings, and update robots.txt for the deployed path/domain. Keep configuration, tools, database files and storage inaccessible over HTTP using the supplied deny rules.

## Installation and admin access

Create an empty destination database and supply server-side configuration following `app/config.example.php`. Then, from the project directory:

```powershell
& C:/xampp/php/php.exe tools/setup.php
& C:/xampp/php/php.exe tools/create-admin.php owner@example.com "Store owner"
```

`database/001_schema.sql` defines the reproducible native schema. Setup also reconciles session-version and imported-address columns on earlier installations; it does not drop existing data.

The initial local administrator is **tibaquipafrica@gmail.com**. Open **`storage/admin-access.txt` locally** for its generated password. This file is denied over HTTP. Change the password at `/user-setting.php` after login. No passwords are included in documentation. The admin creation command can generate a password or use the `TIBA_ADMIN_PASSWORD` environment variable (minimum 12 characters), and can promote/reset an existing account.

## WordPress migration

```powershell
& C:/xampp/php/php.exe tools/import-wordpress.php C:/xampp/htdocs/tiba_wordpress
& C:/xampp/php/php.exe tools/verify-migration.php
```

The importer reads source configuration, detects the actual prefix (`wpd4_`), and uses a read-only source transaction. It does not boot WordPress or modify source files/tables. Runtime code never reads source configuration or tables.

Unique source IDs prevent duplicate records. Repeated imports refresh imported catalog/content and reconcile categories/gallery links; historical orders are skipped once imported. **Rerunning can overwrite later admin edits to imported catalog/content: back up the destination before refreshing from the source.** Native-only records, existing settings and native administrator passwords are preserved.

Imported data:

- 317 published products and one draft, including prices, stock, SKUs, categories, descriptions, featured flags, tags, images and available metadata. One trashed source product is skipped.
- 22 product categories, including hierarchy and available images.
- 60 posts and 39 pages including drafts. Empty shortcode pages, sample/test pages and alternate demo homepages remain drafts; native routes implement shop/cart/checkout/account.
- 531 historical orders, item/payment snapshots and original dates/currency. Historical import does not deduct current stock.
- 121 source accounts plus available phone and billing/shipping address data. WordPress passwords and administrator privileges are not imported. Customers use native password reset.
- Business logo/favicon, contact details, business hours and a relevant source homepage promotional graphic.

Images belong to this project under `uploads/imported/`, copied without recompression. Admin uploads use random filenames in `uploads/media/`, validate MIME and image content, accept JPEG/PNG/WebP/GIF up to 10 MB, and cannot execute PHP. Removing a reference retains the original file to avoid breaking other references.

Reports: `storage/import-report.json` and `storage/migration-verification.json`. Source image `2025/04/mt-sample-background.jpg` is missing. Product source ID 2195 has a duplicate SKU: the native SKU is empty to maintain uniqueness; its original value remains in metadata. All referenced native product/category/gallery/content image paths passed verification.

## Application and admin

`app/bootstrap.php` centralizes sessions, authentication, CSRF, prepared queries, escaping, HTML sanitization and uploads. `app/commerce.php` manages persistent carts/wishlists and transactional orders. `app/storefront.php` renders the PHP routes through shared views. Layout components remain in `overall/`.

Admin features include product/category/blog/page/banner CRUD, multiple product categories and images, editable attributes/variants, inventory, customer details/status, order management/history, settings, contact messages and audit logs. Lists are paginated. Products are archived so historical order snapshots remain intact. Categories cannot be deleted while referenced by products or child categories.

Checkout recalculates prices/totals on the server, locks stock rows, rejects insufficient stock and uses a unique checkout key to prevent duplicate orders. Cancelling a native order restores managed stock only once. A refunded state records a refund performed externally; it does not transfer money or automatically restock returns.

Authentication uses password hashing, session regeneration, login throttling and session versions. Password changes/resets invalidate other sessions. Admin guards verify role and active status on each request. Mutations require CSRF tokens; customer order access enforces ownership or a high-entropy confirmation token. Internal order notes are not exposed on customer confirmations.

Optional clean routes coexist with PHP links: `/shop`, `/product/slug`, `/category/slug`, `/blog`, `/blog/slug`, `/cart`, `/checkout`, `/account`. SEO includes dynamic metadata/canonical URLs, Open Graph fields, product JSON-LD, `/sitemap.xml` and robots.txt.

## Settings, payment and email

Admin > Website settings controls business details, currency, shipping, free-shipping threshold, tax percentage, payment methods, bank instructions, social URLs and homepage sections. Banners and category/product controls manage merchandising.

Payment on delivery is enabled locally; bank transfer is configurable. The application does not collect card details or claim automatic payment success. Live gateways require a separate integration with credentials and verified callbacks. Confirm shipping/tax/payment settings before public launch; initial shipping and tax values are zero.

Local reset emails are saved as protected `storage/reset-*.txt` files. Production uses configured PHP mail transport; verify delivery before inviting customers to reset passwords. Order confirmations are displayed on-site. Automated order emails and a live payment gateway are not configured.

## First-party analytics

Random first-party identifiers populate `analytics_visitors`, `analytics_sessions` and `analytics_events`. Sessions expire after 30 minutes of inactivity; visitor cookies last 180 days. Reports cover traffic, returning visitors, prior periods, sources/campaigns, device/browser/OS, entry/last-active pages, product/category/blog views, searches, and the cart/checkout/purchase funnel. Revenue uses actual paid orders excluding cancelled/refunded orders. Historical sales appear on their original dates; historical traffic is not fabricated.

Analytics do not store raw IPs, raw user agents or query strings, perform fingerprinting, or call external geolocation services. Obvious crawlers, authenticated admins and Do Not Track requests are excluded. JD Shop supplied the functional reporting reference; its branding and credentials were not copied. Local verification visits can appear in development traffic.

## Verification and backup

- `tools/integration-check.py`: 102 HTTP assertions for authentication, CSRF, catalog filters, stock, cart/wishlist, checkout/idempotency, ownership, cancellation, CRUD, uploads, variants, settings, reset tokens and session invalidation. Requires Python requests, BeautifulSoup and the local admin-access file.
- `tools/test-cleanup.php`: deletes only recorded verification fixtures. Run it after integration tests. Fixtures have been removed from the delivered database.
- `tools/browser-check.py`: 40 Playwright checks at 390/768/1440 pixels, including admin, mobile navigation, script/resource errors and horizontal overflow. WordPress requests are blocked during testing. Requires local Playwright/Chromium and the admin-access file.
- `tools/verify-migration.php`: compares all 318 non-trashed source products on names, prices, stock and category relationships, and checks image paths and WordPress references in content.
- Original PHP files are backed up in `storage/before-ecommerce-*.zip`. The initial destination database was empty.
- `tools/backup.php` writes a consistent compressed native SQL backup into protected storage. Keep backups outside public deployment artifacts or retain HTTP deny rules.

The runtime database account was explicitly denied access to `tiba_old`. Browser checks block `tiba_wordpress` requests, and migrated content has no remaining WordPress references. WordPress itself was left intact; the store/admin operate from native tables and local assets.
