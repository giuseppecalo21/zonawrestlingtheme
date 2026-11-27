<?php
/**
 * Aggiornamento del catalogo.
 *
 * Ogni notte (WP-Cron) il plugin scarica da Impact due cataloghi della campagna WWE Shop:
 *  1. "offerte": catalogo in euro (prezzo, link affiliato, foto, wrestler);
 *  2. "nomi": catalogo britannico, da cui prende il nome inglese dello stesso prodotto.
 * Il lavoro è diviso in passi (qualche pagina da 1000 prodotti per volta) per non
 * superare i limiti di tempo dell'hosting; alla fine cancella i prodotti spariti.
 *
 * In alternativa i prodotti si possono caricare dall'esterno con
 * POST /wp-json/zw-shop/v1/import (solo amministratori): lo usa il sito di prova.
 */

defined( 'ABSPATH' ) || exit;

const ZW_SHOP_API = 'https://api.impact.com';

function zw_shop_schedule() {
	if ( ! wp_next_scheduled( 'zw_shop_daily_sync' ) ) {
		// Alle 4:17 ora italiana, lontano dai picchi di traffico.
		$next = new DateTime( 'tomorrow 04:17', wp_timezone() );
		wp_schedule_event( $next->getTimestamp(), 'daily', 'zw_shop_daily_sync' );
	}
}

add_action( 'zw_shop_daily_sync', 'zw_shop_sync_start' );
add_action( 'zw_shop_sync_step', 'zw_shop_sync_step' );

function zw_shop_has_credentials() {
	return '' !== trim( (string) zw_shop_setting( 'account_sid' ) ) && '' !== trim( (string) zw_shop_setting( 'auth_token' ) );
}

/** Avvia un nuovo aggiornamento completo. */
function zw_shop_sync_start() {
	if ( ! zw_shop_has_credentials() ) {
		zw_shop_log( 'Credenziali Impact mancanti: aggiornamento saltato.' );
		return false;
	}
	$sid = rawurlencode( trim( zw_shop_setting( 'account_sid' ) ) );
	$cat = rawurlencode( zw_shop_setting( 'offer_catalog' ) );
	update_option( 'zw_shop_sync', array(
		'run'     => gmdate( 'YmdHis' ),
		'phase'   => 'offers',
		'next'    => "/Mediapartners/{$sid}/Catalogs/{$cat}/Items?PageSize=1000",
		'count'   => 0,
		'names'   => 0,
		'started' => time(),
	), false );
	wp_clear_scheduled_hook( 'zw_shop_sync_step' );
	wp_schedule_single_event( time(), 'zw_shop_sync_step' );
	return true;
}

/** Un passo dell'aggiornamento: al massimo 25 secondi, poi si riprogramma. */
function zw_shop_sync_step() {
	$state = get_option( 'zw_shop_sync' );
	if ( ! is_array( $state ) || empty( $state['phase'] ) || 'done' === $state['phase'] ) {
		return;
	}
	$deadline = time() + 25;
	while ( time() < $deadline && 'done' !== $state['phase'] ) {
		$data = zw_shop_api_get( $state['next'] );
		if ( is_wp_error( $data ) ) {
			$state['errors'] = isset( $state['errors'] ) ? $state['errors'] + 1 : 1;
			zw_shop_log( 'Errore Impact: ' . $data->get_error_message() );
			if ( $state['errors'] >= 5 ) {
				$state['phase'] = 'done';
				$state['failed'] = true;
				zw_shop_log( 'Aggiornamento interrotto dopo 5 errori: restano i prodotti della volta precedente.' );
			}
			break;
		}
		$items = isset( $data['Items'] ) ? $data['Items'] : array();
		if ( 'offers' === $state['phase'] ) {
			$state['count'] += zw_shop_store_offers( $items, $state['run'] );
		} else {
			$state['names'] += zw_shop_store_names( $items );
		}
		$next = isset( $data['@nextpageuri'] ) ? (string) $data['@nextpageuri'] : '';
		if ( '' !== $next ) {
			$state['next'] = $next;
		} elseif ( 'offers' === $state['phase'] ) {
			$sid            = rawurlencode( trim( zw_shop_setting( 'account_sid' ) ) );
			$cat            = rawurlencode( zw_shop_setting( 'name_catalog' ) );
			$state['phase'] = 'names';
			$state['next']  = "/Mediapartners/{$sid}/Catalogs/{$cat}/Items?PageSize=1000";
		} else {
			zw_shop_finish( $state['run'], $state['count'], $state['names'] );
			$state['phase'] = 'done';
		}
	}
	update_option( 'zw_shop_sync', $state, false );
	if ( 'done' !== $state['phase'] ) {
		wp_schedule_single_event( time() + 20, 'zw_shop_sync_step' );
	}
}

