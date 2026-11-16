<?php
/**
 * "Prossimi eventi" in versione compatta (sidebar di articoli e archivi):
 * quattro eventi con immagine, data sovrapposta alla foto e conto alla rovescia.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'upcoming_events_compact', 10 * MINUTE_IN_SECONDS, function () {
	$events = zw_upcoming_events( 4 );
	if ( ! $events ) {
		return;
	}
	?>
	<div class="bg-white rounded-[26px] p-6 nf-shadow-light">
		<?php zw_section_title( 'Prossimi eventi', array( 'size' => 'text-xl', 'link' => get_post_type_archive_link( ZW_EVENT_TYPE ), 'link_label' => 'Calendario →' ) ); ?>
		<div class="space-y-1 -mt-1">
			<?php foreach ( $events as $event ) : ?>
				<a href="<?php echo esc_url( $event['url'] ); ?>" class="group flex items-center gap-3.5 p-2 -mx-2 rounded-[16px] hover:bg-zw-red/[0.06] transition-colors">
					<span class="relative w-[128px] aspect-video rounded-[12px] overflow-hidden shrink-0 bg-zw-velvet nf-shadow-dark">
						<?php echo zw_image( $event['thumb'], 260, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '128px', 'alt' => '' ) ); // phpcs:ignore ?>
						<span class="absolute top-1.5 left-1.5 px-1.5 py-1 rounded-lg bg-white text-zw-ink flex flex-col items-center leading-none">
							<span class="font-sofia font-black text-[15px] leading-none"><?php echo esc_html( $event['day'] ); ?></span>
							<span class="font-sofia font-extrabold text-[9px] text-zw-red tracking-wider mt-0.5"><?php echo esc_html( $event['month'] ); ?></span>
						</span>
					</span>
					<span class="min-w-0 flex-1">
						<span class="block text-[10px] font-extrabold uppercase tracking-wide text-zw-red">
							<?php echo esc_html( $event['fed'] ); ?>
							<span class="text-zw-muted font-semibold normal-case tracking-normal" data-zw-countdown="<?php echo esc_attr( $event['date'] ); ?>" data-zw-tpl="tra"><?php echo esc_html( zw_event_countdown( $event, 'tra' ) ? '• ' . mb_strtolower( zw_event_countdown( $event, 'tra' ) ) : '' ); ?></span>
						</span>
						<span class="block font-sofia-title font-extrabold text-sm text-zw-ink group-hover:text-zw-red transition-colors leading-snug line-clamp-2 mt-0.5"><?php echo esc_html( $event['title'] ); ?></span>
						<?php if ( $event['place'] ) : ?>
							<span class="block text-[11px] text-zw-muted truncate mt-0.5"><?php echo esc_html( $event['place'] ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
} );
