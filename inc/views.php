<?php
/**
 * "Le più lette di oggi": contatore di letture interno.
 *
 * Una tabella minuscola (post, giorno, letture) aggiornata da una chiamata leggera
 * fatta dal browser: funziona anche con le pagine in cache (WP Rocket) e non scrive
 * nei campi personalizzati. I giorni più vecchi di una settimana vengono cancellati.
 */

defined( 'ABSPATH' ) || exit;

function zw_views_table() {
	global $wpdb;
	return $wpdb->prefix . 'zw_views';
}

function zw_views_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( 'CREATE TABLE ' . zw_views_table() . " (
		post_id bigint(20) unsigned NOT NULL,
		day date NOT NULL,
		views int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (post_id, day),
		KEY day_views (day, views)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'zw_views_db', '1', false );
}

add_action( 'after_switch_theme', 'zw_views_install' );
add_action( 'init', function () {
	if ( '1' !== get_option( 'zw_views_db' ) ) {
		zw_views_install();
	}
	if ( ! wp_next_scheduled( 'zw_views_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'zw_views_cleanup' );
	}
} );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( 'zw_views_cleanup' );
} );
add_action( 'zw_views_cleanup', function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . zw_views_table() . ' WHERE day < %s', wp_date( 'Y-m-d', time() - 7 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'zw/v1', '/view/(?P<id>\d+)', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( $request ) {
			global $wpdb;
			$post_id = (int) $request['id'];
			if ( ! zw_opt( 'views_enabled' ) || 'post' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
				return new WP_REST_Response( null, 204 );
			}
			$wpdb->query( $wpdb->prepare(
				'INSERT INTO ' . zw_views_table() . ' (post_id, day, views) VALUES (%d, %s, 1) ON DUPLICATE KEY UPDATE views = views + 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_id,
				zw_today()
			) );
			return new WP_REST_Response( null, 204 );
		},
	) );
} );

/**
 * Articoli più letti oggi. Se le letture di oggi non bastano (es. a inizio giornata)
 * la classifica si completa con ieri e, in ultima istanza, con gli ultimi in primo piano.
 */
function zw_most_read( $count = 5 ) {
	$key = 'zw_most_read_' . $count;
	$ids = get_transient( $key );
	if ( ! is_array( $ids ) ) {
		global $wpdb;
		$ids = array();
		if ( '1' === get_option( 'zw_views_db' ) ) {
			foreach ( array( 0, 1 ) as $days_ago ) {
				if ( count( $ids ) >= $count ) {
					break;
				}
				$day  = wp_date( 'Y-m-d', time() - $days_ago * DAY_IN_SECONDS );
				$rows = $wpdb->get_col( $wpdb->prepare(
					'SELECT v.post_id FROM ' . zw_views_table() . " v INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_status = 'publish' WHERE v.day = %s ORDER BY v.views DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$day,
					$count * 2
				) );
				$ids = array_slice( array_unique( array_merge( $ids, array_map( 'intval', (array) $rows ) ) ), 0, $count );
			}
		}
		if ( count( $ids ) < $count ) {
			$fallback = zw_posts( array(
				'cat'            => zw_cat_id( 'cat_featured' ),
				'posts_per_page' => $count,
				'post__not_in'   => $ids ? $ids : array( 0 ),
				'fields'         => 'ids',
			) );
			$ids = array_slice( array_merge( $ids, array_map( 'intval', (array) $fallback ) ), 0, $count );
		}
		set_transient( $key, $ids, 10 * MINUTE_IN_SECONDS );
	}
	if ( ! $ids ) {
		return array();
	}
	return zw_posts( array( 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => count( $ids ) ) );
}