/** GET su Impact con autenticazione Basic (Account SID + token). */
function zw_shop_api_get( $path ) {
	$url = 0 === strpos( $path, 'http' ) ? $path : ZW_SHOP_API . $path;
	if ( 0 !== strpos( $url, ZW_SHOP_API . '/' ) ) {
		return new WP_Error( 'zw_shop_url', 'Indirizzo non previsto: ' . $url );
	}
	$res = wp_remote_get( $url, array(
		'timeout' => 30,
		'headers' => array(
			'Accept'        => 'application/json',
			'Authorization' => 'Basic ' . base64_encode( trim( zw_shop_setting( 'account_sid' ) ) . ':' . trim( zw_shop_setting( 'auth_token' ) ) ),
		),
	) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	if ( 200 !== $code ) {
		return new WP_Error( 'zw_shop_http', 'HTTP ' . $code . ': ' . substr( wp_remote_retrieve_body( $res ), 0, 200 ) );
	}
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	return is_array( $data ) ? $data : new WP_Error( 'zw_shop_json', 'Risposta non valida' );
}

/** Salva i prodotti del catalogo in euro. Restituisce quanti ne ha salvati. */
function zw_shop_store_offers( array $items, $run ) {
	global $wpdb;
	$table = zw_shop_table();
	$rows  = array();
	foreach ( $items as $it ) {
		$id = isset( $it['CatalogItemId'] ) ? substr( sanitize_text_field( $it['CatalogItemId'] ), 0, 32 ) : '';
		// Solo merchandise WWE (il catalogo contiene anche collaborazioni con squadre di calcio e NFL).
		// Niente prodotti per bambini (campo AgeGroup "Kids"): non sono il pubblico del sito.
		if ( '' === $id || ( isset( $it['Category'] ) && 'WWE' !== $it['Category'] ) || empty( $it['Url'] ) || ( isset( $it['AgeGroup'] ) && 'Kids' === $it['AgeGroup'] ) ) {
			continue;
		}
		$wrestler = sanitize_text_field( isset( $it['SubCategory'] ) ? $it['SubCategory'] : '' );
		$rows[]   = $wpdb->prepare(
			'(%s,%s,%s,%s,%s,%f,%f,%s,%s,%s,%s,%d,%s)',
			$id,
			$wrestler,
			zw_shop_key( $wrestler ),
			sanitize_text_field( isset( $it['Name'] ) ? $it['Name'] : '' ),
			'',
			(float) ( isset( $it['CurrentPrice'] ) ? $it['CurrentPrice'] : 0 ),
			(float) ( isset( $it['OriginalPrice'] ) ? $it['OriginalPrice'] : 0 ),
			substr( sanitize_text_field( isset( $it['Currency'] ) ? $it['Currency'] : 'EUR' ), 0, 3 ),
			esc_url_raw( $it['Url'] ),
			'',
			esc_url_raw( isset( $it['ImageUrl'] ) ? $it['ImageUrl'] : '' ),
			( isset( $it['StockAvailability'] ) && 'InStock' !== $it['StockAvailability'] ) ? 0 : 1,
			$run
		);
	}
	foreach ( array_chunk( $rows, 200 ) as $chunk ) {
		// Il nome inglese resta quello della volta precedente finché non arriva il catalogo britannico.
		$wpdb->query( "INSERT INTO {$table} (item_id,wrestler,wrestler_key,name_local,name_en,price,original_price,currency,url,url_en,image,in_stock,run_id) VALUES "
			. implode( ',', $chunk )
			. ' ON DUPLICATE KEY UPDATE wrestler=VALUES(wrestler), wrestler_key=VALUES(wrestler_key), name_local=VALUES(name_local), price=VALUES(price), original_price=VALUES(original_price), currency=VALUES(currency), url=VALUES(url), image=VALUES(image), in_stock=VALUES(in_stock), run_id=VALUES(run_id)' ); // phpcs:ignore
	}
	return count( $rows );
}

/** Aggiunge nome inglese (e link britannico) ai prodotti già salvati. */
function zw_shop_store_names( array $items ) {
	global $wpdb;
	$table = zw_shop_table();
	$n     = 0;
	foreach ( array_chunk( $items, 200 ) as $chunk ) {
		$names = array();
		$urls  = array();
		$types = array();
		$ids   = array();
		foreach ( $chunk as $it ) {
			$id = isset( $it['CatalogItemId'] ) ? substr( sanitize_text_field( $it['CatalogItemId'] ), 0, 32 ) : '';
			if ( '' === $id || empty( $it['Name'] ) ) {
				continue;
			}
			// Prodotti per bambini classificati male: si riconoscono dal nome inglese.
			if ( zw_shop_is_kids_name( $it['Name'] ) ) {
				$wpdb->delete( $table, array( 'item_id' => $id ) );
				continue;
			}
			$names[] = $wpdb->prepare( 'WHEN %s THEN %s', $id, sanitize_text_field( $it['Name'] ) );
			$types[] = $wpdb->prepare( 'WHEN %s THEN %s', $id, zw_shop_product_type( $it['Name'], isset( $it['Url'] ) ? $it['Url'] : '' ) );
			$urls[]  = $wpdb->prepare( 'WHEN %s THEN %s', $id, esc_url_raw( isset( $it['Url'] ) ? $it['Url'] : '' ) );
			$ids[]   = $wpdb->prepare( '%s', $id );
		}
		if ( $ids ) {
			$n += (int) $wpdb->query( "UPDATE {$table} SET name_en = CASE item_id " . implode( ' ', $names ) . ' ELSE name_en END, url_en = CASE item_id ' . implode( ' ', $urls ) . ' ELSE url_en END, product_type = CASE item_id ' . implode( ' ', $types ) . ' ELSE product_type END WHERE item_id IN (' . implode( ',', $ids ) . ')' ); // phpcs:ignore
		}
	}
	return $n;
}

/** Nome inglese di un prodotto per bambini ("Baby" escluso: c'è la linea Bape "Baby Milo" per adulti). */
function zw_shop_is_kids_name( $name ) {
	return (bool) preg_match( '/\b(youth|kids?|toddlers?|infants?|newborn|preschool)\b/i', (string) $name );
}

/** Fine aggiornamento: via i prodotti non più in catalogo. */
function zw_shop_finish( $run, $count, $names ) {
	global $wpdb;
	$table   = zw_shop_table();
	$removed = 0;
	if ( $count > 0 ) {
		$removed = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE run_id <> %s", $run ) ); // phpcs:ignore
	}
	update_option( 'zw_shop_last_sync', array(
		'time'    => time(),
		'count'   => (int) $count,
		'names'   => (int) $names,
		'removed' => $removed,
	), false );
	zw_shop_log( sprintf( 'Aggiornamento completato: %d prodotti, %d nomi inglesi, %d rimossi.', $count, $names, $removed ) );
}

/** Ultimi 20 messaggi, per la pagina impostazioni. */
function zw_shop_log( $msg ) {
	$log   = (array) get_option( 'zw_shop_log', array() );
	$log[] = wp_date( 'd/m/Y H:i' ) . ' – ' . $msg;
	update_option( 'zw_shop_log', array_slice( $log, -20 ), false );
}

// Caricamento dall'esterno (sito di prova): stesso formato degli Items di Impact.
add_action( 'rest_api_init', function () {
	register_rest_route( 'zw-shop/v1', '/import', array(
		'methods'             => 'POST',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'callback'            => function ( WP_REST_Request $req ) {
			$phase = $req->get_param( 'phase' );
			$run   = preg_replace( '/[^0-9A-Za-z]/', '', (string) $req->get_param( 'run' ) );
			$items = (array) $req->get_param( 'items' );
			if ( '' === $run ) {
				return new WP_Error( 'zw_shop_run', 'Parametro run mancante', array( 'status' => 400 ) );
			}
			if ( 'offers' === $phase ) {
				return array( 'stored' => zw_shop_store_offers( $items, $run ) );
			}
			if ( 'names' === $phase ) {
				return array( 'updated' => zw_shop_store_names( $items ) );
			}
			if ( 'finish' === $phase ) {
				zw_shop_finish( $run, (int) $req->get_param( 'count' ), (int) $req->get_param( 'names' ) );
				return array( 'stats' => zw_shop_stats() );
			}
			return new WP_Error( 'zw_shop_phase', 'Fase sconosciuta', array( 'status' => 400 ) );
		},
	) );
} );
