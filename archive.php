<?php
/**
 * Archivi: categorie, tag, autori, date.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$zw_hero = array( 'kicker' => 'Archivio', 'title' => wp_strip_all_tags( get_the_archive_title() ), 'text' => get_the_archive_description() );
if ( is_category() ) {
	$zw_hero['kicker'] = 'Categoria';
	$zw_hero['title']  = single_cat_title( '', false );
} elseif ( is_tag() ) {
	$zw_hero['kicker'] = 'Tag';
	$zw_hero['title']  = single_tag_title( '', false );
} elseif ( is_author() ) {
	$zw_user           = get_queried_object();
	$zw_role           = trim( (string) get_the_author_meta( 'zw_role', $zw_user->ID ) );
	$zw_hero['kicker'] = $zw_role ? $zw_role : 'Autore';
	$zw_hero['title']  = $zw_user->display_name;
	$zw_hero['text']   = wpautop( esc_html( get_the_author_meta( 'description', $zw_user->ID ) ) );
	$zw_hero['avatar'] = $zw_user->ID;
}
if ( is_paged() ) {
	$zw_hero['title'] .= ' — pagina ' . (int) get_query_var( 'paged' );
}

get_template_part( 'template-parts/page-hero', null, $zw_hero );
get_template_part( 'template-parts/loop' );
get_footer();
