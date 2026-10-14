<?php
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class EditorLinks {
	public static function register(): void {
		if ( ! UrlMapper::is_configured() ) {
			return;
		}
		if ( Settings::enabled( 'editor_links' ) ) {
			add_filter( 'post_link', array( __CLASS__, 'published_link' ), 2000, 2 );
			add_filter( 'page_link', array( __CLASS__, 'page_link' ), 2000, 2 );
			add_filter( 'post_type_link', array( __CLASS__, 'published_link' ), 2000, 2 );
			foreach ( Settings::get()['public_post_types'] as $post_type ) {
				if ( is_string( $post_type ) && preg_match( '/^[a-z0-9_-]+$/D', $post_type ) ) {
					add_filter( 'rest_prepare_' . $post_type, array( __CLASS__, 'rest_response' ), 2000, 3 );
				}
			}
		}
		if ( Settings::enabled( 'preview_links' ) ) {
			// The provider still creates the authenticated destination and parameters.
			add_filter( 'preview_post_link', array( __CLASS__, 'preview_link' ), 2000, 2 );
			add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_preview_compatibility' ), 100 );
		}
	}

	public static function published_link( string $url, $post ): string {
		if ( ! self::is_editor_request() || ! self::can_edit( $post ) ||
			'publish' !== $post->post_status || '' !== $post->post_password ) {
			return $url;
		}
		$mapped = UrlMapper::to_frontend( $url );
		return '' === $mapped ? $url : $mapped;
	}

	public static function page_link( string $url, int $post_id ): string {
		return self::published_link( $url, get_post( $post_id ) );
	}

	/** Save responses carry context in the REST request, sometimes outside $_GET. */
	public static function rest_response( $response, $post, $request ) {
		if ( ! $request instanceof \WP_REST_Request || 'edit' !== $request->get_param( 'context' ) ||
			! $response instanceof \WP_REST_Response || ! self::can_edit( $post ) ||
			'publish' !== $post->post_status || '' !== $post->post_password ) {
			return $response;
		}
		$data = $response->get_data();
		if ( is_array( $data ) && isset( $data['link'] ) && is_string( $data['link'] ) ) {
			$mapped = UrlMapper::to_frontend( $data['link'] );
			if ( '' !== $mapped ) {
				$data['link'] = $mapped;
				$response->set_data( $data );
			}
		}
		return $response;
	}

	private static function is_editor_request(): bool {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			// Read-only editor context, never an authorization signal on its own.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return isset( $_GET['context'] ) && 'edit' === $_GET['context'];
		}
		return is_admin();
	}

	private static function can_edit( $post ): bool {
		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, Settings::get()['public_post_types'], true ) ) {
			return false;
		}
		$type = get_post_type_object( $post->post_type );
		return $type && is_post_type_viewable( $type ) && current_user_can( 'edit_post', $post->ID );
	}

	/** A preview-only mapper. The strict SEO/IndexNow URL policy stays unchanged. */
	public static function preview_link( $url, $post ) {
		if ( ! is_string( $url ) || ! self::can_edit( $post ) || ! UrlMapper::is_configured() ) {
			return $url;
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || ! in_array( UrlMapper::origin( $url ), self::preview_origins(), true ) ||
			! self::safe_preview_path( $parts['path'] ?? '/' ) || preg_match( '/[\x00-\x20\x7f]/', $url ) ) {
			return $url;
		}
		if ( Settings::frontend_origin() === UrlMapper::origin( $url ) ) {
			return $url;
		}
		$path = $parts['path'] ?? '/';
		$base = rtrim( wp_parse_url( home_url(), PHP_URL_PATH ) ?? '', '/' );
		if ( '' !== $base ) {
			if ( $path !== $base && 0 !== strpos( $path, $base . '/' ) ) {
				return $url;
			}
			$path = substr( $path, strlen( $base ) );
		}
		return Settings::frontend_origin() . ( '' === $path ? '/' : $path )
			. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' )
			. ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
	}

	/** Only configured origins, plus the CMS's HTTP/HTTPS standard-port counterpart. */
	private static function preview_origins(): array {
		$cms = UrlMapper::origin( home_url() );
		$origins = array( $cms, Settings::frontend_origin() );
		$parts = wp_parse_url( $cms );
		if ( is_array( $parts ) && ! isset( $parts['port'] ) ) {
			$origins[] = ( 'https' === $parts['scheme'] ? 'http' : 'https' ) . '://' . $parts['host'];
		}
		return array_values( array_unique( array_filter( $origins ) ) );
	}

	private static function safe_preview_path( string $path ): bool {
		if ( '/' !== substr( $path, 0, 1 ) || false !== strpos( $path, '\\' ) ) {
			return false;
		}
		$decoded = $path;
		for ( $i = 0; $i < 3; $i++ ) {
			if ( preg_match( '/%(?:2f|5c|00)/i', $decoded ) ) {
				return false;
			}
			$next = rawurldecode( $decoded );
			if ( $next === $decoded ) {
				break;
			}
			$decoded = $next;
		}
		return false === strpos( $decoded, '%' ) && ! preg_match( '/[\x00-\x20\x7f]/', $decoded ) &&
			! preg_match( '~(?:^|/)(?:\.{1,2}|wp-[^/]*|graphql|api|generate)(?:/|$)|(?:^|/)xmlrpc\.php(?:/|$)~i', $decoded );
	}

	/** Gutenberg may construct a fresh preview URL after the PHP filter ran. */
	public static function enqueue_preview_compatibility(): void {
		$post = get_post();
		if ( ! self::can_edit( $post ) ) {
			return;
		}
		$preview = get_preview_post_link( $post );
		if ( ! is_string( $preview ) || Settings::frontend_origin() !== UrlMapper::origin( $preview ) ||
			! self::safe_preview_path( wp_parse_url( $preview, PHP_URL_PATH ) ?? '/' ) ) {
			return;
		}
		$config = wp_json_encode( array( 'preview' => $preview, 'origins' => self::preview_origins() ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		if ( ! is_string( $config ) ) {
			return;
		}
		$script = <<<'JS'
(function (config) {
    'use strict';
    const canonical = new URL(config.preview);
    function updateLinks() {
        document.querySelectorAll('a[href]').forEach(function (anchor) {
            let link;
            try { link = new URL(anchor.href); } catch (_) { return; }
            if (!config.origins.includes(link.origin) || link.searchParams.get('preview') !== 'true') { return; }
            // Retain fresh WordPress nonce/autosave parameters and provider routing.
            const target = new URL(canonical.href);
            link.searchParams.forEach(function (value, key) { target.searchParams.set(key, value); });
            ['p', 'preview', 'previewPathname', 'typeName'].forEach(function (key) {
                if (canonical.searchParams.has(key)) { target.searchParams.set(key, canonical.searchParams.get(key)); }
            });
            if (anchor.href !== target.href) { anchor.href = target.href; }
        });
    }
    let queued = false;
    const observer = new MutationObserver(function () {
        if (queued) { return; }
        queued = true;
        requestAnimationFrame(function () { queued = false; updateLinks(); });
    });
    function start() {
        updateLinks();
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['href'] });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start, { once: true }); }
    else { start(); }
})(CONFIG);
JS;
		wp_add_inline_script( 'wp-edit-post', str_replace( 'CONFIG', $config, $script ), 'after' );
	}
}
