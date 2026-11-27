<?php
/**
 * Scelta dei prodotti per un articolo.
 *
 * 1. Dai tag dell'articolo (più le corrispondenze manuali) si ricavano i wrestler
 *    presenti nel catalogo. Prima quelli nominati nel titolo, poi in ordine di
 *    comparsa nel testo. Show ed eventi (Raw, WrestleMania...) servono solo se
 *    non c'è nessun wrestler.
 * 2. I prodotti si pescano a turno dai primi wrestler, a caso ma in modo stabile:
 *    la scelta cambia una volta al giorno per articolo, così la pagina in cache
 *    (Varnish, WP Rocket) resta coerente.
 */

defined( 'ABSPATH' ) || exit;

/** Wrestler (chiavi) del catalogo collegati a un articolo, in ordine di importanza. */
function zw_shop_post_wrestlers( $post ) {
	global $wpdb;
	$post = get_post( $post );
	if ( ! $post ) {
		return array( 'keys' => array(), 'generic' => false );
	}

	// Scelta manuale nell'articolo: "Cody Rhodes, Rhea Ripley".
	$forced = trim( (string) get_post_meta( $post->ID, '_zw_shop_wrestlers', true ) );
	$names  = $forced ? array_map( 'trim', explode( ',', $forced ) ) : wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
	if ( ! $names ) {
		return array( 'keys' => array(), 'generic' => false );
	}

	$aliases = zw_shop_aliases();
	$ignored = zw_shop_key_set( 'ignored' );
	$generic = zw_shop_key_set( 'generic' );

	$wanted = array(); // chiave catalogo => nome come appare nell'articolo
	foreach ( $names as $name ) {
		$key = zw_shop_key( $name );
		$key = isset( $aliases[ $key ] ) ? $aliases[ $key ] : $key;
		if ( '' !== $key && ! isset( $ignored[ $key ] ) ) {
			$wanted[ $key ] = $name;
		}
		// Show ed eventi anche dentro tag più lunghi: "WWE Survivor Series 2026" → Survivor Series.
		foreach ( $generic as $g => $unused ) {
			if ( $g !== $key && false !== strpos( " {$key} ", " {$g} " ) && ! isset( $wanted[ $g ] ) ) {
				$wanted[ $g ] = $name;
			}
		}
	}
	if ( ! $wanted ) {
		return array( 'keys' => array(), 'generic' => false );
	}

	$table = zw_shop_table();
	$in    = implode( ',', array_map( function ( $k ) use ( $wpdb ) {
		return $wpdb->prepare( '%s', $k );
	}, array_keys( $wanted ) ) );
	$found = $wpdb->get_results( "SELECT wrestler_key, MAX(wrestler) AS wrestler FROM {$table} WHERE wrestler_key IN ({$in}) AND " . zw_shop_filter_sql() . " GROUP BY wrestler_key", OBJECT_K ); // phpcs:ignore
	if ( ! $found ) {
		return array( 'keys' => array(), 'generic' => false );
	}

	// Ordine: nel titolo prima, poi per prima comparsa nel testo.
	$title = ' ' . zw_shop_key( get_the_title( $post ) ) . ' ';
	$body  = ' ' . zw_shop_key( wp_strip_all_tags( $post->post_content ) ) . ' ';
	$score = array();
	foreach ( $found as $key => $row ) {
		$tag   = zw_shop_key( $wanted[ $key ] );
		$tpos  = false !== strpos( $title, " {$tag} " ) || false !== strpos( $title, " {$key} " );
		$bpos  = strpos( $body, " {$tag} " );
		$bpos2 = strpos( $body, " {$key} " );
		$bpos  = false === $bpos ? $bpos2 : ( false === $bpos2 ? $bpos : min( $bpos, $bpos2 ) );
		$score[ $key ] = ( $tpos ? 0 : 1 ) * 10000000 + ( false === $bpos ? 9999999 : $bpos );
	}
	asort( $score );

	$people = array_values( array_filter( array_keys( $score ), function ( $k ) use ( $generic ) {
		return ! isset( $generic[ $k ] );
	} ) );
	if ( $people ) {
		return array( 'keys' => $people, 'labels' => wp_list_pluck( $found, 'wrestler' ), 'generic' => false );
	}
	return array( 'keys' => array_keys( $score ), 'labels' => wp_list_pluck( $found, 'wrestler' ), 'generic' => true );
}

/**
 * Prodotti da mostrare: array di oggetti (item_id, wrestler, name_en, price, ...).
 * Restituisce anche i wrestler effettivamente usati, per il titolo del riquadro.
 */
function zw_shop_pick_products( $post, $count = null ) {
	global $wpdb;
	$post  = get_post( $post );
	$count = $count ? (int) $count : max( 1, min( 8, (int) zw_shop_setting( 'count' ) ) );
	$match = zw_shop_post_wrestlers( $post );
	if ( ! $match['keys'] ) {
		return array( 'products' => array(), 'wrestlers' => array(), 'generic' => false );
	}

	$keys  = array_slice( $match['keys'], 0, $count ); // non più wrestler che prodotti
	$seed  = abs( crc32( $post->ID . '|' . wp_date( 'Ymd' ) ) ) % 2147483647;
	$table = zw_shop_table();
	$pools  = array();
	$filter = zw_shop_filter_sql();
	foreach ( $keys as $i => $key ) {
		// I filtri sono già preparati (e possono contenere % dei LIKE): si aggiungono dopo prepare().
		$pools[ $key ] = $wpdb->get_results(
			$wpdb->prepare( "SELECT item_id, wrestler, name_en, price, original_price, currency, url, url_en, image FROM {$table} WHERE wrestler_key = %s AND ", $key ) // phpcs:ignore
			. $filter
			. $wpdb->prepare( ' ORDER BY RAND(%d) LIMIT %d', $seed + $i, $count * 3 )
		);
	}

	// A turno dai wrestler, saltando i doppioni (stessa maglia uomo/donna/bambino).
	$picked = array();
	$seen   = array();
	$used   = array();
	for ( $round = 0; count( $picked ) < $count && $round < $count * 3; $round++ ) {
		$progress = false;
		foreach ( $pools as $key => &$pool ) {
			while ( $pool && count( $picked ) < $count ) {
				$p   = array_shift( $pool );
				$sig = zw_shop_design_key( $p->name_en );
				if ( isset( $seen[ $sig ] ) ) {
					continue;
				}
				$seen[ $sig ]  = true;
				$picked[]      = $p;
				$used[ $key ]  = $p->wrestler;
				$progress      = true;
				break;
			}
		}
		unset( $pool );
		if ( ! $progress ) {
			break;
		}
	}

	return array( 'products' => $picked, 'wrestlers' => array_values( $used ), 'generic' => $match['generic'] );
}

/** Firma del modello: il nome senza indicazioni di taglia e destinatario. */
function zw_shop_design_key( $name ) {
	$k = zw_shop_key( $name );
	$k = preg_replace( '/\b(men s|mens|women s|womens|youth|kids|toddler|infant|unisex|ladies|adult|big tall|plus size|black|white|red|blue|navy|gray|grey|heather|heathered|charcoal|green|gold|pink|purple|yellow|orange|royal|cream|natural|silver|maroon|olive|tan|brown)\b/', ' ', $k );
	return trim( preg_replace( '/\s+/', ' ', $k ) );
}
