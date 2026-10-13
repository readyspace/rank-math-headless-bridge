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
			add_action( 'rest_api_init', [ __CLASS__, 'indexnow_key_route' ] );
			add_filter( 'http_request_args', [ __CLASS__, 'request_args' ], 99, 2 );
			add_filter( 'pre_http_request', [ __CLASS__, 'request_guard' ], 99, 3 );
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
		$path = self::indexnow_key_path();
		return '' !== $path ? Settings::frontend_origin() . $path : $location;
	}

	/** Empty configuration retains the original /<current-key>.txt convention. */
	public static function indexnow_key_path(): string {
		$path = Settings::get()['indexnow_key_path'];
		if ( '' !== $path ) {
			return Settings::clean_indexnow_path( $path, true );
		}
		$key = self::indexnow_key();
		return '' !== $key ? '/' . $key . '.txt' : '';
	}

	/** Read readiness only; Rank Math retains key generation and module settings. */
	public static function indexnow_ready(): bool {
		$frontend = wp_parse_url( Settings::frontend_origin() );
		$settings = Settings::get();
		if ( ( '' !== $settings['indexnow_key_path'] && '' === Settings::clean_indexnow_path( $settings['indexnow_key_path'], true ) ) ||
			! is_array( $settings['indexnow_source_origins'] ) || ! is_array( $settings['indexnow_excluded_paths'] ) ||
			! in_array( $settings['indexnow_trailing_slash'], [ 'preserve', 'add', 'remove' ], true ) ) {
			return false;
		}
		foreach ( $settings['indexnow_source_origins'] as $alias ) {
			if ( '' === Settings::clean_origin( $alias ) ) {
				return false;
			}
		}
		foreach ( $settings['indexnow_excluded_paths'] as $path ) {
			if ( '' === Settings::clean_indexnow_path( $path ) ) {
				return false;
			}
		}
		return Settings::enabled( 'indexnow' ) && UrlMapper::is_configured() && self::available()
			&& 'https' === ( $frontend['scheme'] ?? '' ) && ! isset( $frontend['port'] )
			&& (bool) get_option( 'permalink_structure' )
			&& class_exists( '\\RankMath\\Helper' )
			&& \RankMath\Helper::is_module_active( 'instant-indexing' );
	}

	/** This public key transport belongs to IndexNow, independent of REST SEO. */
	public static function indexnow_key_route(): void {
		if ( ! Settings::enabled( 'indexnow' ) ) {
			return;
		}
		register_rest_route( 'headless-bridge/v1', '/indexnow-key', [
			'methods' => 'GET',
			'permission_callback' => '__return_true',
			'callback' => [ __CLASS__, 'indexnow_key_response' ],
		] );
	}

	public static function indexnow_key_response() {
		$key = self::indexnow_ready() ? self::indexnow_key() : '';
		if ( '' === $key || '' === self::indexnow_key_path() ) {
			return new \WP_Error( 'bridge_indexnow_unavailable', __( 'IndexNow verification is unavailable.', 'readyspace-headless-seo-bridge-for-rank-math' ), [ 'status' => 503 ] );
		}
		$response = rest_ensure_response( [ 'key' => $key ] );
		$response->header( 'Cache-Control', 'no-store, max-age=0' );
		return $response;
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

	/** Decode only for policy comparisons; the mapper validates the original URL. */
	private static function decoded_path( string $path ): string {
		for ( $i = 0; $i < 3; $i++ ) {
			$next = rawurldecode( $path );
			if ( $next === $path ) {
				break;
			}
			$path = $next;
		}
		return rtrim( $path, '/' );
	}

	/** URL aliases and policies apply to IndexNow only, never general SEO mapping. */
	private static function indexnow_url( $url, bool $check_post = true ) {
		if ( ! is_string( $url ) || ! UrlMapper::is_configured() || ! UrlMapper::is_content_url( $url, false ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if ( isset( $parts['port'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			return false;
		}
		$settings = Settings::get();
		$origin = UrlMapper::origin( $url );
		$cms_origin = UrlMapper::origin( home_url() );
		$frontend = Settings::frontend_origin();
		if ( $origin !== $cms_origin && $origin !== $frontend ) {
			$aliases = array_map( [ Settings::class, 'clean_origin' ], (array) $settings['indexnow_source_origins'] );
			if ( ! in_array( $origin, $aliases, true ) ) {
				return false;
			}
			// An HTTP alias on the CMS hostname still respects its subdirectory.
			$target = wp_parse_url( $origin, PHP_URL_HOST ) === wp_parse_url( $cms_origin, PHP_URL_HOST ) ? $cms_origin : $frontend;
			$url = $target . ( $parts['path'] ?? '/' );
		}
		$public = UrlMapper::to_frontend( $url, false );
		if ( '' === $public ) {
			return false;
		}
		$path = wp_parse_url( $public, PHP_URL_PATH ) ?? '/';
		$decoded = self::decoded_path( $path );
		$excluded = (array) $settings['indexnow_excluded_paths'];
		$excluded[] = self::indexnow_key_path();
		foreach ( $excluded as $prefix ) {
			$prefix = Settings::clean_indexnow_path( $prefix );
			if ( '' !== $prefix && ( 0 === strcasecmp( $decoded, $prefix ) || 0 === stripos( $decoded, $prefix . '/' ) ) ) {
				return false;
			}
		}
		if ( 'add' === $settings['indexnow_trailing_slash'] ) {
			$public = $frontend . ( '/' === $path ? '/' : rtrim( $path, '/' ) . '/' );
		} elseif ( 'remove' === $settings['indexnow_trailing_slash'] ) {
			$public = $frontend . ( '/' === $path ? '/' : rtrim( $path, '/' ) );
		}
		if ( $check_post && function_exists( 'url_to_postid' ) ) {
			$cms_url = UrlMapper::to_cms( $public );
			$id = '' !== $cms_url ? url_to_postid( $cms_url ) : 0;
			if ( $id && ! self::indexnow_post_allowed( get_post( $id ) ) ) {
				return false;
			}
		}
		return $public;
	}

	/** Known WordPress content keeps native indexability and explicit canonical policy. */
	private static function indexnow_post_allowed( $post ): bool {
		$settings = Settings::get();
		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, $settings['public_post_types'], true ) ||
			'' !== $post->post_password || ! in_array( $post->post_status, [ 'publish', 'trash' ], true ) ) {
			return false;
		}
		$type = get_post_type_object( $post->post_type );
		if ( ! $type || ! $type->public || ! is_post_type_viewable( $type ) ||
			( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post->ID ) ) ||
			( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post->ID ) ) ||
			! is_callable( [ '\\RankMath\\Helper', 'is_post_indexable' ] ) || ! \RankMath\Helper::is_post_indexable( $post->ID ) ) {
			return false;
		}
		$canonical = get_post_meta( $post->ID, 'rank_math_canonical_url', true );
		return ! $canonical || false !== self::indexnow_url( $canonical, false );
	}

	/** An invalid member cancels the complete batch; never report partial success. */
	public static function submission_urls( $urls ) {
		$is_array = is_array( $urls );
		$mapped = [];
		foreach ( $is_array ? $urls : [ $urls ] as $url ) {
			$public = self::indexnow_url( $url );
			if ( false === $public ) {
				return false;
			}
			$mapped[] = $public;
		}
		$mapped = array_values( array_unique( $mapped ) );
		return $is_array ? ( $mapped ?: false ) : ( $mapped[0] ?? false );
	}

	/** Rank Math retains change/deletion events; policy also protects direct calls. */
	public static function publish_url( $url, $post ) {
		return self::indexnow_post_allowed( $post ) ? self::submission_urls( $url ) : false;
	}

	private static function is_indexnow_request( $url ): bool {
		return is_string( $url ) && 1 === preg_match( '~^https://api\\.indexnow\\.org/indexnow/?$~', $url );
	}

	/** Rank Math 1.0.280's manual REST console calls Api::submit directly. */
	public static function request_args( $args, $url ) {
		if ( ! self::is_indexnow_request( $url ) || ! is_array( $args ) || ! is_string( $args['body'] ?? null ) ) {
			return $args;
		}
		$payload = json_decode( $args['body'], true );
		if ( ! is_array( $payload ) || ! is_array( $payload['urlList'] ?? null ) ) {
			return $args;
		}
		$mapped = self::indexnow_ready() ? self::submission_urls( $payload['urlList'] ) : false;
		if ( false !== $mapped ) {
			$payload['urlList'] = $mapped;
			$args['body'] = wp_json_encode( $payload );
		}
		return $args;
	}

	/** Validate the actual final payload; no network request or submission is made here. */
	public static function request_guard( $preempt, $args, $url ) {
		if ( ! self::is_indexnow_request( $url ) ) {
			return $preempt;
		}
		$payload = is_array( $args ) && is_string( $args['body'] ?? null ) ? json_decode( $args['body'], true ) : null;
		$ready = self::indexnow_ready();
		$mapped = $ready && is_array( $payload ) && is_array( $payload['urlList'] ?? null ) ? self::submission_urls( $payload['urlList'] ) : false;
		$key = $ready ? self::indexnow_key() : '';
		$location = self::indexnow_key_location( '', 'request_payload' );
		if ( false === $mapped || '' === $key || '' === $location ||
			( $payload['host'] ?? '' ) !== wp_parse_url( Settings::frontend_origin(), PHP_URL_HOST ) ||
			( $payload['keyLocation'] ?? '' ) !== $location || ! is_string( $payload['key'] ?? null ) ||
			! hash_equals( $key, $payload['key'] ) || array_values( $payload['urlList'] ) !== $mapped ) {
			return new \WP_Error( 'bridge_indexnow_scope', __( 'IndexNow requires current-key public frontend URLs and a valid key-file location.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
		}
		return $preempt;
	}
}
