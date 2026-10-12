# Contributing

Clone the full [GitHub repository](https://github.com/readyspace/rank-math-headless-bridge)
before running the development commands below. The installable ZIP contains runtime
files; guides, frontend examples, tests and development dependencies stay on GitHub.

Run `php tests/run.php`, `php tests/metadata-test.php` (DOM required), and
`node --test tests/frontend-example.test.mjs` before proposing a change.
Build an installable ZIP with `python3 scripts/package.py`.

For the current integration milestone, use Node 24.18 or newer and run `npm ci`,
`npm test`, `npm run test:wordpress`, and `npm run test:next`. The WordPress runner
creates a disposable Playground installation with real upstream plugins; it needs
network access on the first run. Set `BRIDGE_TEST_WP` and `BRIDGE_TEST_PHP` to
choose versions. `BRIDGE_TEST_DEPENDENCIES` can point at a local directory
containing extracted `seo-by-rank-math` and `wp-graphql` plugin folders.
After packaging, set `BRIDGE_TEST_PACKAGE=dist/readyspace-headless-seo-bridge-for-rank-math-0.2.1.zip`
to install and test the ZIP instead of mounting the source as the active plugin.
Run `npm run check:plugin` for the official Plugin Check static checks against that
ZIP. The check uses a disposable WordPress site and installs the official checker;
it does not enable its optional AI analysis. The native directory CI job also runs
Plugin Check. Neither check publishes to WordPress.org.

Thank you for helping make headless WordPress SEO easier to configure and maintain. This project is an independent integration with Rank Math, not a Rank Math product.

## Start here

- Open an issue describing the problem, the expected behavior, and a reproducible example using generic domains.
- For a large change, discuss the approach before writing it. Small bug fixes and documentation corrections can go directly to a pull request.
- Look for `good first issue` or `help wanted` labels. Compatibility fixtures, frontend examples, and clearer setup instructions are useful contributions.
- Treat other contributors respectfully. Discuss the work, avoid personal attacks, and help people with different experience levels participate.

## Development

### Maintainer workflow with GitHub Desktop

Start in GitHub Desktop and select this repository. Use **Fetch origin** and,
when offered, **Pull origin** to get the latest changes. Select or create the
working branch in Desktop before editing files. Open the local checkout with
**Open in your editor** and run the checks appropriate to your change.

Return to Desktop's **Changes** tab to review the files. Write a clear summary,
commit the intended files, then use **Push origin** to send that commit to
GitHub. A push to a branch with an open pull request updates that same request.
For a new branch, use Desktop's **Create Pull Request** action to open the
review page on GitHub. Review and merge there after the checks pass.

For assisted maintainer work in this repository, use GitHub Desktop for branch,
commit and push actions. Local editing, packaging, tests and read-only checks can
use the development tools below. WordPress.org submission and SVN publication
are separate steps; pushing to GitHub does not publish the plugin directory
release.

Clone the repository into a local WordPress development environment. Use the folder name `readyspace-headless-seo-bridge-for-rank-math`. Install Rank Math separately; this repository does not bundle its code.

The current compatibility target is WordPress 6.7 or newer and PHP 7.4 or newer, matching the current Rank Math minimum. Run the checks documented in the repository's CI workflow before opening a pull request. For a change to a WordPress hook, add a focused test of its observable behavior and test it in a disposable WordPress installation. Pure unit tests do not establish full Rank Math compatibility.

Use generic example domains such as `cms.example.com` and `www.example.com`. Do not commit production configuration, authentication cookies, application passwords, private keys, customer content, or a real site's IndexNow key. Do not import third-party plugin source without confirming that the license permits it and preserving required attribution.

## Design rules

- Make WPGraphQL the main Next.js example and setup guide, while keeping it optional for the WordPress plugin. REST is an explicit alternative, never an automatic fallback.
- Write user guides in plain language: define new terms, use short steps and show a concrete example. Keep deeper API details in a clearly marked developer section.
- Preserve the GPL-3.0-or-later license and the upstream acknowledgments in [CREDITS.md](CREDITS.md). Give Rank Math credit without implying its endorsement.
- Keep features optional and disabled until the administrator configures them.
- Preserve authenticated preview flows; published editor links and preview authentication have different requirements.
- Restrict URL mapping to the configured CMS and frontend origins. Preserve media addresses and unrelated external links.
- Do not turn an endpoint into a proxy for arbitrary URLs or expose drafts, private posts, or password-protected posts.
- Check capabilities and nonces on administrative writes, sanitize configuration, and escape output.
- Preserve Rank Math's ownership of metadata and IndexNow submission logic. Prefer its documented interfaces; isolate and document version-sensitive integrations.
- Avoid global behavior changes unrelated to the enabled module. In particular, CMS noindex and mail suppression must remain explicit choices.

## Pull requests

Explain the problem and the resulting behavior. Include the affected WordPress, PHP, Rank Math, and frontend versions, the checks you ran, and any remaining limitation. Update the documentation and changelog when a user's setup or behavior changes.

All contributed code is licensed under GPL-3.0-or-later. By submitting a contribution, you confirm that you can license it on those terms. The maintainers review and merge pull requests; no automatic release or production deployment is implied.

CI for pull requests must work without production credentials. Forked pull requests must not receive deployment secrets. Keep workflow permissions minimal and do not run untrusted pull-request code in a privileged `pull_request_target` job.

## Useful next contributions

- Real WordPress and Rank Math compatibility tests across supported versions.
- Frontend adapters for additional frameworks and permalink layouts.
- Authenticated preview integrations with clear threat models.
- Multilingual and multisite behavior, with explicit per-site configuration.
- Staging migration reports that compare old and new metadata without changing live settings.

For vulnerability reports, follow [SECURITY.md](SECURITY.md) instead of posting exploit details in a public issue.
