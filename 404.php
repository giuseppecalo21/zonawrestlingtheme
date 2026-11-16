<?php
/**
 * Pagina non trovata.
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/page-hero', null, array(
	'kicker' => 'Errore 404',
	'title'  => 'Questa pagina non esiste',
	'text'   => 'Il link potrebbe essere sbagliato oppure l\'articolo è stato spostato. Qui sotto trovi le ultime notizie.',
) );
$zw_feed = zw_feed( array( 'per_page' => 8 ) );
?>
<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 py-10">
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
		<div class="lg:col-span-8 min-w-0">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 mb-8">
				<label class="screen-reader-text" for="s404">Cerca</label>
				<input id="s404" type="search" name="s" placeholder="Cerca wrestler, show o federazione…" class="zw-geo-pill flex-1 min-w-0 px-5 py-3.5 bg-white text-sm border-0 nf-shadow-light focus:ring-2 focus:ring-zw-red" />
				<button type="submit" class="px-6 py-3.5 rounded-full bg-zw-ink hover:bg-zw-red text-white font-sofia font-extrabold text-sm">Cerca</button>
			</form>
			<div class="zw-feed space-y-4"><?php echo $zw_feed['html']; // phpcs:ignore ?></div>
		</div>
		<aside class="lg:col-span-4 min-w-0"><?php get_sidebar(); ?></aside>
	</div>
</main>
<?php
get_footer();
