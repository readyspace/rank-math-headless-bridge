<?php
/** Remove this plugin's own settings when WordPress deletes the plugin. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'headless_bridge_settings' );
