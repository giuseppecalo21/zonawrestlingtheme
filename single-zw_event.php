<?php
/**
 * Pagina di un evento: countdown grande, scheda, tutte le notizie collegate
 * (articoli con i tag indicati nei campi dell'evento) e guida all'evento.
 * I riquadri che il plugin aggiunge al testo vengono tolti in inc/events.php.
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$zw_event  = zw_event( get_the_ID() );
$zw_status = $zw_event ? zw_event_status( $zw_event ) : '';
$zw_count  = $zw_event ? zw_event_news_count( $zw_event['id'] ) : 0;
$zw_tags   = $zw_event ? zw_event_tag_slugs( $zw_event['id'] ) : array();
$zw_feed   = $zw_tags ? zw_feed( array( 'tags' => $zw_tags, 'per_page' => 12 ) ) : array( 'html' => '', 'has_more' => false, 'count' => 0 );
$zw_feds   = get_the_terms( get_the_ID(), 'zw_federation' );
$zw_fed    = ( $zw_feds && ! is_wp_error( $zw_feds ) ) ? $zw_feds[0] : null;
$zw_name   = wp_strip_all_tags( get_the_title() );

$zw_status_label = array(
	'upcoming' => 'In programma',
	'today'    => 'Oggi',
	'live'     => 'In corso',
	'past'     => 'Concluso',
);

zw_stage_open();
?>
	<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-5 sm:pt-12">
		<nav class="hidden sm:flex items-center gap-2 text-xs text-white/55 font-medium mb-5" aria-label="Percorso">
			<?php foreach ( zw_breadcrumb() as $zw_i => $zw_crumb ) : ?>
				<?php echo $zw_i ? '<span aria-hidden="true">/</span>' : ''; ?>
				<a href="<?php echo esc_url( $zw_crumb['url'] ); ?>" class="hover:text-white transition-colors"><?php echo esc_html( $zw_crumb['name'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-center">
			<div class="lg:col-span-7 lg:pb-24">
				<div class="flex flex-wrap items-center gap-2 mb-4">
					<?php if ( $zw_event && $zw_event['fed'] ) : ?>
						<span class="px-3.5 py-1.5 rounded-full bg-zw-red text-white font-sofia font-extrabold text-xs tracking-wide uppercase nf-red-glow">Evento <?php echo esc_html( $zw_event['fed'] ); ?></span>
					<?php endif; ?>
					<?php if ( isset( $zw_status_label[ $zw_status ] ) ) : ?>
						<span class="px-3 py-1.5 rounded-full bg-white/[0.08] text-white/85 font-sofia font-bold text-xs"><?php echo esc_html( $zw_status_label[ $zw_status ] ); ?></span>
					<?php endif; ?>
				</div>

				<h1 class="font-sofia-display font-black text-[32px] leading-[1.05] sm:text-5xl lg:text-[60px] sm:leading-[1.04] text-white"><?php the_title(); ?></h1>

				<?php if ( $zw_event && ( $zw_event['date_txt'] || $zw_event['place'] ) ) : ?>
					<p class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm sm:text-base text-white/80 mt-4">
						<?php if ( $zw_event['date_txt'] ) : ?>
							<time datetime="<?php echo esc_attr( $zw_event['start'] ? $zw_event['start'] : $zw_event['date'] ); ?>" class="flex items-center gap-2 font-semibold"><?php echo zw_icon( 'calendar', 'w-4 h-4 text-zw-redSoft' ); // phpcs:ignore ?><?php echo esc_html( $zw_event['date_txt'] ); ?></time>
						<?php endif; ?>
						<?php if ( $zw_event['place'] ) : ?>
							<span class="text-white/65"><?php echo esc_html( $zw_event['place'] ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<?php if ( $zw_event && $zw_event['date'] ) : ?>
					<div class="mt-7 sm:mt-9">
						<?php if ( 'upcoming' === $zw_status || 'today' === $zw_status ) : ?>
							<p class="font-sofia font-extrabold text-xs uppercase tracking-[0.16em] text-white/55 mb-3">
								<?php echo esc_html( $zw_event['start'] ? 'Mancano all\'inizio' : 'Mancano' ); ?>
							</p>
						<?php endif; ?>
						<?php get_template_part( 'template-parts/countdown', null, array( 'event' => $zw_event, 'size' => 'xl' ) ); ?>
						<?php if ( $zw_event['start_txt'] && 'past' !== $zw_status ) : ?>
							<p class="flex items-center gap-2 text-sm text-white/75 mt-4">
								<?php echo zw_icon( 'clock', 'w-4 h-4 text-zw-redSoft' ); // phpcs:ignore ?>
								<span>In Italia: <strong class="text-white font-semibold"><?php echo esc_html( $zw_event['start_txt'] ); ?></strong></span>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $zw_count ) : ?>
					<a href="#notizie-evento" class="mt-7 inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white text-zw-ink hover:bg-zw-red hover:text-white font-sofia font-extrabold text-sm transition-all">
						<?php echo esc_html( 'past' === $zw_status ? 'Risultati e notizie' : 'Tutte le notizie' ); ?>
						<span class="px-2 py-0.5 rounded-full bg-zw-ink/10 text-xs"><?php echo (int) $zw_count; ?></span>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="lg:col-span-5 relative z-20 -mb-6 lg:-mb-16">
					<figure class="relative rounded-bento overflow-hidden nf-shadow-dark bg-zw-surfaceDark aspect-video m-0">
						<?php echo zw_image( get_post_thumbnail_id(), 1100, array( 'class' => 'w-full h-full object-cover', 'sizes' => '(max-width: 1024px) 100vw, 540px', 'fetchpriority' => 'high', 'alt' => $zw_name ) ); // phpcs:ignore ?>
					</figure>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<div class="h-10 lg:h-6"></div>
</div><!-- fascia scura -->

<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 pt-12 lg:pt-20 pb-14">

	<?php if ( $zw_event ) : ?>
		<?php
		$zw_facts = array();
		if ( $zw_event['date_txt'] ) {
			$zw_facts[] = array( 'calendar', 'Data', $zw_event['date_txt'], '' );
		}
		if ( $zw_event['start_txt'] ) {
			$zw_facts[] = array( 'clock', 'Inizio in Italia', $zw_event['start_txt'], '' );
		}
		if ( $zw_event['venue'] ) {
			$zw_facts[] = array( 'pin', 'Arena', $zw_event['venue'], '' );
		}
		if ( $zw_event['city'] ) {
			$zw_facts[] = array( 'globe', 'Città / Paese', $zw_event['city'], '' );
		}
		if ( $zw_fed ) {
			$zw_facts[] = array( 'flame', 'Federazione', $zw_fed->name, get_term_link( $zw_fed ) );
		}
		if ( $zw_count ) {
			$zw_facts[] = array( 'news', 'Notizie', $zw_count . ( 1 === $zw_count ? ' articolo' : ' articoli' ), '#notizie-evento' );
		}
		?>
		<section class="bg-white rounded-[26px] nf-shadow-light p-5 sm:p-7 mb-10" aria-labelledby="schedaTitle">
			<div class="flex items-center gap-2.5 mb-5">
				<span class="w-2.5 h-6 rounded-full bg-zw-red inline-block nf-red-glow"></span>
				<h2 id="schedaTitle" class="font-sofia font-black text-xl sm:text-2xl text-zw-ink tracking-tight">Scheda evento</h2>
			</div>
			<dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
				<?php foreach ( $zw_facts as $zw_fact ) : ?>
					<div class="flex items-center gap-3.5 rounded-[18px] bg-zw-lightBg px-4 py-3.5">
						<span class="w-10 h-10 rounded-full bg-white text-zw-red flex items-center justify-center shrink-0 nf-shadow-light"><?php echo zw_icon( $zw_fact[0], 'w-[18px] h-[18px]' ); // phpcs:ignore ?></span>
						<div class="min-w-0">
							<dt class="text-[11px] font-sofia font-extrabold uppercase tracking-wider text-zw-muted"><?php echo esc_html( $zw_fact[1] ); ?></dt>
							<dd class="font-sofia font-extrabold text-[15px] text-zw-ink leading-snug m-0">
								<?php if ( $zw_fact[3] && ! is_wp_error( $zw_fact[3] ) ) : ?>
									<a href="<?php echo esc_url( $zw_fact[3] ); ?>" class="hover:text-zw-red transition-colors"><?php echo esc_html( $zw_fact[2] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $zw_fact[2] ); ?>
								<?php endif; ?>
							</dd>
						</div>
					</div>
				<?php endforeach; ?>
			</dl>
		</section>
	<?php endif; ?>

	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
		<div class="lg:col-span-8 min-w-0">

			<?php if ( $zw_feed['count'] ) : ?>
				<section id="notizie-evento" class="scroll-mt-6 mb-12" aria-labelledby="notizieEventoTitle">
					<div class="flex items-center gap-3 mb-6 px-1">
						<span class="w-3 h-8 rounded-full bg-zw-red inline-block nf-red-glow"></span>
						<div>
							<h2 id="notizieEventoTitle" class="font-sofia font-black text-2xl sm:text-4xl text-zw-ink tracking-tight">Tutte le news su <?php echo esc_html( $zw_name ); ?></h2>
							<p class="text-xs text-zw-muted mt-1"><?php echo esc_html( 'past' === $zw_status ? 'Risultati, commenti e tutto quello che è successo' : 'Annunci, card e indiscrezioni, dalla più recente' ); ?></p>
						</div>
					</div>
					<div id="eventFeedList" class="zw-feed space-y-4" aria-live="polite"><?php echo $zw_feed['html']; // phpcs:ignore ?></div>
					<?php if ( $zw_feed['has_more'] ) : ?>
						<div class="mt-8 text-center">
							<a href="<?php echo esc_url( get_tag_link( get_term_by( 'slug', $zw_tags[0], 'post_tag' ) ) ); ?>" data-zw-event-more="<?php echo (int) get_the_ID(); ?>" class="px-8 py-4 rounded-full bg-zw-ink hover:bg-zw-red text-white font-sofia font-extrabold text-base tracking-wide nf-shadow-light transition-all inline-flex items-center gap-3">Carica altre notizie</a>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( '' !== trim( get_the_content() ) ) : ?>
				<section aria-label="Guida all'evento">
					<?php zw_section_title( 'Guida all\'evento', array( 'size' => 'text-2xl sm:text-3xl' ) ); ?>
					<article <?php post_class( 'bg-white rounded-[26px] nf-shadow-light p-5 sm:p-8' ); ?>>
						<div class="zw-prose"><?php the_content(); ?></div>
					</article>
				</section>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/single/google-buttons' ); ?>
		</div>

		<aside class="lg:col-span-4 min-w-0" aria-label="Da non perdere">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</main>
<?php
get_footer();
