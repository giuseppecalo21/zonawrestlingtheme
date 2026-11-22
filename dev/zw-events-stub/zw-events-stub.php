<?php
/**
 * Plugin Name: ZW Events (sostituto per il sito di prova)
 * Description: Registra il tipo zw_event, la tassonomia zw_federation e i campi _zw_event_* come il plugin ZW Events Calendar, così il tema mostra gli eventi. SOLO sito di prova: in produzione resta il plugin vero.
 * Version: 1.1.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	if ( post_type_exists( 'zw_event' ) ) {
		return; // C'è già il plugin vero.
	}
	register_post_type( 'zw_event', array(
		'label'        => 'Eventi',
		'public'       => true,
		'show_in_rest' => true,
		'has_archive'  => 'eventi-wrestling',
		'rewrite'      => array( 'slug' => 'eventi-wrestling', 'with_front' => false ),
		'menu_icon'    => 'dashicons-calendar-alt',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
	) );
	register_taxonomy( 'zw_federation', 'zw_event', array(
		'label'             => 'Federazioni',
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'federazione' ),
	) );
	foreach ( array( '_zw_event_date', '_zw_event_venue', '_zw_event_city', '_zw_event_related_tags' ) as $key ) {
		register_post_meta( 'zw_event', $key, array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
} );

register_activation_hook( __FILE__, function () {
	update_option( 'zw_events_stub_flush', 1 );
} );
add_action( 'init', function () {
	if ( get_option( 'zw_events_stub_flush' ) ) {
		delete_option( 'zw_events_stub_flush' );
		flush_rewrite_rules();
	}
}, 99 );

/*
 * Come il plugin vero: in fondo al testo dell'evento aggiunge "Dettagli evento"
 * e "Tutte le news su questo evento" (articoli con i tag di _zw_event_related_tags),
 * con lo stesso HTML, che il tema già impagina.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'zw_event' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$id    = get_the_ID();
	$date  = (string) get_post_meta( $id, '_zw_event_date', true );
	$venue = (string) get_post_meta( $id, '_zw_event_venue', true );
	$city  = (string) get_post_meta( $id, '_zw_event_city', true );
	$feds  = get_the_terms( $id, 'zw_federation' );

	$rows = array();
	if ( $date ) {
		$rows[] = '<li><strong>Data:</strong> ' . esc_html( date_i18n( 'j F Y', strtotime( $date . ' 12:00:00' ) ) ) . '</li>';
	}
	if ( $venue ) {
		$rows[] = '<li><strong>Arena:</strong> ' . esc_html( $venue ) . '</li>';
	}
	if ( $city ) {
		$rows[] = '<li><strong>Città / Paese:</strong> ' . esc_html( $city ) . '</li>';
	}
	if ( $feds && ! is_wp_error( $feds ) ) {
		$rows[] = '<li><strong>Federazione:</strong> ' . esc_html( implode( ', ', wp_list_pluck( $feds, 'name' ) ) ) . '</li>';
	}
	if ( $rows ) {
		$content .= '<section class="zw-event-meta-box"><h2>Dettagli evento</h2><ul>' . implode( '', $rows ) . '</ul></section>';
	}

	$slugs = array_filter( array_map( 'sanitize_title', preg_split( '/[\s,;]+/', (string) get_post_meta( $id, '_zw_event_related_tags', true ) ) ) );
	if ( $slugs ) {
		$news = get_posts( array(
			'post_type'        => 'post',
			'tag_slug__in'     => $slugs,
			'posts_per_page'   => 50,
			'suppress_filters' => false,
		) );
		if ( $news ) {
			$items = '';
			foreach ( $news as $post ) {
				$items .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a> <span class="zw-news-date">' . esc_html( get_the_date( 'j F Y', $post ) ) . '</span></li>';
			}
			$content .= '<section class="zw-event-news-box"><h2>Tutte le news su questo evento</h2><ul class="zw-event-news-list">' . $items . '</ul></section>';
		}
	}
	return $content;
}, 20 );
