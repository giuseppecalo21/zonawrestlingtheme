<?php
/**
 * Feed "Ultime notizie" della home: filtri per categoria e "Carica altre notizie"
 * senza ricaricare la pagina. Senza JavaScript restano normali link agli archivi.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array $args cat (ID), live (bool), page, per_page, exclude (ID[]).
 * @return array [ html, has_more, count ]
 */
function zw_feed( $args = array() ) {
	$args = wp_parse_args( $args, array( 'cat' => 0, 'live' => false, 'page' => 1, 'per_page' => 0, 'exclude' => array() ) );
	$per  = $args['per_page'] ? (int) $args['per_page'] : max( 1, (int) zw_opt( 'feed_per_page' ) );

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $per + 1, // Uno in più per sapere se c'è un'altra pagina.
		'offset'              => ( max( 1, (int) $args['page'] ) - 1 ) * $per,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $args['cat'] ) {
		$query_args['cat'] = (int) $args['cat'];
	}
	if ( $args['live'] ) {
		$query_args['zw_title_prefix'] = (string) zw_opt( 'live_prefix' );
	}
	if ( $args['exclude'] ) {
		$query_args['post__not_in'] = array_map( 'intval', (array) $args['exclude'] );
	}

	$query    = new WP_Query( $query_args );
	$posts    = $query->posts;
	$has_more = count( $posts ) > $per;
	$posts    = array_slice( $posts, 0, $per );

	ob_start();
	foreach ( $posts as $post ) {
		get_template_part( 'template-parts/card', 'news', array( 'post' => $post ) );
	}
	return array( 'html' => ob_get_clean(), 'has_more' => $has_more, 'count' => count( $posts ) );
}

// Filtro "dirette": titolo che inizia con il prefisso configurato.
add_filter( 'posts_where', function ( $where, $query ) {
	$prefix = $query->get( 'zw_title_prefix' );
	if ( $prefix ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $wpdb->esc_like( $prefix ) . '%' );
	}
	return $where;
}, 10, 2 );

add_action( 'rest_api_init', function () {
	register_rest_route( 'zw/v1', '/feed', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'cat'  => array( 'sanitize_callback' => 'absint', 'default' => 0 ),
			'page' => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
			'live' => array( 'sanitize_callback' => 'absint', 'default' => 0 ),
		),
		'callback'            => function ( $request ) {
			$cat  = (int) $request['cat'];
			$page = min( 50, max( 1, (int) $request['page'] ) );
			$live = (bool) $request['live'];
			$key  = 'zw_feed_' . md5( get_option( 'zw_frag_v', '1' ) . "|$cat|$page|" . (int) $live );
			$data = get_transient( $key );
			if ( ! is_array( $data ) ) {
				$data = zw_feed( array( 'cat' => $cat, 'page' => $page, 'live' => $live ) );
				set_transient( $key, $data, 5 * MINUTE_IN_SECONDS );
			}
			$response = new WP_REST_Response( $data );
			$response->header( 'Cache-Control', 'public, max-age=60' );
			return $response;
		},
	) );
} );
