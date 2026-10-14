<?php
/** Small deterministic contract tests; no WordPress installation or network needed. */
define( 'ABSPATH', __DIR__ . '/' );
$options = array( 'permalink_structure' => '/%postname%/' );
$cms_origin = 'https://cms.example.com';
$uploads_base = 'https://cms.example.com/custom-media';
$errors = array();
$filters = array();
$registered_filters = array();
$admin = true;

function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function home_url() { return $GLOBALS['cms_origin']; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_get_upload_dir() { return array( 'baseurl' => $GLOBALS['uploads_base'] ); }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
function get_post_type_object( $type ) { return (object) array( 'name' => $type, 'public' => in_array( $type, array( 'post', 'page', 'attachment', 'product' ), true ) ); }
function is_post_type_viewable( $type ) { return $type && $type->public; }
function add_settings_error( $setting, $code, $message ) { $GLOBALS['errors'][] = $code; }
function __( $text, $domain = '' ) { return $text; }
function apply_filters( $hook, $value, ...$args ) { return isset( $GLOBALS['filters'][ $hook ] ) ? $GLOBALS['filters'][ $hook ]( $value, ...$args ) : $value; }
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) { $GLOBALS['registered_filters'][ $hook ][ $priority ][] = array( $callback, $accepted_args ); }
function is_admin() { return $GLOBALS['admin']; }
function current_user_can( $capability, ...$args ) { return true; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
class WP_Post {
	public $ID = 1;
	public $post_type = 'post';
	public $post_status = 'publish';
	public $post_password = '';
}
// The real Metadata class gets its own isolated tests in metadata-test.php.
eval( 'namespace HeadlessBridge; final class Metadata { public static $active = false; public static function is_frontend_request(): bool { return self::$active; } }' );
require __DIR__ . '/../includes/class-settings.php';
require __DIR__ . '/../includes/class-url-mapper.php';
require __DIR__ . '/../includes/class-editor-links.php';
require __DIR__ . '/../includes/class-safeguards.php';

use HeadlessBridge\Settings;
use HeadlessBridge\UrlMapper;
use HeadlessBridge\EditorLinks;
use HeadlessBridge\Safeguards;

$checks = 0;
function expect( $actual, $expected, $label ) {
	$GLOBALS['checks']++;
	if ( $actual !== $expected ) {
		fwrite( STDERR, 'FAIL: ' . $label . '\nExpected ' . var_export( $expected, true ) . '\nGot ' . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}
foreach ( array( 'seo', 'metadata', 'graphql', 'indexnow', 'editor_links', 'preview_links', 'cms_noindex', 'cms_sitemaps', 'suppress_mail' ) as $module ) {
	expect( Settings::enabled( $module ), false, 'No activation side effects: ' . $module );
}
expect( Settings::clean_origin( 'https://EXAMPLE.com:443/' ), 'https://example.com', 'Normalize default port and case' );
foreach ( array( 'https://user:password@example.com', 'https://example.com/path', 'https://example.com?token=x', 'https://example.com#section', 'javascript:alert(1)' ) as $bad ) {
	expect( Settings::clean_origin( $bad ), '', 'Reject invalid frontend origin' );
}
$config = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com/', 'indexnow' => '1', 'metadata' => '1', 'public_post_types' => array( 'post', 'page', 'attachment', 'revision', 'private_type', 'post' ) ) );
expect( $config['public_post_types'], array( 'post', 'page' ), 'Public post types only; no media/revisions' );
expect( $config['indexnow'], true, 'Explicit valid module selection' );
expect( $config['indexnow_key_path'], '', 'Existing key-named file remains the default' );
expect( $config['indexnow_trailing_slash'], 'preserve', 'Existing IndexNow URL paths remain the default' );
expect( $config['preview_links'], false, 'Saving existing settings never opts into provider preview changes' );
expect( $config['cms_sitemaps'], false, 'Saving existing settings never disables core sitemaps implicitly' );
$safeguard_config = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'preview_links' => '1', 'cms_sitemaps' => '1', 'suppress_mail' => '1' ) );
expect( $safeguard_config['preview_links'], true, 'Preview repair requires an explicit opt-in' );
expect( $safeguard_config['cms_sitemaps'], true, 'Core sitemap suppression requires an explicit opt-in' );
expect( $safeguard_config['suppress_mail'], true, 'Existing mail suppression remains an independent opt-in' );
$invalid_origin = Settings::sanitize( array( 'frontend_origin' => 'https://cms.example.com', 'preview_links' => '1', 'cms_sitemaps' => '1' ) );
expect( $invalid_origin['preview_links'] || $invalid_origin['cms_sitemaps'], false, 'Origin-dependent safeguards require a separate public frontend' );
$indexnow_config = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'indexnow_key_path' => '/indexnow.txt', 'indexnow_source_origins' => "http://cms.example.com\nhttps://old.example.com/", 'indexnow_excluded_paths' => "/preview\n/api/\n/thank-you", 'indexnow_trailing_slash' => 'add' ) );
expect( $indexnow_config['indexnow_key_path'], '/indexnow.txt', 'An existing public key-file path can be preserved' );
expect( $indexnow_config['indexnow_source_origins'], array( 'http://cms.example.com', 'https://old.example.com' ), 'Explicit source aliases normalize without allowing arbitrary origins' );
expect( $indexnow_config['indexnow_excluded_paths'], array( '/preview', '/api', '/thank-you' ), 'Private frontend paths accept one value per line' );
expect( $indexnow_config['indexnow_trailing_slash'], 'add', 'IndexNow path policy is explicit' );
foreach ( array( '/a/../indexnow.txt', '/%69ndexnow.txt', '/wp-admin/indexnow.txt', '/indexnow.txt?token=x', '//foreign.example/key.txt', '/key.txt\\secret', '/not-a-key.json', '/key.txt ', '/<key>.txt' ) as $bad ) {
    $invalid = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'indexnow_key_path' => $bad ) );
    expect( $invalid['indexnow'], false, 'An invalid key path disables IndexNow rather than falling back to another file' );
}
$invalid = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'indexnow_excluded_paths' => '/preview?token=x' ) );
expect( $invalid['indexnow'], false, 'Invalid private paths fail closed during configuration' );
$invalid = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'indexnow_source_origins' => 'https://user:password@old.example.com' ) );
expect( $invalid['indexnow'], false, 'Invalid aliases fail closed during configuration' );
foreach ( array( 'indexnow_source_origins' => array( array( 'https://old.example.com' ) ), 'indexnow_excluded_paths' => array( array( '/preview' ) ), 'indexnow_trailing_slash' => 'unknown' ) as $name => $value ) {
    $invalid = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', $name => $value ) );
    expect( $invalid['indexnow'], false, 'Malformed IndexNow policy values fail closed' );
}
$invalid = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'indexnow_excluded_paths' => 7 ) );
expect( $invalid['indexnow'], false, 'A non-text private-path list fails closed' );
$GLOBALS['options'][ Settings::OPTION ] = $config;
expect( UrlMapper::to_frontend( 'https://cms.example.com/article/' ), 'https://www.example.com/article/', 'CMS to frontend' );
expect( UrlMapper::to_frontend( 'https://www.example.com/article/' ), 'https://www.example.com/article/', 'Frontend already mapped' );
expect( UrlMapper::to_cms( 'https://www.example.com/article/' ), 'https://cms.example.com/article/', 'Reverse mapping for trusted provider' );
expect( UrlMapper::to_frontend( 'https://cms.example.com/caf%C3%A9/' ), 'https://www.example.com/caf%C3%A9/', 'Unicode slug remains supported' );
foreach ( array( 'https://other.example.com/article/', 'http://cms.example.com/article/', 'https://cms.example.com:8443/article/', 'https://cms.example.com/wp-admin/', 'https://cms.example.com/wp%2dadmin/', 'https://cms.example.com/wp-json/', 'https://cms.example.com/wp-content/image.png', 'https://cms.example.com/graphql', 'https://cms.example.com/article/feed/', 'https://cms.example.com/a/../private/', 'https://cms.example.com/a/%252e%252e/private/', 'https://cms.example.com/a%2fwp-admin/', 'https://cms.example.com/a%255cwp-admin/', 'https://cms.example.com/article/?preview=true', 'https://cms.example.com/article/?p=123', 'https://cms.example.com/article/#private', 'https://cms.example.com/article%0a/' ) as $bad ) {
	expect( UrlMapper::to_frontend( $bad ), '', 'Block backend, auth, foreign and ambiguous URL' );
}
expect( UrlMapper::to_frontend( 'https://cms.example.com/article/?preview_nonce=abc', true ), '', 'Never transfer preview token even with allowed query' );
$schema = array( '@id' => 'https://cms.example.com/#website', 'url' => 'https://cms.example.com/', 'image' => 'https://cms.example.com/other-media/pic.png', 'logo' => array( '@type' => 'ImageObject', '@id' => 'https://cms.example.com/#logo', 'url' => 'https://cms.example.com/unusual/logo.png' ), 'asset' => 'https://cms.example.com/custom-media/file.jpg' );
$mapped = UrlMapper::entity_urls( $schema );
expect( $mapped['@id'], 'https://www.example.com/#website', 'Site identity rewritten' );
expect( $mapped['image'], $schema['image'], 'Custom image property preserved' );
expect( $mapped['logo']['url'], $schema['logo']['url'], 'ImageObject custom media preserved' );
expect( $mapped['logo']['@id'], 'https://www.example.com/#logo', 'Entity references remain consistent' );
expect( $mapped['asset'], $schema['asset'], 'WordPress custom upload base preserved' );
expect( UrlMapper::entity_urls( array( 'video' => 'https://cms.example.com/stream/123', 'audio' => 'https://cms.example.com/listen/123' ) ), array( 'video' => 'https://cms.example.com/stream/123', 'audio' => 'https://cms.example.com/listen/123' ), 'Direct video and audio resources preserved' );
expect( UrlMapper::entity_urls( array( '@id' => 'https://cms.example.com/other-media/photo.jpg#image' ) ), array( '@id' => 'https://cms.example.com/other-media/photo.jpg#image' ), 'Media resource identifiers stay on their actual host' );
$GLOBALS['cms_origin'] = 'https://cms.example.com/wp';
expect( UrlMapper::to_frontend( 'https://cms.example.com/wp/article/' ), 'https://www.example.com/article/', 'CMS subdirectory removed' );
expect( UrlMapper::to_frontend( 'https://cms.example.com/wphack/article/' ), '', 'CMS subdirectory boundary enforced' );
expect( UrlMapper::to_cms( 'https://www.example.com/article/' ), 'https://cms.example.com/wp/article/', 'CMS base restored' );
$GLOBALS['options'][ Settings::OPTION ]['frontend_origin'] = 'https://cms.example.com';
expect( UrlMapper::is_configured(), false, 'Imported same-origin config rejected at runtime' );
$GLOBALS['cms_origin'] = 'https://cms.example.com';
$GLOBALS['options'][ Settings::OPTION ] = $config;
$GLOBALS['filters']['headless_bridge/frontend_url'] = function () { return 'https://foreign.example.com/'; };
expect( UrlMapper::to_frontend( 'https://cms.example.com/article/' ), '', 'Extension cannot change trusted origin' );
unset( $GLOBALS['filters']['headless_bridge/frontend_url'] );
$post = new WP_Post();
expect( EditorLinks::published_link( 'https://cms.example.com/article/', $post ), 'https://www.example.com/article/', 'Editor View uses frontend' );
$post->post_status = 'draft';
expect( EditorLinks::published_link( 'https://cms.example.com/article/', $post ), 'https://cms.example.com/article/', 'Draft links left to preview provider' );
$post->post_status = 'publish';
$post->post_password = 'not-public';
expect( EditorLinks::published_link( 'https://cms.example.com/article/', $post ), 'https://cms.example.com/article/', 'Password content is not redirected' );
$post->post_password = '';
$GLOBALS['admin'] = false;
expect( EditorLinks::published_link( 'https://cms.example.com/article/', $post ), 'https://cms.example.com/article/', 'No general permalink rewrite outside editor' );
expect( Safeguards::robots( array( 'index' => true ) ), array( 'noindex' => true, 'nofollow' => true ), 'WP robots use booleans' );
expect( Safeguards::rank_math_robots( array( 'index' => 'index', 'follow' => 'follow' ) ), array( 'index' => 'noindex', 'follow' => 'nofollow' ), 'Rank Math robots use strings' );
HeadlessBridge\Metadata::$active = true;
expect( Safeguards::rank_math_robots( array( 'index' => 'noindex', 'follow' => 'follow' ) ), array( 'index' => 'noindex', 'follow' => 'follow' ), 'Frontend per-page noindex preserved' );
expect( Safeguards::headers( array() ), array(), 'CMS noindex header not inserted into frontend generation' );
HeadlessBridge\Metadata::$active = false;
expect( Safeguards::headers( array() ), array( 'X-Robots-Tag' => 'noindex, nofollow' ), 'CMS noindex response header' );
expect( Safeguards::mail( null, array() ), false, 'Suppressed mail reports no delivery' );
expect( Safeguards::mail( true, array() ), true, 'Respect existing mail provider result' );
expect( Safeguards::mail( false, array() ), false, 'Respect an earlier mail provider failure' );
$GLOBALS['filters']['headless_bridge/allow_cms_mail'] = function () { return true; };
expect( Safeguards::mail( null, array() ), null, 'Explicit per-call mail opt-in' );
unset( $GLOBALS['filters']['headless_bridge/allow_cms_mail'] );
// Registration, not just callback results, is the public opt-in contract. A CMS
// noindex policy must never silently disable core sitemaps or suppress mail.
$GLOBALS['options'][ Settings::OPTION ] = $config;
$GLOBALS['options'][ Settings::OPTION ]['cms_noindex'] = true;
$GLOBALS['registered_filters'] = array();
Safeguards::register();
expect( isset( $GLOBALS['registered_filters']['wp_robots'] ), true, 'CMS noindex registers its existing robots policy' );
expect( isset( $GLOBALS['registered_filters']['wp_sitemaps_enabled'] ), false, 'CMS noindex alone leaves core sitemap policy untouched' );
expect( isset( $GLOBALS['registered_filters']['pre_wp_mail'] ), false, 'CMS noindex alone leaves mail delivery untouched' );
$GLOBALS['options'][ Settings::OPTION ] = $safeguard_config;
$GLOBALS['registered_filters'] = array();
Safeguards::register();
expect( $GLOBALS['registered_filters']['wp_sitemaps_enabled'][ PHP_INT_MAX ], array( array( array( Safeguards::class, 'suppress_core_sitemaps' ), 1 ) ), 'Core sitemap suppression has one final bridge owner' );
expect( Safeguards::suppress_core_sitemaps( true ), false, 'Opt-in blocks an otherwise enabled core sitemap' );
expect( Safeguards::suppress_core_sitemaps( false ), false, 'Existing disabled sitemap remains disabled' );
expect( $GLOBALS['registered_filters']['pre_wp_mail'][100], array( array( array( Safeguards::class, 'mail' ), 2 ) ), 'Mail opt-in uses the existing suppression callback' );
expect( isset( $GLOBALS['registered_filters']['wp_robots'] ), false, 'Core sitemap and mail choices do not implicitly enable CMS noindex' );
$GLOBALS['options'][ Settings::OPTION ]['frontend_origin'] = '';
$GLOBALS['registered_filters'] = array();
Safeguards::register();
expect( isset( $GLOBALS['registered_filters']['wp_sitemaps_enabled'] ), false, 'Invalid frontend configuration cannot suppress core sitemaps' );
expect( isset( $GLOBALS['registered_filters']['pre_wp_mail'] ), true, 'Existing mail suppression does not require URL mapping' );
$GLOBALS['options'][ Settings::OPTION ] = $config;
$GLOBALS['options']['permalink_structure'] = '';
$plain = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'metadata' => '1', 'graphql' => '1', 'editor_links' => '1', 'preview_links' => '1' ) );
expect( $plain['metadata'] || $plain['graphql'] || $plain['indexnow'] || $plain['editor_links'], false, 'Unsupported plain permalinks diagnosed and modules disabled' );
expect( $plain['preview_links'], true, 'Provider query-based previews remain supported independently of public pretty permalinks' );
expect( in_array( 'pretty_permalinks', $GLOBALS['errors'], true ), true, 'Administrator receives permalink explanation' );
echo 'PASS: ' . $checks . " mapping/settings/editor/safeguard assertions\n";
