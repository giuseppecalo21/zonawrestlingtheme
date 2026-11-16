<?php
/**
 * Prossimi eventi (home): banner 16:9 con l'immagine dell'evento.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_events', 10 * MINUTE_IN_SECONDS, function () {
	$events = zw_upcoming_events( 4 );
	if ( ! $events ) {
		return;
	}
	?>
	<section class="bg-white rounded-[26px] p-6 nf-shadow-light" aria-label="Prossimi eventi">
		<?php zw_section_title( 'Prossimi eventi', array( 'size' => 'text-2xl', 'link' => get_post_type_archive_link( ZW_EVENT_TYPE ), 'link_label' => 'Calendario →' ) ); ?>
		<div class="space-y-4">
			<?php foreach ( $events as $event ) : get_template_part( 'template-parts/event-banner', null, array( 'event' => $event ) ); endforeach; ?>
		</div>
	</section>
	<?php
} );
