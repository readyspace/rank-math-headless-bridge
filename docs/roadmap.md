# Roadmap

## Current milestone: WPGraphQL and App Router

- Optional `ContentNode.headlessSeo` backed by the shared metadata provider.
- Published content only; private/draft/password restrictions remain intact.
- App Router metadata and social/schema rendering examples.
- Disposable real WordPress/Rank Math/WPGraphQL integration tests and production
  Next.js HTML checks.

This milestone remains an alpha until real installation reports and staging
acceptance cover the intended deployments. No public-directory submission or
production migration is implied by completing it.

## Next: publishing freshness

Design explicit, administrator-configured frontend notifications for post,
taxonomy and global Rank Math settings changes. Include signed payloads, bounded
retries, diagnostic status, and Next.js cache invalidation. Handle unpublishing,
deletion and slug changes, including invalidating old paths. Notification URLs
must be explicitly configured, never supplied by an unauthenticated request.

## Then: route and discovery coverage

Add taxonomy, author, archive, search and 404 contexts with frontend route mapping.
Support public-host XML sitemaps and robots.txt with a documented ownership choice.
Keep CMS-only noindex separate from intentional frontend noindex.

## Then: request-time SEO workflows

Expose or synchronize redirects and ensure the frontend emits the requested HTTP
status. Start with exact-match redirects and expand matching semantics only with
tests. Add optional frontend 404 reporting with authentication, rate limiting,
data minimization and retention controls.

## Stable public release

Run WordPress Plugin Check, verify dependency handling and installation ZIPs,
document supported versions and installed Pro features, and gather staging reports.
Choose a WordPress.org-compatible independent slug and align the text domain.
Multisite, multilingual routing, WooCommerce and authenticated preview SEO require
their own supported-feature decisions and compatibility checks.
