# Main Next.js setup: WPGraphQL + App Router

This example uses WPGraphQL to get a page's title and SEO together. WPGraphQL is
required for this path, but optional for the WordPress plugin. REST is available
as a separate choice below. There is no automatic switch between them.

Use an existing Next.js App Router app with React 19. Set up WordPress first with
[the start guide](../../../docs/quick-start.md).

The example files live here on GitHub and are not included in the WordPress ZIP.
Use a staging copy of your frontend for the first connection.

## 1. Copy the helpers

For a project that uses a `src/` folder, copy these files into
`src/lib/headless-bridge/`:

- `metadata.ts`: reads the SEO data and prepares Next.js metadata.
- `HeadlessSeoTags.tsx`: writes sharing tags and schema data.
- `get-content.ts`: asks WordPress for the page title and SEO.

Keep the three files together; their imports work that way. If your project has
no `src/` folder, use `lib/headless-bridge/` instead.

## 2. Set the WordPress GraphQL address

Add this to your Next.js `.env.local`, using your own WordPress address:

```dotenv
WORDPRESS_GRAPHQL_URL=https://cms.example.com/graphql
```

Restart your local Next.js server after changing it. For a hosted app, add the
same variable to your hosting settings and redeploy. Use HTTPS and an address
you control. Do not take the CMS address from a visitor's input.

## 3. Connect a page route

If your app already has a page route, add the helper calls to that route.
Do not replace an existing route or create a conflicting one.

For a new catch-all example route, copy `page.tsx` to
`src/app/[...slug]/page.tsx`. A catch-all route accepts paths with one or more
parts, such as `/news/hello/`. Update its three helper imports to:

```tsx
import { getContent } from '../../lib/headless-bridge/get-content';
import { toNextMetadata } from '../../lib/headless-bridge/metadata';
import { HeadlessSeoTags } from '../../lib/headless-bridge/HeadlessSeoTags';
```

Keep the `notFound` import from `next/navigation`. For a project without `src/`,
use `app/[...slug]/page.tsx`; the relative imports above still work.

The page example calls `toNextMetadata(page.seo)` in `generateMetadata` and
renders `<HeadlessSeoTags seo={page.seo} />` once. Next.js builds SEO tags on the
server, before the visitor's browser runs JavaScript.

It displays the page's title only. Add your existing article renderer and content
query to display the full article. This is a connection example, not a full website.

## 4. Check your paths

The example expects WordPress and Next.js to use the same page paths:

- WordPress: `https://cms.example.com/news/hello/`
- Next.js: `https://www.example.com/news/hello/`

If Next.js uses a different path, your developer must map it back to the CMS path
before calling `nodeByUri`. The bridge does not discover Next.js routes.
The `[...slug]` example does not cover `/`; connect the homepage separately.

## 5. Build and check a published page

Run your app's usual production build, then open a published test page and use
**View Page Source**. Check the title, description, canonical, robots, sharing tags
and schema. Use the checklist in the [start guide](../../../docs/quick-start.md#6-check-the-actual-page).

Remove competing SEO tags from other page or layout components. Our adapter
clears inherited Open Graph and Twitter metadata, but Next.js files such as
`opengraph-image` can take priority. Reconcile those files too.

## REST alternative

You can use this renderer without WPGraphQL. Keep your existing way of loading
content, enable **Published-page SEO endpoint** in WordPress, and fetch SEO with
`get-head-seo.mjs` from the parent example folder.

Pass the full response to `normalizeHeadlessSeo`, then use `toNextMetadata` and
`HeadlessSeoTags`. Check that `seo.postId` matches your content's WordPress ID.
Do not use `get-content.ts` for this path; that helper requires GraphQL.
See the [REST guide](../../../docs/metadata.md).

## Notes for developers

Keep the full `headlessSeo` selection from the helper. Partial data is rejected.
GraphQL schema strings and decoded REST schema use the same renderer.

The title is absolute because Rank Math has already applied its title template.
Social tags keep their order and repeated images. React 19 moves normal `<meta>`
elements into the document head. Schema is decoded and safely serialized; the
component never inserts Rank Math's raw head HTML. Pass a server-generated `nonce`
if your Content Security Policy requires one.

`getContent` shares repeated calls during one render with React `cache`. Its
network request uses `no-store`, a five-second timeout and no redirects. It checks
GraphQL errors and matching IDs. This avoids stale data while you test. It is
not a complete production cache plan. Arrange refreshes before adding a persistent
cache. Missing or invalid SEO throws an error; choose a clear live-site fallback.

To check this repository's example fixture, use Node 24.18+ and run `npm ci`,
`npm test` and `npm run test:next` from the repository root. The last command builds
a small app and checks its HTML. It does not certify a migration of your whole site.
