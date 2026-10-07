<?php
/** Standalone regression tests; no WordPress, network or database required. */
define( 'ABSPATH', '/test-wordpress/' );

final class WP_Error {
	private $code;
	private $data;
	public function __construct( $code, $message, $data = [] ) { $this->code = $code; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_data() { return $this->data; }
}
final class WP_REST_Request {
	private $params = [];
	private $route;
	public function __construct( $method, $route ) { $this->route = $route; }
	public function set_param( $name, $value ) { $this->params[ $name ] = $value; }
	public function get_param( $name ) { return $this->params[ $name ] ?? null; }
	public function get_route() { return $this->route; }
}
final class WP_REST_Response {
	private $data;
	private $status;
	public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function get_data() { return $this->data; }
	public function get_status() { return $this->status; }
}
final class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public $post_password;
	public function __construct( $id, $type = 'post', $status = 'publish', $password = '' ) {
		$this->ID = $id; $this->post_type = $type; $this->post_status = $status; $this->post_password = $password;
	}
}
final class WP_Hook { public $callbacks = []; }

$GLOBALS['wp_filter'] = [];
$GLOBALS['test_settings'] = [ 'frontend_origin' => 'https://www.example.com', 'metadata' => true, 'seo' => true, 'indexnow' => true, 'cms_noindex' => true, 'public_post_types' => [ 'post', 'page' ] ];
$GLOBALS['test_posts'] = [ 1 => new WP_Post( 1 ) ];
$GLOBALS['test_native_calls'] = 0;
$GLOBALS['test_native_mode'] = 'normal';
$GLOBALS['test_html'] = '<title>Hello &amp; Goodbye ✅</title><meta name="description" content="&lt;b&gt;About&lt;/b&gt; this page"><link rel="canonical" href="https://cms.example.com/wp/story/"><meta name="robots" content="noindex, follow"><meta property="og:url" content="https://cms.example.com/wp/story/"><meta property="og:image" content="https://cms.example.com/wp/wp-content/uploads/cover.jpg"><meta property="og:image" content="https://cdn.example.com/cover2.jpg"><meta name="twitter:card" content="summary_large_image"><script>alert("ignored")</script><script type="application/ld+json">{"@id":"https://cms.example.com/wp/story/#article","image":"https://cms.example.com/wp/wp-content/uploads/cover.jpg","description":"<b>Article</b>"}</script><script type="application/ld+json">invalid JSON</script>';

