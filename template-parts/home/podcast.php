<?php
/**
 * Podcast: ultimo episodio. Se nell'articolo c'è un file audio (mp3/m4a) compare
 * il lettore; altrimenti la card porta alla pagina dell'episodio.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_podcast', 10 * MINUTE_IN_SECONDS, function () {
	if ( ! zw_cat_id( 'cat_podcast' ) ) {
		return;
	}
	$posts = zw_posts( array( 'cat' => zw_cat_id( 'cat_podcast' ), 'posts_per_page' => 3 ) );
	if ( ! $posts ) {
		return;
	}
	$episode = $posts[0];
	$audio   = zw_podcast_audio( $episode );
	?>
	<section id="podcast" class="rounded-[26px] bg-gradient-to-br from-[#14161f] via-[#0d0f15] to-[#1d2208] text-white p-6 nf-shadow-dark relative overflow-hidden" aria-label="Podcast">
		<div class="pointer-events-none absolute -top-16 -right-16 w-48 h-48 rounded-full bg-zw-acid/20 blur-3xl"></div>

		<div class="relative flex items-center justify-between mb-5">
			<div class="flex items-center gap-2.5">
				<div class="w-9 h-9 rounded-2xl bg-zw-acid/15 flex items-center justify-center text-zw-acid"><?php echo zw_icon( 'mic', 'w-5 h-5' ); // phpcs:ignore ?></div>
				<div>
					<span class="text-[10px] font-extrabold uppercase tracking-widest text-zw-acid block">Ultimo episodio</span>
					<h2 class="font-sofia font-black text-lg text-white leading-none"><a href="<?php echo esc_url( zw_cat_url( 'cat_podcast' ) ); ?>">Zona Wrestling Podcast</a></h2>
				</div>
			</div>
			<div class="flex items-end gap-1 h-5 px-2.5 py-1 rounded-full bg-zw-acid/10" aria-hidden="true">
				<span class="w-1 h-3.5 bg-zw-acid eq-bar eq-1"></span><span class="w-1 h-4 bg-zw-acid eq-bar eq-2"></span><span class="w-1 h-2.5 bg-zw-acid eq-bar eq-3"></span><span class="w-1 h-4 bg-zw-acid eq-bar eq-4"></span><span class="w-1 h-3 bg-zw-acid eq-bar eq-5"></span>
			</div>
		</div>

		<a href="<?php echo esc_url( get_permalink( $episode ) ); ?>" class="relative flex items-center gap-4 mb-5 group">
			<span class="w-16 h-16 rounded-2xl overflow-hidden shrink-0 relative nf-shadow-dark ring-2 ring-zw-acid/40 bg-black block"><?php echo zw_post_image( $episode, 160, array( 'class' => 'w-full h-full object-cover', 'sizes' => '64px' ) ); // phpcs:ignore ?></span>
			<span class="min-w-0">
				<span class="text-[11px] font-bold text-zw-acid block"><?php echo esc_html( zw_date( $episode ) ); ?></span>
				<span class="font-sofia font-extrabold text-base text-white group-hover:text-zw-acid transition-colors leading-snug line-clamp-2 mt-0.5 block"><?php echo esc_html( get_the_title( $episode ) ); ?></span>
			</span>
		</a>

		<?php if ( $audio ) : ?>
			<div class="relative bg-black/45 backdrop-blur-md rounded-2xl p-4" id="podcastPlayer" data-src="<?php echo esc_url( $audio ); ?>">
				<div data-pod-bar class="w-full h-2 rounded-full bg-white/15 cursor-pointer overflow-hidden mb-2.5" role="slider" aria-label="Posizione" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
					<div data-pod-fill class="h-full rounded-full bg-zw-acid" style="width:0%"></div>
				</div>
				<div class="flex items-center justify-between text-[11px] text-white/55 font-medium mb-3">
					<span data-pod-current class="text-zw-acid font-bold">0:00</span>
					<button type="button" data-pod-speed class="px-2.5 py-0.5 rounded-full bg-white/10 hover:bg-zw-acid hover:text-zw-ink text-[10px] font-bold text-white transition-colors" aria-label="Velocità di riproduzione">1x</button>
					<span data-pod-total>--:--</span>
				</div>
				<div class="flex items-center justify-center gap-5">
					<button type="button" data-pod-skip="-15" class="w-9 h-9 rounded-full bg-white/[0.08] hover:bg-white/[0.16] text-white/80 hover:text-white flex items-center justify-center text-[11px] font-bold transition-all" aria-label="Indietro di 15 secondi">-15</button>
					<button type="button" data-pod-play class="zw-round w-12 h-12 bg-zw-acid hover:bg-zw-acidHover text-zw-ink flex items-center justify-center nf-acid-glow transition-transform active:scale-95" aria-label="Riproduci o metti in pausa">
						<span data-icon-play><?php echo zw_icon( 'play', 'w-5 h-5 ml-0.5' ); // phpcs:ignore ?></span>
						<span data-icon-pause hidden><?php echo zw_icon( 'pause', 'w-5 h-5' ); // phpcs:ignore ?></span>
					</button>
					<button type="button" data-pod-skip="15" class="w-9 h-9 rounded-full bg-white/[0.08] hover:bg-white/[0.16] text-white/80 hover:text-white flex items-center justify-center text-[11px] font-bold transition-all" aria-label="Avanti di 15 secondi">+15</button>
				</div>
			</div>
		<?php else : ?>
			<a href="<?php echo esc_url( get_permalink( $episode ) ); ?>" class="relative flex items-center justify-center gap-2.5 w-full py-3.5 rounded-full bg-zw-acid hover:bg-zw-acidHover text-zw-ink font-sofia font-black text-sm nf-acid-glow transition-all">
				<?php echo zw_icon( 'play', 'w-4 h-4' ); // phpcs:ignore ?> Ascolta l'episodio
			</a>
		<?php endif; ?>

		<?php if ( count( $posts ) > 1 ) : ?>
			<div class="relative mt-4 pt-3.5 border-t border-white/10 space-y-1.5">
				<?php foreach ( array_slice( $posts, 1 ) as $post ) : ?>
					<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="w-full px-3 py-2 rounded-xl hover:bg-white/[0.06] flex items-center justify-between gap-3 text-xs text-white/75 hover:text-white transition-all">
						<span class="truncate"><?php echo esc_html( get_the_title( $post ) ); ?></span>
						<span class="text-zw-acid font-bold shrink-0"><?php echo esc_html( get_the_date( 'd.m', $post ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
} );
