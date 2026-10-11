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
   the real key. The file's only content should be that same key.
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
the adapter is on. Inspect the actual response and history on staging. The alpha's
current tests do not establish real IndexNow delivery for every installation.
See the [IndexNow protocol](https://www.indexnow.org/documentation) for the full rules.
