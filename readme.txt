=== Headless Bridge for Rank Math ===
Tags: headless, rank-math, seo, indexnow, nextjs
Requires at least: 6.7
Requires PHP: 7.4
Stable tag: 0.2.0-alpha.1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Bring Rank Math SEO from WordPress to a separate Next.js site. WPGraphQL is the main example; REST is an optional alternative.

== Description ==

Write and edit in WordPress. Let visitors read your Next.js website. This bridge helps Next.js use the SEO title, description, search instructions, sharing tags and schema created by Rank Math.

Our main Next.js setup uses WPGraphQL to ask for content and SEO together. WPGraphQL remains optional for the WordPress plugin. Developers using REST can enable the separate public-post SEO endpoint. The plugin does not switch APIs automatically. All bridge features start off.

Other optional features fix supported SEO page addresses, adapt Rank Math's IndexNow notices, open published View links on the public website, add CMS noindex instructions or stop WordPress email.

Version 0.2.0-alpha.1 is an early version for staging tests and community feedback. It does not cover every Rank Math feature. The frontend examples must be added to your Next.js app; the plugin does not build or deploy that app.

Rank Math must be installed separately. Enable its Headless CMS Support and ensure PHP DOM is available for either SEO API. Drafts, private posts and password-protected posts do not receive public bridge SEO.

IndexNow needs a current verification key file on the public website. A submission does not guarantee indexing. CMS noindex does not add a login or prevent access. Leave email suppression off if WordPress must send password resets or notices.

This project is open source under GPL-3.0-or-later. Thank you to the Rank Math team and contributors for Rank Math SEO and its headless support, which make this bridge possible. This is an independent community project; these credits do not claim Rank Math's approval or support. Rank Math's code is not bundled here.

Rank Math: https://rankmath.com/
Official Rank Math source: https://github.com/rankmath/seo-by-rank-math
Headless CMS guide: https://rankmath.com/kb/headless-cms-support/

The ZIP includes setup guides and CREDITS.md. Start with docs/quick-start.md. Source, contributions and updates:
https://github.com/readyspace/rank-math-headless-bridge

== Installation ==

1. Test on a copy of your site first.
2. Install and activate Rank Math and this bridge. Install WPGraphQL for the main Next.js setup.
3. Enable Rank Math Headless CMS Support and use readable WordPress permalinks.
4. Open Settings -> Headless Bridge. Enter the public website address and select the public content types.
5. For WPGraphQL, enable SEO through WPGraphQL and Correct Rank Math schema addresses. The REST endpoint can stay off.
6. Follow docs/quick-start.md and the App Router guide to connect Next.js. Check the actual page source.
7. Add other features one at a time. Use docs/migration.md when replacing older helpers.

== Frequently Asked Questions ==

= Is WPGraphQL required? =

For our main Next.js example, yes. For the WordPress plugin as a whole, no. REST and non-GraphQL features remain separate choices. There is no automatic fallback.

= Does GraphQL guarantee a faster website? =

No. It can combine content and SEO in one request. Query size, hosting and caching affect speed. This project has tested correctness, not benchmarked GraphQL against REST.

= Will the plugin add SEO tags to Next.js by itself? =

No. Add the server-side frontend helpers too. The guides explain the steps.

= Can I delete my old helper plugins? =

First compare their behavior on staging. Keep backups and replace one job at a time. Must-use plugins cannot be turned off in the usual Plugins screen. See docs/migration.md.

== Changelog ==

= 0.2.0-alpha.1 =

Optional WPGraphQL SEO, a shared generator, App Router examples, query-context fixes and real WordPress/Next.js checks. Beginner guides lead with WPGraphQL and credit Rank Math.

= 0.1.0 =

Initial alpha with optional features, a public-post SEO endpoint, frontend examples and community guides.
