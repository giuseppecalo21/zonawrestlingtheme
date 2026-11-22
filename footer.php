<?php
/**
 * Footer, menu mobile, ricerca e (in home) visualizzatore delle stories.
 */

defined( 'ABSPATH' ) || exit;

$zw_social = zw_social_links();
$zw_items  = function_exists( 'zw_menu_items' ) ? zw_menu_items() : array();
?>
	<footer class="bg-[#07070a] text-white/55 py-12 px-4 sm:px-6">
		<div class="max-w-[1380px] mx-auto">
			<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
				<div class="flex items-center gap-3.5">
					<?php if ( ! has_custom_logo() ) : ?>
						<span class="w-10 h-10 rounded-2xl bg-zw-red flex items-center justify-center font-sofia font-black text-xl text-white shrink-0">ZW</span>
					<?php endif; ?>
					<div>
						<?php if ( has_custom_logo() ) : ?>
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — Home"><?php echo zw_logo( 'h-10 w-auto max-w-[240px] object-contain' ); // phpcs:ignore ?></a>
						<?php else : ?>
							<span class="font-sofia font-black text-xl text-white">ZONA<span class="text-zw-red">WRESTLING</span>.NET</span>
						<?php endif; ?>
						<?php if ( zw_opt( 'footer_tagline' ) ) : ?>
							<p class="text-xs text-white/45 mt-0.5"><?php echo esc_html( zw_opt( 'footer_tagline' ) ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<nav class="flex flex-wrap items-center gap-2" aria-label="Federazioni">
					<?php
					foreach ( zw_opt_list( 'stories_cats' ) as $zw_slug ) :
						$zw_term = get_category_by_slug( $zw_slug );
						if ( ! $zw_term ) {
							continue;
						}
						?>
						<a href="<?php echo esc_url( get_category_link( $zw_term ) ); ?>" class="px-3.5 py-1.5 rounded-full bg-white/[0.05] hover:bg-zw-red text-xs text-white transition-all"><?php echo esc_html( $zw_term->name ); ?></a>
					<?php endforeach; ?>
				</nav>

				<?php if ( $zw_social ) : ?>
					<div class="flex items-center gap-1.5">
						<?php foreach ( $zw_social as $key => $social ) : ?>
							<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $social['label'] ); ?>" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="w-10 h-10 rounded-full bg-white/[0.07] hover:bg-zw-red text-white/80 hover:text-white flex items-center justify-center transition-all"><?php echo zw_icon( $key ); // phpcs:ignore ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="mt-8 pt-6 border-t border-white/[0.07] flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-xs">
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'container'      => false,
						'depth'          => 1,
						'menu_class'     => 'flex flex-wrap items-center gap-x-5 gap-y-2 text-white/60 [&_a:hover]:text-white',
						'fallback_cb'    => false,
					) );
				}
				?>
				<div class="text-white/35">© 2001–<?php echo esc_html( wp_date( 'Y' ) ); ?> ZonaWrestling.net • Tutti i diritti riservati</div>
			</div>
		</div>
	</footer>

</div><!-- #appShell -->

