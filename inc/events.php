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
 * Il tema si limita a leggere: se il plugin non è attivo, i blocchi non compaiono.
 */

defined( 'ABSPATH' ) || exit;

const ZW_EVENT_TYPE = 'zw_event';

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
	);
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

/**
 * Pagina dell'evento: prima il riquadro "Dettagli evento", poi il testo
 * dell'evento, e solo in fondo l'elenco delle notizie collegate.
 *
 * I due riquadri li aggiunge il plugin al testo; qui ci assicuriamo soltanto
 * dell'ordine, qualunque sia la priorità con cui il plugin li inserisce.
 * Il testo originale (unico) arriva così subito dopo il titolo, e l'elenco
 * di link non lo spinge in basso.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( ZW_EVENT_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$boxes = array();
	foreach ( array( 'meta' => 'zw-event-meta-box', 'news' => 'zw-event-news-box' ) as $key => $class ) {
		$pattern = '#<section\b[^>]*\bclass=(["\'])(?:[^"\']*\s)?' . preg_quote( $class, '#' ) . '(?:\s[^"\']*)?\1[^>]*>.*?</section>#s';
		if ( preg_match( $pattern, $content, $match ) ) {
			$boxes[ $key ] = $match[0];
			$content       = str_replace( $match[0], '', $content );
		}
	}
	if ( ! $boxes ) {
		return $content;
	}
	$meta = isset( $boxes['meta'] ) ? $boxes['meta'] : '';
	$news = isset( $boxes['news'] ) ? $boxes['news'] : '';
	return $meta . trim( $content ) . $news;
}, PHP_INT_MAX );
