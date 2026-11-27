<?php
/**
 * Plugin Name: ZW WWE Shop
 * Description: Prodotti ufficiali di WWE Shop (affiliazione Impact) dentro gli articoli, scelti in base ai tag: un riquadro a metà testo con il merchandise dei wrestler di cui parla la notizia.
 * Version: 0.1.1
 * Requires PHP: 7.4
 * Author: Zona Wrestling
 * Text Domain: zw-wwe-shop
 */

defined( 'ABSPATH' ) || exit;

define( 'ZW_SHOP_VERSION', '0.1.1' );
define( 'ZW_SHOP_DB_VERSION', '1' );
define( 'ZW_SHOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZW_SHOP_URL', plugin_dir_url( __FILE__ ) );

require ZW_SHOP_DIR . 'includes/db.php';
require ZW_SHOP_DIR . 'includes/settings.php';
require ZW_SHOP_DIR . 'includes/sync.php';
require ZW_SHOP_DIR . 'includes/match.php';
require ZW_SHOP_DIR . 'includes/render.php';
require ZW_SHOP_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, function () {
	zw_shop_install_db();
	zw_shop_schedule();
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'zw_shop_daily_sync' );
	wp_clear_scheduled_hook( 'zw_shop_sync_step' );
} );

// Aggiornamento del plugin senza riattivazione: allinea la tabella e il cron.
add_action( 'plugins_loaded', function () {
	if ( get_option( 'zw_shop_db_version' ) !== ZW_SHOP_DB_VERSION ) {
		zw_shop_install_db();
	}
	zw_shop_schedule();
} );
