# Validation for the prepared 0.2.0 release

## WordPress.org preparation checks — 2026-10-11

The installable `headless-bridge-for-rank-math-0.2.0.zip` contains 13 files:
nine runtime PHP files, `readme.txt`, `LICENSE`, `CHANGELOG.md` and `credits.txt`.
Every archived file was compared with its source. Tests, examples, development
dependencies, build caches and the GitHub affiliate README are excluded.

Official Plugin Check **2.1.0** ran all **29 non-experimental static checks**
against the installed ZIP on WordPress **7.1.3**, PHP **8.3.33**:
**zero errors and zero warnings**. The [machine-readable report](plugin-check-report.json)
records every check. Optional AI analysis was not enabled. This is a static
review; it is separate from the real-provider integration tests and is not
WordPress.org approval.

Four narrowly scoped PHPCS comments explain request-context snapshots and
restoration of existing core globals. These preserve the caller's exact inputs;
they do not process form data, use those inputs as a metadata URL, perform admin
writes or create new plugin globals. The executable package still runs the
complete static check set without excluding checks or result codes.

The directory ZIP also installed and activated with real Rank Math **1.0.280**
and WPGraphQL **2.23.1** on WordPress **6.7.10** and **7.1.3**, PHP **8.3.33**.
Each run passed **55 integration assertions**, **63 settings/mapping assertions**,
**115 metadata/Rank Math assertions**, and syntax checks for all nine runtime PHP
files. Additional checks passed for disabling the GraphQL module, running without
WPGraphQL, and uninstalling through WordPress. Uninstall removed only the bridge
settings and preserved Rank Math settings and site content.

The minimum-version local run preceded the final nonfunctional Plugin Check
fixes; the latest-version local run used the final runtime files. All eight
[GitHub CI jobs](https://github.com/readyspace/rank-math-headless-bridge/actions/runs/38145676029)
passed for release source commit `0b10e047c8f0d6e1a2bb13fc8c1f16b5c4506557`.
This includes native PHP 7.4, 8.1, 8.3 and 8.4 regression jobs, the Next.js
production build, packaged WordPress 6.7/latest integration and official Plugin
Check on native PHP 8.3. The native Plugin Check job reported no errors. It runs
static checks; real-provider runtime tests remain separate.

The [WordPress.org readme validator](https://wordpress.org/plugins/developers/readme-validator/)
checked the public preparation branch's `readme.txt`. It reported no errors or
warnings, only notes about optional Upgrade Notice, Screenshots and donation
sections. Contributor `readyspace` and stable tag `0.2.0` appeared in the
validated readme. Directory submission and approval have not taken place.

## Earlier alpha validation

These checks use test sites and test pages to see whether the bridge returns the
right SEO and keeps private content out. They also check the HTML built by Next.js.
Passing them is useful evidence, but your own staging site still needs testing.
The sections below record the exact versions and remaining limits for developers.

These are isolated development results from 2026-10-11. They do not certify a
production migration or complete Rank Math feature coverage.

### Real WordPress and upstream providers

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

### Next.js rendering

Node 24.18.0 ran seven frontend contract/rendering tests. A Next.js 16.4.0,
React 19.3.0 production build and TypeScript check passed. Generated HTML checks
confirmed the exact Rank Math title, one public canonical, robots/Googlebot/Bingbot
directives, repeated Open Graph images, one Twitter card, removal of inherited
social metadata, and safe JSON-LD script serialization.

This is a small App Router fixture, not a migration of an existing application.
It does not exercise a live GraphQL HTTP endpoint, deployment caching, CSP,
file-based social metadata overrides, or every supported Next.js release.

### Earlier packaging and remaining acceptance

The earlier alpha ZIP used an explicit allowlist and contained plugin source, license, changelog,
guides and frontend examples. Tests, development dependencies and upstream plugins
are excluded. The ZIP installed and activated in disposable WordPress 6.7.10.

Staging acceptance is still required for actual route mappings, Pro features,
multisite/multilingual sites, preview authentication, editor workflows and CMS
safeguards. Native IndexNow delivery, public key serving, WordPress Plugin Check,
large-query performance and a deployed frontend remain unverified here. No live
CMS configuration, public plugin-directory listing, or production site changed.

The prepared 0.2.0 package replaces that earlier packaging and Plugin Check
status. Site-specific staging acceptance, live IndexNow delivery, Pro features,
multisite/multilingual compatibility and deployed frontend testing still require
their own evidence. No WordPress.org submission or publication has taken place.
