<?php
/**
 * Home.
 */

defined( 'ABSPATH' ) || exit;

get_header();

zw_stage_open( 'overflow-hidden pb-16' );
?>
	<h1 class="screen-reader-text"><?php bloginfo( 'name' ); ?> — <?php bloginfo( 'description' ); ?></h1>
	<?php
	get_template_part( 'template-parts/home/stories' );
	get_template_part( 'template-parts/home/hero' );
	zw_ad_slot( 'home_after_hero' );
	get_template_part( 'template-parts/home/results' );
	get_template_part( 'template-parts/home/analyses' );
	?>
</div><!-- fascia scura -->

<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 py-10">
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
		<div class="lg:col-span-8 min-w-0">
			<?php get_template_part( 'template-parts/home/feed' ); ?>
		</div>
		<aside class="lg:col-span-4 space-y-7 min-w-0" aria-label="Approfondimenti">
			<?php
			if ( is_active_sidebar( 'zw-sidebar-home' ) ) {
				dynamic_sidebar( 'zw-sidebar-home' );
			}
			get_template_part( 'template-parts/home/podcast' );
			get_template_part( 'template-parts/home/editorials' );
			get_template_part( 'template-parts/home/events' );
			?>
		</aside>
	</div>
</main>

<?php
get_template_part( 'template-parts/home/gallery' );
get_template_part( 'template-parts/home/video' );

get_footer();
