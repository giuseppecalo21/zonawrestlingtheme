<?php
/**
 * Gallery: griglia asimmetrica con riquadri "a intaglio".
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_gallery', 10 * MINUTE_IN_SECONDS, function () {
	if ( ! zw_cat_id( 'cat_gallery' ) ) {
		return;
	}
	$posts = zw_posts( array( 'cat' => zw_cat_id( 'cat_gallery' ), 'posts_per_page' => 4 ) );
	if ( count( $posts ) < 2 ) {
		return;
	}
	$lead   = array_shift( $posts );
	$second = array_shift( $posts );
	$label  = function ( $post ) {
		$labels = zw_post_labels( $post );
		return $labels['fed'] ? $labels['fed'] : $labels['label'];
	};
	?>
	<section id="gallery" class="max-w-[1380px] mx-auto px-4 sm:px-6 pb-14" aria-labelledby="galleryTitle">
		<div class="flex flex-wrap items-center justify-between gap-4 mb-7 px-1">
			<div class="flex items-center gap-3">
				<span class="w-3 h-8 rounded-full bg-zw-red inline-block nf-red-glow"></span>
				<div>
					<h2 id="galleryTitle" class="font-sofia font-black text-3xl sm:text-4xl text-zw-ink tracking-tight">Gallery</h2>
					<p class="text-xs text-zw-muted mt-0.5">Le notizie da guardare, foto dopo foto</p>
				</div>
			</div>
			<a href="<?php echo esc_url( zw_cat_url( 'cat_gallery' ) ); ?>" class="px-4 py-2 rounded-full bg-zw-red/10 hover:bg-zw-red text-zw-red hover:text-white font-sofia font-bold text-xs transition-all">Vedi tutte le gallery →</a>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">
			<div class="lg:col-span-6">
				<article><a href="<?php echo esc_url( get_permalink( $lead ) ); ?>" class="group relative block">
					<div class="relative h-[340px] sm:h-[534px] rounded-[24px] overflow-hidden nf-shadow-light bg-zw-velvet">
						<?php echo zw_post_image( $lead, 1000, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 1024px) 100vw, 670px' ) ); // phpcs:ignore ?>
					</div>
					<div class="-mt-20 sm:-mt-28 relative z-10 pl-6 sm:pl-16">
						<div class="zw-cutin-br bg-[#f2f4f8] pt-6 pl-6 pr-4 pb-4 relative">
							<div class="zw-round absolute -top-4 right-3 w-10 h-10 bg-zw-red text-white flex items-center justify-center nf-red-glow"><?php echo zw_icon( 'gallery', 'w-5 h-5' ); // phpcs:ignore ?></div>
							<span class="text-xs font-extrabold text-zw-red uppercase tracking-wider block mb-2"><?php echo esc_html( $label( $lead ) ); ?></span>
							<h3 class="font-sofia-display font-black text-2xl sm:text-4xl text-zw-ink group-hover:text-zw-red transition-colors leading-[1.08]">
								<?php echo esc_html( get_the_title( $lead ) ); ?>
							</h3>
							<?php if ( has_excerpt( $lead ) ) : ?>
								<p class="text-sm text-zw-muted mt-3.5 leading-relaxed line-clamp-3"><?php echo esc_html( wp_strip_all_tags( $lead->post_excerpt ) ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</a></article>
			</div>

			<div class="lg:col-span-6 space-y-6">
				<article><a href="<?php echo esc_url( get_permalink( $second ) ); ?>" class="group relative block">
					<div class="relative aspect-video rounded-[22px] overflow-hidden bg-zw-velvet nf-shadow-light">
						<?php echo zw_post_image( $second, 1000, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 1024px) 100vw, 670px' ) ); // phpcs:ignore ?>
						<div class="absolute inset-0 bg-black/20"></div>
					</div>
					<div class="zw-cutin-tr bg-[#f2f4f8] w-[86%] sm:w-[78%] -mt-16 relative z-10 pt-4 pr-6 pb-2 pl-1">
						<span class="text-[11px] font-extrabold text-zw-red uppercase tracking-wider block"><?php echo esc_html( $label( $second ) ); ?></span>
						<h3 class="font-sofia-title font-black text-lg sm:text-xl text-zw-ink group-hover:text-zw-red transition-colors mt-0.5 leading-snug">
							<?php echo esc_html( get_the_title( $second ) ); ?>
						</h3>
					</div>
				</a></article>

				<?php if ( $posts ) : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
						<?php foreach ( $posts as $post ) : ?>
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="group block">
								<span class="block aspect-video rounded-[18px] overflow-hidden bg-zw-velvet mb-2.5 nf-shadow-light"><?php echo zw_post_image( $post, 640, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 640px) 100vw, 320px' ) ); // phpcs:ignore ?></span>
								<span class="text-[11px] font-extrabold text-zw-red uppercase tracking-wider block"><?php echo esc_html( $label( $post ) ); ?></span>
								<span class="block font-sofia-title font-extrabold text-base text-zw-ink group-hover:text-zw-red transition-colors leading-snug mt-0.5"><?php echo esc_html( get_the_title( $post ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
} );
