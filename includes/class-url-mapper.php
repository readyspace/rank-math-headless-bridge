<?php
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

/** Maps only configured site URLs; never changes WordPress's stored home/siteurl. */
final class UrlMapper {
	public static function is_configured(): bool {
		$frontend = Settings::frontend_origin();
		return '' !== $frontend && self::origin( home_url() ) !== $frontend;
	}

	public static function origin( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ||
			! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ||
			isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		$scheme = strtolower( $parts['scheme'] );
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		$suffix = ( ( 'https' === $scheme && 443 === $port ) || ( 'http' === $scheme && 80 === $port ) ) ? '' : ':' . $port;
		return $scheme . '://' . strtolower( $parts['host'] ) . $suffix;
	}

	/** Refuse encoded traversal, backend paths, feeds and preview/auth URLs. */
	public static function is_content_url( string $url, bool $allow_query = false ): bool {
		$parts = wp_parse_url( $url );
		if ( '' === self::origin( $url ) || ! is_array( $parts ) || isset( $parts['fragment'] ) ||
			( ! $allow_query && isset( $parts['query'] ) && '' !== $parts['query'] ) ) {
			return false;
		}
		$path = $parts['path'] ?? '/';
		if ( '/' !== substr( $path, 0, 1 ) || false !== strpos( $path, '\\' ) || preg_match( '/[\x00-\x20\x7f]/', $url ) ) {
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
		if ( false !== strpos( $decoded, '%' ) || preg_match( '/[\x00-\x20\x7f]/', $decoded ) || preg_match( '#(?:^|/)(?:\.{1,2}|wp-admin|wp-json|wp-content|wp-includes|graphql|feed)(?:/|$)|(?:^|/)(?:wp-login|xmlrpc)\.php(?:/|$)#i', $decoded ) ) {
			return false;
		}
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			foreach ( array( 'preview', 'preview_id', 'preview_nonce', '_wpnonce', 'nonce', 'code', 'token', 'password', 'rest_route' ) as $key ) {
				if ( array_key_exists( $key, $query ) ) {
					return false;
				}
			}
		}
		return true;
	}

	public static function to_frontend( $url, bool $allow_query = false ): string {
		if ( ! is_string( $url ) || ! self::is_configured() || ! self::is_content_url( $url, $allow_query ) ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		$cms = home_url();
		$origin = self::origin( $url );
		$path = $parts['path'] ?? '/';
		if ( self::origin( $cms ) === $origin ) {
			$base = rtrim( wp_parse_url( $cms, PHP_URL_PATH ) ?? '', '/' );
			if ( '' !== $base ) {
				if ( $path !== $base && 0 !== strpos( $path, $base . '/' ) ) {
					return '';
				}
				$path = substr( $path, strlen( $base ) );
			}
		} elseif ( Settings::frontend_origin() !== $origin ) {
			return '';
		}
		$result = Settings::frontend_origin() . ( '' === $path ? '/' : $path );
		if ( $allow_query && ! empty( $parts['query'] ) ) {
			$result .= '?' . $parts['query'];
		}
		/** Custom route mapping must still resolve to the configured frontend origin. */
		$result = apply_filters( 'headless_bridge/frontend_url', $result, $url );
		return is_string( $result ) && Settings::frontend_origin() === self::origin( $result ) && self::is_content_url( $result, $allow_query ) ? $result : '';
	}

	public static function to_cms( $url ): string {
		if ( ! is_string( $url ) || ! self::is_configured() || ! self::is_content_url( $url ) ) {
			return '';
		}
		if ( self::origin( home_url() ) === self::origin( $url ) ) {
			// to_frontend also verifies the CMS subdirectory boundary.
			return '' !== self::to_frontend( $url ) ? $url : '';
		}
		if ( Settings::frontend_origin() !== self::origin( $url ) ) {
			return '';
		}
		$path = wp_parse_url( $url, PHP_URL_PATH ) ?? '/';
		return rtrim( home_url(), '/' ) . $path;
	}

	/** Keep media and arbitrary references intact; map known site identity and content URLs. */
	public static function entity_urls( $value, string $property = '', bool $media = false ) {
		if ( is_array( $value ) ) {
			$types = isset( $value['@type'] ) ? (array) $value['@type'] : array();
			$media = $media || (bool) array_intersect( $types, array( 'ImageObject', 'VideoObject', 'AudioObject', 'MediaObject' ) ) || in_array( $property, array( 'image', 'logo', 'video', 'audio', 'thumbnail' ), true );
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::entity_urls( $item, is_string( $key ) ? $key : $property, $media );
			}
			return $value;
		}
		if ( ! is_string( $value ) || ! self::is_configured() || self::origin( home_url() ) !== self::origin( $value ) ) {
			return $value;
		}
		if ( in_array( $property, array( 'image', 'logo', 'video', 'audio', 'thumbnail', 'thumbnailUrl', 'contentUrl', 'embedUrl' ), true ) || ( $media && 'url' === $property ) || preg_match( '/\.(?:png|jpe?g|gif|webp|svg|avif|ico|mp[34]|webm|ogg|wav|pdf)(?:[?#]|$)/i', $value ) ) {
			return $value;
		}
		$uploads = wp_get_upload_dir();
		$media_base = isset( $uploads['baseurl'] ) ? rtrim( $uploads['baseurl'], '/' ) : '';
		if ( '' !== $media_base && ( $value === $media_base || 0 === strpos( $value, $media_base . '/' ) ) ) {
			return $value;
		}
		$parts = wp_parse_url( $value );
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
		$base_url = preg_replace( '/#.*$/s', '', $value );
		$mapped = self::to_frontend( $base_url );
		return '' === $mapped ? $value : $mapped . $fragment;
	}
}
