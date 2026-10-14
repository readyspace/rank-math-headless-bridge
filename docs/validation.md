# Validation

## Version 0.4.0 candidate — 2026-10-14

The 0.4.0 package is prepared for publication; it has not yet been published or
installed on the live CleverSpeed CMS. The live MU helpers remain until the
published package, settings and replacement ownership pass deployment checks.

The exact packaged runtime passed disposable WordPress **6.7.10** and **7.1.3**
integration on PHP **8.3.33**, using Rank Math **1.0.280** and WPGraphQL **2.23.1**.
Each run passed 104 settings/mapping/safeguard assertions, 66 authenticated
editor/preview assertions, 116 metadata regressions, 83 IndexNow policies and
92 real WordPress integration checks. Existing raw settings and the native
IndexNow key/history survived activation. Fresh requests verified explicit
helper opt-in, provider ownership, public REST/GraphQL stability and opt-out.
Mail checks used filters without SMTP, and all test posts were disposable fixtures.

The settings and focused editor suites also passed PHP 7.4, 8.1, 8.3 and 8.4.
Frontend example unit tests passed. CI also runs native PHP, WordPress.org Plugin
Check and the existing Next.js production fixture before release acceptance.

Live acceptance must compare authenticated Faust preview routing, Gutenberg
links, CMS mail/core-sitemap filter ownership, existing frontend SEO/media,
IndexNow key/history and the public key file. No live test posts, messages or
IndexNow submissions are required. This adds preview destination handling;
Faust or another provider still owns preview authentication and draft access.

## Version 0.3.0 — 2026-10-14

