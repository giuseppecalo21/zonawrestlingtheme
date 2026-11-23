<?php
/**
 * Ultimi risultati: ticker scorrevole + cassetto con le schede.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_results', 10 * MINUTE_IN_SECONDS, function () {
	if ( ! zw_cat_id( 'cat_results' ) ) {
		return;
	}
	$posts = zw_posts( array( 'cat' => zw_cat_id( 'cat_results' ), 'posts_per_page' => 8 ) );
	if ( ! $posts ) {
		return;
	}
	?>
	<section id="risultati" class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-1 pb-4" aria-label="Ultimi risultati">
		<div class="zw-geo-card bg-[#141722]/95 p-1.5 nf-shadow-dark" id="resultsBar" style="border-radius: var(--pill-radius);">
			<div class="flex items-center justify-between gap-2 sm:gap-3">

				<a href="<?php echo esc_url( zw_cat_url( 'cat_results' ) ); ?>" class="flex items-center gap-2 px-3.5 sm:px-5 py-2.5 rounded-full bg-zw-red text-white shrink-0 z-10">
					<span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
					<span class="font-sofia font-extrabold text-xs sm:text-sm tracking-wide uppercase"><span class="max-sm:hidden">Ultimi </span>Risultati</span>
				</a>

				<div class="results-marquee-wrapper relative overflow-hidden flex-1 py-1">
					<div class="results-marquee-track items-center gap-3">
						<?php for ( $copy = 0; $copy < 2; $copy++ ) : ?>
							<?php foreach ( $posts as $post ) : $parsed = zw_parse_result_title( get_the_title( $post ) ); ?>
								<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" <?php echo $copy ? 'aria-hidden="true" tabindex="-1"' : ''; ?> class="shrink-0 px-4 py-1.5 rounded-full bg-white/[0.05] hover:bg-white/[0.14] flex items-center gap-2 transition-all group">
									<span class="w-1.5 h-1.5 rounded-full bg-zw-red"></span>
									<span class="text-xs leading-none whitespace-nowrap">
										<span class="font-sofia font-extrabold text-zw-redSoft uppercase tracking-wide">Risultati:</span>
										<span class="font-chivo text-white/90 group-hover:text-white font-semibold ml-1"><?php echo esc_html( $parsed['show'] ); ?></span>
									</span>
									<?php if ( $parsed['date'] ) : ?>
										<span class="px-2 py-0.5 rounded-full bg-white/10 text-white/75 text-[10px] font-bold leading-none"><?php echo esc_html( $parsed['date'] ); ?></span>
									<?php endif; ?>
								</a>
							<?php endforeach; ?>
						<?php endfor; ?>
					</div>
				</div>

				<button type="button" id="resultsDrawerToggle" aria-expanded="false" aria-controls="resultsDrawer" class="shrink-0 px-3.5 sm:px-5 py-2.5 rounded-full bg-white/[0.08] hover:bg-zw-red text-white text-xs font-sofia font-bold tracking-wide flex items-center gap-2 transition-all z-10">
					<span data-label>Tutti</span>
					<?php echo zw_icon( 'chevron', 'w-3.5 h-3.5 transition-transform duration-300' ); // phpcs:ignore ?>
				</button>
			</div>

			<div id="resultsDrawer" class="hidden pt-5 pb-4 px-3 sm:px-5 mt-2">
				<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
					<div>
						<h2 class="font-sofia font-extrabold text-lg text-white">Ultimi report</h2>
						<p class="text-xs text-white/55">I risultati completi degli ultimi show, con i vincitori di ogni match.</p>
					</div>
					<button type="button" id="spoilerToggle" aria-pressed="false" class="px-3.5 py-1.5 rounded-full bg-white/[0.08] hover:bg-white/[0.15] text-xs font-medium text-white flex items-center gap-2 transition-all">
						<span data-dot class="w-2 h-2 rounded-full bg-emerald-400"></span>
						<span data-label>Spoiler visibili</span>
					</button>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3.5">
					<?php foreach ( $posts as $post ) : $parsed = zw_parse_result_title( get_the_title( $post ) ); ?>
						<article class="nf-card relative rounded-2xl bg-zw-surfaceElevated p-4 flex flex-col justify-between group">
							<div>
								<div class="flex items-center justify-between gap-2 mb-2">
									<span class="px-2.5 py-0.5 rounded-full bg-zw-red/20 text-zw-redSoft font-sofia font-extrabold text-[10px] uppercase">Risultati</span>
									<span class="text-[11px] text-white/50 font-semibold"><?php echo esc_html( $parsed['date'] ? $parsed['date'] : zw_date( $post ) ); ?></span>
								</div>
								<h3 class="font-sofia-title font-extrabold text-sm sm:text-base text-white group-hover:text-zw-redSoft transition-colors leading-snug">
									<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="after:absolute after:inset-0 after:content-['']"><?php echo esc_html( get_the_title( $post ) ); ?></a>
								</h3>
								<p class="results-spoiler-target text-xs text-white/65 mt-2 line-clamp-2 leading-relaxed"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 22, '…' ) ); ?></p>
							</div>
							<div class="mt-3 pt-2 border-t border-white/[0.06] flex items-center justify-between text-[11px] font-sofia font-bold text-zw-redSoft" aria-hidden="true">
								<span>Leggi report e vincitori</span><span>→</span>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
} );
