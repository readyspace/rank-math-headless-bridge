# Changelog

## 0.2.0 — Prepared first public release

- Package under `headless-bridge-for-rank-math`, matching the text domain and avoiding a third-party brand at the start of the directory slug.
- Include only runtime PHP, readme, license, changelog and credits in the WordPress ZIP; keep setup guides and Next.js examples on GitHub.
- Add WordPress.org listing details for contributor `readyspace`, a ReadySpace introduction and website link, the disclosed Rank Math affiliate link, feature limits, independent Rank Math credits and opt-in IndexNow service disclosure.
- Make settings and API messages translatable; keep the legacy-helper warning on the bridge settings page.
- Remove only the bridge's settings on uninstall, preserving Rank Math settings and site content.
- Add official Plugin Check validation of the ZIP and uninstall integration checks.

## 0.2.0-alpha.1

- Lead Next.js setup and examples with WPGraphQL while keeping it optional at the plugin level; explain REST as a separate choice without automatic fallback.
- Add beginner setup steps, a glossary and troubleshooting; simplify the existing guides.
- Credit Rank Math and the other upstream projects in the README, plugin readme and packaged CREDITS.md; retain GPL-3.0-or-later licensing.
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