<!-- Menu mobile -->
<div id="mobileMenu" class="fixed inset-0 z-50 hidden bg-black/85 backdrop-blur-lg" role="dialog" aria-modal="true" aria-label="Menu" data-zw-modal>
	<div class="absolute top-0 right-0 bottom-0 w-[86%] max-w-[360px] bg-zw-surfaceDark text-white p-5 overflow-y-auto">
		<div class="flex items-center justify-between mb-5">
			<span class="font-sofia font-black text-lg">Menu</span>
			<button type="button" data-zw-close class="w-10 h-10 rounded-full bg-white/10 hover:bg-zw-red flex items-center justify-center" aria-label="Chiudi il menu"><?php echo zw_icon( 'close', 'w-5 h-5' ); // phpcs:ignore ?></button>
		</div>
		<nav class="flex flex-col gap-1" aria-label="Menu principale">
			<?php foreach ( $zw_items as $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="px-4 py-3 rounded-2xl hover:bg-white/[0.07] font-sofia font-extrabold text-base"><?php echo esc_html( $item['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php if ( $zw_social ) : ?>
			<div class="flex flex-wrap items-center gap-2 mt-6 pt-5 border-t border-white/10">
				<?php foreach ( $zw_social as $key => $social ) : ?>
					<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="w-10 h-10 rounded-full bg-white/[0.07] hover:bg-zw-red text-white flex items-center justify-center transition-all"><?php echo zw_icon( $key ); // phpcs:ignore ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Ricerca -->
<div id="searchModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-lg items-start justify-center pt-20 p-4" role="dialog" aria-modal="true" aria-label="Cerca" data-zw-modal>
	<div class="w-full max-w-2xl rounded-modal bg-zw-surfaceDark text-white p-6 nf-shadow-dark">
		<div class="flex items-center justify-between mb-4">
			<span class="font-sofia font-extrabold text-lg text-white">Cerca su ZonaWrestling</span>
			<button type="button" data-zw-close class="px-3 py-1 rounded-full bg-white/10 hover:bg-zw-red text-xs text-white">Chiudi ×</button>
		</div>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
			<label class="screen-reader-text" for="searchBoxInput">Cerca</label>
			<input id="searchBoxInput" type="search" name="s" autocomplete="off" placeholder="Cerca wrestler, show o federazione…" class="flex-1 min-w-0 px-5 py-3.5 rounded-2xl bg-black/50 text-sm text-white placeholder-white/40 border-0 focus:outline-none focus:ring-2 focus:ring-zw-red" />
			<button type="submit" class="px-5 py-3.5 rounded-full bg-zw-red hover:bg-zw-redSoft text-white font-sofia font-extrabold text-sm shrink-0">Cerca</button>
		</form>
		<div id="searchResultsList" class="mt-4 space-y-2 max-h-72 overflow-y-auto" aria-live="polite">
			<p class="text-xs text-white/40 py-4 text-center">Scrivi almeno tre lettere per vedere i primi risultati</p>
		</div>
	</div>
</div>

<?php if ( is_front_page() ) : ?>
<!-- Stories -->
<div id="storiesModal" class="fixed inset-0 z-50 hidden bg-black/90 backdrop-blur-xl items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true" aria-label="Stories" data-zw-modal>
	<div class="relative w-full max-w-[410px] h-[85vh] max-h-[750px] rounded-modal bg-zw-surfaceDark overflow-hidden nf-shadow-dark flex flex-col justify-between select-none">
		<div class="relative z-30 pt-4 px-4">
			<div id="storyProgressSegments" class="flex gap-1.5 mb-3.5"></div>
			<div class="flex items-center justify-between gap-2">
				<a id="storyFedLink" href="#" class="flex items-center gap-2.5 min-w-0">
					<span id="storyFedAvatar" class="zw-round w-10 h-10 bg-zw-velvet flex items-center justify-center font-sofia font-black text-[10px] text-white overflow-hidden shrink-0"></span>
					<span class="min-w-0">
						<span id="storyFedTitle" class="font-sofia font-extrabold text-base text-white block leading-none truncate"></span>
						<span id="storySlideTime" class="text-[11px] text-white/60"></span>
					</span>
				</a>
				<div class="flex items-center gap-1.5 shrink-0">
					<button type="button" id="storyPrevFed" class="px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/20 text-[11px] font-sofia font-bold text-white" aria-label="Federazione precedente">‹</button>
					<button type="button" id="storyNextFed" class="px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/20 text-[11px] font-sofia font-bold text-white" aria-label="Federazione successiva">›</button>
					<button type="button" data-zw-close class="w-8 h-8 rounded-full bg-black/50 hover:bg-zw-red text-white flex items-center justify-center text-base ml-1" aria-label="Chiudi">×</button>
				</div>
			</div>
		</div>

		<img id="storyActivePhoto" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="" class="absolute inset-0 w-full h-full object-cover opacity-85" />
		<div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-black/25"></div>

		<button type="button" id="storyTapPrev" class="absolute inset-y-20 left-0 w-1/3 z-20 cursor-pointer" aria-label="Storia precedente" style="border-radius:0"></button>
		<button type="button" id="storyTapNext" class="absolute inset-y-20 right-0 w-2/3 z-20 cursor-pointer" aria-label="Storia successiva" style="border-radius:0"></button>

		<div class="relative z-30 p-5 sm:p-6">
			<div class="flex items-center gap-2 mb-2.5">
				<span id="storyActiveCategory" class="px-3 py-1 rounded-full bg-zw-red text-white font-sofia font-extrabold text-[11px] uppercase"></span>
				<span id="storySlideCounter" class="text-xs text-white/65 font-medium"></span>
			</div>
			<h3 id="storyActiveHeadline" class="font-sofia-display font-black text-2xl sm:text-3xl text-white mb-2.5"></h3>
			<p id="storyActiveSummary" class="text-xs sm:text-sm text-white/80 leading-relaxed mb-5"></p>
			<a id="storyReadArticleBtn" href="#" class="block w-full py-3.5 rounded-full bg-zw-red hover:bg-zw-redSoft text-white text-center font-sofia font-extrabold text-sm tracking-wide uppercase nf-red-glow transition-all">Leggi l'articolo completo →</a>
		</div>
	</div>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
