<?php
/**
 * "Ultime notizie": filtri per federazione, due modalità di lista, "carica altre".
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_feed', 5 * MINUTE_IN_SECONDS, function () {
	$feed  = zw_feed();
	$chips = array();
	foreach ( zw_opt_list( 'stories_cats' ) as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( $term ) {
			$chips[] = $term;
		}
	}
	$chip_idle   = 'bg-white hover:bg-zw-ink text-zw-ink hover:text-white nf-shadow-light';
	$chip_active = 'bg-zw-red text-white nf-red-glow';
	$news_url    = zw_cat( 'news' ) ? zw_cat_url( 'news' ) : ( get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : '' );
	?>
	<section id="notizie" aria-labelledby="notizieTitle">
		<div class="mb-6 px-1">
			<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
				<div class="flex items-center gap-3">
					<span class="w-3 h-8 rounded-full bg-zw-red inline-block nf-red-glow"></span>
					<div>
						<h2 id="notizieTitle" class="font-sofia font-black text-3xl sm:text-4xl text-zw-ink tracking-tight">Ultime Notizie</h2>
						<p class="text-xs text-zw-muted mt-0.5">Tutti gli aggiornamenti, compresi report e dirette</p>
					</div>
				</div>

				<div class="bg-white p-1 rounded-full flex items-center gap-1 nf-shadow-light" role="group" aria-label="Aspetto della lista">
					<button type="button" data-feed-layout="rich" aria-pressed="true" class="px-3.5 py-1.5 rounded-full bg-zw-red text-white font-sofia font-bold text-xs transition-all">Con anteprima</button>
					<button type="button" data-feed-layout="compact" aria-pressed="false" class="px-3.5 py-1.5 rounded-full text-zw-muted hover:text-zw-ink font-sofia font-bold text-xs transition-all">Solo titoli</button>
				</div>
			</div>

			<div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-5 pb-10 -mt-4 -mb-9 px-6 -mx-6" id="feedChips" data-idle="<?php echo esc_attr( $chip_idle ); ?>" data-active="<?php echo esc_attr( $chip_active ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>#notizie" data-feed-cat="0" aria-current="true" class="px-4 py-2 rounded-full font-sofia font-bold text-xs tracking-wide shrink-0 transition-all flex items-center gap-1.5 <?php echo esc_attr( $chip_active ); ?>">Tutte</a>
				<?php foreach ( $chips as $term ) : ?>
					<a href="<?php echo esc_url( get_category_link( $term ) ); ?>" data-feed-cat="<?php echo (int) $term->term_id; ?>" class="px-4 py-2 rounded-full font-sofia font-bold text-xs tracking-wide shrink-0 transition-all flex items-center gap-1.5 <?php echo esc_attr( $chip_idle ); ?>"><?php echo esc_html( $term->name ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( home_url( '/?s=' . rawurlencode( zw_opt( 'live_prefix' ) ) ) ); ?>" data-feed-cat="0" data-feed-live="1" class="px-4 py-2 rounded-full font-sofia font-bold text-xs tracking-wide shrink-0 transition-all flex items-center gap-1.5 <?php echo esc_attr( $chip_idle ); ?>"><span class="w-1.5 h-1.5 rounded-full bg-current animate-ping"></span>Dirette live</a>
			</div>
		</div>

		<div id="newsFeedList" class="zw-feed space-y-4" aria-live="polite">
			<?php echo $feed['html']; // phpcs:ignore ?>
		</div>

		<div class="mt-8 text-center">
			<a id="loadMoreBtn" href="<?php echo esc_url( $news_url ? $news_url : home_url( '/' ) ); ?>" data-page="1" <?php echo $feed['has_more'] ? '' : 'hidden'; ?> class="px-8 py-4 rounded-full bg-zw-ink hover:bg-zw-red text-white font-sofia font-extrabold text-base tracking-wide nf-shadow-light transition-all inline-flex items-center gap-3">Carica altre notizie</a>
		</div>
	</section>
	<?php
} );
