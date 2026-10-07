<?php
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class EditorLinks {
	public static function register(): void {
		if ( ! Settings::enabled( 'editor_links' ) || ! UrlMapper::is_configured() ) {
			return;
		}
		add_filter( 'post_link', array( __CLASS__, 'published_link' ), 100, 2 );
		add_filter( 'page_link', array( __CLASS__, 'page_link' ), 100, 2 );
		add_filter( 'post_type_link', array( __CLASS__, 'published_link' ), 100, 2 );
		// preview_post_link deliberately remains owned by Faust/your preview provider.
	}

	public static function published_link( string $url, $post ): string {
		if ( ! is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			! $post instanceof \WP_Post || 'publish' !== $post->post_status || '' !== $post->post_password ||
			! in_array( $post->post_type, Settings::get()['public_post_types'], true ) ||
			! is_post_type_viewable( get_post_type_object( $post->post_type ) ) ) {
			return $url;
		}
		$mapped = UrlMapper::to_frontend( $url );
		return '' === $mapped ? $url : $mapped;
	}

	public static function page_link( string $url, int $post_id ): string {
		return self::published_link( $url, get_post( $post_id ) );
	}
}
