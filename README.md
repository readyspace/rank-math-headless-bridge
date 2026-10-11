# Headless Bridge for Rank Math

One configurable WordPress plugin for sites that use Rank Math in the CMS and a separate public frontend.

Instead of maintaining a different helper plugin for every website, install the same version on each WordPress site and give each installation its own frontend address and enabled modules. For example, one installation can connect `cms.example.com` to `www.example.com`, while another connects a different CMS to a different public website.

**Status: 0.2.0-alpha.1.** This implementation is for staging tests and community review. It is an independent, unofficial integration, not a Rank Math product. It does not bundle Rank Math. It does not install frontend code, deploy a website, migrate old helpers, or guarantee search-engine indexing.

## Optional modules

All modules start disabled. Choose only the features your site needs.

| Module | Purpose | Frontend work needed |
| --- | --- | --- |
| SEO URL mapping | Maps eligible CMS URLs in Rank Math structured data, canonicals, and social page addresses to the public frontend, preserving media and unrelated external URLs. | Inspect the rendered schema and your actual route layout. |
| Metadata endpoint | Exposes normalized SEO data for published, publicly readable posts through a read-only REST endpoint. | Render this data in server-generated page HTML. |
| WPGraphQL metadata | Adds optional `headlessSeo` to public content nodes using the same metadata provider. | Use the complete query fragment and an SEO renderer. |
| IndexNow adapter | Adapts Rank Math's IndexNow submissions and verification-file location to the public frontend. | Deploy the current verification key file on that frontend. |
| Published editor links | Sends eligible published **View** links in the WordPress admin to the frontend. | Keep your existing authenticated preview integration. |
| CMS noindex | Adds noindex directives to the CMS while keeping frontend metadata generation separate. | Check the public frontend's robots directives independently. |
| WordPress mail suppression | Stops mail sent through `wp_mail`, unless an application explicitly allows a call. | Confirm that disabling CMS mail suits your workflow. |

An ordinary WordPress website that already serves its public pages generally does not need this bridge. A headless site may already implement some of these features in its frontend or other plugins; avoid enabling overlapping implementations without testing.

## Requirements

- WordPress 6.7 or newer and PHP 7.4 or newer. Current Rank Math 1.0.280 requires WordPress 6.7.
- Rank Math installed separately for SEO and IndexNow modules.
- Rank Math **Headless CMS Support** enabled for REST or GraphQL metadata, and the PHP DOM extension available.
- WPGraphQL installed separately if the optional GraphQL module is enabled. REST-only installations do not need it.
- A separate configured frontend origin. Use its exact public origin, for example `https://www.example.com`, without an article path, query string, or credentials.
- A frontend that renders SEO data on the server. The WordPress plugin alone cannot place tags in a Next.js page.
- Pretty WordPress permalinks for REST/GraphQL metadata, IndexNow, and editor-link modules.
- For IndexNow, Rank Math's Instant Indexing module and settings configured, a standard HTTPS frontend host, and a publicly accessible verification file on that host.

Rank Math's [headless support documentation](https://rankmath.com/kb/headless-cms-support/) explains how to enable its native metadata provider. The bridge normalizes that provider's output; it does not replace Rank Math's SEO editor.

## Install and configure

1. Back up your CMS and test on staging, especially if other headless helper plugins are already active.
2. Put this repository's plugin folder at `wp-content/plugins/rank-math-headless-bridge`, or upload a release ZIP through WordPress.
3. Activate **Headless Bridge for Rank Math**.
4. Open **Settings → Headless Bridge**. Set the frontend origin and choose the public post types that exist on the frontend.
5. Enable modules individually and validate each one. Follow the [migration guide](docs/migration.md) when replacing existing plugins.
6. Integrate [REST metadata](docs/metadata.md) or [WPGraphQL metadata](docs/graphql.md) into the frontend and, if needed, deploy the [IndexNow key file](docs/indexnow.md).

The default route mapping preserves the path: `https://cms.example.com/news/article/` becomes `https://www.example.com/news/article/`. If your frontend uses different routes, supply an explicit mapping and test it. This plugin does not discover a framework's route configuration automatically. WordPress's stored Home and Site URL settings are not changed.

## Frontend example

For a public post with WordPress ID `123`, request:

```text
https://cms.example.com/wp-json/headless-bridge/v1/head?post_id=123
```

The response contains normalized title, description, canonical, robots, Open Graph, Twitter, and JSON-LD fields. Drafts, private posts, password-protected posts, and excluded post types are unavailable through this endpoint. See [the response contract](docs/metadata.md) and the [Next.js examples](examples/nextjs/README.md).

The examples render explicit React tags and safely serialize JSON-LD. They do not inject Rank Math's raw head HTML. Keep the SEO request in your frontend's server-side rendering or build flow; fetching tags only after a browser loads the page is not an equivalent integration.

## Important configuration choices

**CMS noindex is a crawler directive, not access control.** It does not hide or authenticate the CMS. An intentional Rank Math noindex setting on a post must still be respected by the frontend.

**Mail suppression blocks WordPress mail, including password resets and administrative notices.** Leave it disabled unless that is intentional and you have a working recovery process. Mail sent independently by an external service is outside this module's control.

**Preview authentication remains with your preview provider.** The published editor-link module does not forward WordPress nonces or passwords to the frontend and does not replace Faust's preview flow.

**An accepted IndexNow submission is a notification, not proof of indexing.** The key file must live on the host being submitted. The [IndexNow protocol documentation](https://www.indexnow.org/documentation) describes verification and response codes.

## Documentation and contributions

- [Configuration and hooks](docs/configuration.md)
- [Normalized metadata API](docs/metadata.md)
- [Optional WPGraphQL integration](docs/graphql.md)
- [Next.js App Router adapter](examples/nextjs/app-router/README.md)
- [Development roadmap](docs/roadmap.md)
- [IndexNow frontend setup](docs/indexnow.md)
- [Controlled migration and rollback](docs/migration.md)
- [Next.js examples](examples/nextjs/README.md)
- [Validation results and remaining staging checks](docs/validation.md)
- [Contributing](CONTRIBUTING.md), [security reporting](SECURITY.md), and [changelog](CHANGELOG.md)

The initial implementation generalizes the roles of site-specific SEO, IndexNow, editor-link, and CMS safeguard helpers. It is not a byte-for-byte consolidation of every existing plugin or a completed migration of any live site. Real-world compatibility reports, tests, framework adapters, and staged migration guides are welcome.

## License

Original project code is licensed under [GPL-3.0-or-later](LICENSE). Rank Math and framework dependencies remain separate projects with their own licensing and trademarks.
