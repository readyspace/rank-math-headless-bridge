# IndexNow: tell search engines a page changed

IndexNow sends a notice to participating search engines when a public page changes.
It does not publish the page or guarantee that a search engine will index it.
Rank Math manages the submissions and their history. This bridge changes eligible
addresses so the notices point to your public Next.js website.

Set up [Rank Math's Instant Indexing module](https://rankmath.com/kb/how-to-use-indexnow/)
before enabling the bridge's **IndexNow frontend adapter**.

## Start with a text file

The key is a verification code managed by Rank Math. A file containing that code
shows that you control the public website. The bridge cannot upload it to Next.js.

1. Set the bridge's public website address to your exact HTTPS address, such as
   `https://www.example.com`. Use the normal HTTPS port.
2. Copy the current key from Rank Math's Instant Indexing settings.
3. In Next.js, create `public/<CURRENT_KEY>.txt`. Replace `<CURRENT_KEY>` with
   the real key. The file's only content should be that same key. To retain an
   existing filename such as `/indexnow.txt`, set **IndexNow public key path** to
   that path and serve the same key there.
4. Deploy Next.js. Open `https://www.example.com/<CURRENT_KEY>.txt` in a browser.
   It should return the key as plain text with status `200`, without a login or redirect.
5. Enable **IndexNow frontend adapter** in the bridge.
6. Submit one known public URL in Rank Math. Check the submission history.
7. Update a staging test post. Check that the automatic submission uses the
   public URL and a key-file address on the same public host.

The address must match exactly. `www.example.com` and `example.com` are different
hosts. A file on `cms.example.com` does not verify `www.example.com`.

## When the key changes

If Rank Math changes its key, deploy the new file before submitting with the new
key. Verify the new file before removing the old one. Do not put your real site's
key in this repository or public bug reports.

## Optional developer route

The [App Router route example](../examples/nextjs/indexnow-route.ts) reads a fixed
`INDEXNOW_KEY` from server settings and serves only the matching filename.
A static file is simpler for most setups.

If using this route, update the variable when Rank Math changes the key and
redeploy or restart as your host requires. Do not add a root `[slug]` route that
conflicts with an existing content route. Check that middleware and login rules
allow the public key file. The route does not fetch files from WordPress.

## Read-only current-key endpoint

With **IndexNow frontend adapter** enabled, a frontend can ask:

```http
GET /wp-json/headless-bridge/v1/indexnow-key
```

The response is `{ "key": "CURRENT_PUBLIC_VERIFICATION_KEY" }`, with a `no-store`
cache header. This route does not need the **Published-page SEO endpoint** feature,
WPGraphQL or PHP DOM. It returns status `503` when the bridge or native IndexNow
module is unavailable, and does not exist when the adapter is disabled in a fresh
request. It does not generate or rotate keys, expose private settings, submit URLs
or change history. Treat v1 as an additive contract; consume `key` without requiring
an exact bridge version or refusing additional response fields.

A server-side frontend route can fetch this fixed endpoint from its configured CMS
and serve the key as `text/plain` at its configured public key path. Reject missing
or malformed keys and avoid caching stale values. Do not turn the route into an
arbitrary URL proxy. Static files remain supported.

## Manual submissions and private paths

The adapter validates the final native Rank Math IndexNow payload as well as its
public URL filters. This covers Rank Math 1.0.280's manual REST console, which calls
its API directly and bypasses the normal `submit_url` filter. It checks the exact
frontend host, current key, effective public key-file location and every submitted
URL. An invalid member cancels the entire batch; no partial success is reported.
Other HTTP destinations remain unchanged, and the bridge does not create a second
IndexNow client.

Configure frontend-only private paths and any historical source aliases in the
bridge settings. Known WordPress content also keeps native indexability, selected
public types, password/status and explicit canonical restrictions. CMS key serving
still uses its native local URL through the `serve_api_key` context.

For a migration, verify mapping and payload guards using an intercepted request
in a disposable test site. Read the production key file and SEO output without
publishing test content or sending bulk notifications. Retain the current Rank Math
key, settings and history.

## Understand the result

| Status or problem | Meaning or next check |
| --- | --- |
| `200` from an IndexNow submission | The notice was received. Indexing is still up to the search engine. |
| `202` | The notice was received, but key verification is pending. |
| `403` | Check the key and the public verification file. |
| `422` | Check that the submitted URLs belong to the declared host. |
| `429` | You are sending too many requests. Wait; do not retry in a tight loop. |
| Key URL opens a homepage or login | Check Next.js routes, redirects, middleware and access rules. |

Rank Math's **Check Key** action should open the public key-file address while
the adapter is on. Inspect the actual response and history on staging. The recorded
current tests do not establish real IndexNow delivery for every installation.
See the [IndexNow protocol](https://www.indexnow.org/documentation) for the full rules.
