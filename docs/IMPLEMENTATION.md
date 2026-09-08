# Audit and implementation plan

The destination is PHP/mysqli with Bootstrap, jQuery, Owl Carousel and Medion storefront styles. The Remos admin is a partial JD Shop adaptation: its expected schema is absent, its active-admin helper is missing, and its old login uses reversible AES. The destination database tiba_new contains no tables. Preserve the CSS, component class names and responsive grids; replace demo content and incomplete business handlers.

WordPress source: tiba_old, detected prefix wpd4_, uploads in wp-content/uploads. Counts: 317 published products, one draft and one trashed product; no product variations; 22 product categories; 60 published posts; 35 published and four draft pages; 529 attachments; 531 historical completed orders. Source remains read-only. The source contains historical orders, which should be preserved separately from new checkout transactions with source mappings.

JD Shop reference: actions/action.php records meaningful page views, campaign fields, device/browser/OS and product dimensions; traffic-live.php aggregates periods, sources, returning visitors and popular pages. Adapt these concepts to random first-party visitor/session IDs, native indexed tables, safe aggregate queries and no geolocation service or fingerprinting.

Implementation sequence:
1. Back up PHP sources; add reproducible native schema and centralized configuration.
2. CLI importer with source-ID upserts, local media ownership, source content cleanup and import report.
3. Secure common application helpers, customer sessions, cart, transactional orders, and first-party events.
4. Render native catalog/content using existing storefront components; preserve responsive template styles.
5. Replace incomplete admin handlers with authenticated, CSRF-protected native CRUD, settings, orders, audit logs and analytics using existing admin styles.
6. Run migration twice, compare counts, exercise HTTP flows and security boundaries, document deployment and optional integrations.
