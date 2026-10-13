<?php
namespace HeadlessBridge;

defined( 'ABSPATH' ) || exit;

final class Settings {
	const OPTION = 'headless_bridge_settings';

	public static function defaults(): array {
		return array(
			'frontend_origin' => '',
			'seo' => false,
			'metadata' => false,
			'graphql' => false,
			'indexnow' => false,
			'indexnow_key_path' => '',
			'indexnow_source_origins' => array(),
			'indexnow_excluded_paths' => array(),
			'indexnow_trailing_slash' => 'preserve',
			'editor_links' => false,
			'cms_noindex' => false,
			'suppress_mail' => false,
			'public_post_types' => array( 'post', 'page' ),
		);
	}

	public static function get(): array {
		$value = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $value ) ? $value : array() );
	}

	public static function enabled( string $module ): bool {
		$settings = self::get();
		return isset( self::defaults()[ $module ] ) && is_bool( self::defaults()[ $module ] ) && ! empty( $settings[ $module ] );
	}

	public static function frontend_origin(): string {
		return self::clean_origin( self::get()['frontend_origin'] );
	}

	public static function clean_origin( $value ): string {
		if ( ! is_string( $value ) || preg_match( '/[\x00-\x20\x7f]/', $value ) ) {
			return '';
		}
		$parts = wp_parse_url( $value );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ||
			! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ||
			isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ||
			( isset( $parts['path'] ) && ! in_array( $parts['path'], array( '', '/' ), true ) ) ) {
			return '';
		}
		return UrlMapper::origin( $value );
	}

	/** A local public path, never a URL, query, credential or encoded traversal. */
	public static function clean_indexnow_path( $value, bool $key_file = false ): string {
		if ( ! is_string( $value ) || strlen( $value ) > 1024 || '/' !== substr( $value, 0, 1 ) ||
			false !== strpos( $value, '//' ) || false !== strpbrk( $value, '\\%?#' ) || preg_match( '/[\x00-\x20\x7f]/', $value ) ) {
			return '';
		}
		foreach ( explode( '/', $value ) as $part ) {
			if ( '.' === $part || '..' === $part ) {
				return '';
			}
		}
		$value = rtrim( $value, '/' );
		if ( '' === $value || ( $key_file && ( ! preg_match( '/\.txt$/iD', $value ) ||
			strlen( $value ) !== strspn( $value, '/ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789._-' ) ||
			! UrlMapper::is_content_url( 'https://example.com' . $value ) ) ) ) {
			return '';
		}
		return $value;
	}

	/** Accept both checkbox arrays and one-value-per-line configuration fields. */
	private static function lines( $value ): array {
		return is_array( $value ) ? $value : ( is_string( $value ) ? preg_split( '/\r?\n/', $value ) : array() );
	}

	public static function sanitize( $input ): array {
		$output = self::defaults();
		if ( ! is_array( $input ) ) {
			return $output;
		}
		$output['frontend_origin'] = self::clean_origin( $input['frontend_origin'] ?? '' );
		if ( ! empty( $input['frontend_origin'] ) && '' === $output['frontend_origin'] ) {
			add_settings_error( self::OPTION, 'invalid_origin', __( 'Enter a complete public website origin, such as https://www.example.com, without a path, query or credentials.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
		}
		foreach ( array( 'seo', 'metadata', 'graphql', 'indexnow', 'editor_links', 'cms_noindex', 'suppress_mail' ) as $module ) {
			$output[ $module ] = isset( $input[ $module ] ) && in_array( $input[ $module ], array( true, 1, '1' ), true );
		}
		if ( '' === $output['frontend_origin'] || $output['frontend_origin'] === UrlMapper::origin( home_url() ) ) {
			foreach ( array( 'seo', 'metadata', 'graphql', 'indexnow', 'editor_links', 'cms_noindex' ) as $module ) {
				$output[ $module ] = false;
			}
			if ( '' !== $output['frontend_origin'] ) {
				add_settings_error( self::OPTION, 'same_origin', __( 'The public frontend must use a separate origin from the CMS.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
			}
		}
		$frontend = wp_parse_url( $output['frontend_origin'] );
		if ( $output['indexnow'] && ( 'https' !== ( $frontend['scheme'] ?? '' ) || isset( $frontend['port'] ) ) ) {
			$output['indexnow'] = false;
			add_settings_error( self::OPTION, 'indexnow_https', __( 'IndexNow requires an HTTPS public website on the standard port.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
		}
		if ( ! get_option( 'permalink_structure' ) ) {
			$requested = $output['metadata'] || $output['graphql'] || $output['indexnow'] || $output['editor_links'];
			$output['metadata'] = $output['graphql'] = $output['indexnow'] = $output['editor_links'] = false;
			if ( $requested ) {
				add_settings_error( self::OPTION, 'pretty_permalinks', __( 'Pretty WordPress permalinks are required for REST and GraphQL metadata, IndexNow and editor link mapping. Configure them before enabling these modules.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
			}
		}
		$output['indexnow_key_path'] = self::clean_indexnow_path( $input['indexnow_key_path'] ?? '', true );
		if ( ! empty( $input['indexnow_key_path'] ) && '' === $output['indexnow_key_path'] ) {
			$output['indexnow'] = false;
			add_settings_error( self::OPTION, 'invalid_indexnow_key_path', __( 'Enter a public .txt path such as /indexnow.txt, without a hostname, query or encoded characters.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
		}
		foreach ( array( 'indexnow_source_origins', 'indexnow_excluded_paths' ) as $field ) {
			if ( isset( $input[ $field ] ) && ! is_array( $input[ $field ] ) && ! is_string( $input[ $field ] ) ) {
				$output['indexnow'] = false;
				add_settings_error( self::OPTION, 'invalid_indexnow_policy', __( 'IndexNow source origins and excluded paths must be text lines or a list of text values.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
			}
		}
		foreach ( self::lines( $input['indexnow_source_origins'] ?? array() ) as $alias ) {
			if ( ! is_string( $alias ) ) {
				$output['indexnow'] = false;
				add_settings_error( self::OPTION, 'invalid_indexnow_source_origin', __( 'Each IndexNow source alias must be a complete origin without a path, query or credentials.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
				continue;
			}
			$alias = trim( $alias );
			$clean = self::clean_origin( $alias );
			if ( '' !== $clean ) {
				$output['indexnow_source_origins'][] = $clean;
			} elseif ( '' !== $alias ) {
				$output['indexnow'] = false;
				add_settings_error( self::OPTION, 'invalid_indexnow_source_origin', __( 'Each IndexNow source alias must be a complete origin without a path, query or credentials.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
			}
		}
		$output['indexnow_source_origins'] = array_values( array_unique( $output['indexnow_source_origins'] ) );
		foreach ( self::lines( $input['indexnow_excluded_paths'] ?? array() ) as $path ) {
			if ( ! is_string( $path ) ) {
				$output['indexnow'] = false;
				add_settings_error( self::OPTION, 'invalid_indexnow_excluded_path', __( 'Each excluded IndexNow path must start with / and contain no query or encoded characters.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
				continue;
			}
			$path = trim( $path );
			$clean = self::clean_indexnow_path( $path );
			if ( '' !== $clean ) {
				$output['indexnow_excluded_paths'][] = $clean;
			} elseif ( '' !== $path ) {
				$output['indexnow'] = false;
				add_settings_error( self::OPTION, 'invalid_indexnow_excluded_path', __( 'Each excluded IndexNow path must start with / and contain no query or encoded characters.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
			}
		}
		$output['indexnow_excluded_paths'] = array_values( array_unique( $output['indexnow_excluded_paths'] ) );
		if ( in_array( $input['indexnow_trailing_slash'] ?? '', array( 'preserve', 'add', 'remove' ), true ) ) {
			$output['indexnow_trailing_slash'] = $input['indexnow_trailing_slash'];
		} elseif ( isset( $input['indexnow_trailing_slash'] ) ) {
			$output['indexnow'] = false;
			add_settings_error( self::OPTION, 'invalid_indexnow_trailing_slash', __( 'Choose whether IndexNow preserves, adds or removes trailing slashes.', 'readyspace-headless-seo-bridge-for-rank-math' ) );
		}
		$types = isset( $input['public_post_types'] ) && is_array( $input['public_post_types'] ) ? $input['public_post_types'] : array();
		$output['public_post_types'] = array();
		foreach ( $types as $type ) {
			if ( is_string( $type ) && ! in_array( sanitize_key( $type ), array( 'attachment', 'revision' ), true ) && is_post_type_viewable( get_post_type_object( sanitize_key( $type ) ) ) ) {
				$output['public_post_types'][] = sanitize_key( $type );
			}
		}
		$output['public_post_types'] = array_values( array_unique( $output['public_post_types'] ) );
		return $output;
	}

	public static function register(): void {
		add_action( 'admin_menu', static function () {
			add_options_page( __( 'ReadySpace SEO Bridge', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'ReadySpace SEO Bridge', 'readyspace-headless-seo-bridge-for-rank-math' ), 'manage_options', 'headless-bridge', array( __CLASS__, 'render' ) );
		} );
		add_action( 'admin_init', static function () {
			register_setting( 'headless_bridge', self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize' ), 'show_in_rest' => false, 'default' => self::defaults() ) );
		} );
	}


	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$config = self::get();
		echo '<div class="wrap"><h1>' . esc_html__( 'ReadySpace Headless SEO Bridge for Rank Math', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</h1><p>' . esc_html__( 'Connect a separate public website. Enable only the features that your site needs.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</p>';
		settings_errors( self::OPTION );
		echo '<form method="post" action="options.php">';
		settings_fields( 'headless_bridge' );
		echo '<table class="form-table"><tr><th><label for="hb-origin">' . esc_html__( 'Public website address', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</label></th><td><input type="url" class="regular-text" id="hb-origin" name="' . esc_attr( self::OPTION ) . '[frontend_origin]" value="' . esc_attr( $config['frontend_origin'] ) . '" placeholder="https://www.example.com"><p class="description">' . esc_html__( 'Use the public origin only. Your CMS and media addresses stay unchanged.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</p></td></tr>';
		$modules = array(
			'seo' => array( __( 'Correct Rank Math schema addresses', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Use the public website address for structured data, preserving CMS-hosted media.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
			'metadata' => array( __( 'Published-page SEO endpoint', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Provide normalized Rank Math metadata for allowed, published content. The frontend must render it. Enable Rank Math Headless CMS Support first.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
			'graphql' => array( __( 'SEO through WPGraphQL', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Add headlessSeo to content queries. Requires WPGraphQL and Rank Math Headless CMS Support; the REST metadata module can stay disabled.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
			'indexnow' => array( __( 'IndexNow frontend adapter', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Send Rank Math change notifications for the public website. Deploy the verification key file on the frontend before enabling.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
			'editor_links' => array( __( 'Public View links in the editor', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Open published content on the frontend. Authenticated preview links remain with your current preview provider.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
			'cms_noindex' => array( __( 'Keep the CMS out of search results', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Adds noindex to CMS responses. During public metadata generation, bypasses only the CMS-wide indexing flag and keeps page-level robots choices.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
			'suppress_mail' => array( __( 'Suppress WordPress email', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Blocks all wp_mail delivery, including password resets, form notifications and admin alerts. Leave this off if the CMS needs to send email.', 'readyspace-headless-seo-bridge-for-rank-math' ) ),
		);
		foreach ( $modules as $name => $info ) {
			echo '<tr><th>' . esc_html( $info[0] ) . '</th><td><label><input type="checkbox" name="' . esc_attr( self::OPTION ) . '[' . esc_attr( $name ) . ']" value="1" ' . checked( ! empty( $config[ $name ] ), true, false ) . '> ' . esc_html__( 'Enable', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</label><p class="description">' . esc_html( $info[1] ) . '</p></td></tr>';
		}
		echo '<tr><th><label for="hb-key-path">' . esc_html__( 'IndexNow public key path', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</label></th><td><input type="text" class="regular-text" id="hb-key-path" name="' . esc_attr( self::OPTION ) . '[indexnow_key_path]" value="' . esc_attr( $config['indexnow_key_path'] ) . '" placeholder="/indexnow.txt"><p class="description">' . esc_html__( 'Leave empty to use / followed by the current Rank Math key and .txt. The frontend must serve this file; the bridge does not create it.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</p></td></tr>';
		foreach ( array( 'indexnow_source_origins' => array( __( 'Additional IndexNow source origins', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'Optional historical origins, one per line. Only these aliases and the configured CMS and frontend can be mapped. An alias on the CMS hostname uses the CMS base path; other aliases use public frontend paths.', 'readyspace-headless-seo-bridge-for-rank-math' ) ), 'indexnow_excluded_paths' => array( __( 'Excluded IndexNow paths', 'readyspace-headless-seo-bridge-for-rank-math' ), __( 'One public path per line, such as /preview. Each path and its descendants are excluded. The key file is always excluded.', 'readyspace-headless-seo-bridge-for-rank-math' ) ) ) as $name => $info ) {
			echo '<tr><th><label for="hb-' . esc_attr( $name ) . '">' . esc_html( $info[0] ) . '</label></th><td><textarea class="large-text" rows="3" id="hb-' . esc_attr( $name ) . '" name="' . esc_attr( self::OPTION ) . '[' . esc_attr( $name ) . ']">' . esc_textarea( implode( "\n", (array) $config[ $name ] ) ) . '</textarea><p class="description">' . esc_html( $info[1] ) . '</p></td></tr>';
		}
		echo '<tr><th><label for="hb-trailing-slash">' . esc_html__( 'IndexNow trailing slashes', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</label></th><td><select id="hb-trailing-slash" name="' . esc_attr( self::OPTION ) . '[indexnow_trailing_slash]">';
		foreach ( array( 'preserve' => __( 'Preserve existing paths', 'readyspace-headless-seo-bridge-for-rank-math' ), 'add' => __( 'Add a trailing slash', 'readyspace-headless-seo-bridge-for-rank-math' ), 'remove' => __( 'Remove trailing slashes', 'readyspace-headless-seo-bridge-for-rank-math' ) ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $config['indexnow_trailing_slash'], $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select><p class="description">' . esc_html__( 'Match the public website’s canonical paths. This choice affects IndexNow only.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'Public content types', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</th><td>';
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			echo '<label style="display:block"><input type="checkbox" name="' . esc_attr( self::OPTION ) . '[public_post_types][]" value="' . esc_attr( $type->name ) . '" ' . checked( in_array( $type->name, $config['public_post_types'], true ), true, false ) . '> ' . esc_html( $type->labels->name ) . '</label>';
		}
		echo '</td></tr></table>';
		submit_button();
		echo '</form><h2>' . esc_html__( 'Setup status', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</h2><ul>';
		echo '<li>' . esc_html( class_exists( '\RankMath\Helper' ) ? __( 'Rank Math detected.', 'readyspace-headless-seo-bridge-for-rank-math' ) : __( 'Rank Math is not loaded. SEO and IndexNow require it.', 'readyspace-headless-seo-bridge-for-rank-math' ) ) . '</li>';
		echo '<li>' . esc_html( function_exists( 'register_graphql_field' ) ? __( 'WPGraphQL detected.', 'readyspace-headless-seo-bridge-for-rank-math' ) : __( 'WPGraphQL is not loaded. Only the optional GraphQL module requires it.', 'readyspace-headless-seo-bridge-for-rank-math' ) ) . '</li>';
		echo '<li>' . esc_html( UrlMapper::is_configured() ? __( 'A separate public frontend is configured.', 'readyspace-headless-seo-bridge-for-rank-math' ) : __( 'Configure a separate public frontend before enabling bridge modules.', 'readyspace-headless-seo-bridge-for-rank-math' ) ) . '</li>';
		echo '<li>' . esc_html( get_option( 'permalink_structure' ) ? __( 'Pretty permalinks are configured.', 'readyspace-headless-seo-bridge-for-rank-math' ) : __( 'This version requires pretty permalinks for content URL mapping.', 'readyspace-headless-seo-bridge-for-rank-math' ) ) . '</li>';
		echo '<li>' . esc_html__( 'Frontend key-file reachability and rendering are checked separately during deployment; this page does not claim they are working.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</li></ul>';
		if ( ! empty( $config['indexnow'] ) && RankMath::indexnow_ready() ) {
			$key = RankMath::indexnow_key();
			if ( is_string( $key ) && preg_match( '/^[a-zA-Z0-9-]{8,128}$/D', $key ) ) {
				echo '<p>' . esc_html__( 'Public IndexNow key file:', 'readyspace-headless-seo-bridge-for-rank-math' ) . ' <code>' . esc_html( RankMath::indexnow_key_location( '', 'settings_field' ) ) . '</code></p>';
				echo '<p>' . esc_html__( 'The file must contain exactly the current Rank Math key. Rotate the frontend file whenever this key changes.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</p>';
			}
		}
		echo '<p>' . esc_html__( 'Test migration on a staging site before replacing existing helpers. Enable each responsibility in only one plugin.', 'readyspace-headless-seo-bridge-for-rank-math' ) . '</p></div>';
	}
}
