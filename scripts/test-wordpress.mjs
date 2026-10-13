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
  for (const name of ['run.php', 'metadata-test.php', 'indexnow-test.php']) {
    const result = await site.playground.run({ scriptPath: `${testPath}/tests/${name}` });
    if (result.exitCode !== 0 || result.errors) throw new Error(result.errors || result.text);
    console.log(result.text.trim());
  }
  const result = await site.playground.run({ code: `<?php try { require '${testPath}/tests/wordpress-integration.php'; } catch (\\Throwable $error) { echo 'INTEGRATION FAILURE: ' . $error->getMessage() . "\\n" . $error->getTraceAsString(); exit(1); }` });
  if (result.exitCode !== 0 || result.errors) throw new Error(result.errors || result.text);
  const report = JSON.parse(result.text);
  if (!report.passed) throw new Error('Integration did not report successful assertions.');
  console.log('Real WordPress integration:', report);
  const disabled = await site.playground.run({ code: `<?php require '/wordpress/wp-load.php'; wp_set_current_user(1); $result = graphql(['query' => '{ __type(name: "ContentNode") { fields { name } } }']); if (!empty($result['errors'])) { throw new RuntimeException(wp_json_encode($result['errors'])); } $names = array_column($result['data']['__type']['fields'], 'name'); if (in_array('headlessSeo', $names, true)) { throw new RuntimeException('Disabled GraphQL field remained in the schema.'); } require_once ABSPATH . 'wp-admin/includes/plugin.php'; deactivate_plugins('wp-graphql/wp-graphql.php'); $settings = HeadlessBridge\\Settings::get(); $settings['graphql'] = true; update_option(HeadlessBridge\\Settings::OPTION, $settings); echo 'Disabled GraphQL module: passed.';` });
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
