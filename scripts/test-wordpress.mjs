// Disposable WordPress + real upstream plugins. Nothing connects to a production CMS.
import { runCLI } from '@wp-playground/cli';
import { fileURLToPath } from 'node:url';
import { resolve, dirname, basename } from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const pluginPath = '/wordpress/wp-content/plugins/readyspace-headless-seo-bridge-for-rank-math';
const packageFile = process.env.BRIDGE_TEST_PACKAGE;
const testPath = packageFile ? '/bridge-source' : pluginPath;
const dependencies = process.env.BRIDGE_TEST_DEPENDENCIES;
const mount = [{ hostPath: root, vfsPath: testPath }];
if (packageFile) mount.push({ hostPath: dirname(resolve(root, packageFile)), vfsPath: '/bridge-package' });
if (dependencies) {
  for (const slug of ['seo-by-rank-math', 'wp-graphql']) {
    mount.push({ hostPath: resolve(dependencies, slug), vfsPath: `/wordpress/wp-content/plugins/${slug}` });
  }
}
const blueprint = {
  preferredVersions: { php: process.env.BRIDGE_TEST_PHP || '8.3', wp: process.env.BRIDGE_TEST_WP || 'latest' },
  steps: [
    { step: 'defineWpConfigConsts', consts: { WP_DEBUG: true, WP_DEBUG_DISPLAY: true, WP_DISABLE_FATAL_ERROR_HANDLER: true } },
    { step: 'setSiteOptions', options: {
      permalink_structure: '/%postname%/',
      blog_public: '0',
      rank_math_registration_skip: true,
      rank_math_modules: ['rich-snippet', 'instant-indexing'],
      'rank-math-options-general': { headless_support: 'on' },
      'rank-math-options-instant-indexing': { indexnow_api_key: 'bridge-fixture-key-12345678', bing_post_types: [] },
      rank_math_indexnow_log: [{ url: 'https://www.example.com/preserved-history/', status: 200, manual_submission: true, message: 'Fixture existing history', time: 1 }],
      headless_bridge_settings: { frontend_origin: 'https://www.example.com', seo: true, metadata: true, graphql: true, indexnow: true, indexnow_key_path: '/indexnow.txt', indexnow_source_origins: ['http://www.example.com', 'https://example.com'], indexnow_excluded_paths: ['/preview', '/api', '/thank-you'], indexnow_trailing_slash: 'add', cms_noindex: true, public_post_types: ['post', 'page'] },
    } },
    ...(!dependencies ? ['seo-by-rank-math', 'wp-graphql'].map(slug => ({ step: 'installPlugin', pluginData: { resource: 'wordpress.org/plugins', slug } })) : []),
    ...(packageFile ? [{ step: 'installPlugin', pluginData: { resource: 'vfs', path: `/bridge-package/${basename(packageFile)}` } }] : []),
    { step: 'activatePlugin', pluginPath: 'seo-by-rank-math/rank-math.php' },
    { step: 'activatePlugin', pluginPath: 'wp-graphql/wp-graphql.php' },
    { step: 'activatePlugin', pluginPath: 'readyspace-headless-seo-bridge-for-rank-math/rank-math-headless-bridge.php' },
  ],
};
const site = await runCLI({ command: 'server', port: 0, workers: 1, php: blueprint.preferredVersions.php, wp: blueprint.preferredVersions.wp, 'site-url': 'https://cms.example.com', mount, blueprint });
try {
  const preserved = await site.playground.run({ code: `<?php require '/wordpress/wp-load.php'; $expected = ['frontend_origin' => 'https://www.example.com', 'seo' => true, 'metadata' => true, 'graphql' => true, 'indexnow' => true, 'indexnow_key_path' => '/indexnow.txt', 'indexnow_source_origins' => ['http://www.example.com', 'https://example.com'], 'indexnow_excluded_paths' => ['/preview', '/api', '/thank-you'], 'indexnow_trailing_slash' => 'add', 'cms_noindex' => true, 'public_post_types' => ['post', 'page']]; if (get_option('headless_bridge_settings') !== $expected) { throw new RuntimeException('Activating the release changed existing bridge settings.'); } if (get_option('rank-math-options-instant-indexing')['indexnow_api_key'] !== 'bridge-fixture-key-12345678' || count(get_option('rank_math_indexnow_log')) !== 1) { throw new RuntimeException('Activating the release changed the native key or history.'); } echo 'Existing bridge settings and native IndexNow key/history preserved after activation: passed.';` });
  if (preserved.exitCode !== 0 || preserved.errors) throw new Error(preserved.errors || preserved.text);
  console.log(preserved.text);
  const syntax = await site.playground.run({ code: `<?php $files = array_merge(glob('${pluginPath}/*.php'), glob('${pluginPath}/includes/*.php'), glob('${pluginPath}/tests/*.php')); foreach ($files as $file) { token_get_all(file_get_contents($file), TOKEN_PARSE); } echo 'PHP syntax: ' . count($files) . ' files passed.';` });
  if (syntax.exitCode !== 0 || syntax.errors) throw new Error(syntax.errors || syntax.text);
  console.log(syntax.text);
  for (const name of ['run.php', 'editor-links-test.php', 'metadata-test.php', 'indexnow-test.php']) {
    const result = await site.playground.run({ scriptPath: `${testPath}/tests/${name}` });
    if (result.exitCode !== 0 || result.errors) throw new Error(result.errors || result.text);
    console.log(result.text.trim());
  }
  const optIn = await site.playground.run({ code: `<?php
    require '/wordpress/wp-load.php';
    $settings = HeadlessBridge\\Settings::get();
    foreach (['editor_links', 'preview_links', 'cms_sitemaps', 'suppress_mail'] as $feature) {
      if ($settings[$feature] !== false) { throw new RuntimeException('Existing installations must not opt into ' . $feature . '.'); }
    }
    if (has_filter('wp_sitemaps_enabled', [HeadlessBridge\\Safeguards::class, 'suppress_core_sitemaps']) !== false || has_filter('pre_wp_mail', [HeadlessBridge\\Safeguards::class, 'mail']) !== false || has_filter('preview_post_link', [HeadlessBridge\\EditorLinks::class, 'preview_link']) !== false) { throw new RuntimeException('An opt-in helper loaded before being enabled.'); }
    add_filter('wp_sitemaps_enabled', '__return_true', 1);
    if (!wp_sitemaps_get_server()->sitemaps_enabled()) { throw new RuntimeException('CMS noindex alone must not suppress core sitemaps.'); }
    if (apply_filters('pre_wp_mail', null, []) !== null) { throw new RuntimeException('Mail was suppressed before opt-in.'); }
    foreach (['editor_links', 'preview_links', 'cms_sitemaps', 'suppress_mail'] as $feature) { $settings[$feature] = true; }
    update_option(HeadlessBridge\\Settings::OPTION, $settings);
    echo 'Existing-site helper defaults and explicit opt-in: passed.';
  ` });
  if (optIn.exitCode !== 0 || optIn.errors) throw new Error(optIn.errors || optIn.text);
  console.log(optIn.text);
  const editorRest = await site.playground.run({ code: `<?php
    define('REST_REQUEST', true);
    require '/wordpress/wp-load.php';
    wp_set_current_user(1);
    $published = wp_insert_post(['post_title' => 'Editor contract', 'post_name' => 'bridge-editor-contract', 'post_status' => 'publish']);
    $draft = wp_insert_post(['post_title' => 'Preview contract', 'post_status' => 'draft']);
    $_GET = $_REQUEST = ['context' => 'view'];
    if (get_permalink($published) !== 'https://cms.example.com/bridge-editor-contract/') { throw new RuntimeException('Public REST link changed with editor helpers enabled.'); }
    $_GET = $_REQUEST = ['context' => 'edit'];
    if (get_permalink($published) !== 'https://www.example.com/bridge-editor-contract/') { throw new RuntimeException('Authenticated REST edit link did not use the public frontend.'); }
    $request = new WP_REST_Request('GET', '/wp/v2/posts/' . $published);
    $request->set_param('context', 'edit');
    $response = rest_do_request($request);
    if ($response->get_status() !== 200 || $response->get_data()['link'] !== 'https://www.example.com/bridge-editor-contract/') { throw new RuntimeException('Real Gutenberg REST edit response has the wrong published link.'); }
    $_GET = $_REQUEST = [];
    $response = rest_do_request($request);
    if ($response->get_status() !== 200 || $response->get_data()['link'] !== 'https://www.example.com/bridge-editor-contract/') { throw new RuntimeException('REST request context was ignored when the query-string context was absent.'); }
    $save_request = new WP_REST_Request('POST', '/wp/v2/posts/' . $published);
    $save_request->set_body_params(['context' => 'edit', 'title' => 'Updated editor contract']);
    $save_response = rest_do_request($save_request);
    if ($save_response->get_status() !== 200 || $save_response->get_data()['link'] !== 'https://www.example.com/bridge-editor-contract/') { throw new RuntimeException('Gutenberg save response with body context has the wrong View link.'); }
    $provider_url = 'https://cms.example.com/preview/?p=' . $draft . '&preview=true&preview_id=' . $draft . '&preview_nonce=provider-token&provider=a%2Bb#provider-state';
    $provider = static function ($url, $post) use ($provider_url) { return $provider_url; };
    add_filter('preview_post_link', $provider, 10, 2);
    if (get_preview_post_link($draft) !== str_replace('https://cms.example.com', 'https://www.example.com', $provider_url)) { throw new RuntimeException('Provider preview query, nonce or state was changed.'); }
    if (has_filter('preview_post_link', $provider) !== 10) { throw new RuntimeException('The preview provider was removed.'); }
    wp_set_current_user(0);
    if (get_permalink($published) !== 'https://cms.example.com/bridge-editor-contract/' || get_preview_post_link($draft) !== $provider_url) { throw new RuntimeException('Anonymous REST context acquired editor or preview mapping.'); }
    remove_filter('preview_post_link', $provider, 10);
    echo 'Real REST edit/public permissions and provider-owned draft previews: passed.';
  ` });
  if (editorRest.exitCode !== 0 || editorRest.errors) throw new Error(editorRest.errors || editorRest.text);
  console.log(editorRest.text);
  const result = await site.playground.run({ code: `<?php try { require '${testPath}/tests/wordpress-integration.php'; } catch (\\Throwable $error) { echo 'INTEGRATION FAILURE: ' . $error->getMessage() . "\\n" . $error->getTraceAsString(); exit(1); }` });
  if (result.exitCode !== 0 || result.errors) throw new Error(result.errors || result.text);
  const report = JSON.parse(result.text);
  if (!report.passed) throw new Error('Integration did not report successful assertions.');
  console.log('Real WordPress integration:', report);
  const disabled = await site.playground.run({ code: `<?php require '/wordpress/wp-load.php'; wp_set_current_user(1); $result = graphql(['query' => '{ __type(name: "ContentNode") { fields { name } } }']); if (!empty($result['errors'])) { throw new RuntimeException(wp_json_encode($result['errors'])); } $names = array_column($result['data']['__type']['fields'], 'name'); if (in_array('headlessSeo', $names, true)) { throw new RuntimeException('Disabled GraphQL field remained in the schema.'); } if (has_filter('wp_sitemaps_enabled', [HeadlessBridge\\Safeguards::class, 'suppress_core_sitemaps']) !== false || has_filter('pre_wp_mail', [HeadlessBridge\\Safeguards::class, 'mail']) !== false || has_filter('preview_post_link', [HeadlessBridge\\EditorLinks::class, 'preview_link']) !== false || has_filter('post_link', [HeadlessBridge\\EditorLinks::class, 'published_link']) !== false) { throw new RuntimeException('Disabled editor or safeguard helper remained registered.'); } add_filter('wp_sitemaps_enabled', '__return_true', 1); if (!wp_sitemaps_get_server()->sitemaps_enabled() || apply_filters('pre_wp_mail', null, []) !== null) { throw new RuntimeException('Opting out did not restore core sitemap/mail decisions.'); } require_once ABSPATH . 'wp-admin/includes/plugin.php'; deactivate_plugins('wp-graphql/wp-graphql.php'); $settings = HeadlessBridge\\Settings::get(); $settings['graphql'] = true; update_option(HeadlessBridge\\Settings::OPTION, $settings); echo 'Disabled GraphQL, editor and safeguard modules: passed.';` });
  if (disabled.exitCode !== 0 || disabled.errors) throw new Error(disabled.errors || disabled.text);
  console.log(disabled.text);
  const absent = await site.playground.run({ code: `<?php require '/wordpress/wp-load.php'; if (function_exists('graphql')) { throw new RuntimeException('WPGraphQL should be absent.'); } $post = get_page_by_path('bridge-alpha', OBJECT, 'post'); $data = HeadlessBridge\\Metadata::for_post($post->ID); if (is_wp_error($data) || $data['title'] !== 'Alpha custom SEO title') { throw new RuntimeException('REST provider failed without optional WPGraphQL.'); } echo 'Absent WPGraphQL dependency: passed.';` });
  if (absent.exitCode !== 0 || absent.errors) throw new Error(absent.errors || absent.text);
  console.log(absent.text);
  const uninstall = await site.playground.run({ code: `<?php require '/wordpress/wp-load.php'; require_once ABSPATH . 'wp-admin/includes/plugin.php'; $rank_math = get_option('rank-math-options-general'); $post = get_page_by_path('bridge-alpha', OBJECT, 'post'); uninstall_plugin('readyspace-headless-seo-bridge-for-rank-math/rank-math-headless-bridge.php'); if (false !== get_option('headless_bridge_settings', false)) { throw new RuntimeException('Bridge settings remain after uninstall.'); } if ($rank_math !== get_option('rank-math-options-general') || !get_post($post->ID)) { throw new RuntimeException('Uninstall modified Rank Math settings or site content.'); } echo 'Uninstall removes only bridge settings: passed.';` });
  if (uninstall.exitCode !== 0 || uninstall.errors) throw new Error(uninstall.errors || uninstall.text);
  console.log(uninstall.text);
} catch (error) {
  // PHP-wasm exceptions otherwise dump the response byte-by-byte.
  const response = error?.response;
  const detail = response ? new TextDecoder().decode(Uint8Array.from(Object.values(response.bytes))) : error.message;
  console.error(detail || error.message);
  process.exitCode = 1;
} finally {
  await site[Symbol.asyncDispose]();
}
