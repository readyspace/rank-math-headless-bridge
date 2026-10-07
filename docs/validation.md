# Initial validation

Version 0.1.0 is an alpha implementation, not a completed production migration.

The initial source was checked with PHP 8.3.12 CLI and the DOM extension in an
isolated temporary directory. No WordPress installation or live-site settings
were changed by these checks. All PHP source files passed syntax checks; the
settings/URL/editor/safeguard and metadata/IndexNow regression suites passed.
The latter uses small WordPress and Rank Math test doubles, not a real CMS.

Node.js 22.23.1 also passed three frontend-example tests covering script-boundary
escaping, unsafe URL rejection and post-ID validation before network access.
The installable package uses an explicit file allowlist. GitHub CI exercises
PHP 7.4, 8.1, 8.3 and 8.4 plus the frontend-example tests; consult the actual run
before assuming any matrix result has passed.

Staging acceptance remains required with a real WordPress/Rank Math provider,
your post types and route mappings, native IndexNow submission logs and a
deployed public verification key. Check canonical/schema output, per-page robots,
draft/password protection, Gutenberg View links, authenticated previews, key
rotation, and password-reset/notification mail. A full Next.js application build
and a migration of the existing site-specific plugins were not performed.
