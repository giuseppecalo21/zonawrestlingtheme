<?php
/**
 * Elenco di articoli con paginazione e sidebar (archivi, ricerca, blog).
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 py-10">
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
		<div class="lg:col-span-8 min-w-0">
			<?php if ( have_posts() ) : ?>
				<div class="zw-feed space-y-4">
					<?php
					$zw_n = 0;
					while ( have_posts() ) {
						the_post();
						get_template_part( 'template-parts/card', 'news', array( 'post' => get_post() ) );
						if ( 4 === ++$zw_n ) {
							zw_ad_slot( 'archive_inline' );
						}
					}
					?>
				</div>
				<?php
				the_posts_pagination( array(
					'mid_size'           => 1,
					'prev_text'          => '‹<span class="screen-reader-text"> Pagina precedente</span>',
					'next_text'          => '<span class="screen-reader-text">Pagina successiva </span>›',
					'screen_reader_text' => 'Pagine',
					'class'              => 'zw-pagination mt-10',
				) );
				?>
			<?php else : ?>
				<div class="bg-white rounded-[22px] nf-shadow-light p-8 text-center">
					<h2 class="font-sofia font-black text-2xl text-zw-ink">Nessun articolo trovato</h2>
					<p class="text-sm text-zw-muted mt-2">Prova con un'altra ricerca oppure torna alle ultime notizie.</p>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block mt-5 px-6 py-3 rounded-full bg-zw-ink hover:bg-zw-red text-white font-sofia font-extrabold text-sm transition-all">Vai alla home →</a>
				</div>
			<?php endif; ?>
		</div>
		<aside class="lg:col-span-4 min-w-0" aria-label="Da non perdere">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</main>