function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url() { return 'https://cms.example.com/wp'; }
function wp_get_upload_dir() { return [ 'baseurl' => 'https://cms.example.com/wp/wp-content/uploads' ]; }
function wp_upload_dir() { return wp_get_upload_dir(); }
function get_option( $name, $default = false ) {
	if ( 'headless_bridge_settings' === $name ) { return $GLOBALS['test_settings']; }
	if ( 'blog_public' === $name ) { return apply_filters( 'pre_option_blog_public', false ) ?: 0; }
	return $default;
}
function rank_math() { return null; }
function wp_strip_all_tags( $text, $remove_breaks = false ) { return strip_tags( $text ); }
function get_post( $id ) { return $GLOBALS['test_posts'][ $id ] ?? null; }
function get_permalink( $post ) { return 'https://cms.example.com/wp/story/'; }
function get_post_type_object( $type ) {
	return (object) [ 'name' => $type, 'public' => 'secret' !== $type, 'publicly_queryable' => 'hidden' !== $type ];
}
function is_post_type_viewable( $type ) { return $type && $type->public && $type->publicly_queryable; }
function wp_is_post_revision( $id ) { return 10 === $id; }
function wp_is_post_autosave( $id ) { return 11 === $id; }
function get_queried_object_id() { return $GLOBALS['wp_query']->queried_object->ID; }
function get_queried_object() { return $GLOBALS['wp_query']->queried_object; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function rest_ensure_response( $value ) { return new WP_REST_Response( $value ); }
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	if ( ! isset( $GLOBALS['wp_filter'][ $tag ] ) ) { $GLOBALS['wp_filter'][ $tag ] = new WP_Hook(); }
	$GLOBALS['wp_filter'][ $tag ]->callbacks[ $priority ][] = [ $callback, $accepted_args ];
}
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) { add_filter( $tag, $callback, $priority, $accepted_args ); }
function remove_filter( $tag, $callback, $priority = 10 ) {
	foreach ( $GLOBALS['wp_filter'][ $tag ]->callbacks[ $priority ] ?? [] as $key => $entry ) {
		if ( $entry[0] === $callback ) { unset( $GLOBALS['wp_filter'][ $tag ]->callbacks[ $priority ][ $key ] ); }
	}
}
function apply_filters( $tag, $value, ...$arguments ) {
	$callbacks = $GLOBALS['wp_filter'][ $tag ]->callbacks ?? [];
	ksort( $callbacks );
	foreach ( $callbacks as $entries ) { foreach ( $entries as $entry ) { $value = call_user_func_array( $entry[0], array_slice( [ $value, ...$arguments ], 0, $entry[1] ) ); } }
	return $value;
}
function do_action( $tag, ...$arguments ) {
	$callbacks = $GLOBALS['wp_filter'][ $tag ]->callbacks ?? [];
	ksort( $callbacks );
	foreach ( $callbacks as $entries ) { foreach ( $entries as $entry ) { call_user_func_array( $entry[0], array_slice( $arguments, 0, $entry[1] ) ); } }
}
function rest_do_request( $request ) {
	$GLOBALS['test_native_calls']++;
	check( '/rankmath/v1/getHead' === $request->get_route(), 'Use the native internal route.' );
	check( 'https://cms.example.com/wp/story/' === $request->get_param( 'url' ), 'Use the trusted CMS permalink.' );
	check( [] === $_GET && [] === $_POST && [] === $_REQUEST, 'Incoming query parameters cannot alter the trusted native query.' );
	check( '' === $_SERVER['PATH_INFO'] && '' === $_SERVER['QUERY_STRING'], 'FastCGI path and query input cannot override the trusted permalink.' );
	check( \HeadlessBridge\Metadata::is_frontend_request(), 'Frontend scope is active during generation.' );
	check( 1 === get_option( 'blog_public' ), 'CMS-wide noindex is bypassed only within generation.' );
	$GLOBALS['wp_filter']['wp'] = new WP_Hook();
	$GLOBALS['wp_filter']['parse_request'] = new WP_Hook();
	add_filter( 'test_added_inside_native', '__return_true' );
	$_SERVER['REQUEST_URI'] = '/story/';
	$GLOBALS['wp_query'] = (object) [ 'queried_object' => new WP_Post( 'mismatch' === $GLOBALS['test_native_mode'] ? 2 : 1 ) ];
	if ( 'shortcircuit' === $GLOBALS['test_native_mode'] ) { return new WP_REST_Response( [ 'success' => true, 'head' => $GLOBALS['test_html'] ] ); }
	do_action( 'rank_math/frontend/loaded' );
	if ( 'throw' === $GLOBALS['test_native_mode'] ) { ob_start(); echo 'must not leak'; throw new RuntimeException( 'Dependency failed.' ); }
	if ( 'unavailable' === $GLOBALS['test_native_mode'] ) { return new WP_Error( 'rest_no_route', 'No native route.' ); }
	return new WP_REST_Response( [ 'success' => true, 'head' => $GLOBALS['test_html'] ] );
}
function check( $condition, $message ) {
	$GLOBALS['test_assertions'] = ( $GLOBALS['test_assertions'] ?? 0 ) + 1;
	if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); }
}

require dirname( __DIR__ ) . '/includes/class-settings.php';
require dirname( __DIR__ ) . '/includes/class-url-mapper.php';
require dirname( __DIR__ ) . '/includes/class-rank-math.php';
require dirname( __DIR__ ) . '/includes/class-metadata.php';

