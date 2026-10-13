# What we plan to build next

## Delivered in version 0.2.1

- WPGraphQL is the main Next.js integration, but optional for the WordPress plugin.
- REST remains a separate alternative. There is no automatic API fallback.
- Rank Math creates SEO for published posts, pages and supported custom types.
- Drafts, private posts and password-protected posts are excluded from public bridge SEO.
- App Router examples render search tags, sharing tags and schema on the server.
- Tests use real WordPress, Rank Math and WPGraphQL, plus a Next.js production build.

Version **0.2.1** was published on **October 13, 2026** as **ReadySpace Headless
SEO Bridge for Rank Math**. Install it from the
[WordPress Plugin Directory](https://wordpress.org/plugins/readyspace-headless-seo-bridge-for-rank-math/).
The release is available in `trunk` and `tags/0.2.1` at SVN revision **3743846**,
uploaded by `readyspace`.

See [test results and limits](validation.md). Test your own staging site before
changing a live website. This release does not cover every Rank Math feature or
automatically migrate an existing frontend. It remains an independent project;
publication does not mean Rank Math endorses it.

## Next: keep saved pages fresh

When an editor changes content or SEO, Next.js should refresh any saved copy.
Plan notifications from WordPress to an address set by the administrator.
Include a signature to verify the sender, limited retries and useful status messages.

Cover title and SEO changes, global settings, unpublished or deleted posts and
changed page paths. An old path may need refreshing too. Do not let visitors
choose where the server sends these notices.

## Then: more page types and discovery

Add SEO for categories, authors, archives, search results and 404 pages.
Each needs a clear public path mapping.

Add public XML sitemaps and robots.txt. A sitemap lists page addresses for search
engines. robots.txt gives crawler instructions. Decide whether WordPress or Next.js
serves these files, and keep CMS-only `noindex` separate from public-page choices.

## Then: redirects and 404 reports

A redirect sends an old address to a new one. The frontend must return the correct
HTTP status, not only display a different page. Start with exact-match redirects.

Optional 404 reports could tell WordPress when a public page is missing. Plan
sender verification, limits on reports, minimal stored data and a retention period.

## Maintain the public release

WordPress.org confirmed the slug `readyspace-headless-seo-bridge-for-rank-math`
and approved the plugin on October 13, 2026. Version 0.2.1 is now available from
the directory. The [publication guide](wordpress-org-submission.md) records the
review, package checks and SVN release. Future releases need their own checks,
version numbers and SVN tags.

Keep collecting staging feedback and record the WordPress, PHP, Rank Math,
WPGraphQL and Next.js versions actually tested. Test multisite, multilingual
paths, WooCommerce, Pro features and authenticated previews before claiming
support for those setups. Real-provider PHP 7.4 support and live IndexNow delivery
still need verification; publication does not establish those results.

The planned additions above are future work, not features delivered by 0.2.1.
Installing the public plugin and changing a live website remain separate steps.
