# Start here: WordPress + WPGraphQL + Next.js

This guide connects an existing WordPress site to an existing Next.js site.
WordPress is where you edit. Next.js is the website visitors see.

Use a test copy of your site first. This plugin is still an alpha, which means
it is an early version. Someone who manages your Next.js code must complete the
frontend steps. You do not need to understand every line of code to follow the plan.

## 1. Check your starting point

You need WordPress 6.7+, PHP 7.4+, Rank Math and a Next.js App Router project using React 19. Your WordPress server also needs PHP DOM. Ask your host to check that last item if you do not manage the server.

We use example addresses throughout:

| Address | Purpose |
| --- | --- |
| `https://cms.example.com` | WordPress, where you write and edit |
| `https://www.example.com` | Next.js, where visitors read |

Replace them with your own addresses. Keep the WordPress Home and Site URL
settings as they are; configure the public website in the bridge instead.

## 2. Install the WordPress plugins

1. Install and activate **Rank Math SEO**.
2. Install and activate **WPGraphQL**.
3. Upload the bridge ZIP through **Plugins → Add New Plugin → Upload Plugin**.
4. Activate **Headless Bridge for Rank Math**.

WPGraphQL is required for this setup, but remains optional for the bridge as a
whole. If your project uses REST, follow the [REST guide](metadata.md) instead.
Missing WPGraphQL does not cause an automatic switch to REST.

## 3. Set up WordPress

1. In **Settings → Permalinks**, use readable links such as **Post name**.
   On an existing site, have your developer plan any link changes before saving.
2. Switch Rank Math to **Advanced Mode** if needed. Open
   **Rank Math SEO → General Settings → Others** and enable **Headless CMS Support**.
   [Rank Math's own guide](https://rankmath.com/kb/headless-cms-support/) shows this setting.
3. Open **Settings → Headless Bridge**.
4. Enter your **Public website address**, for example `https://www.example.com`.
   Enter only the address, with no `/news/hello/` path after it.
5. Select the **Public content types** that your Next.js site actually displays.
6. Enable **SEO through WPGraphQL** and **Correct Rank Math schema addresses**.
7. Leave **Published-page SEO endpoint** off unless you also want REST.
   Leave other optional features off for this first test. Save changes.

## 4. Test one published post

Pick a public post with no password. In its Rank Math settings, give it a title
and description you can easily recognize, then save it.

Run the complete query in the [GraphQL guide](graphql.md). Use that post's numeric
WordPress ID. You can find it in an editor URL such as `post.php?post=123&action=edit`.
The ID is `123` in that example.

You should receive `headlessSeo` with your chosen title and description. Its
`postId` should match the post's `databaseId`. A canonical address, when Rank Math
provides one, should point to the public website. A deliberate external canonical
is kept. Rank Math may omit a canonical on a page marked `noindex`.

A draft, private post or password-protected post will not have public bridge SEO.
If the test fails, use the [troubleshooting table](graphql.md#when-something-goes-wrong).

## 5. Connect your Next.js project

Follow the [App Router steps](../examples/nextjs/app-router/README.md).
They explain which files to copy, where to put them and how to set
`WORDPRESS_GRAPHQL_URL` to your WordPress GraphQL address.

The example fetches a page's title and its SEO together. It shows the title on
the page. Add your existing article renderer to show the full article as well.
The plugin does not build or deploy your Next.js site for you.

## 6. Check the actual page

Open the public Next.js page and use your browser's **View Page Source** option.
Check the HTML sent by the server, not just what the editor shows.

- Find the title and description you set in Rank Math.
- Find one `rel="canonical"` link when Rank Math supplies a canonical.
- Check `robots` instructions. A post deliberately marked `noindex` must keep it.
- Look for social sharing tags such as `og:image`.
- If the post has schema data, look for `application/ld+json`.
- Make sure a layout or another SEO component has not added competing tags.

If your CMS uses `/news/hello/`, this first example expects Next.js to serve
`/news/hello/` too. Different paths need an explicit mapping by your developer.
A normal catch-all route does not handle the homepage; set that up separately.

## 7. Add other features later

Once the first page works, consider [IndexNow](indexnow.md), published View links
or CMS `noindex`. These are separate choices. CMS `noindex` is a search-engine
instruction, not a way to make WordPress private.

Keep WordPress email enabled if you need password resets or notices. If replacing
older helper plugins, follow the [migration guide](migration.md) one feature at a time.

The current examples fetch fresh data. Before adding a long-lived cache, arrange
for it to refresh when editors change content or SEO. Automatic refresh notices
are on our [roadmap](roadmap.md).
