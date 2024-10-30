<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ip2location_hello_greeting_lookup_mode' );
delete_option( 'ip2location_hello_greeting_database' );
delete_option( 'ip2location_hello_greeting_debug_log_enabled' );

wp_cache_flush();