**ReadySpace Headless SEO Bridge for Rank Math 0.3.0 is published on
WordPress.org.** Account `readyspace` committed it in SVN revision **3745065**
on October 14, 2026 at **07:47:34 SGT**. The directory identifies 0.3.0 as its
current version. All **13 files** in the
[public ZIP](https://downloads.wordpress.org/plugin/readyspace-headless-seo-bridge-for-rank-math.0.3.0.zip)
match the tested release manifest and the official SHA-256 and MD5 checksums.
The release is available in
[`tags/0.3.0`](https://plugins.svn.wordpress.org/readyspace-headless-seo-bridge-for-rank-math/tags/0.3.0/).

| Archive | SHA-256 |
| --- | --- |
| Tested 0.3.0 ZIP | `8ba8eb71d3e35a9fe9723e7337d5560aaf8373190d808729c31a6070a7584d35` |
| Public WordPress.org 0.3.0 ZIP | `43812e5660eea2c9d7438fcf1e62656072bc41b041492cb762ce79ffd2a3efc2` |

WordPress.org repacked the ZIP; the individual release files are identical.
The following checks passed for source commit
`c0d41869aaec53d0649600d0988049d875d04d0a` in
[PR #4](https://github.com/readyspace/rank-math-headless-bridge/pull/4), with
[all eight push jobs](https://github.com/readyspace/rank-math-headless-bridge/actions/runs/38481544019)
and all eight pull-request jobs passing:

- The packaged ZIP installed and activated on WordPress **6.7.10** and
  **7.1.3**, PHP **8.3.33**, Rank Math **1.0.280** and WPGraphQL **2.23.1**.
  Each run passed **81 integration**, **84 settings/mapping**, **116 metadata**
  and **83 IndexNow** assertions, followed by disabled GraphQL, absent
  WPGraphQL and uninstall checks.
- Native PHP **7.4**, **8.1**, **8.3** and **8.4** jobs passed syntax checks,
  the same standalone settings/metadata/IndexNow suites and **seven** additional
  missing-native IndexNow assertions.
- **Seven** frontend tests passed. A **Next.js 16.4.0 / React 19.3.0** production
  build passed TypeScript and generated HTML checks for titles, canonicals,
  robots, social tags and safe JSON-LD.
- Official **Plugin Check 2.1.0** ran against the installed package in native
  CI and returned **“Success: Checks complete. No errors found.”** This run
  produced no JSON findings report; the saved
  [plugin-check-report.json](plugin-check-report.json) identifies the historical
  0.2.1 package and must not be used as evidence for the 0.3.0 ZIP.

IndexNow fixtures verified current-key access, configured key paths, native CMS
key-serving context, URL aliases, exclusions, slash policy, whole-batch
rejection and final HTTP validation. Real Rank Math `Api::submit()` calls
exercised its manual-console bypass with outgoing HTTP intercepted in the
disposable installation. No external IndexNow submission was made by these
checks. Key, module settings, automatic content types and existing history were
checked for preservation.

The v1 GraphQL and REST SEO contracts remain unchanged. Package checks do not
certify every live migration, external IndexNow delivery or frontend key-file
reachability. Full provider integration remains tested on PHP 8.3; standalone
PHP 7.4 checks do not establish real-provider PHP 7.4 support. Test your own
route mappings, previews, safeguards and deployed frontend before enabling or
replacing helpers. The records below describe earlier releases.

## Publication checks — 2026-10-13

**ReadySpace Headless SEO Bridge for Rank Math 0.2.1 is published on
WordPress.org.** The review team confirmed the replacement slug and approved
the plugin on October 13, 2026. Account `readyspace` then uploaded the release
in SVN revision **3743846**, with matching files in `trunk` and `tags/0.2.1`.

- [Public plugin listing](https://wordpress.org/plugins/readyspace-headless-seo-bridge-for-rank-math/)
- [WordPress.org download for version 0.2.1](https://downloads.wordpress.org/plugin/readyspace-headless-seo-bridge-for-rank-math.0.2.1.zip)
- [Published SVN tag](https://plugins.svn.wordpress.org/readyspace-headless-seo-bridge-for-rank-math/tags/0.2.1/)

All **13 files** in the public download match the approved submission ZIP.
WordPress.org repacked those same files, so the archive hashes differ:

| Archive | SHA-256 |
| --- | --- |
| Approved submission ZIP | `7df1c622474dd9baddca071cf74603e2ff5587c57294ea4338072219c0e5e752` |
| Public WordPress.org ZIP | `d98c66110e0b77c99067e9c5fdead4c00427079cc41cd48c6c00afdbae2bb0bd` |

The saved Plugin Check report identifies the approved submission ZIP. The
comparison confirms that the public download contains the same checked release
files; it does not add new runtime test coverage. Publication does not mean
Rank Math endorses this independent project or that every website setup has
been tested. The checks below record what passed and what remains unverified.

## Corrected name and package — 2026-10-12

**ReadySpace Headless SEO Bridge for Rank Math 0.2.1** uses the folder and text
domain `readyspace-headless-seo-bridge-for-rank-math`. WordPress.org's October 12
preliminary name review prompted this change. The review team confirmed this
name and slug on October 13; publication is recorded above.

The approved ZIP `readyspace-headless-seo-bridge-for-rank-math-0.2.1.zip` contains
exactly 13 allowlisted files. At validation time, every archive entry matched its
release source. Later GitHub documentation updates do not change this tested ZIP.
Its SHA-256 is
`7df1c622474dd9baddca071cf74603e2ff5587c57294ea4338072219c0e5e752`.
The saved [Plugin Check report](plugin-check-report.json) identifies this ZIP.
Official Plugin Check 2.1.0 ran all 29 non-experimental static checks on WordPress
7.1.3 and PHP 8.3.33: **zero errors and zero warnings**. No checks or result
codes were excluded; optional AI analysis was not enabled.

The same final ZIP installed and activated on WordPress **6.7.10** and
**7.1.3**, with Rank Math **1.0.280**, WPGraphQL **2.23.1** and PHP **8.3.33**.
Each run passed 55 integration, 63 settings/mapping and 115 metadata/Rank Math
assertions, syntax checks for all nine PHP files, disabled GraphQL, absent
WPGraphQL and uninstall checks. A new assertion verified that activating the
renamed package preserved previously saved bridge settings exactly, before the
other tests changed those settings. This does not simulate both old plugin
folders remaining on disk or certify a live migration. The guide explains how
to replace either old folder without running its uninstaller.

The namespace, option name, REST route, GraphQL fields and developer hooks are
unchanged. Runtime differences are limited to the name, text domain and version.
Seven frontend tests passed. A Next.js 16.4.0/React 19.3.0 production build,
TypeScript checks and generated HTML assertions passed on Node 24.19.0.

The official online readme validator reported no errors or warnings for the
new display name and stable tag 0.2.1. Only optional Upgrade Notice, Screenshots
and donate-link notes remain. The Rank Math affiliate disclosure, ReadySpace
introduction and independent-project credits remain in the packaged readme.

The first local WordPress attempts stopped at a download timeout before testing
plugin code. Downloading the official dependencies and using IPv4-first Node
connections resolved this; the successful runs used the unchanged test suites
and real upstream plugins. All eight push and eight pull-request CI jobs passed
for source commit `d82da4016908eae5eac8aa4bd9aa234d4b8350a6` in
[PR #3](https://github.com/readyspace/rank-math-headless-bridge/pull/3), which was
merged into main. The exact checked ZIP was uploaded to the existing WordPress.org
submission on October 12. The page and automated email confirmed receipt of
version 0.2.1. A reply in the original review thread requested the replacement
slug. WordPress.org confirmed the replacement slug and approved the plugin on
October 13, before the release was published. The sections below are historical
0.2.0/alpha evidence.

## Historical 0.2.0 preparation checks — 2026-10-11

The installable `headless-bridge-for-rank-math-0.2.0.zip` contains 13 files:
nine runtime PHP files, `readme.txt`, `LICENSE`, `CHANGELOG.md` and `credits.txt`.
Every archived file was compared with its source. Tests, examples, development
dependencies, build caches and the GitHub README are excluded. The packaged
`readme.txt` includes the disclosed Rank Math affiliate link and ReadySpace
introduction used in the proposed directory description.

Official Plugin Check **2.1.0** ran all **29 non-experimental static checks**
against the installed ZIP on WordPress **7.1.3**, PHP **8.3.33**:
**zero errors and zero warnings**. The [original 0.2.0 report](https://github.com/readyspace/rank-math-headless-bridge/blob/15f0f837fed83eb4b0131ca9aa15b596c0339b83/docs/plugin-check-report.json)
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
checked the final packaged `readme.txt`. It reported no errors or
warnings, only notes about optional Upgrade Notice, Screenshots and donation
sections. Contributor `readyspace` and stable tag `0.2.0` appeared in the
validated readme. Version 0.2.0 was submitted on October 11, 2026;
WordPress.org requested a distinctive name on October 12. Version 0.2.0 was
replaced by the renamed 0.2.1 release, approved and published on October 13.

After adding the ReadySpace introduction, author website and disclosed Rank Math
affiliate link, the ZIP was rebuilt and all 29 static checks passed again with
zero errors and warnings. The saved report's SHA-256 identifies that updated
ZIP. The final readme was also validated again; the same optional notes remain.
These changes affect documentation and the author header, not plugin behavior.

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

Rank Math 1.0.280 requires WordPress 6.7. A WordPress 6.5 attempt could not load
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

Those alpha checks did not cover native IndexNow delivery, public key serving,
WordPress Plugin Check, large-query performance or a deployed frontend. They did
not change a live CMS or production website. Later package checks and directory
publication are recorded above.

The published 0.2.1 release replaces the earlier packaging. Plugin Check and
directory publication are complete. Site-specific staging acceptance still needs
its own evidence for actual route mappings, Pro features, multisite/multilingual
sites, preview authentication, editor workflows, CMS safeguards, live IndexNow
delivery, public key serving, large-query performance and a deployed frontend.
Publishing the plugin did not configure or migrate a live WordPress/Next.js site.
