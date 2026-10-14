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

## IndexNow migration settings

Enable **IndexNow frontend adapter** only after configuring Rank Math's Instant
Indexing module and deploying the frontend key file. The REST SEO feature can
remain off. See the [IndexNow guide](indexnow.md).

- **IndexNow public key path:** leave empty for `/<CURRENT_KEY>.txt`, or preserve
  an existing path such as `/indexnow.txt`. Use a public `.txt` path on the configured
  frontend. The bridge does not upload the file or change Rank Math's key.
- **Additional IndexNow source origins:** explicitly list historical origins,
  one per line. They are accepted only for IndexNow input, then mapped to the exact
  configured public origin. An alias on the CMS hostname retains the CMS base path.
  Other aliases use public frontend paths. Empty means only the current CMS and frontend.
- **Excluded IndexNow paths:** list private frontend routes such as `/preview`,
  `/api` or `/thank-you`. Each path and its descendants are rejected, including
  encoded spellings. The key file and WordPress backend/media paths are always excluded.
- **IndexNow trailing slashes:** preserve paths by default, or match a frontend
  that consistently adds or removes them. This setting does not change SEO canonicals.

A malformed path or source alias disables IndexNow when settings are saved. The
bridge rejects a complete batch if any URL fails policy. For known WordPress posts,
it also checks selected public types, status, password, Rank Math indexability and
explicit external canonicals. Public frontend routes with no corresponding CMS post
remain eligible unless excluded; the bridge cannot infer those routes' access rules.

## CMS search instructions

**Keep the CMS out of search results** adds `noindex` to CMS responses. It does
not protect the CMS with a login. While creating frontend SEO, the bridge leaves
out this CMS-only rule. A deliberate Rank Math `noindex` choice for a post or
content type still applies. Check both the API data and your Next.js page source.

**Disable WordPress core sitemaps on the CMS** is a separate opt-in safeguard.
It disables WordPress core XML sitemaps, without changing Rank Math module options
or your frontend sitemap. Leave it off if you use core CMS sitemaps.

## Editor links and previews

**Public View links in the editor** changes eligible published View links to
use the public website. It also covers authenticated REST edit responses used by
Gutenberg, and requires permission to edit the individual post. Anonymous REST and
GraphQL content URLs stay unchanged. Drafts are not mapped as published links.

**Frontend preview links** is a separate opt-in feature for an existing
authenticated preview integration, such as Faust. It moves eligible CMS preview
destinations to the configured frontend after the provider adds its parameters.
It also repairs dynamically generated Gutenberg preview anchors while retaining
fresh WordPress nonce and autosave parameters. The preview provider continues to
control authentication and draft access. This option does not create a preview
endpoint or make drafts publicly available. Keep it off if your provider already
handles every destination. Test both **View** and **Preview** after changing helpers.

## WordPress email

Leave **Suppress WordPress email** off if WordPress must send password resets,
form notifications or admin notices. If enabled, suppressed `wp_mail` calls report
failure. Emails sent directly by a separate service are outside this setting.

A developer can allow a specific message through `headless_bridge/allow_cms_mail`.
Test recovery and notification flows before relying on that exception.

## Turning off or deleting the bridge

Deactivating the plugin keeps its saved settings. Activate it again to use those
settings. Turn off individual features in the bridge settings if you only want
to stop one job.

Deleting the new `readyspace-headless-seo-bridge-for-rank-math` version through
WordPress removes the bridge's own saved settings. Deleting the older 0.2.0
version does the same, because both use the same setting name. It does not delete
Rank Math settings, posts or Next.js files. Back up your bridge settings before
deleting it.

If moving from the older `rank-math-headless-bridge` or
`headless-bridge-for-rank-math` folder, use the
[migration steps](migration.md#upgrading-an-older-bridge-zip) so you preserve your
settings and keep only one bridge active.

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
