<?php
/**
 * Sidebar di articoli e archivi: banner, più lette, prossimi eventi.
 */

defined( 'ABSPATH' ) || exit;

if ( is_active_sidebar( 'zw-sidebar-article' ) ) {
	dynamic_sidebar( 'zw-sidebar-article' );
}
get_template_part( 'template-parts/most-read', null, array( 'layout' => 'list' ) );
?>
<div class="sticky top-6 mt-6">
	<?php get_template_part( 'template-parts/upcoming-events' ); ?>
</div>
