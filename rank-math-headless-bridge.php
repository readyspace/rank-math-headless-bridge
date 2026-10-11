<?php
/**
 * Plugin Name: Headless Bridge for Rank Math
 * Description: Configurable SEO, IndexNow, editor links and optional CMS safeguards for a separate headless frontend.
 * Version: 0.2.0-alpha.1
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Author: Headless Bridge contributors
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: headless-bridge-for-rank-math
 */

namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

define( 'HEADLESS_BRIDGE_VERSION', '0.2.0-alpha.1' );
define( 'HEADLESS_BRIDGE_FILE', __FILE__ );

foreach ( array( 'settings', 'url-mapper', 'rank-math', 'metadata', 'graphql', 'editor-links', 'safeguards' ) as $component ) {
	require_once __DIR__ . '/includes/class-' . $component . '.php';
}

add_action( 'plugins_loaded', static function () {
	Settings::register();
	RankMath::register();
	Metadata::register();
	GraphQL::register();
	EditorLinks::register();
	Safeguards::register();
}, 20 );
