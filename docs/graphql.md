# GraphQL: the main Next.js integration

WPGraphQL lets Next.js ask WordPress for a page and its SEO in one request.
The bridge adds an SEO field called `headlessSeo` to supported content.

WPGraphQL is optional for the WordPress plugin, but required for this integration.
See the [setup guide](quick-start.md) before running the query below. The bridge's
REST endpoint can stay off. There is no automatic REST fallback.

## Ask for one post and its SEO

Run this in your GraphQL tool against your WordPress GraphQL address, usually
`https://cms.example.com/graphql`. Replace the example address with your own.

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

In the tool's **Variables** box, enter your numeric post ID as a string:

```json
{ "id": "123" }
```

Use a real published post ID instead of `123`. The page's `title` is its visible
WordPress title. `headlessSeo.title` is the search title computed by Rank Math.
`postId` should match `databaseId`.

Pages and custom content types have their own query fields. A developer can also
use `nodeByUri` to find content by its path. The [Next.js example](../examples/nextjs/app-router/get-content.ts)
does that. Custom types must be public, allowed in bridge settings and registered
with WPGraphQL using `show_in_graphql`.

## What the SEO fields mean

| Field | Meaning |
| --- | --- |
| `title` and `description` | The search title and short description created by Rank Math. |
| `canonical` | The preferred page address, when Rank Math provides one. |
| `robots` | Search-engine instructions, including deliberate `noindex`. |
| `openGraph` and `twitter` | Sharing tags. Lists keep repeated images in order. |
| `jsonLd` | Schema data. GraphQL returns one JSON **string** per schema script. |

Our Next.js helper reads each schema string with `JSON.parse` and safely writes it
into the page. REST returns the schema already decoded as objects or arrays.
Both APIs use the same Rank Math generator. See the [field reference](metadata.md)
for the shared data shape and the [glossary](glossary.md) for unfamiliar terms.

## When something goes wrong

| What you see | What to check |
| --- | --- |
| `headlessSeo` is an unknown field | Activate WPGraphQL, enable **SEO through WPGraphQL**, then clear any saved GraphQL schema in your tools or cache. |
| `post` is `null` | Check the post ID and whether the caller can read that post. Use a published public post for this test. |
| `headlessSeo` is `null` | Drafts, private posts, password-protected posts and excluded types do not get public bridge SEO, even for an administrator. |
| `headlessSeo` is `null` and the response has `errors` | Check Rank Math, Headless CMS Support, PHP DOM and the public website address. |
| CMS addresses or wrong paths appear | Check the bridge's public website address and any custom path mapping. Check the actual Next.js HTML too. |

A GraphQL response can have HTTP status `200` and still contain `errors`. Our
example checks those errors rather than treating broken SEO as a good result.
Keep your existing authenticated preview tool for drafts.

## Notes for developers

The field is on WPGraphQL's `ContentNode` interface. Its bridge-specific name
helps avoid clashes with another plugin's `seo` field. Still, choose one source
of SEO tags in Next.js so pages do not contain competing tags.

Repeated requests for the same post inside one GraphQL execution reuse its SEO.
That saved result is not kept between requests. Rank Math still generates a full
head for each distinct post, even if the query only selects its title. Use small
pages of results for large sites. A single GraphQL request is not a speed guarantee.

The generator calls Rank Math's local REST route inside PHP. This does not make
an extra network request or require the bridge's public REST endpoint to be on.
If WPGraphQL is absent, non-GraphQL bridge features can still work.

For server-side diagnostics, `headless_bridge/metadata_generation_failed` receives
a caught exception. Keep details in private logs. Public errors stay generic.

This field supports published posts, pages and eligible custom types. Category,
author, archive, search, 404 and preview SEO are future work. So are sitemaps,
redirect handling and automatic cache-update notices.
