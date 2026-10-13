# Security

## Supported releases

Security fixes target the latest published 0.2.x release and the development
branch. The current published version is **0.2.1**, available on
[WordPress.org](https://wordpress.org/plugins/readyspace-headless-seo-bridge-for-rank-math/). Earlier alpha and
pre-directory builds are unsupported. Use the
[upgrade guide](docs/migration.md#upgrading-an-older-bridge-zip) to replace an older
ZIP while preserving your bridge settings.

## Report a vulnerability privately

Use this repository's **Security → Report a vulnerability** option if private vulnerability reporting is available. Include the affected version, the configuration required to reproduce the issue, the impact, and a minimal reproduction using a disposable site.

If private reporting is unavailable, ask the maintainers for a private reporting channel without including the vulnerability details. Do not open a public issue containing exploit instructions, authentication tokens, personal data, or a real site's configuration. Allow maintainers to investigate and coordinate a fix before public disclosure.

## Scope and configuration

The plugin processes SEO data and public URLs and can optionally change CMS indexing headers and WordPress mail behavior. Its public metadata endpoint is intended for published, publicly readable posts. Frontend examples must render this data safely rather than inject raw HTML returned by another service.

An IndexNow verification key is intentionally served publicly by the protocol. It is not a WordPress login credential. Nevertheless, use a site's real key only in that site's deployment and avoid placing it in bug reports or shared test fixtures.

The plugin does not create a login wall, secure an exposed CMS, or authenticate frontend previews. Use your normal WordPress access controls, updates, backups, and hosting security. A CMS noindex directive asks crawlers not to index pages; it does not prevent a person from opening them. WordPress mail suppression affects calls through WordPress mail handling and does not control independently delivered mail from external services.
