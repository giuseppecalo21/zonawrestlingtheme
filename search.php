<?php
/**
 * Risultati della ricerca.
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/page-hero', null, array(
	'kicker' => 'Ricerca',
	'title'  => '«' . get_search_query() . '»',
	'text'   => $wp_query->found_posts ? sprintf( '%s risultati trovati', number_format_i18n( $wp_query->found_posts ) ) : '',
) );
get_template_part( 'template-parts/loop' );
get_footer();
