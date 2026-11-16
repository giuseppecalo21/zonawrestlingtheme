<?php
/**
 * Configurazione del tema, pulizia dell'head, stili e script.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'zonawrestling', ZW_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 400, 'flex-width' => true, 'flex-height' => true ) );

	register_nav_menus( array(
		'primary' => 'Menu principale (header)',
		'footer'  => 'Menu footer',
	) );

	// Nessuna nuova dimensione immagine: il tema sceglie tra quelle già generate
	// (comprese quelle create da Newspaper), così non serve rigenerare l'archivio.
} );

if ( ! isset( $content_width ) ) {
	$content_width = 760;
}

add_action( 'widgets_init', function () {
	$base = array(
		'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="font-sofia font-black text-xl text-zw-ink tracking-tight mb-3">',
		'after_title'   => '</h2>',
	);
	register_sidebar( array_merge( $base, array(
		'id'          => 'zw-sidebar-article',
		'name'        => 'Sidebar articolo (in alto)',
		'description' => 'Mostrata sopra "Le più lette" negli articoli e negli archivi. Adatta ai banner.',
	) ) );
	register_sidebar( array_merge( $base, array(
		'id'          => 'zw-sidebar-home',
		'name'        => 'Sidebar home (in alto)',
		'description' => 'Mostrata sopra il podcast nella home. Adatta ai banner.',
	) ) );
} );

/* ---------------------------------------------------------------------------
 * Head più leggero.
 * ------------------------------------------------------------------------- */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
} );
add_filter( 'emoji_svg_url', '__return_false' );

// Carica il CSS dei soli blocchi presenti nella pagina.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

add_action( 'wp_enqueue_scripts', function () {
	$css = ZW_DIR . '/assets/css/main.css';
	$js  = ZW_DIR . '/assets/js/main.js';

	wp_enqueue_style(
		'zw-fonts',
		'https://fonts.googleapis.com/css2?family=Chivo:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Sofia+Sans+Condensed:wght@800;900&family=Sofia+Sans:wght@600;700;800;900&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'zw-main', ZW_URI . '/assets/css/main.css', array(), file_exists( $css ) ? filemtime( $css ) : ZW_VERSION );
	wp_enqueue_script( 'zw-main', ZW_URI . '/assets/js/main.js', array(), file_exists( $js ) ? filemtime( $js ) : ZW_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	wp_localize_script( 'zw-main', 'ZW', array(
		'rest'    => esc_url_raw( rest_url() ),
		'home'    => esc_url_raw( home_url( '/' ) ),
		'postId'  => ( is_singular( 'post' ) && ! is_preview() ) ? get_queried_object_id() : 0,
		'views'   => (int) zw_opt( 'views_enabled' ),
		'stories' => is_front_page() ? zw_stories_data() : array(),
	) );

	// Gli stili globali di theme.json non servono a un tema classico come questo.
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}, 20 );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
	}
	return $urls;
}, 10, 2 );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'antialiased';
	return $classes;
} );

add_filter( 'excerpt_length', function () {
	return 28;
} );
add_filter( 'excerpt_more', function () {
	return '…';
} );

// La home del tema costruisce le proprie liste: la query principale resta minima.
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', 'post' );
	}
} );
