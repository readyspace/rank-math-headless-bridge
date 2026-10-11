// Run the official Plugin Check static checks against the actual installable ZIP.
// No production site, credentials or optional AI analysis are used.
import { runCLI } from '@wp-playground/cli';
import { fileURLToPath } from 'node:url';
import { resolve, dirname, basename } from 'node:path';
import { readFile, writeFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';

const root = fileURLToPath(new URL('../', import.meta.url));
const packageFile = resolve(root, process.env.BRIDGE_TEST_PACKAGE || 'dist/headless-bridge-for-rank-math-0.2.0.zip');
const packageSha256 = createHash('sha256').update(await readFile(packageFile)).digest('hex');
const checker = process.env.BRIDGE_PLUGIN_CHECK_DIR;
const mount = [{ hostPath: dirname(packageFile), vfsPath: '/bridge-package' }];
if (checker) mount.push({ hostPath: resolve(checker), vfsPath: '/wordpress/wp-content/plugins/plugin-check' });
const blueprint = {
  preferredVersions: { php: '8.3', wp: process.env.BRIDGE_TEST_WP || 'latest' },
  steps: [
    { step: 'defineWpConfigConsts', consts: { WP_DEBUG: true, WP_DEBUG_DISPLAY: true } },
    { step: 'installPlugin', pluginData: { resource: 'vfs', path: `/bridge-package/${basename(packageFile)}` } },
    ...(!checker ? [{ step: 'installPlugin', pluginData: { resource: 'wordpress.org/plugins', slug: 'plugin-check' } }] : []),
    { step: 'activatePlugin', pluginPath: 'plugin-check/plugin.php' },
  ],
};
const site = await runCLI({ command: 'server', port: 0, workers: 1, php: '8.3', wp: blueprint.preferredVersions.wp, mount, blueprint });
try {
  const setup = `<?php
    require '/wordpress/wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    wp_set_current_user(1);
    $context = new WordPress\\Plugin_Check\\Checker\\Check_Context(
      WP_PLUGIN_DIR . '/headless-bridge-for-rank-math/rank-math-headless-bridge.php',
      'headless-bridge-for-rank-math', 'new'
    );
    $checks = (new WordPress\\Plugin_Check\\Checker\\Default_Check_Repository())->get_checks(
      WordPress\\Plugin_Check\\Checker\\Check_Repository::TYPE_STATIC
    );
  `;
  const inventory = await site.playground.run({ code: setup + `
    echo wp_json_encode([
      'pluginCheck' => WP_PLUGIN_CHECK_VERSION, 'wordpress' => $GLOBALS['wp_version'], 'php' => PHP_VERSION,
      'scope' => 'All non-experimental static checks; runtime integration is tested separately.',
      'checks' => array_keys($checks->to_map())
    ]);
  ` });
  if (inventory.exitCode !== 0 || inventory.errors) throw new Error(inventory.errors || inventory.text);
  const report = { ...JSON.parse(inventory.text), package: basename(packageFile), packageSha256, errorCount: 0, warningCount: 0, results: {} };
  // Separate PHP requests avoid retaining every scanner's memory at once in WebAssembly.
  for (const slug of report.checks) {
    if (!/^[a-z0-9_]+$/.test(slug)) throw new Error('Unexpected check slug.');
    console.log(`Checking ${slug}…`);
    const result = await site.playground.run({ code: setup + `
    $result = (new WordPress\\Plugin_Check\\Checker\\Checks())->run_checks($context, [$checks['${slug}']]);
    echo wp_json_encode([
      'errorCount' => $result->get_error_count(), 'warningCount' => $result->get_warning_count(),
      'errors' => $result->get_errors(), 'warnings' => $result->get_warnings()
    ]);
  ` });
    if (result.exitCode !== 0 || result.errors) throw new Error(result.errors || result.text);
    const finding = JSON.parse(result.text);
    report.results[slug] = finding;
    report.errorCount += finding.errorCount;
    report.warningCount += finding.warningCount;
    await writeFile(resolve(root, 'dist/plugin-check-report.json'), JSON.stringify(report, null, 2) + '\n');
  }
  const reportPath = resolve(root, 'dist/plugin-check-report.json');
  await writeFile(reportPath, JSON.stringify(report, null, 2) + '\n');
  console.log(`Plugin Check ${report.pluginCheck}: ${report.checks.length} static checks, ${report.errorCount} errors, ${report.warningCount} warnings.`);
  console.log(`Report: ${reportPath}`);
  if (report.errorCount || report.warningCount) process.exitCode = 1;
} catch (error) {
  const response = error?.response;
  const detail = response ? new TextDecoder().decode(Uint8Array.from(Object.values(response.bytes))) : error.message;
  console.error(detail || error.stack || error.message);
  process.exitCode = 1;
} finally {
  await site[Symbol.asyncDispose]();
}
