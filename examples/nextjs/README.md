# Next.js examples

These are integration examples, not a complete frontend application. Adapt them to your existing routes, content fetching, preview provider, and deployment. They are not automatically installed by the WordPress plugin.

## Server-rendered metadata with the Pages Router

1. Configure the WordPress plugin's frontend origin, allowed public post types, and metadata module. Enable Rank Math's Headless CMS Support.
2. Copy `get-head-seo.mjs` into your frontend's server-side data layer and `SeoHead.jsx` into its SEO components, adjusting imports.
3. Set a trusted server-side `WORDPRESS_CMS_URL`, for example `https://cms.example.com`. Do not take this address from a visitor's query string.
4. In the page's existing `getServerSideProps` or `getStaticProps`, fetch the published content, call `getHeadSeo(post.id)`, and pass the result to `SeoHead`.
5. Check the initial page HTML for one correct title, canonical, robots set, social tags, and JSON-LD. Confirm the actual content matches the post ID used.

`pages-router-demo.jsx` uses `EXAMPLE_WORDPRESS_POST_ID` to demonstrate server rendering for a fixed public post. Replace this demonstration with your route's real content query; the metadata endpoint does not return article content or resolve a frontend slug.

The helper requests uncached data, sets a timeout, refuses redirects, and checks the response contract. Choose a deliberate production caching and failure policy. For example, static generation should fail visibly when critical SEO data is unavailable rather than silently deploy empty tags. A live route may use a previously validated cache or existing safe fallback if your application implements one.

`SeoHead` renders title and tag values as React elements, uses an HTTP(S)-only canonical attribute, preserves repeated Open Graph properties, and escapes script-boundary characters in serialized JSON-LD. Its JSON-LD script uses HTML insertion only for the safely serialized JSON; it never inserts Rank Math's raw head HTML. Review your Content Security Policy and add a server-generated script nonce if your deployment requires one.

Avoid competing SEO owners in layout and page components. Next.js's [Head documentation](https://nextjs.org/docs/pages/api-reference/components/head) describes this component's behavior. This component is for the Pages Router. An App Router application should adapt the normalized contract through its metadata API and a safe JSON-LD component rather than copy `next/head` into server components.

## IndexNow verification file

The simplest integration is a static `public/<CURRENT_KEY>.txt` file containing Rank Math's current key. Deploy it and check `https://www.example.com/<CURRENT_KEY>.txt` returns plain text with status `200`.

If using a runtime route, `indexnow-route.ts` is an optional App Router example following Next.js's [Route Handler API](https://nextjs.org/docs/app/api-reference/file-conventions/route). It uses asynchronous route parameters as in current Next.js versions. Adapt the signature for your installed version and run that frontend's normal type checks and build.

Set the server-side `INDEXNOW_KEY` to the current key for that site's WordPress installation. The route serves only an exact `<KEY>.txt` path. An unknown key returns `404`; the route performs no CMS fetch and is not an arbitrary URL proxy. Rotation requires updating frontend configuration and deploying or restarting it as appropriate.

Do not blindly add a root dynamic route if your application already has a root `[slug]` route or uses the Pages Router for those paths. Use the static file or deliberately integrate an exact-key check into an existing reviewed route. Ensure middleware and authentication allow the verification URL publicly. See [the full setup guide](../../docs/indexnow.md).

## Preview and draft content

The public metadata endpoint intentionally excludes drafts, private posts, and password-protected content. Keep authenticated preview routing in your current provider. These examples do not implement preview login, forward WordPress nonces, or change Faust's authentication flow.
