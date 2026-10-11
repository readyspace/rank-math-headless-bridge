# Next.js App Router adapter

Use these server-side examples with Next.js App Router and React 19. They do not
install frontend code automatically. The WordPress plugin and frontend must be
configured separately. The adapter accepts the bridge's complete REST response
or the complete `headlessSeo` GraphQL fragment in [the GraphQL guide](../../../docs/graphql.md).

## GraphQL content and SEO together

1. Enable the bridge's GraphQL module and Rank Math Headless CMS Support.
2. Copy `metadata.ts`, `HeadlessSeoTags.tsx` and `get-content.ts` into your frontend.
3. Set a trusted server-side `WORDPRESS_GRAPHQL_URL`, such as
   `https://cms.example.com/graphql`. Do not derive this address from user input.
4. Adapt `page.tsx` to your existing content route and update its imports.
   The example shows the title; integrate your existing content renderer there.
5. Call `toNextMetadata(seo)` from `generateMetadata` and render
   `<HeadlessSeoTags seo={seo} />` once in the corresponding server page.

The example assumes matching CMS/frontend paths and published content. If your
frontend uses different routes, implement an explicit reverse mapping before
querying `nodeByUri`. The bridge does not discover Next.js routes. Existing
authentication and draft-preview integration remain separate.

`getContent` queries content identity and SEO together. React `cache` deduplicates
calls by `generateMetadata` and the page during a render. The underlying fetch
uses `no-store`, a timeout and no redirects, and checks GraphQL errors and the
matching post ID. This is a correctness-first example, not a production cache
policy. Add cache invalidation before introducing persistent caching.

## REST alternative

Keep your existing WPGraphQL content query, then pass the bridge REST result to
`normalizeHeadlessSeo` and use the same renderer. Verify `seo.postId` matches the
content node's `databaseId`. The existing `get-head-seo.mjs` demonstrates a trusted
server-side REST fetch. Only the selected transport needs to be enabled.

## Rendering ownership

`toNextMetadata` supplies the computed title, description, canonical and robots.
It uses an absolute title because Rank Math already applies title templates.
It preserves robots strings, including Googlebot and Bingbot directives.

`HeadlessSeoTags` is a server component. React 19 hoists its normal `<meta>`
elements into the document head. It preserves ordered Open Graph/Twitter tags,
including repeated images and custom properties, without converting them to a
limited framework-specific social schema. JSON-LD is decoded, serialized and
escaped at the script boundary. Pass a server-generated `nonce` if your CSP
requires one. The component never injects Rank Math's raw head HTML.

Remove competing route-level SEO output. The metadata adapter clears inherited
Open Graph and Twitter metadata so the component owns those tags. File-based
metadata such as `opengraph-image` has higher priority in Next.js and must also
be reconciled if present. Do not fetch metadata only after browser hydration.

Malformed or missing SEO data throws rather than silently building a page with
empty metadata. Choose an explicit fallback policy for your live application.

## Verification

From the repository root, run `npm ci`, `npm test` and `npm run test:next` using
Node 24.18 or newer. The latter builds a small App Router fixture and checks its
generated HTML for an exact title, one canonical, public-page robots, repeated
social images and safe JSON-LD. These checks do not establish a full application
migration or compatibility with every Next.js version.