use HeadlessBridge\Metadata;
use HeadlessBridge\RankMath;

check( class_exists( 'DOMDocument' ), 'The PHP DOM extension is required for these metadata tests.' );
$normalized = Metadata::normalize_head( $GLOBALS['test_html'] );
check( ! is_wp_error( $normalized ), 'Valid Rank Math HTML is normalized.' );
check( 'Hello & Goodbye ✅' === $normalized['title'], 'Title entities and UTF-8 are preserved.' );
check( 'About this page' === $normalized['description'], 'Text fields contain no HTML.' );
check( 'https://www.example.com/story/' === $normalized['canonical'], 'Canonical points to the frontend.' );
check( 'noindex, follow' === $normalized['robots']->robots, 'Per-post noindex is preserved.' );
check( 3 === count( $normalized['openGraph'] ), 'Repeated Open Graph images are retained.' );
check( 'https://cms.example.com/wp/wp-content/uploads/cover.jpg' === $normalized['openGraph'][1]['content'], 'CMS media URLs remain unchanged.' );
check( 'https://www.example.com/story/#article' === $normalized['jsonLd'][0]['@id'], 'Schema identities map with fragments.' );
check( 'Article' === $normalized['jsonLd'][0]['description'], 'Schema text does not contain markup.' );
check( 1 === count( $normalized['jsonLd'] ), 'Malformed JSON-LD is ignored.' );
check( ! isset( $normalized['head'] ) && ! isset( $normalized['html'] ), 'Raw head HTML is never returned.' );
check( is_wp_error( Metadata::normalize_head( '<script>alert(1)</script>' ) ), 'Unsupported executable HTML is rejected.' );
check( '' === Metadata::normalize_head( '<title>Safe</title><link rel="canonical" href="javascript:alert(1)">' )['canonical'], 'Executable canonical schemes are rejected.' );
check( 'https://another.example.com/source/' === Metadata::normalize_head( '<link rel="canonical" href="https://another.example.com/source/">' )['canonical'], 'Deliberate external canonicals are preserved.' );

$submitted = RankMath::submission_urls( [ 'https://cms.example.com/wp/story/', 'https://www.example.com/story/', 'https://cms.example.com/wp/story/?preview=true', 'https://other.example.com/story/', 'https://cms.example.com/wp/wp-admin/', 7 ] );
check( [ 'https://www.example.com/story/' ] === $submitted, 'IndexNow submissions are mapped, deduplicated and filtered.' );
check( false === RankMath::submission_urls( [] ), 'An empty IndexNow list cancels submission.' );
check( 'https://www.example.com/story/' === RankMath::submission_urls( 'https://cms.example.com/wp/story/' ), 'Scalar submission remains scalar.' );
check( false === RankMath::submission_urls( 'https://cms.example.com/wp/story/?tracking=1' ), 'IndexNow refuses all query URLs.' );
check( 'https://cms.example.com/wp/key.txt' === RankMath::indexnow_key_location( 'https://cms.example.com/wp/key.txt', 'serve_api_key' ), 'The CMS verification handler is preserved.' );
eval( 'namespace RankMath\\Instant_Indexing; final class Api { public static function get() { return new self(); } public function get_key() { return "test-verification-key-12345678"; } }' );
check( 'www.example.com' === RankMath::indexnow_host( 'cms.example.com' ), 'IndexNow uses the exact configured public host.' );
check( 'https://www.example.com/test-verification-key-12345678.txt' === RankMath::indexnow_key_location( 'https://cms.example.com/wp/key.txt', 'request_payload' ), 'IndexNow verifies the current key on the public frontend host.' );
check( 'https://cms.example.com/wp/key.txt' === RankMath::indexnow_key_location( 'https://cms.example.com/wp/key.txt', 'serve_api_key' ), 'A present API object does not change CMS key serving.' );

