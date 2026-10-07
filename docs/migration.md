# Controlled migration from site-specific helpers

This plugin does not deactivate, delete, or migrate existing helpers automatically. Similar names do not establish that two helpers have identical behavior. Review the actual code and current site configuration before deciding which modules replace them.

## 1. Inventory and back up

Record each normal and must-use plugin, its file path, its role, and its known frontend dependency. Include SEO output, URL mapping, IndexNow, editor links, authenticated previews, CMS robots behavior, and mail behavior. Save recoverable copies of the current files, settings, and frontend deployment.

Do not assume a custom safeguard only controls robots or mail. It may also change API access, redirects, forms, authentication, or other business behavior that this bridge does not implement. The initial bridge is a generalized implementation, not a byte-for-byte replacement for every legacy plugin.

Record baseline results for a representative article, page, and custom post type: title, description, canonical, robots, JSON-LD, social images, public frontend response, CMS indexing directives, published View link, and authenticated Preview flow. Also record the current IndexNow key and submission behavior without posting the real key publicly.

## 2. Prepare staging

Use a staging CMS and frontend with explicit test configuration. Keep production keys, credentials, and automatic submissions out of a shared test environment. Install the bridge with all modules disabled, then configure that staging frontend's exact origin and public post types.

Implement the frontend metadata renderer and key file if those modules will be used. Check that the frontend actually supports the mapped paths; matching origins alone do not establish matching routes.

## 3. Replace one role at a time

Enable a module only when its equivalent legacy behavior has been disabled on staging. Avoid running two IndexNow adapters or two conflicting SEO URL mappers together: both may change the same request or output.

Normal plugins can be deactivated through WordPress. **Must-use plugins cannot be deactivated through the usual Plugins screen.** Move the specific helper outside `wp-content/mu-plugins/` into a backed-up location, and check any loader file that includes it. Renaming a directory or moving a file may not disable code that a separate loader still imports. Preserve a clear rollback path; do not delete the only copy.

Do not remove your authenticated preview provider merely because published View links work. Preview integration remains separate in this first version. Similarly, retain any legacy behavior that the bridge does not cover until an explicit replacement is ready.

## 4. Validate the replacement

| Area | What to check |
| --- | --- |
| Metadata | API output and initial frontend HTML have the intended title, description, canonical, robots, social tags, and schema. No duplicate conflicting tags. |
| Schema | Eligible public site/content URLs use the frontend; media and unrelated URLs still resolve. |
| Robots | CMS is noindexed only when intended. Frontend preserves intentional Rank Math post/type directives and remains indexable where intended. |
| Key file | Current verification key is served as plain text with `200` on the exact public frontend host. |
| Manual IndexNow | One test submission has the expected frontend URL, host, and key location; inspect Rank Math history. |
| Publish/update | Automatic submission and frontend content/metadata refresh work with the deployment's cache policy. |
| Editor View | Published eligible content opens the frontend. Draft/private/password-protected content does not gain public links. |
| Preview | Existing authenticated previews still work and do not expose WordPress nonces or passwords in public URLs. |
| Mail | With suppression off, required messages still work. If enabling it, test deliberate suppression, recovery, and any allowed mail calls. |

Test an intentionally noindexed post as well as an indexable post. CMS isolation must not accidentally convert deliberate noindex metadata into indexable frontend output. Test nested pages, custom post types, social images, and any custom permalink mapping your site uses.

## 5. Roll out and roll back

After staging results are satisfactory, schedule a controlled production change, deploy required frontend changes, back up current production settings, and repeat the critical checks. Adopt a released plugin version consistently across sites, with separate configuration for each installation.

If a check fails, disable the affected bridge module, restore its corresponding helper and previous settings, and restore frontend changes if necessary. Recheck metadata, robots, editor links, and submissions after rollback. Keep mail recovery independent of a suppressed CMS mail channel.

Do not remove the original helper backups until the deployment has been observed and the team has a reliable recovery path. Submit sanitized compatibility findings to the repository so other sites can benefit from the migration work.
