# Configuration and hooks

Configure each WordPress installation independently. The plugin does not share settings or credentials between websites.

## Frontend origin and post types

Set the public frontend origin to an absolute HTTP(S) origin such as `https://www.example.com`. Do not include a content path, query string, fragment, or user credentials. Use HTTPS for IndexNow. The CMS and frontend must be separate, and the frontend host must be the one used by its canonical URLs and verification key.

Select only public post types that your frontend actually serves. The metadata endpoint and published editor links also require a published, publicly readable, password-free post. The default mapping retains each WordPress permalink's path and removes a CMS home subdirectory when present.

The bridge requires pretty WordPress permalinks for REST/GraphQL metadata, IndexNow, and published editor links. Plain query-string permalinks are not supported by those modules; configure and test a suitable permalink layout before enabling them.

Enable modules separately. Rank Math metadata and schema integrations depend on Rank Math being active; a missing provider should be treated as a setup error rather than replaced with fabricated SEO output.

## SEO and metadata

Enable Rank Math's **Headless CMS Support** and the bridge's metadata module to use `/wp-json/headless-bridge/v1/head?post_id=123`. The PHP DOM extension is required. A firewall must allow legitimate frontend requests to the CMS API.

The metadata endpoint accepts a post ID, not an arbitrary URL. It asks the local Rank Math provider for that post's metadata. The SEO module maps eligible site identity and content addresses, canonical URLs, and social page URLs to the frontend. It leaves protected backend paths, media, and unrelated origins intact.

CMS noindex is separate from a post's SEO intent. When the optional CMS safeguard is enabled, public CMS output receives noindex directives. During frontend metadata generation, that CMS-only override is excluded, while Rank Math's deliberate post and post-type directives remain applicable. Validate the actual response and generated frontend HTML on staging.

## Editor links and preview

The editor-link module maps eligible published links in the admin to the frontend. It does not rewrite authenticated previews, drafts, or password-protected content. Keep your existing Faust or other preview provider configured and test both **View** and **Preview** after any migration.

This module leaves REST post responses unchanged. Some block-editor View links are populated from those responses; verify the link immediately after saving or publishing in Gutenberg. Your existing frontend integration may need to supply that link.

## Mail suppression

Leave mail suppression disabled unless your architecture intentionally prevents WordPress from sending mail. Enabling it affects password resets, administrative notices, and other calls to `wp_mail`. Suppressed calls report failure rather than a successful delivery. It does not stop email sent directly by another service.

An application can explicitly allow individual mail calls through `headless_bridge/allow_cms_mail`. Avoid broad exceptions based on user-controlled fields. Test your recovery and notification workflows before enabling this option.

## Extension hooks

The initial extension points are:

| Filter | Input | Purpose |
| --- | --- | --- |
| `headless_bridge/frontend_url` | Mapped URL, original URL | Customize content path mapping. Results must remain valid content URLs on the configured frontend origin. |
| `headless_bridge/allow_cms_mail` | Boolean, `wp_mail` attributes | Permit a specific mail call when suppression is enabled. Default is false. |

For example, a frontend that places articles under `/articles/` rather than `/news/` can change the mapped path:

```php
add_filter( 'headless_bridge/frontend_url', function ( $mapped, $original ) {
    $parts = wp_parse_url( $mapped );
    $path  = $parts['path'] ?? '/';
    if ( 0 === strpos( $path, '/news/' ) ) {
        return preg_replace( '#/news/#', '/articles/', $mapped, 1 );
    }
    return $mapped;
}, 10, 2 );
```

This is an example, not an automatic route detector. Test the homepage, nested pages, custom post types, and canonical URLs. Do not redirect all published pages into an article route or append preview credentials. A custom mapping may need additional frontend or provider integration for metadata cases beyond the published-post endpoint.
