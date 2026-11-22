<?php
/**
 * Eventi: integrazione con il plugin ZW Events Calendar.
 *
 * Il plugin registra il tipo di contenuto `zw_event` (archivio /eventi-wrestling/),
 * la tassonomia `zw_federation` e questi campi:
 *   _zw_event_date          data in formato AAAA-MM-GG
 *   _zw_event_venue         arena
 *   _zw_event_city          città / paese
 *   _zw_event_related_tags  slug dei tag collegati, separati da virgola
 *
 * Il tema aggiunge un solo campo suo, facoltativo:
 *   _zw_event_start_it      inizio in Italia, "AAAA-MM-GG HH:MM" (ora italiana)
 * La data del plugin è quella locale dell'evento: un PLE americano del 28 in Italia
 * comincia spesso nella notte del 29. Con questo campo il countdown arriva al secondo.
 *
 * Per il resto il tema legge soltanto: se il plugin non è attivo, i blocchi non compaiono.
 */

defined( 'ABSPATH' ) || exit;

const ZW_EVENT_TYPE = 'zw_event';
const ZW_EVENT_START = '_zw_event_start_it';

function zw_events_active() {
	return post_type_exists( ZW_EVENT_TYPE );
}

function zw_today() {
	return wp_date( 'Y-m-d' );
}

/** Dati di un evento pronti per i template. */
function zw_event( $event ) {
	$event = get_post( $event );
	if ( ! $event || ZW_EVENT_TYPE !== $event->post_type ) {
		return null;
	}
	$date  = (string) get_post_meta( $event->ID, '_zw_event_date', true );
	$venue = trim( (string) get_post_meta( $event->ID, '_zw_event_venue', true ) );
	$city  = trim( (string) get_post_meta( $event->ID, '_zw_event_city', true ) );
	$terms = get_the_terms( $event, 'zw_federation' );
	$ts    = preg_match( '/^\d{4}-\d{2}-\d{2}/', $date ) ? strtotime( substr( $date, 0, 10 ) . ' 12:00:00 UTC' ) : 0;
	$start = zw_event_start( $event->ID );

	return array(
		'id'       => $event->ID,
		'title'    => get_the_title( $event ),
		'url'      => get_permalink( $event ),
		'date'     => $ts ? substr( $date, 0, 10 ) : '',
		'ts'       => $ts,
		'day'      => $ts ? gmdate( 'd', $ts ) : '',
		'month'    => $ts ? strtoupper( mb_substr( date_i18n( 'M', $ts, true ), 0, 3 ) ) : '',
		'date_num' => $ts ? gmdate( 'd.m.Y', $ts ) : '',
		'date_txt' => $ts ? date_i18n( 'j F Y', $ts, true ) : '',
		'venue'    => $venue,
		'city'     => $city,
		'place'    => implode( ' • ', array_filter( array( $venue, $city ) ) ),
		'fed'      => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '',
		'thumb'    => (int) get_post_thumbnail_id( $event ),
		// Inizio in Italia (se indicato): ISO 8601 con fuso, testo e timestamp.
		'start'     => $start ? $start->format( 'c' ) : '',
		'start_ts'  => $start ? $start->getTimestamp() : 0,
		'start_txt' => $start ? zw_event_start_text( $start ) : '',
	);
}

/** Inizio in Italia come DateTimeImmutable (fuso Europe/Rome), oppure null. */
function zw_event_start( $event_id ) {
	$value = trim( (string) get_post_meta( $event_id, ZW_EVENT_START, true ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}$/', $value ) ) {
		return null;
	}
	$start = date_create_immutable( str_replace( 'T', ' ', $value ), new DateTimeZone( 'Europe/Rome' ) );
	return $start ? $start : null;
}

/** "domenica 29 novembre, ore 00:00" (ora italiana). */
function zw_event_start_text( DateTimeImmutable $start ) {
	return wp_date( 'l j F, \o\r\e H:i', $start->getTimestamp(), new DateTimeZone( 'Europe/Rome' ) );
}

/**
 * Stato dell'evento rispetto a adesso: 'upcoming', 'live' (cominciato da meno di
 * 5 ore, solo con l'orario), 'today', 'past' oppure '' se manca la data.
 */
function zw_event_status( $event_data ) {
	if ( ! empty( $event_data['start_ts'] ) ) {
		$diff = $event_data['start_ts'] - time();
		if ( $diff > 0 ) {
			return 'upcoming';
		}
		return $diff > -5 * HOUR_IN_SECONDS ? 'live' : 'past';
	}
	$days = zw_event_days( $event_data );
	if ( null === $days ) {
		return '';
	}
	return $days > 0 ? 'upcoming' : ( 0 === $days ? 'today' : 'past' );
}

