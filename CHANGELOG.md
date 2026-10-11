# Changelog

## 0.2.0-alpha.1

- Add optional `ContentNode.headlessSeo` WPGraphQL metadata, separate from other SEO extensions.
- Share the public-only Rank Math metadata provider between REST and GraphQL.
- Isolate native query generation without replacing the caller's original WordPress objects or breaking main-query aliases.
- Generate the filtered document title even when the headless CMS theme lacks title-tag support.
- Align the WordPress minimum with Rank Math 1.0.280's WordPress 6.7 requirement.
- Add App Router/React 19 rendering examples accepting both transport contracts.
- Add real WordPress integration and Next.js production HTML checks.
- Document the supported-feature boundaries and the publishing-freshness roadmap.

## 0.1.0 — Initial alpha

- Introduce a configurable plugin for headless WordPress installations using Rank Math.
- Add optional public URL mapping for Rank Math structured data, a normalized published-post metadata endpoint, and an IndexNow frontend adapter.
- Add optional published editor links, CMS noindex directives, and WordPress mail suppression.
- Include Next.js integration examples, a controlled migration guide, and contribution and security policies.

All modules start disabled. This initial release is a foundation for staged testing and community review; it has not established production compatibility with every WordPress, Rank Math, or headless frontend configuration. It does not migrate or remove existing site-specific plugins automatically.
