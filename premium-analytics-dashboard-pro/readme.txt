=== Premium Analytics Dashboard Pro ===
Contributors: premiumanalyticsdashboardpro
Tags: analytics, leads, contact form 7, dashboard, visitor tracking
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade, standalone WordPress analytics dashboard with Contact Form 7 lead capture, visitor tracking, and a premium admin UI.

== Description ==

Premium Analytics Dashboard Pro turns your WordPress admin into a self-hosted analytics and lead-management platform:

* Automatic Contact Form 7 lead capture, with dynamic field storage (no hardcoded field names).
* Visitor and session tracking (unique/returning visitors, devices, browsers, countries).
* A premium, glassmorphism-styled dashboard with light/dark mode.
* Role-based access: Administrator, Manager, Sales, Viewer.
* 100% self-contained — no external SaaS, no Node.js build step, no React, no Laravel.

This is Phase 1 of the plugin: core architecture, database schema, activation/deactivation, the admin menu (Dashboard, Leads, Visitors, Forms, Analytics, Reports, Settings, Users, Logs) and the dashboard UI shell. Lead capture, visitor tracking, charts and exports are wired in subsequent development phases.

== Installation ==

1. Upload the `premium-analytics-dashboard-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to the new "Analytics Pro" menu in the WordPress admin sidebar.
4. (Optional) Install and activate Contact Form 7 to enable lead capture.

== Frequently Asked Questions ==

= Does this plugin send my data anywhere? =

No. All data is stored in custom tables in your own WordPress database. No external services are called.

= Does deactivating the plugin delete my data? =

No. Deactivation never touches your data. Data is only removed on uninstall (plugin deletion), and only if you have explicitly enabled "Delete data on uninstall" in Settings.

= Is this compatible with Multisite? =

Yes. Each site in the network gets its own set of tables, created automatically on activation and on new site creation.

== Changelog ==

= 1.1.0 =
* Phase 2: Automatic Contact Form 7 lead capture (unlimited forms), dynamic field storage with no hardcoded field names, first-touch UTM/referrer/landing-page attribution, IP/browser/OS/device detection, and uploaded file capture into a permanent media directory.

= 1.0.0 =
* Phase 1: Plugin architecture, folder structure, database schema, activation/deactivation, admin menu, and the premium dashboard UI shell.
