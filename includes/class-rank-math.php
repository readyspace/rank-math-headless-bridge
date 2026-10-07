<?php
/** Rank Math integration using its public filters; no separate indexing client. */
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class RankMath {

	public static function register() {
		if ( Settings::enabled( 'seo' ) ) {
			add_filter( 'rank_math/json_ld', [ __CLASS__, 'schema' ], 99 );
			add_filter( 'rank_math/frontend/canonical', [ __CLASS__, 'page_url' ], 99 );
			add_filter( 'rank_math/opengraph/facebook/og_url', [ __CLASS__, 'page_url' ], 99 );
			add_filter( 'rank_math/opengraph/twitter/twitter_url', [ __CLASS__, 'page_url' ], 99 );
		}
		if ( Settings::enabled( 'indexnow' ) ) {
			add_filter( 'rank_math/instant_indexing/indexnow_host', [ __CLASS__, 'indexnow_host' ], 99 );
			add_filter( 'rank_math/instant_indexing/indexnow_key_location', [ __CLASS__, 'indexnow_key_location' ], 99, 2 );
			add_filter( 'rank_math/instant_indexing/submit_url', [ __CLASS__, 'submission_urls' ], 99 );
			add_filter( 'rank_math/instant_indexing/publish_url', [ __CLASS__, 'publish_url' ], 99, 2 );
		}
	}

	public static function available(): bool {
		return function_exists( 'rank_math' );
	}

	/** Media and external identities remain on their original hosts. */
	public static function schema( $data ) {
		return UrlMapper::is_configured() ? UrlMapper::entity_urls( $data ) : $data;
	}

	/** Preserve deliberate external canonicals instead of inventing a replacement. */
	public static function page_url( $url ) {
		if ( ! is_string( $url ) || ! UrlMapper::is_configured() ) {
			return $url;
		}
		$mapped = UrlMapper::to_frontend( $url );
		return '' !== $mapped ? $mapped : $url;
	}

	public static function indexnow_host( $host ) {
		$frontend = Settings::frontend_origin();
		$mapped   = '' !== $frontend ? wp_parse_url( $frontend, PHP_URL_HOST ) : false;
		return is_string( $mapped ) && '' !== $mapped ? $mapped : $host;
	}

	/** The native CMS key handler must still recognize its own local URL. */
	public static function indexnow_key_location( $location, $context = '' ) {
		if ( 'serve_api_key' === $context || ! UrlMapper::is_configured() ) {
			return $location;
		}
		$key = self::indexnow_key();
		return '' !== $key ? Settings::frontend_origin() . '/' . $key . '.txt' : $location;
	}

	/** Retrieve the public verification key without creating or rotating one. */
	public static function indexnow_key(): string {
		$class = '\\RankMath\\Instant_Indexing\\Api';
		if ( ! self::available() || ! class_exists( $class ) || ! is_callable( [ $class, 'get' ] ) ) {
			return '';
		}
		try {
			$api = $class::get();
			$key = is_object( $api ) && is_callable( [ $api, 'get_key' ] ) ? $api->get_key() : '';
		} catch ( \Throwable $error ) {
			return '';
		}
		return is_string( $key ) && preg_match( '/\A[a-zA-Z0-9-]{8,128}\z/', $key ) ? $key : '';
	}

	/** Return false for an empty result: Rank Math uses it to cancel submission. */
	public static function submission_urls( $urls ) {
		if ( ! UrlMapper::is_configured() ) {
			return false;
		}
		$is_array = is_array( $urls );
		$mapped   = [];
		foreach ( $is_array ? $urls : [ $urls ] as $url ) {
			if ( ! is_string( $url ) ) {
				continue;
			}
			$public = UrlMapper::to_frontend( $url, false );
			if ( '' !== $public ) {
				$mapped[] = $public;
			}
		}
		$mapped = array_values( array_unique( $mapped ) );
		return $is_array ? ( $mapped ?: false ) : ( $mapped[0] ?? false );
	}

	/** Native Rank Math retains responsibility for indexability and deletion events. */
	public static function publish_url( $url, $post ) {
		$settings = Settings::get();
		$types    = $settings['public_post_types'] ?? [ 'post', 'page' ];
		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, $types, true ) || '' !== $post->post_password ) {
			return false;
		}
		$type = get_post_type_object( $post->post_type );
		if ( ! $type || ! $type->public || ( function_exists( 'is_post_type_viewable' ) && ! is_post_type_viewable( $type ) ) || ! in_array( $post->post_status, [ 'publish', 'trash' ], true ) ) {
			return false;
		}
		return self::submission_urls( $url );
	}
}
