<?php
/** Runs in an isolated, real WordPress installation with Rank Math and WPGraphQL. */
require_once '/wordpress/wp-load.php';

use HeadlessBridge\Metadata;
use HeadlessBridge\Settings;

$checks = 0;
function bridge_assert( $condition, $message ) {
	$GLOBALS['checks']++;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

bridge_assert( class_exists( 'DOMDocument' ), 'DOM is required.' );
bridge_assert( function_exists( 'graphql' ) && function_exists( 'rank_math' ), 'Real dependencies must be active.' );
bridge_assert( \RankMath\Helper::get_settings( 'general.headless_support' ), 'Fixture must enable Rank Math headless support.' );
bridge_assert( isset( rest_get_server()->get_routes()['/rankmath/v1/getHead'] ), 'Native Rank Math route must be registered.' );
register_post_type( 'bridge_book', [ 'public' => true, 'show_in_graphql' => true, 'graphql_single_name' => 'BridgeBook', 'graphql_plural_name' => 'BridgeBooks', 'supports' => [ 'title', 'editor', 'custom-fields' ] ] );
flush_rewrite_rules( false );
$ids = [];
foreach ( [ 'alpha', 'beta', 'book' ] as $slug ) {
	$id = wp_insert_post( [ 'post_title' => ucfirst( $slug ), 'post_name' => 'bridge-' . $slug, 'post_content' => '<p>Content for ' . $slug . '.</p>', 'post_status' => 'publish', 'post_type' => 'book' === $slug ? 'bridge_book' : 'post' ] );
	update_post_meta( $id, 'rank_math_title', ucfirst( $slug ) . ' custom SEO title' );
	update_post_meta( $id, 'rank_math_description', 'Description for ' . $slug );
	update_post_meta( $id, 'rank_math_schema_Article', [ '@type' => 'Article', 'headline' => ucfirst( $slug ) . ' schema headline', 'isPrimary' => true ] );
	$ids[ $slug ] = $id;
}
update_post_meta( $ids['beta'], 'rank_math_robots', [ 'noindex', 'nofollow' ] );
$private = wp_insert_post( [ 'post_title' => 'Private', 'post_status' => 'private' ] );
$draft = wp_insert_post( [ 'post_title' => 'Draft', 'post_status' => 'draft' ] );
$password = wp_insert_post( [ 'post_title' => 'Protected', 'post_status' => 'publish', 'post_password' => 'fixture' ] );
$settings = Settings::get();
$settings['public_post_types'][] = 'bridge_book';
update_option( Settings::OPTION, $settings );
wp_set_current_user( 0 );

// REST and GraphQL must return the same computed output without request-state leakage.
$_GET = [ 'p' => $private, 'preview' => 'true' ];
$_REQUEST = $_GET;
$_SERVER['QUERY_STRING'] = http_build_query( $_GET );
$original_query = $GLOBALS['wp_query'];
$original_query_state = clone $GLOBALS['wp_query'];
$original_uri = $_SERVER['REQUEST_URI'];
$seo = [];
$generations = 0;
add_action( 'rank_math/frontend/loaded', static function () use ( &$generations ) { $generations++; }, 2 );
$native_failure = '';
add_action( 'headless_bridge/metadata_generation_failed', static function ( $error ) use ( &$native_failure ) { $native_failure = $error->getMessage(); } );
add_filter( 'rest_request_after_callbacks', static function ( $response ) use ( &$native_failure ) {
	if ( is_wp_error( $response ) ) { $native_failure = $response->get_error_code() . ': ' . $response->get_error_message(); }
	return $response;
} );
$original_hooks = array_keys( $GLOBALS['wp_filter'] );
foreach ( $ids as $slug => $id ) {
	bridge_assert( ( new \RankMath\Rest\Headless() )->is_valid_url( get_permalink( $id ) ), 'Native URL validation denied ' . get_permalink( $id ) . ' for CMS ' . home_url() . ' is_url=' . (int) \RankMath\Helpers\Url::is_url( get_permalink( $id ) ) . ' external=' . (int) \RankMath\Helpers\Url::is_external( get_permalink( $id ) ) );
	$seo[ $slug ] = Metadata::for_post( $id );
	bridge_assert( ! is_wp_error( $seo[ $slug ] ), 'Real Rank Math metadata failed: ' . $slug . ' ' . $native_failure . ( is_wp_error( $seo[ $slug ] ) ? ' ' . $seo[ $slug ]->get_error_message() : '' ) );
	bridge_assert( ucfirst( $slug ) . ' custom SEO title' === $seo[ $slug ]['title'], 'Unexpected title for ' . $slug . ': ' . $seo[ $slug ]['title'] );
	bridge_assert( 'Description for ' . $slug === $seo[ $slug ]['description'], 'Description leaked between posts: ' . $slug );
	if ( 'beta' !== $slug ) {
		bridge_assert( false !== strpos( wp_json_encode( $seo[ $slug ]['jsonLd'] ), ucfirst( $slug ) . ' schema headline' ), 'Schema did not use the requested post: ' . $slug );
	}
	bridge_assert( ( 'beta' === $slug && '' === $seo[ $slug ]['canonical'] ) || 0 === strpos( $seo[ $slug ]['canonical'], 'https://www.example.com/' ), 'Canonical must use frontend: ' . $slug . ' ' . $seo[ $slug ]['canonical'] );
	bridge_assert( $GLOBALS['wp_query'] === $original_query && $GLOBALS['wp_query'] == $original_query_state && $_SERVER['REQUEST_URI'] === $original_uri, 'Native provider changed outer request context or object identity.' );
	bridge_assert( isset( $_GET['preview'] ) && ! Metadata::is_frontend_request(), 'Input or scope leaked.' );
}
bridge_assert( false !== strpos( $seo['beta']['robots']->robots, 'noindex' ), 'Explicit per-post noindex lost.' );
bridge_assert( false === strpos( $seo['alpha']['robots']->robots, 'noindex' ), 'CMS noindex leaked into frontend SEO.' );
bridge_assert( $seo['alpha']['jsonLd'], 'Rank Math schema must be generated.' );
bridge_assert( array_keys( $GLOBALS['wp_filter'] ) === $original_hooks, 'Provider hooks leaked into the caller.' );

$fields = 'postId title description canonical robots { robots googlebot bingbot } openGraph { property content } twitter { name content } jsonLd';
$before_graphql = $generations;
$query = sprintf( '{ first: post(id: "%d", idType: DATABASE_ID) { headlessSeo { %s } databaseId title content } second: post(id: "%d", idType: DATABASE_ID) { headlessSeo { %s } databaseId title content } again: post(id: "%d", idType: DATABASE_ID) { headlessSeo { title } } bridgeBook(id: "%d", idType: DATABASE_ID) { headlessSeo { title } title } }', $ids['alpha'], $fields, $ids['beta'], $fields, $ids['alpha'], $ids['book'] );
$result = graphql( [ 'query' => $query ] );
bridge_assert( empty( $result['errors'] ), 'GraphQL execution failed: ' . wp_json_encode( $result['errors'] ?? [] ) );
bridge_assert( 3 === $generations - $before_graphql, 'Aliases must share generation only within this execution.' );
foreach ( [ 'first' => 'alpha', 'second' => 'beta' ] as $alias => $slug ) {
	$node = $result['data'][ $alias ];
	bridge_assert( $node['headlessSeo']['title'] === $seo[ $slug ]['title'], 'GraphQL and REST providers differ.' );
	bridge_assert( $node['headlessSeo']['canonical'] === $seo[ $slug ]['canonical'], 'GraphQL canonical differs.' );
	bridge_assert( ucfirst( $slug ) === $node['title'] && false !== strpos( $node['content'], 'Content for ' . $slug ), 'SEO generation corrupted later content fields.' );
	bridge_assert( ( 'beta' === $slug && [] === $node['headlessSeo']['jsonLd'] ) || ( ! empty( $node['headlessSeo']['jsonLd'] ) && is_array( json_decode( $node['headlessSeo']['jsonLd'][0], true ) ) ), 'Schema strings must remain valid JSON; noindex may omit schema.' );
}
bridge_assert( 'Alpha custom SEO title' === $result['data']['again']['headlessSeo']['title'], 'Repeated alias changed SEO.' );
bridge_assert( 'Book custom SEO title' === $result['data']['bridgeBook']['headlessSeo']['title'], 'CPT interface field missing.' );
$request = new WP_REST_Request( 'GET', '/headless-bridge/v1/head' );
$request->set_param( 'post_id', $ids['alpha'] );
$response = rest_do_request( $request );
bridge_assert( 200 === $response->get_status() && $response->get_data()['title'] === $seo['alpha']['title'], 'Real public REST transport must match GraphQL.' );

foreach ( [ $private, $draft, $password ] as $id ) {
	$data = Metadata::for_post( $id );
	bridge_assert( is_wp_error( $data ) && 404 === $data->get_error_data()['status'], 'Restricted post must be excluded.' );
}
// Even an administrator's query must not expose non-public SEO via this field.
wp_set_current_user( 1 );
foreach ( [ $private, $draft, $password ] as $id ) {
	$result = graphql( [ 'query' => sprintf( '{ post(id: "%d", idType: DATABASE_ID) { headlessSeo { title } } }', $id ) ] );
	bridge_assert( empty( $result['errors'] ) && null === ( $result['data']['post']['headlessSeo'] ?? null ), 'Authenticated query exposed restricted SEO.' );
}
$settings['metadata'] = false;
update_option( Settings::OPTION, $settings );
$request = new WP_REST_Request( 'GET', '/headless-bridge/v1/head' );
$request->set_param( 'post_id', $ids['alpha'] );
bridge_assert( is_wp_error( Metadata::get_head( $request ) ), 'Disabled REST transport must stay disabled.' );
$result = graphql( [ 'query' => sprintf( '{ post(id: "%d", idType: DATABASE_ID) { headlessSeo { title } } }', $ids['alpha'] ) ] );
bridge_assert( empty( $result['errors'] ) && 'Alpha custom SEO title' === $result['data']['post']['headlessSeo']['title'], 'GraphQL incorrectly depends on REST module.' );

// An earlier execution must not hide an upstream failure in a later execution.
$throw = static function () { throw new RuntimeException( 'Private provider diagnostics must stay private.' ); };
add_action( 'rank_math/frontend/loaded', $throw, 3 );
$result = graphql( [ 'query' => sprintf( '{ post(id: "%d", idType: DATABASE_ID) { title headlessSeo { title } } }', $ids['alpha'] ) ] );
remove_action( 'rank_math/frontend/loaded', $throw, 3 );
bridge_assert( ! empty( $result['errors'] ) && null === $result['data']['post']['headlessSeo'] && 'Alpha' === $result['data']['post']['title'], 'Provider failure must be nullable without hiding content.' );
bridge_assert( false === strpos( wp_json_encode( $result ), 'Private provider diagnostics' ), 'Public GraphQL error leaked internal diagnostics.' );
bridge_assert( ! Metadata::is_frontend_request() && isset( $_GET['preview'] ) && $_SERVER['REQUEST_URI'] === $original_uri, 'Failure did not restore context.' );

$settings['metadata'] = true;
$settings['graphql'] = false;
update_option( Settings::OPTION, $settings );

echo wp_json_encode( [ 'passed' => $checks, 'wordpress' => $GLOBALS['wp_version'], 'php' => PHP_VERSION, 'rankMath' => rank_math()->version, 'wpgraphql' => WPGRAPHQL_VERSION ] );
