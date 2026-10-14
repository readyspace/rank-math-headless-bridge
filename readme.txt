=== ReadySpace Headless SEO Bridge for Rank Math ===
Contributors: readyspace
Tags: headless, seo, graphql, nextjs, indexnow
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Use Rank Math SEO on a separate Next.js website. Optional WPGraphQL support, a REST alternative and guides for headless WordPress.

== Description ==

Write and edit in WordPress. Let visitors read your Next.js website. This bridge helps Next.js use the SEO title, description, search instructions, sharing tags and schema created by Rank Math.

Rank Math is a WordPress SEO plugin. You use it to set how your pages appear in search results and when people share links. Install Rank Math separately; its code is not included in this bridge.

[Explore Rank Math (affiliate link)](https://rankmath.com/?ref=super%20salesman)

Affiliate disclosure: If you make a qualifying purchase through this link, this project's owner may earn a commission. A paid Rank Math plan is not required to use this bridge.

Our main Next.js setup uses WPGraphQL to ask for content and SEO together. WPGraphQL remains optional for the WordPress plugin. Developers using REST can enable the separate public-post SEO endpoint. The plugin does not switch APIs automatically. All bridge features start off.

Other optional features fix supported SEO page addresses, adapt Rank Math's IndexNow notices, open published View links on the public website, preserve frontend preview destinations, add CMS noindex instructions, disable WordPress core CMS sitemaps or stop WordPress email.

Test the bridge on a copy of your site before changing a live site. It does not cover every Rank Math feature. The frontend examples must be added to your Next.js app; the plugin does not build or deploy that app.

For either SEO API, install and activate Rank Math, enable its Headless CMS Support and make sure PHP DOM is available. Ask your host about PHP DOM if needed. Drafts, private posts and password-protected posts do not receive public bridge SEO.

The current version supports published posts, pages and selected public custom post types. Archive SEO, sitemap generation, redirects, 404 reporting and automatic Next.js cache updates are not included. An optional CMS safeguard can disable WordPress core sitemaps without changing Rank Math sitemap settings. See the validation guide for tested versions and remaining checks.

IndexNow needs a current verification key file on the public website. Keep an existing .txt filename with the optional public key path setting. A public read-only key endpoint is available only while the IndexNow adapter is enabled. A submission does not guarantee indexing. CMS noindex does not add a login or prevent access. Leave email suppression off if WordPress must send password resets or notices.

This project is open source under GPL-3.0-or-later. Thank you to the Rank Math team and contributors for Rank Math SEO and its headless support, which make this bridge possible. This is an independent community project; these credits do not claim Rank Math's approval or support. Rank Math's code is not bundled here.

Official Rank Math source: https://github.com/rankmath/seo-by-rank-math
Headless CMS guide: https://rankmath.com/kb/headless-cms-support/

= About ReadySpace =

ReadySpace provides cloud and hosting services for businesses. ReadySpace and community contributors develop and share this free, open-source bridge. You can use it with your chosen hosting provider; a ReadySpace account is not required.

[Learn about ReadySpace](https://readyspace.com)

Start with the [beginner setup guide](https://github.com/readyspace/rank-math-headless-bridge/blob/main/docs/quick-start.md). Read the [tests and limits](https://github.com/readyspace/rank-math-headless-bridge/blob/main/docs/validation.md) before using it on a live site. Setup guides and Next.js examples are available on GitHub; they are not included in the WordPress ZIP. The ZIP includes the plugin, licence, changelog and credits.

[Source code and contributions](https://github.com/readyspace/rank-math-headless-bridge)

== External services and privacy ==

= Optional IndexNow notifications =

IndexNow tells participating search engines that public pages were added, changed or deleted. This feature is optional. The bridge's IndexNow frontend adapter starts off. It works with Rank Math's Instant Indexing module, which you must configure separately.

When Rank Math sends an automatic or manual IndexNow notification, the bridge can change eligible page addresses to your public website. Rank Math makes the request to https://api.indexnow.org/indexnow/ and sends the public page URLs, website host, verification key and key-file address. Rank Math also sends its client information, including its version, whether the request was manual and a coded identifier based on your WordPress home address. The receiving server can see your WordPress server's IP address. IndexNow shares submitted URLs with participating search engines.

This bridge does not send post bodies, passwords or visitor details to IndexNow. Only submit public URLs you want search engines to know about. A received notification does not guarantee indexing. Turning the bridge adapter off does not turn off Rank Math's own IndexNow module.

* [IndexNow service](https://www.indexnow.org/)
* [IndexNow protocol](https://www.indexnow.org/documentation)
* [IndexNow terms and website privacy information](https://www.indexnow.org/terms)
* [Rank Math IndexNow setup](https://rankmath.com/kb/how-to-use-indexnow/)

IndexNow's website privacy section describes its website, not every participating search engine's data handling. Check the policies of the search engines and hosting services you choose.

= SEO APIs =

The optional IndexNow key endpoint returns only the current public verification key. It does not generate a key, expose other settings or contact an external service. It remains separate from the REST SEO feature.

The bridge generates SEO inside your WordPress site. Its REST route and optional WPGraphQL field let your frontend request SEO for eligible public content. They do not contact a bridge vendor service. Your Next.js app and hosting provider handle those requests according to your own setup. The bridge does not add analytics or collect usage reports.

== Installation ==

1. Test on a copy of your site first.
2. Install and activate Rank Math and this bridge. Install WPGraphQL for the main Next.js setup.
3. Enable Rank Math Headless CMS Support and use readable WordPress permalinks.
4. Open Settings -> ReadySpace SEO Bridge. Enter the public website address and select the public content types.
5. For WPGraphQL, enable SEO through WPGraphQL and Correct Rank Math schema addresses. The REST endpoint can stay off.
6. Follow the linked beginner setup guide and App Router guide to connect Next.js. Check the actual page source.
7. Add other features one at a time. Follow the migration guide when replacing older helpers. Read the external-service notes before enabling IndexNow.

== Frequently Asked Questions ==

= Is WPGraphQL required? =

For our main Next.js example, yes. For the WordPress plugin as a whole, no. REST and non-GraphQL features remain separate choices. There is no automatic fallback.

= Does GraphQL guarantee a faster website? =

No. It can combine content and SEO in one request. Query size, hosting and caching affect speed. This project has tested correctness, not benchmarked GraphQL against REST.

= Will the plugin add SEO tags to Next.js by itself? =

No. Add the server-side frontend helpers too. The guides explain the steps.

= Is this an official Rank Math plugin? =

No. This is an independent open-source community project. Rank Math has not endorsed it or agreed to support it. Please report bridge issues to this project's maintainers.

= Does the bridge need a paid account? =

The bridge has no paid features, licence keys or required bridge account. Rank Math, WPGraphQL and your hosting services are separate projects with their own requirements.

= Can I delete my old helper plugins? =

First compare their behavior on staging. Keep backups and replace one job at a time. Must-use plugins cannot be turned off in the usual Plugins screen. Follow the [migration guide](https://github.com/readyspace/rank-math-headless-bridge/blob/main/docs/migration.md).

= Does the bridge replace my preview provider? =

No. Optional Frontend preview links preserves the existing provider's preview parameters and repairs Gutenberg preview destinations. You still need an authenticated frontend preview integration, such as Faust. It does not make drafts public or change public SEO and IndexNow mapping.

= How do I upgrade an older bridge ZIP? =

Version 0.2.1 uses the folder readyspace-headless-seo-bridge-for-rank-math. Older ZIPs used rank-math-headless-bridge or headless-bridge-for-rank-math. Back up your settings and old files, deactivate the old bridge and follow the [folder-change guide](https://github.com/readyspace/rank-math-headless-bridge/blob/main/docs/migration.md#upgrading-an-older-bridge-zip). Never activate both copies. Deactivation keeps settings. Deleting a bridge version with an uninstaller through WordPress removes their shared bridge settings, so remove only the old code folder after backing up.

== Changelog ==

= 0.4.0 =

Add opt-in frontend preview links for existing authenticated preview providers, including dynamic Gutenberg links. Extend published editor View links to permission-checked REST edit responses. Add separate opt-in WordPress core CMS sitemap suppression. Preserve existing settings, GraphQL SEO, IndexNow key and history; all new features default off.

= 0.3.0 =

Preserve an existing IndexNow key-file path, read the current public verification key, and validate automatic and manual Rank Math payloads with one shared policy. Configure source aliases, private frontend paths and trailing slashes without changing the SEO APIs. Reject entire invalid batches and protect known private, password-protected, nonindexable or externally canonicalized posts. No key rotation or history reset.

= 0.2.1 =

Use the distinctive ReadySpace Headless SEO Bridge for Rank Math name, with a matching directory folder and text domain. Existing bridge settings, SEO APIs and Next.js helpers stay compatible. Follow the migration guide when replacing an older ZIP.

= 0.2.0 =

Prepared first public release with directory documentation, external-service disclosure and packaging that matches the plugin slug. Optional WPGraphQL SEO supports published content; Next.js App Router examples are available on GitHub.

= 0.2.0-alpha.1 =

Optional WPGraphQL SEO, a shared generator, App Router examples, query-context fixes and real WordPress/Next.js checks. Beginner guides lead with WPGraphQL and credit Rank Math.

= 0.1.0 =

Initial alpha with optional features, a public-post SEO endpoint, frontend examples and community guides.
