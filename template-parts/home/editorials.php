<?php
/**
 * Editoriali: uno in evidenza e tre con miniatura.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_editorials', 10 * MINUTE_IN_SECONDS, function () {
	if ( ! zw_cat_id( 'cat_editorials' ) ) {
		return;
	}
	$posts = zw_posts( array( 'cat' => zw_cat_id( 'cat_editorials' ), 'posts_per_page' => 4 ) );
	if ( ! $posts ) {
		return;
	}
	$lead = array_shift( $posts );
	?>
	<section class="bg-white rounded-[26px] p-6 nf-shadow-light" aria-label="Editoriali">
		<?php zw_section_title( 'Editoriali', array( 'size' => 'text-2xl', 'link' => zw_cat_url( 'cat_editorials' ), 'link_label' => 'Tutti →' ) ); ?>

		<article class="nf-card rounded-2xl overflow-hidden bg-zw-velvet text-white mb-4 group relative h-52">
			<?php echo zw_post_image( $lead, 640, array( 'class' => 'w-full h-full object-cover nf-img-zoom opacity-80', 'sizes' => '(max-width: 1024px) 100vw, 400px' ) ); // phpcs:ignore ?>
			<div class="absolute inset-0 nf-vignette-card"></div>
			<div class="absolute bottom-4 left-4 right-4">
				<h3 class="font-sofia-title font-extrabold text-lg text-white group-hover:text-zw-redSoft transition-colors leading-snug line-clamp-3">
					<a href="<?php echo esc_url( get_permalink( $lead ) ); ?>" class="after:absolute after:inset-[-999px] after:content-['']"><?php echo esc_html( get_the_title( $lead ) ); ?></a>
				</h3>
				<span class="text-[11px] text-white/65 mt-1 block"><?php echo esc_html( zw_date( $lead ) ); ?> • di <?php echo esc_html( get_the_author_meta( 'display_name', $lead->post_author ) ); ?></span>
			</div>
		</article>

		<div class="space-y-3">
			<?php foreach ( $posts as $post ) : ?>
				<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="bg-[#f2f4f8] hover:bg-zw-red/10 rounded-2xl p-3 transition-all flex items-center gap-3.5 group">
					<span class="w-24 h-16 rounded-xl overflow-hidden shrink-0 bg-zw-velvet block"><?php echo zw_post_image( $post, 200, array( 'class' => 'w-full h-full object-cover', 'sizes' => '96px' ) ); // phpcs:ignore ?></span>
					<span class="min-w-0 flex-1">
						<span class="block text-[10px] text-zw-muted font-medium mb-0.5"><?php echo esc_html( zw_date( $post ) ); ?></span>
						<span class="block font-sofia-title font-extrabold text-sm text-zw-ink group-hover:text-zw-red transition-colors line-clamp-2 leading-snug"><?php echo esc_html( get_the_title( $post ) ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
} );
