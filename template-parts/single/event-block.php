<?php
/**
 * Riquadro dell'evento collegato ai tag dell'articolo (stile "card chiara").
 */

defined( 'ABSPATH' ) || exit;

$zw_event = zw_event_for_post();
if ( ! $zw_event ) {
	return;
}
$zw_days  = zw_event_days( $zw_event );
$zw_count = zw_event_news_count( $zw_event['id'] );
// Nel conteggio c'è anche l'articolo che si sta leggendo.
$zw_other = max( 0, $zw_count - 1 );

if ( null === $zw_days ) {
	$zw_when = '';
} elseif ( $zw_days < 0 ) {
	$zw_when = 'Si è svolto il ' . $zw_event['date_txt'];
} elseif ( 0 === $zw_days ) {
	$zw_when = 'Oggi, ' . $zw_event['date_txt'];
} elseif ( 1 === $zw_days ) {
	$zw_when = 'Domani, ' . $zw_event['date_txt'];
} else {
	$zw_when = 'In programma il ' . $zw_event['date_txt'];
}
?>
<aside class="my-8" aria-label="Evento collegato">
	<a href="<?php echo esc_url( $zw_event['url'] ); ?>" class="nf-card bg-white rounded-[22px] p-3 nf-shadow-light flex flex-row-reverse sm:flex-row items-stretch gap-3.5 sm:gap-4 group">
		<span class="relative w-[40%] sm:w-[210px] aspect-video rounded-[16px] overflow-hidden shrink-0 self-center bg-zw-velvet block">
			<?php echo zw_image( $zw_event['thumb'], 440, array( 'class' => 'absolute inset-0 w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 640px) 40vw, 210px', 'alt' => '' ) ); // phpcs:ignore ?>
		</span>
		<span class="min-w-0 flex flex-col justify-center py-1 pl-1 sm:pl-0 pr-2">
			<span class="text-[11px] font-extrabold text-zw-red uppercase tracking-wide"><?php echo esc_html( $zw_event['fed'] ? 'Evento ' . $zw_event['fed'] : 'L\'evento' ); ?></span>
			<span class="font-sofia-title font-extrabold text-base sm:text-xl text-zw-ink group-hover:text-zw-red transition-colors mt-0.5"><?php echo esc_html( $zw_event['title'] ); ?></span>
			<?php if ( $zw_when ) : ?>
				<span class="flex items-center gap-1.5 text-[11px] sm:text-xs text-zw-muted font-semibold mt-1.5">
					<?php echo zw_icon( 'calendar', 'w-3.5 h-3.5 shrink-0 text-zw-red' ); // phpcs:ignore ?>
					<span><?php echo esc_html( $zw_when ); ?><?php echo $zw_event['place'] ? ' • ' . esc_html( $zw_event['place'] ) : ''; ?></span>
				</span>
			<?php endif; ?>
			<span class="text-[11px] sm:text-[13px] text-zw-ink/80 mt-1.5 sm:mt-2">
				<?php if ( $zw_other > 1 ) : ?>
					Altre <strong><?php echo (int) $zw_other; ?> notizie</strong> su questo evento.
				<?php endif; ?>
				<span class="font-sofia font-extrabold text-zw-red whitespace-nowrap"><?php echo esc_html( null !== $zw_days && $zw_days < 0 ? 'Risultati e notizie →' : 'Vai alla pagina dell\'evento →' ); ?></span>
			</span>
		</span>
	</a>
</aside>
