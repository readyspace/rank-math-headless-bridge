# Normalized metadata API

## Request

```text
GET https://cms.example.com/wp-json/headless-bridge/v1/head?post_id=123
```

Enable the metadata module, configure the frontend origin and allowed public post types, use pretty WordPress permalinks, activate Rank Math, enable its Headless CMS Support, and ensure PHP DOM is installed. The endpoint accepts a positive integer `post_id`. It does not accept an arbitrary URL to fetch. When the module is disabled, its route is not registered and WordPress returns a route-not-found response.

This public read-only endpoint is limited to published, publicly readable, password-free posts in the configured public post types. It is not a preview API and does not expose draft or private content. Your frontend still needs its existing API or GraphQL query to load the article content and identify the corresponding WordPress post ID.

Rank Math remains the metadata provider. Its [native headless API](https://rankmath.com/kb/headless-cms-support/) returns head HTML; the bridge extracts supported fields into JSON for explicit rendering.

## Successful response

```json
{
  "postId": 123,
  "title": "Example article | Example Company",
  "description": "A short description of this example article.",
  "canonical": "https://www.example.com/news/example-article/",
  "robots": {
    "robots": "index, follow, max-image-preview:large",
    "googlebot": "index, follow",
    "bingbot": "index, follow"
  },
  "openGraph": [
    { "property": "og:type", "content": "article" },
    { "property": "og:url", "content": "https://www.example.com/news/example-article/" },
    { "property": "og:image", "content": "https://cms.example.com/wp-content/uploads/example.jpg" }
  ],
  "twitter": [
    { "name": "twitter:card", "content": "summary_large_image" }
  ],
  "jsonLd": [
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "url": "https://www.example.com/news/example-article/"
    }
  ]
}
```

This is an illustrative response, not a promise that every post has every tag.

| Field | Type | Handling |
| --- | --- | --- |
| `postId` | Integer | WordPress post ID requested. |
| `title`, `description`, `canonical` | String | Empty if that field is absent. Avoid rendering empty tags. |
| `robots` | Object | Optional `robots`, `googlebot`, and `bingbot` strings. Preserve intentional noindex directives. |
| `openGraph` | Array of `{property, content}` | Ordered tags; repeated properties can represent multiple images. |
| `twitter` | Array of `{name, content}` | Ordered Twitter card tags. |
| `jsonLd` | Array | Decoded JSON-LD objects or arrays, one per supported source script. |

There is no raw `head` HTML field in this API. Metadata and schema URLs are normalized according to the configured origin and eligible URL mapping. Media remains on its existing host when appropriate.

## Errors

Errors use WordPress REST's normal `code`, `message`, and `data.status` shape. Do not treat an error response as valid SEO metadata.

| Status | Meaning |
| --- | --- |
| 400 | Invalid request parameters, such as a missing or invalid post ID. |
| 404 | Unknown, excluded, non-public, or password-protected post. |
| 422 | Unsupported permalink or metadata that could not be parsed as usable provider output. |
| 503 | Configuration, a dependency, the native provider, or provider data is unavailable. |

## Frontend rendering

Request metadata during server rendering, static generation, or an equivalent framework build step and render the result in the initial HTML. Integrate it with your existing route-level SEO component; emitting a second set of conflicting canonical or robots tags can undo the intended result.

React escapes title and meta tag values when they are rendered as normal elements. For JSON-LD, serialize the decoded JSON and escape `<` before placing it in a script element, so embedded `</script>` text cannot close the element. Do not render provider HTML with an unrestricted HTML injection mechanism. The [Next.js examples](../examples/nextjs/README.md) demonstrate this distinction.

Choose and test a frontend cache policy that fits your content publishing workflow. A long metadata cache can continue serving old titles, canonicals, or robots after an editor changes them. The example requests uncached metadata to make initial validation straightforward; production caching and invalidation belong to the frontend deployment.

When the CMS noindex module is enabled, the metadata generation path excludes that CMS-only safeguard. It must still respect noindex intentionally set in Rank Math for an individual post or post type. Confirm both the API response and the rendered frontend page, rather than checking the WordPress editor alone.

## Current scope

The first version focuses on published posts and pages, including eligible public custom post types. It does not provide a generalized archive, taxonomy, author, search, 404, or authenticated preview metadata endpoint. Those routes require an explicit frontend implementation or a separately reviewed adapter.