/** Giorni che mancano all'evento (negativo se è passato). */
function zw_event_days( $event_data ) {
	if ( empty( $event_data['date'] ) ) {
		return null;
	}
	return (int) round( ( strtotime( $event_data['date'] . ' 12:00:00 UTC' ) - strtotime( zw_today() . ' 12:00:00 UTC' ) ) / DAY_IN_SECONDS );
}

/** "Oggi", "Domani", "Tra 12 giorni", oppure stringa vuota per gli eventi passati. */
function zw_event_countdown( $event_data, $prefix = 'Tra' ) {
	$days = zw_event_days( $event_data );
	if ( null === $days || $days < 0 ) {
		return '';
	}
	if ( 0 === $days ) {
		return 'Oggi';
	}
	if ( 1 === $days ) {
		return 'Domani';
	}
	return sprintf( '%s %d giorni', $prefix, $days );
}

/** Prossimi eventi, dal più vicino. */
function zw_upcoming_events( $count = 4 ) {
	if ( ! zw_events_active() ) {
		return array();
	}
	$posts = zw_posts( array(
		'post_type'      => ZW_EVENT_TYPE,
		'posts_per_page' => $count,
		'meta_key'       => '_zw_event_date',
		'orderby'        => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
		'meta_query'     => array(
			array( 'key' => '_zw_event_date', 'value' => zw_today(), 'compare' => '>=', 'type' => 'CHAR' ),
		),
	) );
	return array_values( array_filter( array_map( 'zw_event', $posts ) ) );
}

/** Mappa [ slug del tag => ID degli eventi collegati ], in cache. */
function zw_event_tag_map() {
	if ( ! zw_events_active() ) {
		return array();
	}
	$map = get_transient( 'zw_event_tag_map' );
	if ( is_array( $map ) ) {
		return $map;
	}
	global $wpdb;
	$map  = array();
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.ID, m.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_zw_event_related_tags' WHERE p.post_type = %s AND p.post_status = 'publish' AND m.meta_value <> ''",
		ZW_EVENT_TYPE
	) );
	foreach ( (array) $rows as $row ) {
		foreach ( zw_event_parse_tags( $row->meta_value ) as $slug ) {
			$map[ $slug ][] = (int) $row->ID;
		}
	}
	set_transient( 'zw_event_tag_map', $map, HOUR_IN_SECONDS );
	return $map;
}

function zw_event_parse_tags( $value ) {
	return array_values( array_unique( array_filter( array_map( 'sanitize_title', preg_split( '/[\s,;]+/', (string) $value ) ) ) ) );
}

/**
 * Evento da mostrare in fondo a un articolo.
 *
 * Tra gli eventi collegati ai tag dell'articolo vince quello con la data più vicina
 * a quella di pubblicazione dell'articolo (per un articolo appena uscito: il più
 * vicino a oggi, passato o futuro). A parità di distanza la scelta è casuale.
 */
function zw_event_for_post( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! zw_events_active() ) {
		return null;
	}
	$tags = get_the_tags( $post );
	if ( ! $tags || is_wp_error( $tags ) ) {
		return null;
	}
	$map = zw_event_tag_map();
	$ids = array();
	foreach ( $tags as $tag ) {
		if ( isset( $map[ $tag->slug ] ) ) {
			$ids = array_merge( $ids, $map[ $tag->slug ] );
		}
	}
	$ids = array_unique( $ids );
	if ( ! $ids ) {
		return null;
	}

	$reference = strtotime( get_the_date( 'Y-m-d', $post ) . ' 12:00:00 UTC' );
	$best      = array();
	$best_dist = null;
	foreach ( $ids as $id ) {
		$event = zw_event( $id );
		if ( ! $event ) {
			continue;
		}
		// Un evento senza data resta in coda, ma è meglio di niente.
		$dist = $event['ts'] ? abs( $event['ts'] - $reference ) : PHP_INT_MAX;
		if ( null === $best_dist || $dist < $best_dist ) {
			$best_dist = $dist;
			$best      = array( $event );
		} elseif ( $dist === $best_dist ) {
			$best[] = $event;
		}
	}
	if ( ! $best ) {
		return null;
	}
	return $best[ count( $best ) > 1 ? wp_rand( 0, count( $best ) - 1 ) : 0 ];
}

