<?php
/**
 * Countdown di un evento.
 *
 * Con l'inizio in Italia (_zw_event_start_it): giorni, ore, minuti e secondi, poi
 * "In corso" per cinque ore e infine "Evento concluso". Con la sola data: i giorni
 * che mancano. I numeri stampati qui servono senza JavaScript e per i motori di
 * ricerca: nel browser vengono ricalcolati ogni secondo (anche da pagina in cache).
 *
 * @var array $args [ 'event' => dati di zw_event(), 'size' => 'xl' | 'sm', 'past_link' => url ]
 */

defined( 'ABSPATH' ) || exit;

$zw_ev   = isset( $args['event'] ) ? $args['event'] : null;
$zw_size = isset( $args['size'] ) && 'sm' === $args['size'] ? 'sm' : 'xl';
if ( ! $zw_ev || ! $zw_ev['date'] ) {
	return;
}
$zw_status = zw_event_status( $zw_ev );
$zw_xl     = 'xl' === $zw_size;

// Classi per le due misure.
$zw_box  = $zw_xl ? 'min-w-[68px] sm:min-w-[104px] px-2 sm:px-4 py-3 sm:py-5 rounded-[18px] sm:rounded-[24px]' : 'min-w-[54px] sm:min-w-[62px] px-1.5 py-2 rounded-[14px]';
$zw_num  = $zw_xl ? 'text-[34px] sm:text-[64px]' : 'text-2xl sm:text-[28px]';
$zw_lbl  = $zw_xl ? 'text-[10px] sm:text-xs mt-1.5 sm:mt-2' : 'text-[9px] mt-1';
$zw_gap  = $zw_xl ? 'gap-2 sm:gap-3.5' : 'gap-1.5';
$zw_tile = 'bg-white/[0.07] border border-white/10 backdrop-blur-md text-white text-center';

$zw_live_html = '<span class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-zw-red text-white font-sofia font-black uppercase tracking-wide nf-red-glow ' . ( $zw_xl ? 'text-lg sm:text-2xl' : 'text-sm' ) . '"><span class="w-2.5 h-2.5 rounded-full bg-white animate-ping"></span>In corso</span>';
$zw_past_html = '<span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white/10 text-white/85 font-sofia font-extrabold ' . ( $zw_xl ? 'text-base sm:text-lg' : 'text-xs' ) . '">Evento concluso</span>';
?>
<div class="zw-countdown" data-size="<?php echo esc_attr( $zw_size ); ?>">
	<?php if ( $zw_ev['start'] ) : ?>
		<?php
		$zw_diff  = max( 0, $zw_ev['start_ts'] - time() );
		$zw_units = array(
			'd' => array( (int) floor( $zw_diff / DAY_IN_SECONDS ), 'Giorni' ),
			'h' => array( (int) floor( $zw_diff % DAY_IN_SECONDS / HOUR_IN_SECONDS ), 'Ore' ),
			'm' => array( (int) floor( $zw_diff % HOUR_IN_SECONDS / MINUTE_IN_SECONDS ), 'Minuti' ),
			's' => array( (int) ( $zw_diff % MINUTE_IN_SECONDS ), 'Secondi' ),
		);
		?>
		<div data-zw-clock="<?php echo esc_attr( $zw_ev['start'] ); ?>">
			<div data-clock-boxes class="flex items-stretch <?php echo esc_attr( $zw_gap ); ?>" <?php echo 'upcoming' === $zw_status ? '' : 'hidden'; ?> role="timer" aria-label="<?php echo esc_attr( 'Tempo che manca all\'inizio di ' . wp_strip_all_tags( $zw_ev['title'] ) ); ?>">
				<?php foreach ( $zw_units as $zw_u => $zw_unit ) : ?>
					<span class="<?php echo esc_attr( $zw_box . ' ' . $zw_tile ); ?> flex flex-col items-center justify-center">
						<span data-u="<?php echo esc_attr( $zw_u ); ?>" class="font-sofia-display font-black leading-none tabular-nums <?php echo esc_attr( $zw_num ); ?>"><?php echo esc_html( 'd' === $zw_u ? $zw_unit[0] : sprintf( '%02d', $zw_unit[0] ) ); ?></span>
						<span class="font-sofia font-extrabold uppercase tracking-[0.12em] text-white/60 <?php echo esc_attr( $zw_lbl ); ?>"><?php echo esc_html( $zw_unit[1] ); ?></span>
					</span>
				<?php endforeach; ?>
			</div>
			<div data-clock-live <?php echo 'live' === $zw_status ? '' : 'hidden'; ?>><?php echo $zw_live_html; // phpcs:ignore ?></div>
			<div data-clock-past <?php echo 'past' === $zw_status ? '' : 'hidden'; ?>><?php echo $zw_past_html; // phpcs:ignore ?></div>
		</div>
	<?php else : ?>
		<?php
		$zw_days = (int) zw_event_days( $zw_ev );
		$zw_big  = $zw_days > 1 ? (string) $zw_days : ( 1 === $zw_days ? 'Domani' : 'Oggi' );
		?>
		<div data-zw-daysbox="<?php echo esc_attr( $zw_ev['date'] ); ?>">
			<div data-clock-boxes class="flex items-stretch <?php echo esc_attr( $zw_gap ); ?>" <?php echo 'past' === $zw_status ? 'hidden' : ''; ?>>
				<span class="<?php echo esc_attr( $zw_box . ' ' . $zw_tile ); ?> flex flex-col items-center justify-center <?php echo $zw_xl ? 'sm:min-w-[150px]' : ''; ?>">
					<span data-num class="font-sofia-display font-black leading-none tabular-nums <?php echo esc_attr( $zw_num ); ?>"><?php echo esc_html( $zw_big ); ?></span>
					<span data-lbl class="font-sofia font-extrabold uppercase tracking-[0.12em] text-white/60 <?php echo esc_attr( $zw_lbl ); ?>" <?php echo $zw_days > 1 ? '' : 'hidden'; ?>>Giorni</span>
				</span>
			</div>
			<div data-clock-past <?php echo 'past' === $zw_status ? '' : 'hidden'; ?>><?php echo $zw_past_html; // phpcs:ignore ?></div>
		</div>
	<?php endif; ?>
</div>
