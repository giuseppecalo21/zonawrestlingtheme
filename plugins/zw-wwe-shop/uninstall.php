<?php
/** Cancellazione del plugin: via tabella e opzioni. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}zw_shop_products" ); // phpcs:ignore
foreach ( array( 'zw_shop_settings', 'zw_shop_sync', 'zw_shop_last_sync', 'zw_shop_log', 'zw_shop_db_version' ) as $opt ) {
	delete_option( $opt );
}
delete_post_meta_by_key( '_zw_shop_off' );
delete_post_meta_by_key( '_zw_shop_wrestlers' );
