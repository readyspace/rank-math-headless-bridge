# What we plan to build next

## Available in this alpha

- WPGraphQL is the main Next.js integration, but optional for the WordPress plugin.
- REST remains a separate alternative. There is no automatic API fallback.
- Rank Math creates SEO for published posts, pages and supported custom types.
- Drafts, private posts and password-protected posts are excluded from public bridge SEO.
- App Router examples render search tags, sharing tags and schema on the server.
- Tests use real WordPress, Rank Math and WPGraphQL, plus a Next.js production build.

See [test results and limits](validation.md). This alpha still needs reports from
real staging sites. It is not a complete migration or every Rank Math feature.

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

## Before a stable public release

Run WordPress Plugin Check, verify dependencies and ZIP installation, and record
the versions and Pro features actually supported. Confirm an independent
WordPress.org slug and matching text domain.

Test multisite, multilingual paths, WooCommerce and authenticated previews before
claiming support. Gather staging feedback. Publishing to WordPress.org and
changing live websites are separate steps.
