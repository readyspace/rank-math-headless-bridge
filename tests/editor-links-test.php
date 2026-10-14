<?php
/** Isolated authenticated editor contracts. No WordPress, database or network. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'REST_REQUEST', true );
$options = array( 'permalink_structure' => '/%postname%/' );
$cms_origin = 'https://cms.example.com';
$allowed = false;
$admin = false;
$hooks = array();
$inline = array();
$current_post = null;
$provider_preview = '';
$checks = 0;

function get_option( $name, $default = false ) { return $GLOBALS['options'][ $name ] ?? $default; }
function home_url() { return $GLOBALS['cms_origin']; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function is_admin() { return $GLOBALS['admin']; }
function current_user_can( $capability, $id ) {
	$GLOBALS['last_capability'] = array( $capability, $id );
	return $GLOBALS['allowed'];
}
function get_post_type_object( $name ) {
	return in_array( $name, array( 'post', 'page', 'product', 'secret' ), true ) ? (object) array( 'public' => 'secret' !== $name ) : null;
}
function is_post_type_viewable( $type ) { return $type && $type->public; }
function get_post( $id = null ) { return null === $id ? $GLOBALS['current_post'] : ( $GLOBALS['posts'][ $id ] ?? null ); }
function add_filter( $name, $callback, $priority = 10, $arguments = 1 ) { $GLOBALS['hooks'][ $name ] = array( $callback, $priority, $arguments ); }
function add_action( $name, $callback, $priority = 10, $arguments = 1 ) { add_filter( $name, $callback, $priority, $arguments ); }
function apply_filters( $name, $value, ...$arguments ) { return isset( $GLOBALS['hooks'][ $name ] ) ? $GLOBALS['hooks'][ $name ][0]( $value, ...$arguments ) : $value; }
function get_preview_post_link( $post ) { return apply_filters( 'preview_post_link', $GLOBALS['provider_preview'], $post ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_add_inline_script( $handle, $script, $position ) { $GLOBALS['inline'][] = array( $handle, $script, $position ); return true; }
class WP_Post {
	public $ID = 42;
	public $post_type = 'post';
	public $post_status = 'publish';
	public $post_password = '';
}
class WP_REST_Request {
	public $context;
	public function get_param( $name ) { return 'context' === $name ? $this->context : null; }
}
class WP_REST_Response {
	private $data;
	public function __construct( $data ) { $this->data = $data; }
	public function get_data() { return $this->data; }
	public function set_data( $data ) { $this->data = $data; }
}
require __DIR__ . '/../includes/class-settings.php';
require __DIR__ . '/../includes/class-url-mapper.php';
require __DIR__ . '/../includes/class-editor-links.php';
use HeadlessBridge\EditorLinks;
use HeadlessBridge\Settings;
use HeadlessBridge\UrlMapper;

function editor_check( $condition, $message ) {
	$GLOBALS['checks']++;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function editor_equal( $actual, $expected, $message ) { editor_check( $actual === $expected, $message ); }

$settings = Settings::defaults();
$settings['frontend_origin'] = 'https://www.example.com';
$options[ Settings::OPTION ] = $settings;
EditorLinks::register();
editor_equal( $hooks, array(), 'Default-off editor and preview features must register no hooks.' );

$options[ Settings::OPTION ]['editor_links'] = true;
EditorLinks::register();
editor_equal( array_keys( $hooks ), array( 'post_link', 'page_link', 'post_type_link', 'rest_prepare_post', 'rest_prepare_page' ), 'View links alone must not take over previews.' );
editor_equal( $hooks['post_link'][1], 2000, 'Editor mapping must run after the existing provider.' );
editor_equal( $hooks['rest_prepare_post'][2], 3, 'REST responses need the request object as well as the content object.' );
$hooks = array();
$options[ Settings::OPTION ]['editor_links'] = false;
$options[ Settings::OPTION ]['preview_links'] = true;
EditorLinks::register();
editor_equal( array_keys( $hooks ), array( 'preview_post_link', 'enqueue_block_editor_assets' ), 'Preview support must be independently opt-in.' );
editor_equal( $hooks['preview_post_link'][1], 2000, 'Preview mapping must run after the provider parameters exist.' );
editor_equal( $hooks['preview_post_link'][2], 2, 'Preview authorization needs the actual post object.' );
$saved_hooks = $hooks;
$hooks = array();
$options[ Settings::OPTION ]['frontend_origin'] = $cms_origin;
EditorLinks::register();
editor_equal( $hooks, array(), 'Same-origin imports must not activate editor mapping.' );
$options[ Settings::OPTION ]['frontend_origin'] = 'https://www.example.com';
$hooks = $saved_hooks;

$post = new WP_Post();
$posts = array( $post->ID => $post );
$cms_link = 'https://cms.example.com/article/';
$public_link = 'https://www.example.com/article/';
$_GET = array( 'context' => 'edit' );
editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Anonymous REST edit queries must not change links.' );
$allowed = true;
$_GET = array( 'context' => 'view' );
editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Authenticated public REST reads must not change links.' );
$_GET = array();
editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Public/GraphQL requests without edit context remain untouched.' );
$admin = true;
editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'REST must not inherit ordinary-admin mapping without edit context.' );
$_GET = array( 'context' => array( 'edit' ) );
editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Malformed REST context must not grant editor behavior.' );
$_GET = array( 'context' => 'edit' );
editor_equal( EditorLinks::published_link( $cms_link, $post ), $public_link, 'Authorized REST edit responses use the public View URL.' );
editor_equal( $last_capability, array( 'edit_post', 42 ), 'Object-specific edit permission is required.' );
editor_equal( EditorLinks::page_link( $cms_link, 42 ), $public_link, 'Page links resolve their own post for permission checks.' );
editor_equal( EditorLinks::page_link( $cms_link, 999 ), $cms_link, 'Unknown pages are untouched.' );
editor_equal( EditorLinks::published_link( 'https://foreign.example/article/', $post ), 'https://foreign.example/article/', 'View links cannot target arbitrary origins.' );
editor_equal( EditorLinks::published_link( 'https://cms.example.com/wp-json/', $post ), 'https://cms.example.com/wp-json/', 'CMS API links remain CMS links.' );
foreach ( array( 'draft', 'private', 'future' ) as $status ) {
	$post->post_status = $status;
	editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Unpublished View links remain owned by the provider: ' . $status );
}
$post->post_status = 'publish';
$post->post_password = 'protected';
editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Password-protected View links remain unchanged.' );
$post->post_password = '';
foreach ( array( 'product', 'secret', 'missing' ) as $type ) {
	$post->post_type = $type;
	editor_equal( EditorLinks::published_link( $cms_link, $post ), $cms_link, 'Unselected, private or missing content types are untouched: ' . $type );
}
$post->post_type = 'post';

$_GET = array();
$request = new WP_REST_Request();
$request->context = 'edit';
$original_data = array( 'link' => $cms_link, 'guid' => array( 'rendered' => $cms_link ), 'title' => array( 'rendered' => 'Article' ) );
$response = new WP_REST_Response( $original_data );
editor_equal( EditorLinks::rest_response( $response, $post, $request ), $response, 'REST response identity must stay intact.' );
$expected_data = $original_data;
$expected_data['link'] = $public_link;
editor_equal( $response->get_data(), $expected_data, 'Actual REST edit context maps only link even when the query superglobal is empty.' );
$allowed = false;
$response = new WP_REST_Response( $original_data );
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data(), $original_data, 'REST response mapping cannot bypass object permission.' );
$allowed = true;
$request->context = 'view';
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data(), $original_data, 'REST public context does not inherit editor response mapping.' );
$request->context = 'edit';
$post->post_status = 'draft';
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data(), $original_data, 'Draft REST links stay with the preview provider.' );
$post->post_status = 'publish';
$post->post_password = 'protected';
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data(), $original_data, 'Password-protected REST links must not become public View destinations.' );
$post->post_password = '';
$post->post_type = 'product';
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data(), $original_data, 'Unselected post types remain untouched in REST response mapping.' );
$post->post_type = 'post';
$response = new WP_REST_Response( array( 'link' => 'https://foreign.example/article/' ) );
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data()['link'], 'https://foreign.example/article/', 'REST link mapping cannot replace a foreign provider destination.' );
$response = new WP_REST_Response( array( 'link' => array( 'unexpected' ) ) );
EditorLinks::rest_response( $response, $post, $request );
editor_equal( $response->get_data(), array( 'link' => array( 'unexpected' ) ), 'Unexpected response shapes are preserved.' );
editor_equal( EditorLinks::rest_response( 'provider-error', $post, $request ), 'provider-error', 'Non-REST provider results are preserved.' );

$preview = 'https://cms.example.com/preview/?p=42&preview=true&previewPathname=%2F%3Fp%3D42&typeName=Post&preview_id=42&preview_nonce=private-nonce&provider=a%2Bb#editor';
$expected = str_replace( 'https://cms.example.com', 'https://www.example.com', $preview );
$post->post_status = 'draft';
editor_equal( EditorLinks::preview_link( $preview, $post ), $expected, 'Authorized draft preview preserves provider query/fragment bytes.' );
editor_equal( EditorLinks::preview_link( $expected, $post ), $expected, 'A provider frontend destination remains byte-identical.' );
editor_equal( EditorLinks::preview_link( str_replace( 'https://cms.', 'http://cms.', $preview ), $post ), $expected, 'The configured CMS standard-port HTTP alias remains compatible.' );
$allowed = false;
editor_equal( EditorLinks::preview_link( $preview, $post ), $preview, 'Preview credentials must not be remapped for anonymous users.' );
$allowed = true;
editor_equal( EditorLinks::preview_link( $preview, null ), $preview, 'A preview without its post object cannot pass authorization.' );
editor_equal( EditorLinks::preview_link( null, $post ), null, 'A provider null result is preserved.' );
foreach ( array(
	'https://foreign.example/preview/?preview=true',
	'https://user:secret@cms.example.com/preview/?preview=true',
	'https://cms.example.com:8443/preview/?preview=true',
	'https://cms.example.com/wp-admin/?preview=true',
	'https://cms.example.com/%2577p-admin/?preview=true',
	'https://cms.example.com/wp-content/?preview=true',
	'https://cms.example.com/graphql?preview=true',
	'https://cms.example.com/api/preview/?preview=true',
	'https://cms.example.com/generate/?preview=true',
	'https://cms.example.com/xmlrpc.php?preview=true',
	'https://cms.example.com/a/../preview/?preview=true',
	'https://cms.example.com/a%252fwp-admin/?preview=true',
	'https://cms.example.com/preview%0a/?preview=true',
) as $unsafe ) {
	editor_equal( EditorLinks::preview_link( $unsafe, $post ), $unsafe, 'Unsafe editor destination must stay unchanged: ' . $unsafe );
}
editor_equal( UrlMapper::to_frontend( $preview, true ), '', 'Opt-in previews must not weaken the SEO/IndexNow mapper.' );
$cms_origin = 'https://cms.example.com/wp';
editor_equal( EditorLinks::preview_link( 'https://cms.example.com/wp/?preview=true&p=42', $post ), 'https://www.example.com/?preview=true&p=42', 'CMS subdirectories are removed at a path boundary.' );
editor_equal( EditorLinks::preview_link( 'https://cms.example.com/wphack/?preview=true&p=42', $post ), 'https://cms.example.com/wphack/?preview=true&p=42', 'CMS subdirectory lookalikes must remain untouched.' );
$cms_origin = 'https://cms.example.com';

$current_post = $post;
$provider_preview = $preview;
$allowed = false;
EditorLinks::enqueue_preview_compatibility();
editor_equal( $inline, array(), 'Anonymous editor rendering must not expose preview configuration.' );
$allowed = true;
$provider_preview = 'https://foreign.example/?preview=true&p=42';
EditorLinks::enqueue_preview_compatibility();
editor_equal( $inline, array(), 'Do not repair links around a foreign provider destination.' );
$provider_preview = $preview;
EditorLinks::enqueue_preview_compatibility();
editor_equal( count( $inline ), 1, 'Authorized configured previews receive one block-editor compatibility script.' );
editor_equal( $inline[0][0], 'wp-edit-post', 'Use the existing authenticated editor asset handle.' );
editor_equal( $inline[0][2], 'after', 'Provider configuration follows the editor asset.' );
preg_match( '/\}\)\((\{.*\})\);\s*$/s', $inline[0][1], $match );
$config = isset( $match[1] ) ? json_decode( $match[1], true ) : null;
editor_equal( $config['preview'] ?? null, $expected, 'The browser receives the provider destination after origin mapping.' );
editor_equal( $config['origins'] ?? null, array( 'https://cms.example.com', 'https://www.example.com', 'http://cms.example.com' ), 'Browser repair is scoped only to configured origins.' );
$inline = array();
$provider_preview = 'https://cms.example.com/?p=42&preview=true&extra=</script><script>alert(1)</script>';
EditorLinks::enqueue_preview_compatibility();
editor_check( false === strpos( $inline[0][1], '</script>' ), 'Provider query text cannot terminate the inline script.' );

echo 'PASS: ' . $checks . " authenticated editor/preview assertions\n";
