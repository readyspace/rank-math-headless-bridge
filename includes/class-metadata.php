<?php
/** Public, normalized metadata for explicitly allowed published WordPress posts. */
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class Metadata {

	/** Only true while generating the separate frontend's metadata. */
	private static $frontend_request = false;

	public static function register() {
		if ( Settings::enabled( 'metadata' ) ) {
			add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		}
	}

	public static function is_frontend_request(): bool {
		return self::$frontend_request;
	}

	public static function register_routes() {
		register_rest_route( 'headless-bridge/v1', '/head', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'get_head' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'post_id' => [
					'type' => 'integer', 'minimum' => 1, 'required' => true,
					'description' => __( 'ID of a published, password-free post in an allowed public post type.', 'readyspace-headless-seo-bridge-for-rank-math' ),
				],
			],
		] );
	}

	/** Accept a trusted ID, never an arbitrary URL or a preview request. */
	public static function get_head( $request ) {
		if ( ! Settings::enabled( 'metadata' ) ) {
			return self::error( 'bridge_unavailable', __( 'The REST metadata module is disabled.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		}
		$data = self::for_post( $request->get_param( 'post_id' ) );
		return is_wp_error( $data ) ? $data : rest_ensure_response( $data );
	}

	/** Shared provider for REST and GraphQL. Returns normalized data or WP_Error. */
	public static function for_post( $id ) {
		if ( ( ! Settings::enabled( 'metadata' ) && ! Settings::enabled( 'graphql' ) ) || ! UrlMapper::is_configured() || ! RankMath::available() || ! function_exists( 'rest_do_request' ) || ! class_exists( '\DOMDocument' ) ) {
			return self::error( 'bridge_unavailable', __( 'Metadata requires a configured frontend, Rank Math headless support, and the PHP DOM extension.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		}
		if ( ( ! is_int( $id ) && ( ! is_string( $id ) || ! ctype_digit( $id ) ) ) || (int) $id < 1 ) {
			return self::error( 'bridge_invalid_post', __( 'A positive integer post_id is required.', 'readyspace-headless-seo-bridge-for-rank-math' ), 400 );
		}
		$post = get_post( (int) $id );
		if ( ! self::is_public_post( $post ) ) {
			return self::error( 'bridge_post_not_found', __( 'Public post not found.', 'readyspace-headless-seo-bridge-for-rank-math' ), 404 );
		}
		$permalink = get_permalink( $post );
		$public    = is_string( $permalink ) ? UrlMapper::to_frontend( $permalink, false ) : '';
		$cms_url   = '' !== $public ? UrlMapper::to_cms( $permalink ) : '';
		if ( '' === $cms_url ) {
			return self::error( 'bridge_invalid_permalink', __( 'This post does not have a supported public permalink.', 'readyspace-headless-seo-bridge-for-rank-math' ), 422 );
		}

		$native = new \WP_REST_Request( 'GET', '/rankmath/v1/getHead' );
		$native->set_param( 'url', $cms_url );
		$response = self::native_head( $native, (int) $post->ID );
		if ( is_wp_error( $response ) ) {
			return self::error( 'bridge_rank_math_unavailable', __( 'Rank Math could not generate metadata. Enable its Headless CMS Support option.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		}
		if ( ! is_object( $response ) || ! is_callable( [ $response, 'get_data' ] ) || ! is_callable( [ $response, 'get_status' ] ) ) {
			return self::error( 'bridge_rank_math_unavailable', __( 'Rank Math did not return head metadata.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		}
		$data = $response->get_data();
		if ( $response->get_status() >= 400 || ! is_array( $data ) || empty( $data['success'] ) || ! isset( $data['head'] ) || ! is_string( $data['head'] ) ) {
			return self::error( 'bridge_rank_math_unavailable', __( 'Rank Math did not return head metadata.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		}
		$head = self::normalize_head( $data['head'] );
		if ( is_wp_error( $head ) ) {
			return $head;
		}
		return [ 'postId' => (int) $post->ID ] + $head;
	}

	public static function is_public_post( $post ): bool {
		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status || '' !== $post->post_password || wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
			return false;
		}
		$settings = Settings::get();
		$types    = $settings['public_post_types'] ?? [ 'post', 'page' ];
		$type     = get_post_type_object( $post->post_type );
		return in_array( $post->post_type, $types, true ) && $type && $type->public && ( ! function_exists( 'is_post_type_viewable' ) || is_post_type_viewable( $type ) );
	}

	/**
	 * Rank Math's native head endpoint rebuilds the WordPress query and hooks.
	 * Restore the request context even when a dependency throws. No remote fetch.
	 */
	private static function native_head( $request, int $expected_post_id ) {
		$names = [ 'wp', 'wp_query', 'wp_the_query', 'post', 'wp_rewrite', 'wp_actions', 'wp_filters', 'wp_current_filter', 'query_string', 'posts', 'request', 'more', 'single', 'authordata', 'currentday', 'currentmonth', 'pages', 'page', 'numpages', 'multipage' ];
		$saved = [];
		$clones = new \SplObjectStorage();
		foreach ( $names as $name ) {
			$exists         = array_key_exists( $name, $GLOBALS );
			$value          = $exists ? $GLOBALS[ $name ] : null;
			$saved[ $name ] = [ $exists, $value ];
		}
		$hooks = $GLOBALS['wp_filter'] ?? [];
		$server_values = [];
		foreach ( [ 'REQUEST_URI', 'PATH_INFO', 'QUERY_STRING' ] as $name ) {
			// Snapshot only: restore the caller's exact bytes, never use them as metadata input.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$server_values[ $name ] = [ array_key_exists( $name, $_SERVER ), $_SERVER[ $name ] ?? null ];
		}
		// Preserve request context without processing form data or performing an administrative write.
		// phpcs:ignore WordPress.Security.NonceVerification
		$inputs     = [ $_GET, $_POST, $_REQUEST ];
		$old_scope  = self::$frontend_request;
		$buffer     = ob_get_level();
		$public     = static function () { return 1; };
		$identity_verified = false;
		try {
			// Work on isolated copies. Preserve aliases such as wp_query === wp_the_query
			// and restore the original objects, including references held by the caller.
			foreach ( $saved as $name => $state ) {
				if ( $state[0] && is_object( $state[1] ) ) {
					if ( ! $clones->contains( $state[1] ) ) {
						$clones[ $state[1] ] = clone $state[1];
					}
					// These names come only from the fixed WordPress-core allowlist above.
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$GLOBALS[ $name ] = $clones[ $state[1] ];
				}
			}
			foreach ( $hooks as $name => $hook ) {
				$GLOBALS['wp_filter'][ $name ] = is_object( $hook ) ? clone $hook : $hook;
			}
			self::$frontend_request = true;
			// A headless installation may have no active theme declaring title support.
			// Emit the WordPress document title with Rank Math's filters in this scope.
			if ( function_exists( 'current_theme_supports' ) && ! current_theme_supports( 'title-tag' ) ) {
				add_action( 'rank_math/head', static function () {
					echo '<title>' . esc_html( wp_get_document_title() ) . '</title>';
				}, 1 );
			}
			// Do not let incoming p/preview/replytocom parameters alter the trusted post query.
			$_GET     = [];
			$_POST    = [];
			$_REQUEST = [];
			$_SERVER['PATH_INFO']    = '';
			$_SERVER['QUERY_STRING'] = '';
			// CMS isolation must not override the public site's per-post SEO policy.
			if ( Settings::enabled( 'cms_noindex' ) ) {
				add_filter( 'pre_option_blog_public', $public, PHP_INT_MAX );
			}
			// The native endpoint clears wp hooks. This event follows parsing its trusted URL.
			add_action( 'rank_math/frontend/loaded', static function () use ( $expected_post_id, &$identity_verified ) {
				if ( (int) get_queried_object_id() !== $expected_post_id || ! self::is_public_post( get_queried_object() ) ) {
					throw new \RuntimeException( 'The trusted permalink did not resolve to the requested post.' );
				}
				$identity_verified = true;
			}, 0 );
			if ( is_callable( [ '\\RankMath\\Paper\\Paper', 'reset' ] ) ) {
				add_action( 'rank_math/frontend/loaded', [ '\\RankMath\\Paper\\Paper', 'reset' ], 1 );
			}
			$response = rest_do_request( $request );
			return $identity_verified ? $response : self::error( 'bridge_unverified_metadata', __( 'Rank Math did not verify the requested public post.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		} catch ( \Throwable $error ) {
			/** Internal diagnostics; never included in the public metadata response. */
			do_action( 'headless_bridge/metadata_generation_failed', $error );
			return self::error( 'bridge_generation_failed', __( 'Metadata generation failed.', 'readyspace-headless-seo-bridge-for-rank-math' ), 503 );
		} finally {
			while ( ob_get_level() > $buffer ) {
				ob_end_clean();
			}
			remove_filter( 'pre_option_blog_public', $public, PHP_INT_MAX );
			$_GET     = $inputs[0];
			$_POST    = $inputs[1];
			$_REQUEST = $inputs[2];
			self::$frontend_request = $old_scope;
			$GLOBALS['wp_filter']   = $hooks;
			foreach ( $saved as $name => $state ) {
				if ( $state[0] ) {
					// Restore existing WordPress-core globals; do not create plugin globals.
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$GLOBALS[ $name ] = $state[1];
				} else {
					unset( $GLOBALS[ $name ] );
				}
			}
			foreach ( $server_values as $name => $state ) {
				if ( $state[0] ) {
					$_SERVER[ $name ] = $state[1];
				} else {
					unset( $_SERVER[ $name ] );
				}
			}
			if ( is_callable( [ '\\RankMath\\Paper\\Paper', 'reset' ] ) ) {
				try {
					\RankMath\Paper\Paper::reset();
				} catch ( \Throwable $error ) {
					// A REST caller may have no frontend query to reinitialize.
				}
			}
		}
	}

	/** Extract data fields only; callers never receive executable head HTML. */
	public static function normalize_head( string $html ) {
		if ( ! class_exists( '\DOMDocument' ) || '' === trim( $html ) || strlen( $html ) > 2097152 ) {
			return self::error( 'bridge_invalid_metadata', __( 'Rank Math returned unsupported metadata.', 'readyspace-headless-seo-bridge-for-rank-math' ), 422 );
		}
		$result = [ 'title' => '', 'description' => '', 'canonical' => '', 'robots' => [], 'openGraph' => [], 'twitter' => [], 'jsonLd' => [] ];
		$old    = libxml_use_internal_errors( true );
		$dom    = new \DOMDocument();
		try {
			$loaded = $dom->loadHTML( '<?xml encoding="UTF-8"><html><head>' . $html . '</head><body></body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
			if ( ! $loaded ) {
				return self::error( 'bridge_invalid_metadata', __( 'Rank Math returned unsupported metadata.', 'readyspace-headless-seo-bridge-for-rank-math' ), 422 );
			}
			$titles = $dom->getElementsByTagName( 'title' );
			if ( $titles->length ) {
				$result['title'] = self::text( $titles->item( 0 )->textContent );
			}
			foreach ( $dom->getElementsByTagName( 'link' ) as $link ) {
				if ( 'canonical' === strtolower( trim( $link->getAttribute( 'rel' ) ) ) ) {
					$result['canonical'] = self::metadata_url( $link->getAttribute( 'href' ) );
						break;
					}
				}
				foreach ( $dom->getElementsByTagName( 'meta' ) as $meta ) {
				$name     = strtolower( trim( $meta->getAttribute( 'name' ) ) );
				$property = strtolower( trim( $meta->getAttribute( 'property' ) ) );
				$content  = self::text( $meta->getAttribute( 'content' ) );
				if ( '' === $content ) {
					continue;
				}
				if ( 'description' === $name ) {
					$result['description'] = $content;
				} elseif ( in_array( $name, [ 'robots', 'googlebot', 'bingbot' ], true ) ) {
					$result['robots'][ $name ] = $content;
				} elseif ( 0 === strpos( $property, 'og:' ) || 0 === strpos( $property, 'article:' ) || 0 === strpos( $property, 'profile:' ) ) {
					if ( 'og:url' === $property ) {
						$content = self::metadata_url( $content );
					}
					if ( '' !== $content ) {
						$result['openGraph'][] = [ 'property' => $property, 'content' => $content ];
					}
				} elseif ( 0 === strpos( $name, 'twitter:' ) ) {
					if ( 'twitter:url' === $name ) {
						$content = self::metadata_url( $content );
					}
					if ( '' !== $content ) {
						$result['twitter'][] = [ 'name' => $name, 'content' => $content ];
					}
				}
			}
			foreach ( $dom->getElementsByTagName( 'script' ) as $script ) {
				if ( 'application/ld+json' !== strtolower( trim( $script->getAttribute( 'type' ) ) ) ) {
					continue;
				}
				$data = json_decode( $script->textContent, true, 64 );
				if ( JSON_ERROR_NONE === json_last_error() && is_array( $data ) ) {
					$result['jsonLd'][] = self::schema_text( UrlMapper::entity_urls( $data ) );
				}
			}
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $old );
		}
		if ( '' === $result['title'] && '' === $result['canonical'] && '' === $result['description'] && ! $result['robots'] && ! $result['openGraph'] && ! $result['twitter'] && ! $result['jsonLd'] ) {
			return self::error( 'bridge_invalid_metadata', __( 'Rank Math returned no supported metadata.', 'readyspace-headless-seo-bridge-for-rank-math' ), 422 );
		}
		$result['robots'] = (object) $result['robots'];
		return $result;
	}

	private static function text( string $value ): string {
		return trim( wp_strip_all_tags( $value, true ) );
	}

	private static function schema_text( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::schema_text( $item );
			}
			return $value;
		}
		return is_string( $value ) ? self::text( $value ) : $value;
	}

	private static function metadata_url( string $url ): string {
		$url   = trim( $url );
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! isset( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		return RankMath::page_url( $url );
	}

	private static function error( string $code, string $message, int $status ) {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
