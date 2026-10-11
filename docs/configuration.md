# Plugin settings

Each WordPress site has its own bridge settings. Start with the
[WordPress + WPGraphQL + Next.js guide](quick-start.md), then return here for extra options.

## Public website address

Enter the address visitors use, such as `https://www.example.com`.
Do not add a page path, query or login details. The public website and WordPress
must have separate origins. An origin is the beginning of an address, before a page path.

The default mapping keeps page paths. For example, CMS `/news/hello/` becomes
public `/news/hello/`. A CMS home subfolder is removed when needed.
Images and unrelated websites keep their own addresses.

## Public content types

Select only the content your Next.js site displays, such as posts and pages.
The SEO APIs also check that each item is published, public and has no password.
Use readable WordPress permalinks. Plain `?p=123` links are not supported by the
metadata, IndexNow and editor-link modules.

## Main setup: WPGraphQL

Activate WPGraphQL and Rank Math. Enable Rank Math **Headless CMS Support**.
Then turn on **SEO through WPGraphQL** and **Correct Rank Math schema addresses**.
The public REST endpoint can stay off. See the [query guide](graphql.md).

If WPGraphQL is missing, this GraphQL setup cannot work. The plugin does not
switch to REST on its own. Other enabled features can still work without WPGraphQL.
Both SEO APIs need PHP DOM on the WordPress server.

## REST alternative

Enable **Published-page SEO endpoint** and use the [REST guide](metadata.md).
Next.js asks for SEO using a public WordPress post ID. Rank Math **Headless CMS
Support** is still required. Choose one frontend SEO source, even if both APIs
are enabled. Your firewall must allow the API requests you intend to use.

## CMS search instructions

**Keep the CMS out of search results** adds `noindex` to CMS responses. It does
not protect the CMS with a login. While creating frontend SEO, the bridge leaves
out this CMS-only rule. A deliberate Rank Math `noindex` choice for a post or
content type still applies. Check both the API data and your Next.js page source.

## Editor links and previews

**Public View links in the editor** changes eligible published View links to
use the public website. Drafts and authenticated previews stay with your existing
preview tool. Test both **View** and **Preview** after changing helpers.

Some Gutenberg View links come from REST post responses, which this module does
not change. Check the link after saving or publishing; your frontend integration
may need to supply it.

## WordPress email

Leave **Suppress WordPress email** off if WordPress must send password resets,
form notifications or admin notices. If enabled, suppressed `wp_mail` calls report
failure. Emails sent directly by a separate service are outside this setting.

A developer can allow a specific message through `headless_bridge/allow_cms_mail`.
Test recovery and notification flows before relying on that exception.

## Developer hooks

| Hook | Inputs | Purpose |
| --- | --- | --- |
| `headless_bridge/frontend_url` | Mapped URL, original URL | Change a page path. The result must remain a valid content URL on the configured public origin. |
| `headless_bridge/allow_cms_mail` | Boolean, mail attributes | Allow a specific message while mail suppression is on. Default: false. |
| `headless_bridge/metadata_generation_failed` | Caught exception | Send generation details to private server logs. This is an action, not a URL filter. |

For a site that uses `/articles/` publicly instead of CMS `/news/`:

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

This changes mapped public URLs. Your Next.js content query must also map the
public path back to the CMS path. Test nested pages, custom types and canonicals;
do not send every content type to an article route or add preview credentials.
