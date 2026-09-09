# Tiba Quip standalone ecommerce

Store: `http://localhost/tiba_quip/`  
Admin: `http://localhost/tiba_quip/admin/`

## Requirements and configuration

PHP 7.4+ with mysqli/mysqlnd, mbstring, DOM, fileinfo and sessions; MySQL 8 or compatible MariaDB; Apache or LiteSpeed with `.htaccess` enabled. PHP 8.1+ is recommended. The storefront retains Medion/Bootstrap/jQuery styles and responsive components. Administration retains its Remos styles, with ApexCharts and a locally bundled TinyMCE editor (license in `admin/js/tinymce/`).

The native database is `tiba_new`. Its connection credentials and environment are in `control/core/database/connect.php`, following the same pattern as JD Shop. The local runtime account `tiba_quip_runtime` has only SELECT/INSERT/UPDATE/DELETE access to the native database.

For deployment, set `environment` to `production` and configure HTTPS. The application detects its own domain and installation folder automatically. Keep configuration, database files and storage inaccessible over HTTP using the supplied deny rules.

## Installation and admin access

Import the included native database backup into a new database, then update `control/core/database/connect.php` with its server credentials and installation path. No WordPress or JD Shop migration is required. The running application uses only this project and its native database.

## Application and admin

The shared storefront navigation follows the JD Shop reference: compact sticky header, product-category dropdown, mobile navigation and an AJAX cart drawer. Add-to-cart opens a quantity dialog and updates the badge without navigating away. Drawer quantity controls and removal use `cart-api.php` with native stock validation, ownership checks and CSRF protection. Cart and checkout pages refresh their displayed order review after drawer edits.

The shop provides a mobile filter drawer, category/price/search filters, sorting, page-size selection, persistent grid/list layout and automatic product loading on scroll. Loading retains the selected filters, uses deterministic ordering and rejects duplicate cards. Failed requests expose a retry button; ordinary pagination remains available without JavaScript. Background product loads do not create additional analytics page views.

`app/bootstrap.php` centralizes sessions, authentication, CSRF, prepared queries, escaping, HTML sanitization and uploads. `app/commerce.php` manages persistent carts/wishlists and transactional orders. Each active public PHP route contains its own storefront controller. Layout components remain in `overall/`.

Product cards display live average ratings and review counts, including products loaded on scroll. Customers can submit or update a 1–5 star rating and optional comment on the product page; names are public and email addresses remain private. Reviews are associated with the signed-in account or persistent guest cart cookie. Admin > Product reviews can hide or publish reviews; hidden reviews are excluded from averages and cannot be republished by customer edits. The shop also supports sorting by average rating.

Admin features include product/category/blog/page/banner CRUD, multiple product categories and images, editable attributes/variants, inventory, customer details/status, order management/history, settings, contact messages and audit logs. Lists are paginated. Products are archived so historical order snapshots remain intact. Categories cannot be deleted while referenced by products or child categories.

Checkout recalculates prices/totals on the server, locks stock rows, rejects insufficient stock and uses a unique checkout key to prevent duplicate orders. Cancelling a native order restores managed stock only once. A refunded state records a refund performed externally; it does not transfer money or automatically restock returns.

Authentication uses password hashing, session regeneration, login throttling and session versions. Password changes/resets invalidate other sessions. Admin guards verify role and active status on each request. Mutations require CSRF tokens; customer order access enforces ownership or a high-entropy confirmation token. Internal order notes are not exposed on customer confirmations.

Public navigation uses direct PHP routes so the site does not require root rewrite rules. SEO includes dynamic metadata/canonical URLs, Open Graph fields, product JSON-LD, `sitemap.php` and robots.txt.

## Settings, payment and email

Admin > Website settings controls business details, currency, shipping, free-shipping threshold, tax percentage, payment methods, bank instructions, social URLs and homepage sections. Banners and category/product controls manage merchandising.

Payment on delivery is enabled locally; bank transfer is configurable. The application does not collect card details or claim automatic payment success. Live gateways require a separate integration with credentials and verified callbacks. Confirm shipping/tax/payment settings before public launch; initial shipping and tax values are zero.

Local reset emails are saved as protected `storage/reset-*.txt` files. Production uses configured PHP mail transport; verify delivery before inviting customers to reset passwords. Order confirmations are displayed on-site. Automated order emails and a live payment gateway are not configured.

## First-party analytics

Random first-party identifiers populate `analytics_visitors`, `analytics_sessions` and `analytics_events`. Sessions expire after 30 minutes of inactivity; visitor cookies last 180 days. Reports cover traffic, returning visitors, prior periods, sources/campaigns, device/browser/OS, entry/last-active pages, product/category/blog views, searches, and the cart/checkout/purchase funnel. Revenue uses actual paid orders excluding cancelled/refunded orders. Historical sales appear on their original dates; historical traffic is not fabricated.

The admin dashboard mirrors the JD Shop operational overview with sales, income, orders, visitors, top products, recent orders, fulfillment, low-stock alerts, product traffic, monthly and 14-day charts, live visitors, traffic breakdowns, recent visits and a country map. Live dashboard data refreshes every 30 seconds. Country detection uses the visitor IP with IPAPI as its primary source; the IP-derived country is cached for seven days in the active session. The traffic endpoint is restricted to authenticated administrators.

Analytics use the visitor IP transiently to obtain the IPAPI-derived country, but do not store raw IPs, raw user agents or query strings or perform fingerprinting. Obvious crawlers, authenticated admins and Do Not Track requests are excluded. Local verification visits can appear in development traffic.

## DirectAdmin deployment

Tiba Quip is a standalone PHP/MySQL application. Upload the project and the complete `uploads/` folder, import the native `tiba_new` database backup, then edit `control/core/database/connect.php` with the DirectAdmin database name, user and password and set `environment` to `production`. The application detects its installation folder automatically. Preserve every `.htaccess` file and make `uploads/media/` and `storage/` writable by PHP.
