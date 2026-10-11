<?php
/**
 * Plugin Name: Headless Bridge for Rank Math
 * Plugin URI: https://github.com/readyspace/rank-math-headless-bridge
 * Description: Configurable SEO, IndexNow, editor links and optional CMS safeguards for a separate headless frontend.
 * Version: 0.2.0
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Author: ReadySpace contributors
 * Author URI: https://readyspace.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: headless-bridge-for-rank-math
 */

namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

define( 'HEADLESS_BRIDGE_VERSION', '0.2.0' );
define( 'HEADLESS_BRIDGE_FILE', __FILE__ );

foreach ( array( 'settings', 'url-mapper', 'rank-math', 'metadata', 'graphql', 'editor-links', 'safeguards' ) as $headless_bridge_component ) {
	require_once __DIR__ . '/includes/class-' . $headless_bridge_component . '.php';
}
unset( $headless_bridge_component );

add_action( 'plugins_loaded', static function () {
	Settings::register();
	RankMath::register();
	Metadata::register();
	GraphQL::register();
	EditorLinks::register();
	Safeguards::register();
}, 20 );
