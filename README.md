# Headless Bridge for Rank Math

Bring Rank Math's SEO settings from WordPress to a separate Next.js website.

You write and edit in WordPress. Visitors read your Next.js website. This plugin
helps Next.js use the page title, description, search instructions and sharing
images that Rank Math creates.

**Start here: [WordPress + WPGraphQL + Next.js setup](docs/quick-start.md).**

Our main Next.js example uses **WPGraphQL** to ask for a page and its SEO data in
one request. WPGraphQL is optional for the WordPress plugin. Developers who use
REST can choose the [REST alternative](docs/metadata.md). Nothing switches APIs
automatically, and every bridge feature starts turned off.

**Version: 0.2.0.** Prepared for the first public release, with the supported
features and limits below. The WordPress.org listing still needs submission and
review. Test on a copy of your site first and read
[what has been tested](docs/validation.md) before changing a live site.

## What is Rank Math?

Rank Math is an SEO plugin for WordPress. It helps you set the titles and short
descriptions search engines use, choose which pages can appear in search results,
and add structured information about your content. It also helps prepare the
previews people see when they share a link.

You manage these settings in WordPress with Rank Math. This bridge helps your
separate Next.js website use the SEO data that Rank Math creates. Install Rank
Math separately before setting up the bridge.

[Explore Rank Math (affiliate link)](https://rankmath.com/?ref=super%20salesman)

**Affiliate disclosure:** This is an affiliate link. If you make a qualifying
purchase through it, this project's owner may earn a commission.

## About ReadySpace

[ReadySpace](https://readyspace.com) provides cloud and hosting services for
businesses. ReadySpace and community contributors develop and share this free,
open-source bridge. You can use it with your chosen hosting provider; a
ReadySpace account is not required.

## How it works

1. **WordPress** stores your posts and pages.
2. **Rank Math** creates their SEO data from your settings.
3. **This bridge** prepares that data and fixes supported page addresses.
4. **WPGraphQL** sends the requested page and SEO data to Next.js.
5. **Next.js** puts the SEO tags in the page it sends to visitors and search engines.

Example: a post edited at `cms.example.com/news/hello/` has its preferred public
page address set to `www.example.com/news/hello/`. Images can stay on the CMS.
The bridge keeps the path unless a developer adds a custom route mapping.

Rank Math still owns the SEO settings and generates the data. The bridge uses
its native headless API inside WordPress, including when Next.js uses GraphQL.
This internal call does not make a second network request.

New to these terms? Read the [short glossary](docs/glossary.md).

## What you need

- WordPress **6.7 or newer** and PHP **7.4 or newer**.
- Rank Math installed and active. Turn on its **Headless CMS Support** option.
- PHP **DOM**, a server feature used to read SEO tags. Ask your host if it is available.
- A Next.js site that builds pages on the server. Our main example uses App Router and React 19.
- WPGraphQL installed and active **if you choose the main GraphQL setup**.
- WordPress links such as `/news/hello/`, rather than plain `?p=123` links.

The plugin cannot place tags in Next.js by itself. You also need to add the
frontend example to your Next.js project. The [setup guide](docs/quick-start.md)
walks through both sides.

## Installing the prepared release

The WordPress ZIP installs into `wp-content/plugins/headless-bridge-for-rank-math/`.
It contains the WordPress plugin and its licence, changelog and credits.
The setup guides and Next.js examples stay in this GitHub repository; they are
not copied into your frontend or included in the lean WordPress ZIP.

If you already installed an older ZIP under `rank-math-headless-bridge/`, follow
the [folder-change steps](docs/migration.md#upgrading-an-older-bridge-zip) before
activating this version. Keep only one bridge active.

## Choose the features you need

Open **Settings → Headless Bridge** in WordPress. All features start off.

| Feature in WordPress | What it does |
| --- | --- |
| SEO through WPGraphQL | Main Next.js path: adds `headlessSeo` to your content queries. Requires WPGraphQL. |
| Published-page SEO endpoint | REST alternative: Next.js asks for SEO using a WordPress post ID. |
| Correct Rank Math schema addresses | Uses public page addresses in supported SEO data. Keeps image addresses where they belong. |
| IndexNow frontend adapter | Helps Rank Math notify supported search engines about your public website's changed pages. |
| Public View links in the editor | Opens eligible published pages on your public website. |
| Keep the CMS out of search results | Adds `noindex` instructions to CMS pages. It does not add a login or block access. |
| Suppress WordPress email | Stops WordPress emails, including password resets. Leave this off if you need those emails. |

For the main setup, enable **SEO through WPGraphQL** and **Correct Rank Math
schema addresses**. You can leave the REST endpoint off. Enabling either API
never publishes drafts, private posts or password-protected posts through the
bridge's SEO field.

## Current limits

This release handles published posts, pages and supported custom content types.
It does not yet cover every Rank Math feature. Category/archive SEO, sitemaps,
redirects, 404 reporting and automatic frontend cache updates are future work.
Authenticated draft previews stay with your existing preview tool.

GraphQL can combine content and SEO in one request. It does not guarantee a faster
website. We have tested the results, but have not measured GraphQL against REST.

## Guides

- [Start here: WordPress, WPGraphQL and Next.js](docs/quick-start.md)
- [Words used in these guides](docs/glossary.md)
- [Next.js examples](examples/nextjs/README.md)
- [GraphQL query and troubleshooting](docs/graphql.md)
- [Plugin settings and developer hooks](docs/configuration.md)
- [REST alternative](docs/metadata.md)
- [IndexNow setup](docs/indexnow.md)
- [Replacing older helper plugins](docs/migration.md)
- [Tests and limits](docs/validation.md)
- [Preparing the WordPress.org listing](docs/wordpress-org-submission.md)
- [What we plan to build next](docs/roadmap.md)
- [Contributing](CONTRIBUTING.md), [reporting security issues](SECURITY.md) and [changes](CHANGELOG.md)

## Open source and credits

This project is open source under [GPL-3.0-or-later](LICENSE). You can use, study,
change and share it under that license. If you share a changed version, follow
the same license and include its source code and required notices.

**Thank you to the Rank Math team and contributors** for building Rank Math and
its Headless CMS Support. Their work makes this bridge possible. Visit
[Rank Math](https://rankmath.com/), their
[official source repository](https://github.com/rankmath/seo-by-rank-math), and
[headless setup guide](https://rankmath.com/kb/headless-cms-support/).

This is an independent community project. Credit does not mean Rank Math has
reviewed, approved or agreed to support it. Rank Math is installed separately;
its source code is not bundled here. See [all credits](CREDITS.md).
