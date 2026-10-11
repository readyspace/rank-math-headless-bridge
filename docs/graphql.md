# Optional WPGraphQL integration

Enable **SEO through WPGraphQL** under Settings → Headless Bridge. Install
WPGraphQL and Rank Math separately, enable Rank Math's Headless CMS Support,
configure pretty permalinks and the public frontend origin, and choose the
public content types your frontend serves. PHP DOM is required. The REST
metadata module does not need to be enabled.

The bridge adds `headlessSeo` to WPGraphQL's `ContentNode` interface. Posts,
pages and custom post types registered with `show_in_graphql` inherit the field.
It uses the same computed Rank Math provider and URL normalization as REST.
It does not copy arbitrary post meta or expose Rank Math's private options.

```graphql
query PublicPostSeo($id: ID!) {
  post(id: $id, idType: DATABASE_ID) {
    databaseId
    title
    headlessSeo {
      postId
      title
      description
      canonical
      robots { robots googlebot bingbot }
      openGraph { property content }
      twitter { name content }
      jsonLd
    }
  }
}
```

Pass a numeric WordPress ID as the `$id` variable. `postId` matches the node's
`databaseId`. Custom post types use their own registered GraphQL root fields,
or a content-node query with the appropriate fragments.

## Response contract

Fields match the [REST metadata contract](metadata.md), except that `jsonLd`
contains one **JSON string** per schema script. This keeps the GraphQL type
stable when an editor changes Rank Math's schema type. Parse each string as
JSON, then safely serialize the decoded value when rendering. It is never
raw HTML. The App Router adapter's `normalizeHeadlessSeo` accepts both transports.
Open Graph and Twitter remain ordered lists so repeated images are preserved.

`headlessSeo` is nullable. It returns null for excluded post types, drafts,
private or password-protected posts, including when the caller is an administrator.
WPGraphQL first applies its own node permissions. If a public supported post's
provider fails, the field returns null and GraphQL reports an error; clients
must check `errors` even when the HTTP response is 200.

Authenticated preview SEO is deliberately outside this public contract. Keep
your existing preview provider. Do not mark this field previewable or reuse it
to expose draft metadata without a separately reviewed integration.

## Coexistence and performance

The bridge uses `headlessSeo` and `HeadlessBridge*` types rather than another
plugin's `seo` field or Rank Math types. Choose one frontend SEO owner even if
another GraphQL SEO extension is active. Compatibility with every such extension
has not been established by the current tests.

The resolver reuses generated metadata for a post within the current GraphQL
context. It does not create a persistent metadata cache. Rank Math's native
provider rebuilds WordPress query state per distinct post, so do not request
SEO for thousands of nodes in one query. Pagination and production benchmarks
are required for large builds.

The GraphQL module adds nothing when disabled and can be used without enabling
the bridge's REST route. If WPGraphQL is absent, the other modules remain usable.
Clear any external GraphQL schema cache after enabling or disabling the module.

Generation failures can be observed server-side through
`headless_bridge/metadata_generation_failed`, which receives the caught exception.
Keep diagnostics in private logs; the public transport returns a generic error.

## Current coverage

Published posts, pages and eligible custom post types are supported. Taxonomy,
author, archive, search, 404 and preview metadata are separate future work.
Sitemap serving, redirect execution, 404 reporting and cache-invalidation webhooks
are not implemented by this field.
