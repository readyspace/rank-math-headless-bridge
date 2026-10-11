# Replacing older helper plugins

A helper plugin is small custom code added to do a specific job. This bridge can
replace some helpers, but it does not remove or migrate them automatically.
Test one job at a time on a staging copy of your site.

## Upgrading an older bridge ZIP

The first ZIP installed into `wp-content/plugins/rank-math-headless-bridge/`.
The prepared directory release installs into
`wp-content/plugins/headless-bridge-for-rank-math/`. WordPress treats these as
two separate plugin folders, even though the display name is the same.

1. Back up your WordPress database and old bridge files. Also write down the
   public website address, selected content types and enabled bridge features.
2. Deactivate the old bridge in **Plugins**. Deactivation keeps its settings.
3. Use your host's file manager or ask your developer to copy the old
   `rank-math-headless-bridge` folder to a backup location outside
   `wp-content/plugins/`, then remove only that old folder from the plugins
   directory. This removes the old code without running a plugin deletion task.
4. Upload `headless-bridge-for-rank-math-0.2.0.zip` and activate the new bridge.
5. Open **Settings → Headless Bridge** and check your saved choices. Repeat the
   published-page SEO checks below before changing the live site.

Never activate both copies. The saved setting name `headless_bridge_settings`
and the SEO APIs remain the same, so the folder change can keep the existing
settings. Keep your backup until you have checked the result.

Deleting the new directory version through WordPress runs its uninstaller and
removes the bridge's own settings. It leaves Rank Math settings and content
alone. To pause the new version and keep settings, deactivate it instead.

The sections below cover other helpers you may also be replacing.

## 1. Write down what you have

Ask your developer to list normal plugins and must-use plugins, their file paths
and what they do. Include SEO, URL mapping, IndexNow, View links, previews, CMS
search instructions and email behavior. Back up the files, settings and frontend.

Names alone do not prove two plugins do the same job. An older helper might also
handle logins, forms or redirects that this bridge does not cover.

Save the current results for a post, page and custom content type. Record titles,
descriptions, canonicals, robots, social images and schema. Also check View links,
previews and IndexNow history. Keep real keys and passwords out of public reports.

## 2. Set up a test copy

Use a staging CMS and frontend with their own settings. Keep live credentials
and automatic live-site submissions out of a shared test setup.

Install the bridge with its features off. Set the test website address and public
content types. Follow the [WPGraphQL start guide](quick-start.md) and connect the
frontend renderer. Add an IndexNow key file only if you are testing that feature.
Check that the public paths really exist in Next.js.

## 3. Replace one job

On staging, disable the old code for one job, then enable its bridge replacement.
Do not run two competing URL mappers, SEO renderers or IndexNow adapters.

Normal plugins can be deactivated in WordPress. Must-use plugins load from
`wp-content/mu-plugins/` and cannot be turned off in the usual Plugins screen.
Have your developer move the specific helper to a backup location and check any
loader that includes it. Keep a working copy to restore; do not delete the only copy.

Keep your existing preview tool. A working published View link does not replace
authenticated draft previews. Also keep any old behavior the bridge does not cover.

## 4. Compare the results

| Area | What should work |
| --- | --- |
| GraphQL or REST SEO | Correct title, description, canonical, robots, images and schema. |
| Next.js page source | The same intended SEO, without competing duplicate tags. |
| Addresses | Public page URLs use the public website. Images and outside links still work. |
| Search instructions | CMS `noindex` applies only when wanted. A deliberate post `noindex` remains on the frontend. |
| IndexNow | The public key file works; manual and automatic submissions use public URLs. |
| Content updates | Changed content and SEO appear after the frontend's planned refresh. |
| View links | Eligible published pages open the public website. |
| Previews | Existing login-protected previews still work. No passwords or nonces enter public links. |
| Email | Required email works with suppression off. If suppression is wanted, recovery and permitted messages are tested. |

Test an indexable post and one deliberately marked `noindex`. Include nested
pages, custom types, different social images and any custom path mapping.

## 5. Move to the live site and keep a way back

After staging passes, back up the live settings and files. Deploy the required
frontend changes, replace one job and repeat the important checks.

If a check fails, turn off that bridge feature and restore its old helper and
settings. Restore frontend changes if needed. Recheck SEO, previews and
submissions. Keep recovery independent of WordPress mail if mail is suppressed.

Keep the backups while observing the live site. Share reports with example
addresses and no private data so other developers can learn from them.
