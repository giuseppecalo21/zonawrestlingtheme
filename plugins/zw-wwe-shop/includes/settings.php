<?php
/**
 * Impostazioni (opzione zw_shop_settings) con i valori predefiniti.
 */

defined( 'ABSPATH' ) || exit;

/** Cataloghi della campagna WWE Shop su Impact (ID verificati il 27/11/2026). */
function zw_shop_catalogs() {
	return array(
		'10418' => 'Spagna (EUR, link a EuroShop in spagnolo)',
		'10417' => 'Francia (EUR, link a EuroShop in francese)',
		'10419' => 'Germania (EUR, link a EuroShop in tedesco)',
		'10416' => 'Regno Unito (GBP, nomi e link in inglese)',
		'10403' => 'Stati Uniti (USD, nomi e link in inglese)',
		'10420' => 'Australia (AUD)',
	);
}

function zw_shop_defaults() {
	return array(
		'enabled'       => 1,
		'account_sid'   => '',
		'auth_token'    => '',
		'offer_catalog' => '10418', // prezzi in euro, link e foto
		'name_catalog'  => '10416', // nomi in inglese
		'landing'       => 'catalog', // catalog | en_eur (sperimentale)
		'count'         => 4,
		'paragraph'     => 0, // 0 = a metà articolo, N = dopo il paragrafo N
		'aliases'       => "undertaker = The Undertaker\nStone Cold = \"Stone Cold\" Steve Austin\nSteve Austin = \"Stone Cold\" Steve Austin\nMonday Night RAW = Raw\nFriday Night Smackdown = SmackDown\nSmackdown Live = SmackDown\nFinn Balor = Finn Bálor\nBloodline = The Bloodline",
		'ignored'       => "WWE Merchandise",
		'generic'       => "Raw\nSmackDown\nNXT\nWrestleMania\nRoyal Rumble\nSummerSlam\nSurvivor Series\nMoney in the Bank\nElimination Chamber\nNight of Champions\nSaturday Night's Main Event\nCrown Jewel\nBacklash\nClash in Paris\nWrestlepalooza\nAAA",
		'title_one'     => 'Il merchandise ufficiale di %s',
		'title_many'    => 'Il merchandise ufficiale dei protagonisti',
		'title_generic' => 'Il merchandise ufficiale WWE',
		'disclosure'    => 'Link di affiliazione: acquistando da qui sostieni Zona Wrestling, senza costi aggiuntivi. I prezzi sono aggiornati una volta al giorno e possono variare.',
	);
}

function zw_shop_settings() {
	static $s = null;
	if ( null === $s ) {
		$s = wp_parse_args( (array) get_option( 'zw_shop_settings', array() ), zw_shop_defaults() );
	}
	return $s;
}

function zw_shop_setting( $key ) {
	$s = zw_shop_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/** Righe di una textarea, senza vuote. */
function zw_shop_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/** Corrispondenze "tag = wrestler del catalogo" come chiave tag => chiave wrestler. */
function zw_shop_aliases() {
	$map = array();
	foreach ( zw_shop_lines( zw_shop_setting( 'aliases' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '=', $line, 2 ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
			$map[ zw_shop_key( $parts[0] ) ] = zw_shop_key( $parts[1] );
		}
	}
	return $map;
}

/** Insieme di chiavi da una textarea (ignorati, generici). */
function zw_shop_key_set( $setting ) {
	$set = array();
	foreach ( zw_shop_lines( zw_shop_setting( $setting ) ) as $line ) {
		$set[ zw_shop_key( $line ) ] = true;
	}
	return $set;
}
