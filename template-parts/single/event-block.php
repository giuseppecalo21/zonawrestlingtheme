<?php
/**
 * Richiamo all'evento collegato ai tag dell'articolo: card scura con foto,
 * countdown (per gli eventi ancora da tenersi) e link alla scheda evento.
 */

defined( 'ABSPATH' ) || exit;

$zw_event = zw_event_for_post();
if ( ! $zw_event ) {
	return;
}
$zw_status = zw_event_status( $zw_event );
$zw_count  = zw_event_news_count( $zw_event['id'] );
// Nel conteggio c'è anche l'articolo che si sta leggendo.
$zw_other = max( 0, $zw_count - 1 );
$zw_past  = 'past' === $zw_status;

if ( $zw_past ) {
	$zw_when = 'Si è svolto il ' . $zw_event['date_txt'];
} elseif ( $zw_event['start_txt'] ) {
	$zw_when = 'In Italia ' . $zw_event['start_txt'];
} else {
	$zw_when = $zw_event['date_txt'];
}
?>
<aside class="my-10 not-prose" aria-label="Evento collegato">
	<div class="relative overflow-hidden rounded-[26px] bg-zw-velvet text-white nf-shadow-dark">
		<?php if ( $zw_event['thumb'] ) : ?>
			<div class="absolute inset-0 opacity-30" aria-hidden="true">
				<?php echo zw_image( $zw_event['thumb'], 700, array( 'class' => 'w-full h-full object-cover blur-[2px] scale-105', 'sizes' => '700px', 'alt' => '' ) ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
		<div class="absolute inset-0" style="background: linear-gradient(100deg, rgba(9,10,15,.97) 30%, rgba(9,10,15,.82) 65%, rgba(59,13,84,.6) 100%);" aria-hidden="true"></div>

		<div class="relative p-5 sm:p-6 flex flex-col sm:flex-row gap-5 sm:items-center">
			<a href="<?php echo esc_url( $zw_event['url'] ); ?>" class="relative w-full sm:w-[220px] aspect-video rounded-[18px] overflow-hidden shrink-0 bg-zw-surfaceDark block group" tabindex="-1" aria-hidden="true">
				<?php echo zw_image( $zw_event['thumb'], 460, array( 'class' => 'absolute inset-0 w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 640px) 100vw, 220px', 'alt' => '' ) ); // phpcs:ignore ?>
				<?php if ( $zw_event['day'] ) : ?>
					<span class="absolute top-2 left-2 px-2 py-1.5 rounded-xl bg-white text-zw-ink flex flex-col items-center leading-none nf-shadow-dark">
						<span class="font-sofia font-black text-lg leading-none"><?php echo esc_html( $zw_event['day'] ); ?></span>
						<span class="font-sofia font-extrabold text-[10px] text-zw-red tracking-wider mt-0.5"><?php echo esc_html( $zw_event['month'] ); ?></span>
					</span>
				<?php endif; ?>
			</a>

			<div class="min-w-0 flex-1">
				<span class="inline-flex items-center gap-2 text-[11px] font-sofia font-extrabold uppercase tracking-[0.14em] text-zw-redSoft">
					<?php echo esc_html( $zw_event['fed'] ? 'Evento ' . $zw_event['fed'] : 'L\'evento' ); ?>
				</span>
				<a href="<?php echo esc_url( $zw_event['url'] ); ?>" class="block font-sofia-title font-extrabold text-xl sm:text-2xl text-white hover:text-zw-redSoft transition-colors mt-1 leading-tight"><?php echo esc_html( $zw_event['title'] ); ?></a>
				<p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs sm:text-[13px] text-white/70 mt-2">
					<?php echo zw_icon( 'calendar', 'w-3.5 h-3.5 shrink-0 text-zw-redSoft' ); // phpcs:ignore ?>
					<span><?php echo esc_html( $zw_when ); ?></span>
					<?php if ( $zw_event['place'] ) : ?>
						<span class="text-white/45">•</span><span><?php echo esc_html( $zw_event['place'] ); ?></span>
					<?php endif; ?>
				</p>

				<?php if ( ! $zw_past ) : ?>
					<div class="mt-4">
						<?php get_template_part( 'template-parts/countdown', null, array( 'event' => $zw_event, 'size' => 'sm' ) ); ?>
					</div>
				<?php endif; ?>

				<div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-4">
					<a href="<?php echo esc_url( $zw_event['url'] ); ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-zw-red hover:bg-white hover:text-zw-ink text-white font-sofia font-extrabold text-xs sm:text-sm nf-red-glow transition-all">
						<?php echo esc_html( $zw_past ? 'Risultati e notizie' : 'Vai alla scheda evento' ); ?> <span aria-hidden="true">→</span>
					</a>
					<?php if ( $zw_other > 1 ) : ?>
						<span class="text-xs text-white/65">Altre <strong class="text-white"><?php echo (int) $zw_other; ?> notizie</strong> su questo evento</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</aside>
