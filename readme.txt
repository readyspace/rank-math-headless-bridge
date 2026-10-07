=== Headless Bridge for Rank Math ===
Tags: headless, rank-math, seo, indexnow, nextjs
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Optional SEO, IndexNow, published editor links, and CMS safeguards for WordPress sites with a separate headless frontend.

== Description ==

Use one configurable plugin version across different headless WordPress sites rather than maintain website-specific helper plugins. Each installation has its own frontend origin and optional modules. All modules are disabled by default.

Features include Rank Math structured-data URL mapping, a normalized public-post metadata endpoint, an IndexNow frontend adapter, published editor links, optional CMS noindex directives, and optional WordPress mail suppression.

Version 0.1.0 is an alpha intended for staging tests and community review. The plugin is an independent, unofficial integration. It does not bundle Rank Math, deploy a frontend, replace authenticated preview providers, migrate existing helpers automatically, or guarantee indexing.

Rank Math's Headless CMS Support must be enabled and PHP DOM must be available for the metadata endpoint. The frontend must render that data in its page HTML. IndexNow requires deploying the current Rank Math verification key on the public frontend host.

Mail suppression blocks WordPress mail, including password resets. Enable it only when that behavior is intentional. CMS noindex is a crawler directive and does not restrict access to the CMS.

Setup, frontend examples, migration, contribution, and security documentation:
https://github.com/ReadySpace/rank-math-headless-bridge

== Installation ==

1. Back up the site and test on staging.
2. Upload the rank-math-headless-bridge folder to wp-content/plugins or upload a release ZIP in WordPress.
3. Activate Headless Bridge for Rank Math.
4. Configure the public frontend origin, supported post types, and desired modules under Settings -> Headless Bridge. Use pretty WordPress permalinks for metadata, IndexNow, and editor links.
5. Integrate the frontend metadata renderer and IndexNow key file as required.
6. Validate existing helper replacements using the repository's migration guide before changing a live site.

== Frequently Asked Questions ==

= Does every Rank Math user need this? =

No. It is intended for WordPress used as a CMS with a separate frontend. Enable only the modules your architecture needs.

= Will it automatically add SEO tags to Next.js? =

No. The frontend must request and render the normalized metadata. The repository includes server-side Next.js examples.

= Does an IndexNow success mean my page is indexed? =

No. It means the submission was received. Search engines decide whether and when to crawl and index.

= Can I simply delete my old must-use plugins? =

Use the staging migration guide first. Must-use plugins cannot be deactivated in the usual Plugins screen. Keep recoverable backups and move replaced helpers outside the must-use plugin directory only after validating the replacement.

== Changelog ==

= 0.1.0 =

Initial alpha with configurable modules, a normalized metadata endpoint, frontend examples, and community documentation.
