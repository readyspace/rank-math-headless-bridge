<?php
/** Optional WPGraphQL transport. Rank Math remains the metadata provider. */
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class GraphQL {
	/** Cache only within the current GraphQL execution's context, never on disk. */
	private static $contexts;

	public static function register(): void {
		if ( Settings::enabled( 'graphql' ) ) {
			add_action( 'graphql_register_types', [ __CLASS__, 'register_types' ] );
		}
	}

	public static function register_types(): void {
		if ( ! Settings::enabled( 'graphql' ) || ! function_exists( 'register_graphql_object_type' ) || ! function_exists( 'register_graphql_field' ) ) {
			return;
		}
		register_graphql_object_type( 'HeadlessBridgeRobots', [
			'description' => __( 'Rank Math crawler directives for the public frontend.', 'readyspace-headless-seo-bridge-for-rank-math' ),
			'fields' => [
				'robots' => [ 'type' => 'String' ],
				'googlebot' => [ 'type' => 'String' ],
				'bingbot' => [ 'type' => 'String' ],
			],
		] );
		register_graphql_object_type( 'HeadlessBridgeOpenGraphTag', [
			'fields' => [ 'property' => [ 'type' => 'String' ], 'content' => [ 'type' => 'String' ] ],
		] );
		register_graphql_object_type( 'HeadlessBridgeTwitterTag', [
			'fields' => [ 'name' => [ 'type' => 'String' ], 'content' => [ 'type' => 'String' ] ],
		] );
		register_graphql_object_type( 'HeadlessBridgeSeo', [
			'description' => __( 'Computed SEO for published, password-free content; not an editor or preview API.', 'readyspace-headless-seo-bridge-for-rank-math' ),
			'fields' => [
				'postId' => [ 'type' => 'Int' ],
				'title' => [ 'type' => 'String' ],
				'description' => [ 'type' => 'String' ],
				'canonical' => [ 'type' => 'String' ],
				'robots' => [ 'type' => 'HeadlessBridgeRobots' ],
				'openGraph' => [ 'type' => [ 'list_of' => 'HeadlessBridgeOpenGraphTag' ] ],
				'twitter' => [ 'type' => [ 'list_of' => 'HeadlessBridgeTwitterTag' ] ],
				'jsonLd' => [
					'type' => [ 'list_of' => 'String' ],
					'description' => __( 'One JSON string per schema graph. Parse as JSON and safely serialize before rendering.', 'readyspace-headless-seo-bridge-for-rank-math' ),
					'resolve' => static function ( $seo ) {
						return array_map( 'wp_json_encode', $seo['jsonLd'] );
					},
				],
			],
		] );
		register_graphql_field( 'ContentNode', 'headlessSeo', [
			'type' => 'HeadlessBridgeSeo',
			'description' => __( 'Optional Rank Math headless metadata. Null for unsupported or non-public content.', 'readyspace-headless-seo-bridge-for-rank-math' ),
			'resolve' => [ __CLASS__, 'resolve' ],
		] );
	}

	public static function resolve( $source, $args, $context ) {
		// Let WPGraphQL authorize the node, then enforce the bridge's public-only contract.
		if ( ! Settings::enabled( 'graphql' ) || ! $source instanceof \WPGraphQL\Model\Post ) {
			return null;
		}
		$id = (int) $source->databaseId;
		if ( ! Metadata::is_public_post( get_post( $id ) ) ) {
			return null;
		}
		if ( ! self::$contexts ) {
			self::$contexts = new \SplObjectStorage();
		}
		$cache = is_object( $context ) && self::$contexts->contains( $context ) ? self::$contexts[ $context ] : [];
		if ( ! array_key_exists( $id, $cache ) ) {
			$cache[ $id ] = Metadata::for_post( $id );
			if ( is_object( $context ) ) {
				self::$contexts[ $context ] = $cache;
			}
		}
		if ( is_wp_error( $cache[ $id ] ) ) {
			throw new \GraphQL\Error\UserError( esc_html__( 'Headless SEO metadata is unavailable. Check the bridge and Rank Math headless settings.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
		}
		return $cache[ $id ];
	}
}
