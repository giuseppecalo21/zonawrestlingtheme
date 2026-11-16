<?php
/**
 * Carosello "Le analisi degli show" con le stelline.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_analyses', 10 * MINUTE_IN_SECONDS, function () {
	if ( ! zw_cat_id( 'cat_analysis' ) ) {
		return;
	}
	$posts = zw_posts( array( 'cat' => zw_cat_id( 'cat_analysis' ), 'posts_per_page' => 8 ) );
	if ( ! $posts ) {
		return;
	}
	?>
	<section id="analisi" class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-3" aria-label="Le analisi degli show">
		<div class="flex items-center justify-between mb-4 px-1">
			<div class="flex items-center gap-3 min-w-0">
				<span class="w-2.5 h-6 rounded-full bg-zw-red inline-block nf-red-glow shrink-0"></span>
				<h2 class="font-sofia font-black text-xl sm:text-2xl text-white tracking-tight"><a href="<?php echo esc_url( zw_cat_url( 'cat_analysis' ) ); ?>" class="hover:text-zw-redSoft transition-colors">Le analisi degli show</a></h2>
				<span class="text-xs text-white/45 hidden md:inline truncate">Le ultime puntate recensite match per match</span>
			</div>
			<div class="flex items-center gap-2 shrink-0">
				<button type="button" data-scroll="#analysesTrack" data-dir="-1" class="w-9 h-9 rounded-full bg-white/[0.08] hover:bg-zw-red text-white flex items-center justify-center transition-all" aria-label="Scorri a sinistra">‹</button>
				<button type="button" data-scroll="#analysesTrack" data-dir="1" class="w-9 h-9 rounded-full bg-white/[0.08] hover:bg-zw-red text-white flex items-center justify-center transition-all" aria-label="Scorri a destra">›</button>
			</div>
		</div>

		<div id="analysesTrack" class="flex items-stretch gap-5 overflow-x-auto no-scrollbar scroll-smooth snap-x pt-5 -mt-5 pb-16 -mb-14 px-6 -mx-6">
			<?php foreach ( $posts as $post ) : $labels = zw_post_labels( $post ); $stars = zw_review_stars( $post->ID ); ?>
				<article class="nf-card snap-start shrink-0 w-[85%] sm:w-[46%] lg:w-[calc(33.333%-14px)] aspect-video rounded-[20px] overflow-hidden relative bg-zw-surfaceDark nf-shadow-dark group">
					<div class="absolute inset-0"><?php echo zw_post_image( $post, 700, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 640px) 85vw, 440px' ) ); // phpcs:ignore ?></div>
					<div class="absolute inset-0 nf-vignette-analysis"></div>
					<div class="absolute inset-x-4 bottom-4 sm:inset-x-5 sm:bottom-5 z-10">
						<h3 class="font-sofia-title font-black text-lg sm:text-[21px] text-white group-hover:text-zw-redSoft transition-colors leading-[1.16] line-clamp-2 drop-shadow">
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="after:absolute after:inset-[-999px] after:content-['']"><?php echo esc_html( get_the_title( $post ) ); ?></a>
						</h3>
						<div class="flex items-center gap-3 mt-2.5 text-white">
							<?php if ( $labels['label'] ) : ?>
								<span class="px-2.5 py-0.5 bg-white text-[#090a0f] font-sofia font-extrabold text-[11px] tracking-wider uppercase leading-tight" style="border-radius:4px"><?php echo esc_html( $labels['label'] ); ?></span>
							<?php endif; ?>
							<?php
							if ( null !== $stars ) {
								echo zw_stars( $stars ); // phpcs:ignore
							}
							?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
} );
