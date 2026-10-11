# Validation for 0.2.0-alpha.1

These checks use test sites and test pages to see whether the bridge returns the
right SEO and keeps private content out. They also check the HTML built by Next.js.
Passing them is useful evidence, but your own staging site still needs testing.
The sections below record the exact versions and remaining limits for developers.

These are isolated development results from 2026-10-11. They do not certify a
production migration or complete Rank Math feature coverage.

## Real WordPress and upstream providers

WordPress Playground 3.1.57 ran real Rank Math 1.0.280 and WPGraphQL 2.23.1
with PHP 8.3.33 and DOM. The completed runs were:

- WordPress 7.1.3: 53 integration assertions, plus disabled-module and absent
  WPGraphQL checks, using the source plugin.
- WordPress 6.7.10: 55 integration assertions, including per-post schema identity,
  plus the same optional-dependency checks, using an installed release ZIP.

Checks cover computed titles/descriptions, public canonicals, deliberate noindex,
schema generation, distinct posts/custom post types, repeated aliases, real REST
and GraphQL agreement, published-only access even for administrators, provider
failures, query/input/hook restoration, and GraphQL without the REST module.
The fixture deliberately does not declare theme title-tag support.

The standalone regression suites also passed: 63 settings/mapping/editor/safeguard
assertions and 115 metadata/Rank Math assertions. All eight packaged PHP files
passed parser checks; the source run also parsed the three test PHP files.

Current Rank Math requires WordPress 6.7. A WordPress 6.5 attempt could not load
its implementation and is not a supported combination for this milestone.
A PHP 7.4 Playground run failed with a WebAssembly function/signature error during
the standalone metadata suite. Real-provider PHP 7.4 compatibility has therefore
not been validated locally. Native PHP CI jobs remain configured for 7.4, 8.1,
8.3 and 8.4; check their actual results before relying on them.

## Next.js rendering

Node 24.18.0 ran seven frontend contract/rendering tests. A Next.js 16.4.0,
React 19.3.0 production build and TypeScript check passed. Generated HTML checks
confirmed the exact Rank Math title, one public canonical, robots/Googlebot/Bingbot
directives, repeated Open Graph images, one Twitter card, removal of inherited
social metadata, and safe JSON-LD script serialization.

This is a small App Router fixture, not a migration of an existing application.
It does not exercise a live GraphQL HTTP endpoint, deployment caching, CSP,
file-based social metadata overrides, or every supported Next.js release.

## Packaging and remaining acceptance

The ZIP uses an explicit allowlist and contains plugin source, license, changelog,
guides and frontend examples. Tests, development dependencies and upstream plugins
are excluded. The ZIP installed and activated in disposable WordPress 6.7.10.

Staging acceptance is still required for actual route mappings, Pro features,
multisite/multilingual sites, preview authentication, editor workflows and CMS
safeguards. Native IndexNow delivery, public key serving, WordPress Plugin Check,
large-query performance and a deployed frontend remain unverified here. No live
CMS configuration, public plugin-directory listing, or production site changed.
