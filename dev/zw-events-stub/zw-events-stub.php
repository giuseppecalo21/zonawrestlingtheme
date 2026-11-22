<?php
/**
 * Plugin Name: ZW Events (sostituto per il sito di prova)
 * Description: Registra il tipo zw_event, la tassonomia zw_federation e i campi _zw_event_* come il plugin ZW Events Calendar, così il tema mostra gli eventi. SOLO sito di prova: in produzione resta il plugin vero.
 * Version: 1.0.0
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
