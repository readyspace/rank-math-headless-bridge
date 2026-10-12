# What we plan to build next

## Prepared for version 0.2.1

- WPGraphQL is the main Next.js integration, but optional for the WordPress plugin.
- REST remains a separate alternative. There is no automatic API fallback.
- Rank Math creates SEO for published posts, pages and supported custom types.
- Drafts, private posts and password-protected posts are excluded from public bridge SEO.
- App Router examples render search tags, sharing tags and schema on the server.
- Tests use real WordPress, Rank Math and WPGraphQL, plus a Next.js production build.

See [test results and limits](validation.md). Test your own staging site before
changing a live website. This release does not cover every Rank Math feature or
automatically migrate an existing frontend.

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

## WordPress.org publication

Version 0.2.0 was submitted on October 11, 2026. On October 12, the review team
requested a more distinctive name. Version 0.2.1 uses **ReadySpace Headless SEO
Bridge for Rank Math**, with the proposed directory slug and text domain
`readyspace-headless-seo-bridge-for-rank-math`. The corrected ZIP was uploaded on October 12, 2026.
Slug confirmation and approval are pending. The
[submission guide](wordpress-org-submission.md) tracks final package checks,
WordPress.org review and publication. The plugin is not listed merely because
its changes are merged on GitHub.

Test multisite, multilingual paths, WooCommerce and authenticated previews before
claiming support for those setups. Keep collecting staging feedback and record
the versions and Pro features actually tested. Publishing to WordPress.org and
changing live websites are separate steps.
