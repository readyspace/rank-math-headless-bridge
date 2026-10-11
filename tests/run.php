<?php
/** Small deterministic contract tests; no WordPress installation or network needed. */
define( 'ABSPATH', __DIR__ . '/' );
$options = array( 'permalink_structure' => '/%postname%/' );
$cms_origin = 'https://cms.example.com';
$uploads_base = 'https://cms.example.com/custom-media';
$errors = array();
$filters = array();
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
function is_admin() { return $GLOBALS['admin']; }
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
foreach ( array( 'seo', 'metadata', 'graphql', 'indexnow', 'editor_links', 'cms_noindex', 'suppress_mail' ) as $module ) {
	expect( Settings::enabled( $module ), false, 'No activation side effects: ' . $module );
}
expect( Settings::clean_origin( 'https://EXAMPLE.com:443/' ), 'https://example.com', 'Normalize default port and case' );
foreach ( array( 'https://user:password@example.com', 'https://example.com/path', 'https://example.com?token=x', 'https://example.com#section', 'javascript:alert(1)' ) as $bad ) {
	expect( Settings::clean_origin( $bad ), '', 'Reject invalid frontend origin' );
}
$config = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com/', 'indexnow' => '1', 'metadata' => '1', 'public_post_types' => array( 'post', 'page', 'attachment', 'revision', 'private_type', 'post' ) ) );
expect( $config['public_post_types'], array( 'post', 'page' ), 'Public post types only; no media/revisions' );
expect( $config['indexnow'], true, 'Explicit valid module selection' );
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
$GLOBALS['filters']['headless_bridge/allow_cms_mail'] = function () { return true; };
expect( Safeguards::mail( null, array() ), null, 'Explicit per-call mail opt-in' );
$GLOBALS['options']['permalink_structure'] = '';
$plain = Settings::sanitize( array( 'frontend_origin' => 'https://www.example.com', 'indexnow' => '1', 'metadata' => '1', 'graphql' => '1', 'editor_links' => '1' ) );
expect( $plain['metadata'] || $plain['graphql'] || $plain['indexnow'] || $plain['editor_links'], false, 'Unsupported plain permalinks diagnosed and modules disabled' );
expect( in_array( 'pretty_permalinks', $GLOBALS['errors'], true ), true, 'Administrator receives permalink explanation' );
echo 'PASS: ' . $checks . " mapping/settings/editor/safeguard assertions\n";
