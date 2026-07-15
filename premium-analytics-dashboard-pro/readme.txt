=== Premium Analytics Dashboard Pro ===
Contributors: premiumanalyticsdashboardpro
Tags: analytics, leads, contact form 7, dashboard, visitor tracking
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.6.0
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

= 1.6.0 =
* Phase 7: Notifications. A dashboard notification bell (header dropdown, unread badge, mark read / mark all read) fires on every new lead.
* Email notification via wp_mail() to one or more configurable recipients.
* Telegram notification via the free, public Telegram Bot API (site owner supplies their own Bot Token + Chat ID).
* WhatsApp "integration ready": a provider-agnostic webhook URL (Twilio, Gupshup, 360dialog, or a custom relay) that receives a JSON payload on every new lead — no specific paid SaaS is bundled or called automatically.
* All three channels are opt-in and only ever fire when the site owner has supplied their own credentials/URL in Settings — nothing is sent anywhere without explicit configuration.

= 1.5.0 =
* Phase 6: Full Reports generation — Daily, Weekly, Monthly, Quarterly, Yearly and Custom Date Range, each producing a complete business report (leads by status/form/country, visitor/session/pageview totals, average session duration, bounce rate, conversion rate, traffic sources, and a leads trend chart).
* Export the generated report to PDF (multi-section, via the same dependency-free PDF writer introduced in Phase 5, now extended to flow across headings/tables/paragraphs and page breaks) or CSV.

= 1.4.0 =
* Phase 5: Advanced Lead Management. The Leads page is now a fully AJAX-driven data table: live search, sortable columns, pagination, and filters (status, country, form, date range).
* Inline lead status changes, a slide-over detail panel showing every captured field plus dynamic custom fields, notes, and tags (add/remove).
* Delete and bulk-delete with capability checks.
* Export to CSV, Export to Excel (opens natively in Excel/Sheets), Export to PDF (rendered by a small hand-written PDF generator with zero external libraries — see includes/class-pad-pdf-writer.php), and a print-friendly view — all four honor whatever filters are currently applied on screen.

= 1.3.0 =
* Phase 4: Interactive charts, powered by ApexCharts (vendored locally under assets/js/vendor — no CDN, no external request at runtime).
* Dashboard: Leads Over Time (with Daily/Weekly/Monthly/Yearly tabs), Traffic Sources, Country Analytics, Device Analytics, and an Hourly Activity Heatmap.
* Analytics: Lead Conversion trend, Country/City/Browser/Device/Operating System breakdowns.
* All charts fetch real data through a capability-gated, nonce-verified `pad_get_chart_data` AJAX endpoint, and re-theme instantly when Dark/Light mode is toggled.

= 1.2.0 =
* Phase 3: Visitor tracking — unique/returning visitors, sessions, page views, visit duration and bounce rate, all captured via a lightweight vanilla-JS frontend beacon (no framework, no build step).
* First-touch traffic-source classification (Google Search, Facebook, Instagram, LinkedIn, Paid, Organic, Referral, Direct) reusing the Phase 2 attribution cookie.
* Screen resolution, browser language and timezone captured client-side; browser/OS/device parsed server-side.
* Country detection via zero-cost CDN/proxy headers (e.g. Cloudflare) with a `pad_geoip_lookup` filter for site owners who want to plug in their own local GeoIP database — no third-party geolocation API is ever called.
* Fixed the "Visitors Online" dashboard card to use last-activity time instead of session-start time, so long-running sessions are still counted correctly.

= 1.1.0 =
* Phase 2: Automatic Contact Form 7 lead capture (unlimited forms), dynamic field storage with no hardcoded field names, first-touch UTM/referrer/landing-page attribution, IP/browser/OS/device detection, and uploaded file capture into a permanent media directory.

= 1.0.0 =
* Phase 1: Plugin architecture, folder structure, database schema, activation/deactivation, admin menu, and the premium dashboard UI shell.
