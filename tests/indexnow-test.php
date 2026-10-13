<?php
/** Offline IndexNow policy contracts. No WordPress bootstrap, database or network. */
namespace RankMath {
	if ( ! in_array( '--missing-native', $GLOBALS['argv'] ?? [], true ) ) {
		final class Helper {
			public static function is_module_active( $module ) { return 'instant-indexing' === $module && $GLOBALS['instant_active']; }
			public static function is_post_indexable( $id ) { return 3 !== $id; }
		}
	}
}
namespace RankMath\Instant_Indexing {
	if ( ! in_array( '--missing-native', $GLOBALS['argv'] ?? [], true ) ) {
		final class Api {
			public static function get() { return new self(); }
			public function get_key() { ++$GLOBALS['key_reads']; return $GLOBALS['fixture_key']; }
		}
	}
}
namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	final class WP_Post {
		public $ID, $post_type, $post_status, $post_password;
		public function __construct( $id, $status = 'publish', $password = '', $type = 'post' ) {
			$this->ID = $id; $this->post_status = $status; $this->post_password = $password; $this->post_type = $type;
		}
	}
	final class WP_Error {
		private $code, $data;
		public function __construct( $code, $message = '', $data = [] ) { $this->code = $code; $this->data = $data; }
		public function get_error_code() { return $this->code; }
		public function get_error_data() { return $this->data; }
	}
	final class WP_REST_Response {
		private $data;
		public $headers = [];
		public function __construct( $data ) { $this->data = $data; }
		public function get_data() { return $this->data; }
		public function header( $name, $value ) { $this->headers[ $name ] = $value; }
	}
	final class FixtureHook { public $callbacks = []; }
	$wp_filter = []; $routes = []; $checks = 0; $key_reads = 0; $instant_active = true;
	$fixture_key = str_repeat( 'a', 32 );
	$cms_origin = 'https://cms.example.com';
	$options = [
		'permalink_structure' => '/%postname%/',
		'headless_bridge_settings' => [ 'frontend_origin' => 'https://example.com', 'indexnow' => true, 'metadata' => false, 'public_post_types' => [ 'post', 'page' ] ],
		'rank-math-options-instant-indexing' => [ 'indexnow_api_key' => $fixture_key, 'bing_post_types' => [ 'post', 'page' ] ],
		'rank_math_indexnow_log' => [ [ 'url' => 'https://example.com/history/', 'status' => 200 ] ],
	];
	$posts = [ 1 => new WP_Post( 1 ), 2 => new WP_Post( 2, 'draft' ), 3 => new WP_Post( 3 ), 4 => new WP_Post( 4, 'publish', 'secret' ), 5 => new WP_Post( 5 ), 6 => new WP_Post( 6, 'trash' ), 7 => new WP_Post( 7, 'publish', '', 'private_record' ), 8 => new WP_Post( 8, 'publish', '', 'page' ), 9 => new WP_Post( 9, 'future' ), 10 => new WP_Post( 10 ), 11 => new WP_Post( 11 ) ];
	$post_paths = [ 1 => '/article', 2 => '/draft', 3 => '/noindex', 4 => '/password', 5 => '/external', 6 => '/deleted', 7 => '/private-record', 8 => '/page', 9 => '/future', 10 => '/revision', 11 => '/autosave' ];
	$post_meta = [ 5 => [ 'rank_math_canonical_url' => 'https://foreign.example/article/' ] ];
	function callback_id( $callback ) { return is_array( $callback ) ? $callback[0] . '::' . $callback[1] : ( is_string( $callback ) ? $callback : spl_object_hash( $callback ) ); }
	function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) {
		if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) { $GLOBALS['wp_filter'][ $hook ] = new FixtureHook(); }
		$GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][ callback_id( $callback ) ] = [ $callback, $accepted ];
	}
	function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) { add_filter( $hook, $callback, $priority, $accepted ); }
	function apply_filters( $hook, $value, ...$args ) {
		$priorities = $GLOBALS['wp_filter'][ $hook ]->callbacks ?? []; ksort( $priorities );
		foreach ( $priorities as $entries ) { foreach ( $entries as $entry ) { $value = call_user_func_array( $entry[0], array_slice( [ $value, ...$args ], 0, $entry[1] ) ); } }
		return $value;
	}
	function do_action( $hook, ...$args ) {
		$priorities = $GLOBALS['wp_filter'][ $hook ]->callbacks ?? []; ksort( $priorities );
		foreach ( $priorities as $entries ) { foreach ( $entries as $entry ) { call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) ); } }
	}
	function get_option( $name, $default = false ) { return $GLOBALS['options'][ $name ] ?? $default; }
	function update_option( ...$args ) { throw new \RuntimeException( 'Unexpected option write.' ); }
	function wp_remote_request( ...$args ) { throw new \RuntimeException( 'Unexpected network request.' ); }
	function home_url( $path = '' ) { return $GLOBALS['cms_origin'] . $path; }
	function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
	function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
	function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['post_meta'][ $id ][ $key ] ?? ''; }
	function url_to_postid( $url ) {
		$base = parse_url( $GLOBALS['cms_origin'], PHP_URL_PATH ) ?? '';
		$path = substr( parse_url( $url, PHP_URL_PATH ) ?? '/', strlen( $base ) );
		return (int) array_search( rtrim( $path, '/' ), $GLOBALS['post_paths'], true );
	}
	function get_post_type_object( $type ) { return (object) [ 'name' => $type, 'public' => in_array( $type, [ 'post', 'page', 'attachment' ], true ) ]; }
	function is_post_type_viewable( $type ) { return $type && $type->public; }
	function wp_is_post_revision( $id ) { return 10 === $id; }
	function wp_is_post_autosave( $id ) { return 11 === $id; }
	function wp_get_upload_dir() { return [ 'baseurl' => home_url( '/wp-content/uploads' ) ]; }
	function rank_math() { return new \stdClass(); }
	function register_rest_route( $namespace, $route, $args ) { $GLOBALS['routes'][ $namespace . $route ] = $args; }
	function rest_ensure_response( $value ) { return new WP_REST_Response( $value ); }
	function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_SLASHES ); }
	function is_wp_error( $value ) { return $value instanceof WP_Error; }
	function __( $value, $domain = '' ) { return $value; }
	function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
	function add_settings_error( ...$args ) { $GLOBALS['settings_errors'][] = $args[1]; }
	function verify( $condition, $label ) { ++$GLOBALS['checks']; if ( ! $condition ) { throw new \RuntimeException( 'FAIL: ' . $label ); } }
	function blocked( $args, $endpoint = 'https://api.indexnow.org/indexnow/' ) { return is_wp_error( \HeadlessBridge\RankMath::request_guard( false, $args, $endpoint ) ); }
	function payload_args( $urls ) {
		return [ 'body' => wp_json_encode( [ 'host' => 'example.com', 'key' => $GLOBALS['fixture_key'], 'keyLocation' => 'https://example.com/indexnow.txt', 'urlList' => $urls ] ), 'headers' => [ 'Content-Type' => 'application/json' ] ];
	}
	require dirname( __DIR__ ) . '/includes/class-settings.php';
	require dirname( __DIR__ ) . '/includes/class-url-mapper.php';
	require dirname( __DIR__ ) . '/includes/class-rank-math.php';
	use HeadlessBridge\RankMath;
	use HeadlessBridge\Settings;
	$snapshot = wp_json_encode( $options );
	RankMath::register(); do_action( 'rest_api_init' );
	verify( isset( $routes['headless-bridge/v1/indexnow-key'] ) && ! isset( $routes['headless-bridge/v1/head'] ), 'Key transport is independent of the disabled REST SEO module.' );
	verify( 'GET' === $routes['headless-bridge/v1/indexnow-key']['methods'] && '__return_true' === $routes['headless-bridge/v1/indexnow-key']['permission_callback'], 'Key route is public and GET only.' );
	if ( in_array( '--missing-native', $argv ?? [], true ) ) {
		verify( ! RankMath::indexnow_ready(), 'Missing native dependencies are not ready.' );
		verify( is_wp_error( RankMath::indexnow_key_response() ), 'Missing native key response fails closed.' );
		verify( blocked( payload_args( [ 'https://example.com/article/' ] ) ), 'Missing native dependency prevents final HTTP traffic.' );
		verify( 'native-local' === RankMath::indexnow_key_location( 'native-local', 'serve_api_key' ), 'Missing native dependency preserves CMS key serving.' );
		verify( $snapshot === wp_json_encode( $options ), 'Dependency failure changes no options or history.' );
		echo 'IndexNow missing-native tests: ' . $checks . " assertions passed.\n"; exit;
	}
	foreach ( [ 'indexnow_host', 'indexnow_key_location', 'submit_url', 'publish_url' ] as $hook ) {
		$count = 0; foreach ( $wp_filter['rank_math/instant_indexing/' . $hook]->callbacks as $entries ) { $count += count( $entries ); }
		verify( 1 === $count, 'One official callback owns ' . $hook . '.' );
	}
	verify( RankMath::indexnow_ready(), 'Enabled native module and supported settings are ready.' );
	verify( 'example.com' === RankMath::indexnow_host( 'cms.example.com' ), 'Exact public apex host.' );
	verify( '/' . $fixture_key . '.txt' === RankMath::indexnow_key_path(), 'Existing default key filename remains compatible.' );
	$options[ Settings::OPTION ]['indexnow_key_path'] = '/indexnow.txt';
	verify( 'https://example.com/indexnow.txt' === RankMath::indexnow_key_location( 'native-local', 'request_payload' ), 'Configured public key location.' );
	verify( 'native-local' === RankMath::indexnow_key_location( 'native-local', 'serve_api_key' ), 'Native CMS key-serving context remains unchanged.' );
	$response = RankMath::indexnow_key_response();
	verify( ! is_wp_error( $response ) && [ 'key' => $fixture_key ] === $response->get_data(), 'Current native key is read without rotation.' );
	verify( 'no-store, max-age=0' === $response->headers['Cache-Control'], 'Key transport forbids caching.' );
	$options[ Settings::OPTION ] += [ 'indexnow_source_origins' => [ 'http://cms.example.com', 'http://www.example.com', 'https://www.example.com' ], 'indexnow_excluded_paths' => [ '/api', '/preview', '/thank-you' ], 'indexnow_trailing_slash' => 'add' ];
	foreach ( [ 'https://cms.example.com/article', 'https://example.com/article/', 'http://www.example.com/article', 'https://www.example.com/article/' ] as $url ) { verify( 'https://example.com/article/' === RankMath::submission_urls( $url ), 'Trusted historical alias maps: ' . $url ); }
	verify( '' === \HeadlessBridge\UrlMapper::to_frontend( 'https://www.example.com/article/' ), 'IndexNow aliases never expand SEO trust.' );
	foreach ( [ 'https://staging.example.com/article/', 'https://foreign.example/article/', 'https://cms.example.com/wp-admin/', 'https://cms.example.com/api/enquiry/', 'https://cms.example.com/preview/', 'https://cms.example.com/%2570review/', 'https://cms.example.com/thank-you/', 'https://cms.example.com/indexnow.txt', 'https://cms.example.com/article/?preview=1', 'https://cms.example.com/article/#private', 'https://cms.example.com:443/article/', 'https://cms.example.com/%2e%2e/article/', 'https://cms.example.com/a%252fprivate/', 'https://cms.example.com/draft/', 'https://cms.example.com/noindex/', 'https://cms.example.com/password/', 'https://cms.example.com/external/', 'https://cms.example.com/private-record/', 'https://cms.example.com/future/', 'https://cms.example.com/revision/', 'https://cms.example.com/autosave/' ] as $url ) { verify( false === RankMath::submission_urls( $url ), 'Unsafe/private URL rejected: ' . $url ); }
	verify( [ 'https://example.com/article/' ] === RankMath::submission_urls( [ 'https://cms.example.com/article', 'https://www.example.com/article/' ] ), 'Mapped aliases are deduplicated.' );
	verify( false === RankMath::submission_urls( [ 'https://cms.example.com/article/', 'https://foreign.example/article/' ] ), 'Mixed batch fails wholly.' );
	verify( false === RankMath::submission_urls( [] ) && false === RankMath::submission_urls( null ), 'Empty/non-string input cancels submission.' );
	verify( 'https://example.com/previewed-article/' === RankMath::submission_urls( 'https://cms.example.com/previewed-article/' ), 'Excluded paths respect segment boundaries.' );
	verify( 'https://example.com/deleted/' === RankMath::publish_url( 'https://cms.example.com/deleted/', get_post( 6 ) ), 'Native deletion notification survives.' );
	verify( 'https://example.com/page/' === RankMath::publish_url( 'https://cms.example.com/page/', get_post( 8 ) ), 'Selected public Page survives.' );
	foreach ( [ 2, 3, 4, 5, 7, 9, 10, 11 ] as $id ) { verify( false === RankMath::publish_url( home_url( $post_paths[ $id ] . '/' ), get_post( $id ) ), 'Automatic restricted post rejected: ' . $id ); }
	$options[ Settings::OPTION ]['indexnow_trailing_slash'] = 'remove';
	verify( 'https://example.com/article' === RankMath::submission_urls( 'https://cms.example.com/article/' ), 'Explicit remove slash policy.' );
	$options[ Settings::OPTION ]['indexnow_trailing_slash'] = 'preserve';
	verify( 'https://example.com/article' === RankMath::submission_urls( 'https://cms.example.com/article' ), 'Default preserves slashless path.' );
	$options[ Settings::OPTION ]['indexnow_trailing_slash'] = 'add';
	$cms_origin = 'https://cms.example.com/wp';
	verify( 'https://example.com/article/' === RankMath::submission_urls( 'http://cms.example.com/wp/article' ), 'CMS HTTP alias respects subdirectory.' );
	verify( false === RankMath::submission_urls( 'http://cms.example.com/article/' ), 'CMS alias cannot bypass subdirectory boundary.' );
	$cms_origin = 'https://cms.example.com';
	$args = payload_args( [ 'https://cms.example.com/article', 'https://www.example.com/article/' ] );
	$mapped = RankMath::request_args( $args, 'https://api.indexnow.org/indexnow/' );
	verify( [ 'https://example.com/article/' ] === json_decode( $mapped['body'], true )['urlList'], 'Manual native bypass payload is mapped and deduplicated.' );
	verify( ! blocked( $mapped ), 'Valid final public payload is allowed.' );
	verify( blocked( $args ), 'Unmapped final payload is rejected.' );
	verify( $args === RankMath::request_args( $args, 'https://another.example/api' ) && ! blocked( $args, 'https://another.example/api' ), 'Unrelated HTTP traffic is untouched.' );
	verify( true === RankMath::request_guard( true, $mapped, 'https://api.indexnow.org/indexnow/' ), 'Existing successful HTTP interceptor result is preserved.' );
	foreach ( [ 'host' => 'cms.example.com', 'key' => str_repeat( 'b', 32 ), 'keyLocation' => 'https://example.com/stale.txt', 'urlList' => [ 'https://example.com/article/', 'https://example.com/preview/' ] ] as $field => $value ) {
		$payload = json_decode( $mapped['body'], true ); $payload[ $field ] = $value;
		verify( blocked( [ 'body' => wp_json_encode( $payload ) ] ), 'Invalid final payload rejected: ' . $field );
	}
	foreach ( [ [], [ 'body' => '{}' ], [ 'body' => '{' ], [ 'body' => [] ] ] as $invalid ) { verify( blocked( $invalid ), 'Malformed payload fails closed.' ); }
	$instant_active = false;
	verify( ! RankMath::indexnow_ready() && is_wp_error( RankMath::indexnow_key_response() ) && blocked( $mapped ), 'Disabled native module blocks key access and HTTP.' );
	$instant_active = true;
	$options[ Settings::OPTION ]['indexnow'] = false;
	verify( ! RankMath::indexnow_ready() && is_wp_error( RankMath::indexnow_key_response() ) && blocked( $mapped ), 'Disabled bridge module fails closed.' );
	$options[ Settings::OPTION ]['indexnow'] = true;
	$fixture_key = 'bad key';
	verify( '' === RankMath::indexnow_key() && is_wp_error( RankMath::indexnow_key_response() ) && blocked( $mapped ), 'Malformed native key fails closed without rotation.' );
	$fixture_key = str_repeat( 'a', 32 );
	$initial = json_decode( $snapshot, true );
	verify( $initial['rank-math-options-instant-indexing'] === $options['rank-math-options-instant-indexing'] && $initial['rank_math_indexnow_log'] === $options['rank_math_indexnow_log'], 'Every read-only mapping/key/guard check preserves native key/settings/history.' );
	$config = Settings::sanitize( [ 'frontend_origin' => 'https://example.com', 'indexnow' => '1', 'indexnow_key_path' => '/indexnow.txt', 'indexnow_source_origins' => "https://www.example.com\nhttps://www.example.com\nhttp://cms.example.com", 'indexnow_excluded_paths' => "/api/\n/preview\n/preview", 'indexnow_trailing_slash' => 'add', 'public_post_types' => [ 'post', 'page' ] ] );
	verify( '/indexnow.txt' === $config['indexnow_key_path'] && [ 'https://www.example.com', 'http://cms.example.com' ] === $config['indexnow_source_origins'] && [ '/api', '/preview' ] === $config['indexnow_excluded_paths'] && 'add' === $config['indexnow_trailing_slash'], 'Settings normalize multiline policies and deduplicate.' );
	foreach ( [ 'https://example.com/key.txt', '/key.txt?x=1', '/%2e%2e/key.txt', '//key.txt', '/../key.txt', '/key.json', '/key.txt#secret', '/key file.txt' ] as $path ) { verify( '' === Settings::clean_indexnow_path( $path, true ), 'Invalid public key path rejected: ' . $path ); }
	echo 'IndexNow policy tests: ' . $checks . " assertions passed.\n";
}