$request = new WP_REST_Request( 'GET', '/headless-bridge/v1/head' );
$request->set_param( 'post_id', 1 );
$GLOBALS['wp_query'] = (object) [ 'marker' => 'original' ];
add_action( 'wp', static function () {} );
add_action( 'parse_request', static function () {} );
$_GET = [ 'post_id' => '1', 'p' => '999', 'preview' => 'true' ];
$_POST = [ 'name' => 'private' ];
$_REQUEST = $_GET + $_POST;
$_SERVER['REQUEST_URI'] = '/wp-json/headless-bridge/v1/head?post_id=1&p=999';
$_SERVER['PATH_INFO'] = '/wp-json/headless-bridge/v1/head';
$_SERVER['QUERY_STRING'] = 'p=999&preview=true';
$outer = [ $_GET, $_POST, $_REQUEST, $_SERVER['REQUEST_URI'], $_SERVER['PATH_INFO'], $_SERVER['QUERY_STRING'], ob_get_level() ];
$response = Metadata::get_head( $request );
check( ! is_wp_error( $response ) && 1 === $response->get_data()['postId'], 'Published public content returns normalized metadata.' );
check( 'noindex, follow' === $response->get_data()['robots']->robots, 'Frontend generation retains page-level noindex.' );

foreach ( [ 'normal', 'mismatch', 'throw', 'unavailable', 'shortcircuit' ] as $mode ) {
	$GLOBALS['test_native_mode'] = $mode;
	$response = Metadata::get_head( $request );
	check( 'normal' === $mode ? ! is_wp_error( $response ) : is_wp_error( $response ), 'Query mismatch and dependency errors fail closed.' );
	check( [ $_GET, $_POST, $_REQUEST, $_SERVER['REQUEST_URI'], $_SERVER['PATH_INFO'], $_SERVER['QUERY_STRING'], ob_get_level() ] === $outer, 'All input and output-buffer context is restored.' );
	check( ! Metadata::is_frontend_request() && 0 === get_option( 'blog_public' ), 'Frontend scope and CMS-wide indexing policy are restored.' );
	check( 'original' === $GLOBALS['wp_query']->marker, 'The original WordPress query is restored.' );
	check( ! isset( $GLOBALS['wp_filter']['test_added_inside_native'] ), 'Native hook additions do not leak into the REST caller.' );
	check( ! empty( $GLOBALS['wp_filter']['wp']->callbacks ) && ! empty( $GLOBALS['wp_filter']['parse_request']->callbacks ), 'Native removed hooks are restored.' );
}

$GLOBALS['test_native_mode'] = 'normal';
foreach ( [ new WP_Post( 2, 'post', 'private' ), new WP_Post( 3, 'post', 'draft' ), new WP_Post( 4, 'post', 'future' ), new WP_Post( 5, 'post', 'publish', 'secret' ), new WP_Post( 6, 'secret' ), new WP_Post( 7, 'attachment' ), new WP_Post( 8, 'hidden' ), new WP_Post( 10 ), new WP_Post( 11 ) ] as $post ) {
	$GLOBALS['test_posts'][ $post->ID ] = $post;
	$request->set_param( 'post_id', $post->ID );
	$calls = $GLOBALS['test_native_calls'];
	$response = Metadata::get_head( $request );
	check( is_wp_error( $response ) && 404 === $response->get_error_data()['status'], 'Private, password-protected, disallowed and revision posts are rejected.' );
	check( $calls === $GLOBALS['test_native_calls'], 'Rejected posts never invoke Rank Math generation.' );
}
$request->set_param( 'post_id', '1e2' );
check( 400 === Metadata::get_head( $request )->get_error_data()['status'], 'Ambiguous IDs are rejected.' );

echo 'Metadata and Rank Math regression tests: ' . $GLOBALS['test_assertions'] . " assertions passed.\n";