/** Numero di articoli pubblicati con i tag collegati all'evento (in cache per un'ora). */
function zw_event_news_count( $event_id ) {
	$key   = 'zw_ev_count_' . (int) $event_id;
	$count = get_transient( $key );
	if ( false !== $count ) {
		return (int) $count;
	}
	$slugs = zw_event_parse_tags( get_post_meta( $event_id, '_zw_event_related_tags', true ) );
	$count = 0;
	if ( $slugs ) {
		$query = new WP_Query( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'tag_slug__in'        => $slugs,
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
		) );
		$count = (int) $query->found_posts;
	}
	set_transient( $key, $count, HOUR_IN_SECONDS );
	return $count;
}

// Nell'archivio eventi il template fa le proprie liste (prossimi / passati).
add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( ZW_EVENT_TYPE ) ) {
		$query->set( 'posts_per_page', 1 );
		$query->set( 'no_found_rows', true );
	}
} );

/* ---------------------------------------------------------------------------
 * Campo "Inizio in Italia" nella schermata dell'evento.
 * ------------------------------------------------------------------------- */

add_action( 'add_meta_boxes', function () {
	if ( ! zw_events_active() ) {
		return;
	}
	add_meta_box( 'zw_event_start', 'Inizio in Italia (tema)', function ( $post ) {
		$value = str_replace( ' ', 'T', (string) get_post_meta( $post->ID, ZW_EVENT_START, true ) );
		wp_nonce_field( 'zw_event_start', 'zw_event_start_nonce' );
		echo '<p><input type="datetime-local" name="zw_event_start" value="' . esc_attr( $value ) . '" style="width:100%" /></p>';
		echo '<p class="description">Data e ora <strong>italiane</strong> di inizio (per esempio la notte tra sabato e domenica per i PLE americani). Facoltativo: se lo compili, nella pagina compare il countdown con ore, minuti e secondi.</p>';
	}, ZW_EVENT_TYPE, 'side', 'high' );
} );

add_action( 'save_post_' . ZW_EVENT_TYPE, function ( $post_id ) {
	if ( ! isset( $_POST['zw_event_start_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zw_event_start_nonce'] ) ), 'zw_event_start' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$value = isset( $_POST['zw_event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['zw_event_start'] ) ) : '';
	if ( preg_match( '/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/', $value, $m ) ) {
		update_post_meta( $post_id, ZW_EVENT_START, $m[1] . ' ' . $m[2] );
	} else {
		delete_post_meta( $post_id, ZW_EVENT_START );
	}
} );

/* ---------------------------------------------------------------------------
 * Pagina dell'evento.
 * ------------------------------------------------------------------------- */

/** Slug dei tag collegati a un evento. */
function zw_event_tag_slugs( $event_id ) {
	return zw_event_parse_tags( get_post_meta( $event_id, '_zw_event_related_tags', true ) );
}

/**
 * Il tema impagina da sé scheda e notizie dell'evento: i riquadri che il plugin
 * aggiunge in fondo al testo ("Dettagli evento", "Tutte le news su questo evento")
 * vengono tolti, così non compaiono due volte.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( ZW_EVENT_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return preg_replace( '#<section[^>]*class="[^"]*\bzw-event-(?:meta|news)-box\b[^"]*"[^>]*>.*?</section>#s', '', $content );
}, PHP_INT_MAX );

// Dati strutturati dell'evento (schema.org SportsEvent).
add_action( 'wp_head', function () {
	if ( ! is_singular( ZW_EVENT_TYPE ) ) {
		return;
	}
	$event = zw_event( get_queried_object_id() );
	if ( ! $event || ! $event['date'] ) {
		return;
	}
	$data = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'SportsEvent',
		'name'                => wp_strip_all_tags( $event['title'] ),
		'url'                 => $event['url'],
		'startDate'           => $event['start'] ? $event['start'] : $event['date'],
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'sport'               => 'Professional wrestling',
	);
	$excerpt = wp_strip_all_tags( get_the_excerpt( $event['id'] ) );
	if ( $excerpt ) {
		$data['description'] = wp_trim_words( $excerpt, 50, '…' );
	}
	if ( $event['thumb'] ) {
		$image = wp_get_attachment_image_src( $event['thumb'], 'full' );
		if ( $image ) {
			$data['image'] = array( $image[0] );
		}
	}
	if ( $event['venue'] || $event['city'] ) {
		$data['location'] = array(
			'@type'   => 'Place',
			'name'    => $event['venue'] ? $event['venue'] : $event['city'],
			'address' => $event['city'] ? $event['city'] : $event['venue'],
		);
	}
	if ( $event['fed'] ) {
		$data['organizer'] = array( '@type' => 'Organization', 'name' => $event['fed'] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 21 );
