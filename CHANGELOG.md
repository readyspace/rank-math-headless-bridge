# Changelog

## 0.4.0 — Editor and CMS helper replacement

- Extend published editor View links to authenticated REST edit responses with per-post edit permissions; leave anonymous content URLs unchanged.
- Add opt-in frontend preview destinations and Gutenberg link repair while retaining the existing authenticated preview provider and its parameters.
- Add opt-in WordPress core CMS sitemap suppression, separate from CMS noindex and Rank Math sitemap settings.
- Preserve existing settings, GraphQL and REST SEO contracts, IndexNow key and history. New options default off; the existing mail suppression option remains independent.

## 0.3.0 — IndexNow migration contracts

- Keep existing public key filenames with a configurable path; preserve the native CMS key-serving context, current key, module settings and history.
- Add a read-only current-key REST route when IndexNow is enabled, independent of the REST SEO feature.
- Apply the same public URL and payload policy to automatic events and Rank Math's manual REST console, which bypasses its URL filter in version 1.0.280.
- Reject entire invalid batches and known private, password-protected, disallowed, nonindexable or externally canonicalized content.
- Add explicit historical source origins, excluded frontend paths and IndexNow trailing-slash choices. Existing installations keep key-named files, exact configured origins and original paths by default.
- Keep the v1 WPGraphQL and REST SEO shapes unchanged; do not add site-specific aliases or settings.
- Replace filename-based helper warnings with guidance to assign each enabled responsibility to one plugin.

## 0.2.1 — First WordPress.org release (2026-10-13)

- Publish the approved plugin on WordPress.org: [plugin page](https://wordpress.org/plugins/readyspace-headless-seo-bridge-for-rank-math/) and [version 0.2.1 download](https://downloads.wordpress.org/plugin/readyspace-headless-seo-bridge-for-rank-math.0.2.1.zip).
- Rename the plugin to **ReadySpace Headless SEO Bridge for Rank Math** following the WordPress.org name review.
- Match the package folder and text domain to `readyspace-headless-seo-bridge-for-rank-math`.
- Keep stored bridge settings, REST and GraphQL APIs, developer hooks and Next.js integration compatible.
- Explain how to replace either older folder without running an uninstaller that removes shared settings.

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
