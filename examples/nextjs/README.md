# Next.js examples

**Start with [WPGraphQL + App Router](app-router/README.md).** It is our main
Next.js integration. Next.js asks for content and SEO together through WPGraphQL.
The WordPress plugin still works without WPGraphQL if you choose REST instead.

These files are examples for an existing Next.js app. The plugin does not copy
frontend files, build a site or deploy it for you. If you are setting up WordPress
first, use the [start guide](../../docs/quick-start.md).

Download or copy these examples from this GitHub repository. They are not
included in the lean WordPress plugin ZIP. Test the connection on staging before
changing your live frontend.

## Pick your path

| Your project | Guide |
| --- | --- |
| App Router with React 19 and WPGraphQL | [Main App Router guide](app-router/README.md) |
| App Router using REST for SEO | [REST alternative in the same guide](app-router/README.md#rest-alternative) |
| Older Pages Router app using REST | Steps below |
| IndexNow verification file | [IndexNow guide](../../docs/indexnow.md) |

## Pages Router: REST alternative

1. In WordPress, enable Rank Math **Headless CMS Support**. Configure the public
   website address and content types in the bridge. Enable **Published-page SEO endpoint**.
2. Copy `get-head-seo.mjs` and `SeoHead.jsx` into your frontend. Update imports for
   the folders you chose.
3. Set the server environment variable `WORDPRESS_CMS_URL`, for example
   `https://cms.example.com`. Use an address you control, not one supplied by a visitor.
4. In your existing `getServerSideProps` or `getStaticProps`, load the public page
   content, call `getHeadSeo(post.id)` and pass its result to `SeoHead`.
5. Check the initial page HTML for the correct title, canonical and other SEO tags.

`pages-router-demo.jsx` shows this for one fixed `EXAMPLE_WORDPRESS_POST_ID`.
Replace that fixed ID with the ID from your real page query. The REST SEO endpoint
does not return the article text or find a page by its frontend path.

These helpers fetch fresh data and stop on invalid responses. Decide how your
live site should handle failures and refresh any saved data. Keep one SEO
renderer per page. Use `next/head` only with Pages Router; App Router uses the
metadata API and our server component. See the
[Next.js Head guide](https://nextjs.org/docs/pages/api-reference/components/head).

## Other tasks

For IndexNow, start with a static key file in `public/`. The
[IndexNow guide](../../docs/indexnow.md) explains key changes and the optional
`indexnow-route.ts` example.

Draft and private previews stay with your existing preview tool. These examples
do not implement preview login or send WordPress passwords and nonces to Next.js.
