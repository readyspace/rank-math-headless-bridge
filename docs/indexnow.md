# IndexNow frontend setup

IndexNow notifies participating search engines that a public URL has changed. It does not publish the page, make it indexable, or guarantee indexing. A `200` response confirms receipt; `202` means verification is pending. See the [protocol documentation](https://www.indexnow.org/documentation).

The bridge adapts Rank Math's submission data to the configured public frontend. Rank Math still manages its current key, enabled post types, submissions, and history. Configure its [Instant Indexing module](https://rankmath.com/kb/how-to-use-indexnow/) before enabling the bridge's IndexNow adapter.

## Recommended: static verification file

1. Configure the bridge's exact public HTTPS frontend origin, for example `https://www.example.com`.
2. In Rank Math's Instant Indexing settings, copy the current key for this CMS installation.
3. In the frontend project, create a UTF-8 text file named `<CURRENT_KEY>.txt`, with the current key as its only content. For Next.js, place it in `public/`.
4. Deploy the frontend. Open `https://www.example.com/<CURRENT_KEY>.txt` in a browser and confirm it returns the key as plain text with HTTP `200`, without a login, redirect to the homepage, or a framework error page.
5. Enable the bridge's IndexNow adapter. Submit one known public frontend URL manually through Rank Math and check its history.
6. Publish or update a staging test post and confirm the automatic submission contains the frontend URL and uses a verification-file location on the same frontend host.

The bridge cannot upload or deploy this file to a separate frontend. If Rank Math rotates its key, deploy the new file before making submissions with that key. Remove the previous file only after the new one is verified and any transition requirements are satisfied. Do not publish a real site's key in this repository or bug reports.

The hostname is exact: `www.example.com` and `example.com` are different hosts. Use the origin visitors actually receive, including its `www` choice. A file on `cms.example.com` does not verify submissions for `www.example.com`.

## Optional: fixed-key server route

[The App Router example](../examples/nextjs/indexnow-route.ts) serves only the exact key in a server-side deployment variable. It does not fetch an arbitrary requested filename from WordPress. Keep the variable synchronized with Rank Math's current key and redeploy or restart the frontend according to the host's environment-variable behavior.

A static file is simpler when your build/deploy process can maintain it. Use a server route only when the frontend already supports runtime routes and the route does not collide with existing content or authentication middleware. Do not expose a key-discovery endpoint or a general proxy to the CMS.

## Validation and troubleshooting

| Check | Expected result |
| --- | --- |
| Frontend page URL | A public, deployed route with correct canonical and intended robots directives. |
| Frontend key file | `200` and the exact current key as plain text on the same host. |
| Manual submission | A frontend URL, matching host/key location, and a valid protocol response. |
| Automatic submission | A publish/update action produces the intended frontend URL in Rank Math history. |
| Key rotation | New frontend file/route configuration is deployed before submissions use the new key. |

A `403` commonly means verification failed. A `422` can indicate that the submitted URLs do not belong to the declared host. A `429` is a rate-limit response; do not repeatedly retry it in a tight loop. Also inspect redirects, access controls, middleware, and frontend rewrites if the key URL returns a login page or homepage.

Rank Math's **Check Key** action should open the frontend verification location while the adapter is enabled. Always inspect the actual response and submission history on staging. Compatibility with every Rank Math release has not yet been established by this alpha.
