<?php
/**
 * Modello di riserva (es. pagina "Articoli" del blog).
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/page-hero', null, array(
	'kicker' => 'Zona Wrestling',
	'title'  => is_home() && get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : 'Ultime notizie',
) );
get_template_part( 'template-parts/loop' );
get_footer();
