<?php
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class Safeguards {
	public static function register(): void {
		if ( Settings::enabled( 'cms_noindex' ) && UrlMapper::is_configured() ) {
			add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 100 );
			add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'rank_math_robots' ), 100 );
			add_filter( 'wp_headers', array( __CLASS__, 'headers' ), 100 );
		}
		if ( Settings::enabled( 'suppress_mail' ) ) {
			add_filter( 'pre_wp_mail', array( __CLASS__, 'mail' ), 100, 2 );
		}
	}

	public static function robots( array $robots ): array {
		if ( Metadata::is_frontend_request() ) {
			return $robots;
		}
		unset( $robots['index'], $robots['follow'] );
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		return $robots;
	}

	public static function headers( array $headers ): array {
		if ( ! Metadata::is_frontend_request() && ! is_admin() ) {
			$headers['X-Robots-Tag'] = 'noindex, nofollow';
		}
		return $headers;
	}

	public static function rank_math_robots( array $robots ): array {
		if ( ! Metadata::is_frontend_request() ) {
			$robots['index'] = 'noindex';
			$robots['follow'] = 'nofollow';
		}
		return $robots;
	}

	public static function mail( $result, array $attributes ) {
		if ( null !== $result || apply_filters( 'headless_bridge/allow_cms_mail', false, $attributes ) ) {
			return $result;
		}
		// Return false so callers see delivery failure, rather than a false success.
		return false;
	}
}
