<?php
/**
 * Calendario eventi: prossimi (dal più vicino) e ultimi conclusi.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$zw_type = get_post_type_object( ZW_EVENT_TYPE );
get_template_part( 'template-parts/page-hero', null, array(
	'kicker' => 'Calendario',
	'title'  => 'Eventi di wrestling',
	'text'   => $zw_type && $zw_type->description ? esc_html( $zw_type->description ) : 'Tutti i prossimi pay-per-view e show speciali, con data, sede e notizie collegate.',
) );

$zw_upcoming = zw_upcoming_events( 60 );
$zw_past     = array_values( array_filter( array_map( 'zw_event', zw_posts( array(
	'post_type'      => ZW_EVENT_TYPE,
	'posts_per_page' => 12,
	'meta_key'       => '_zw_event_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'meta_query'     => array( array( 'key' => '_zw_event_date', 'value' => zw_today(), 'compare' => '<', 'type' => 'CHAR' ) ),
) ) ) ) );
?>
<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 py-10">
	<?php if ( $zw_upcoming ) : ?>
		<?php zw_section_title( 'Prossimi eventi', array( 'size' => 'text-2xl sm:text-3xl' ) ); ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
			<?php foreach ( $zw_upcoming as $zw_event ) : get_template_part( 'template-parts/event-banner', null, array( 'event' => $zw_event ) ); endforeach; ?>
		</div>
	<?php else : ?>
		<p class="bg-white rounded-[22px] nf-shadow-light p-8 text-center text-zw-muted">Al momento non ci sono eventi in calendario.</p>
	<?php endif; ?>

	<?php if ( $zw_past ) : ?>
		<div class="mt-14">
			<?php zw_section_title( 'Eventi conclusi', array( 'size' => 'text-2xl sm:text-3xl' ) ); ?>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
				<?php foreach ( $zw_past as $zw_event ) : get_template_part( 'template-parts/event-banner', null, array( 'event' => $zw_event ) ); endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();
