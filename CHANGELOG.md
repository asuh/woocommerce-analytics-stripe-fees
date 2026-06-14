# Changelog

All notable changes to Woocommerce Analytics Stripe Fees are documented here.

## 1.1.0 - 2026-06-14

### Added

-   Added Stripe fee totals to the WooCommerce Analytics Revenue report.
-   Added `Net After Fees` columns for Orders and Revenue report tables.
-   Added a `Stripe Fees` chart option for the Revenue report and Analytics dashboard.
-   Added Revenue CSV export support for Stripe fee values.
-   Added focused PHPUnit coverage for Stripe fee helper behavior.
-   Added a GPL-3.0-or-later license file.

### Changed

-   Replaced the generated scaffold admin page with focused Analytics report extensions.
-   Moved shared Stripe fee parsing and report interval logic into `WoocommerceAnalyticsStripeFees\StripeFees`.
-   Improved Revenue fee aggregation by querying orders once across the report range and grouping results into intervals.
-   Updated admin currency display to respect WooCommerce currency settings when available.
-   Made admin asset loading defensive when build files are missing.
-   Normalized text domain usage to `woocommerce-analytics-stripe-fees`.
-   Updated release packaging so development dependencies are restored after generating the plugin zip.
-   Updated development dependencies and npm overrides so Composer and npm audits pass.
-   Replaced scaffold translation metadata with plugin-specific strings.

### Removed

-   Removed unused block metadata.
-   Removed scaffold/demo WooCommerce component UI.
-   Removed the placeholder PHPUnit test.

## 1.0.0 - 2026-01-13

### Added

-   Initial release.
-   Added Stripe fee data to WooCommerce Analytics Orders report rows.
-   Added an Orders report table column for Stripe fees.
-   Added Orders CSV export support for Stripe fee values.
-   Added production zip build workflow.
