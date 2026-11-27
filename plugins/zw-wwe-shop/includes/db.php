<?php
/**
 * Tabella dei prodotti: una riga per prodotto, presa dal catalogo in euro
 * (prezzo, link, foto) e completata con il nome inglese del catalogo britannico.
 */

defined( 'ABSPATH' ) || exit;

function zw_shop_table() {
	global $wpdb;
	return $wpdb->prefix . 'zw_shop_products';
}

function zw_shop_install_db() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = zw_shop_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$table} (
		item_id varchar(32) NOT NULL,
		wrestler varchar(191) NOT NULL DEFAULT '',
		wrestler_key varchar(191) NOT NULL DEFAULT '',
		name_local text NOT NULL,
		name_en text NOT NULL,
		price decimal(10,2) NOT NULL DEFAULT 0,
		original_price decimal(10,2) NOT NULL DEFAULT 0,
		currency char(3) NOT NULL DEFAULT 'EUR',
		url text NOT NULL,
		url_en text NOT NULL,
		image text NOT NULL,
		product_type varchar(20) NOT NULL DEFAULT '',
		in_stock tinyint(1) NOT NULL DEFAULT 1,
		run_id varchar(20) NOT NULL DEFAULT '',
		PRIMARY KEY  (item_id),
		KEY wrestler_key (wrestler_key),
		KEY run_id (run_id),
		KEY product_type (product_type)
	) {$charset};" );
	update_option( 'zw_shop_db_version', ZW_SHOP_DB_VERSION, false );
}

/**
 * Chiave di confronto per nomi di wrestler e tag: minuscole, senza accenti,
 * virgolette e punteggiatura. "\"Stone Cold\" Steve Austin" → "stone cold steve austin".
 */
function zw_shop_key( $name ) {
	$name = html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' );
	$name = remove_accents( $name );
	$name = strtolower( $name );
	$name = preg_replace( '/[^a-z0-9]+/', ' ', $name );
	return trim( $name );
}

/** Numero di prodotti e di wrestler in tabella. */
function zw_shop_stats() {
	global $wpdb;
	$table = zw_shop_table();
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return array( 'products' => 0, 'with_name' => 0, 'wrestlers' => 0 );
	}
	return array(
		'products'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
		'with_name' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE name_en <> ''" ),
		'wrestlers' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT wrestler_key) FROM {$table}" ),
	);
}

/**
 * Condizioni SQL dei filtri scelti nelle impostazioni (tipi, saldi, prezzo, parole escluse).
 * Valgono sia per scegliere i wrestler sia per pescare i prodotti.
 */
function zw_shop_filter_sql() {
	global $wpdb;
	$where = array( "name_en <> ''", 'in_stock = 1', 'price > 0' );
	$types = array_intersect( (array) zw_shop_setting( 'types' ), array_keys( zw_shop_types() ) );
	if ( ! $types ) {
		return '1 = 0';
	}
	$where[] = 'product_type IN (' . implode( ',', array_map( function ( $t ) use ( $wpdb ) {
		return $wpdb->prepare( '%s', $t );
	}, $types ) ) . ')';
	if ( zw_shop_setting( 'only_sale' ) ) {
		$where[] = 'original_price > price + 0.009';
	}
	if ( (float) zw_shop_setting( 'min_price' ) > 0 ) {
		$where[] = $wpdb->prepare( 'price >= %f', (float) zw_shop_setting( 'min_price' ) );
	}
	if ( (float) zw_shop_setting( 'max_price' ) > 0 ) {
		$where[] = $wpdb->prepare( 'price <= %f', (float) zw_shop_setting( 'max_price' ) );
	}
	foreach ( zw_shop_lines( zw_shop_setting( 'exclude_words' ) ) as $word ) {
		$where[] = $wpdb->prepare( 'name_en NOT LIKE %s', '%' . $wpdb->esc_like( $word ) . '%' );
	}
	return implode( ' AND ', $where );
}

/** Prodotti per tipo (tutti, e quanti passano i filtri), per la pagina impostazioni. */
function zw_shop_type_counts() {
	global $wpdb;
	$table = zw_shop_table();
	return $wpdb->get_results( "SELECT product_type, COUNT(*) AS n FROM {$table} WHERE name_en <> '' AND in_stock = 1 GROUP BY product_type", OBJECT_K ) ?: array(); // phpcs:ignore
}

function zw_shop_filtered_count() {
	global $wpdb;
	$table = zw_shop_table();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE " . zw_shop_filter_sql() ); // phpcs:ignore
}

/** Elenco wrestler => numero di prodotti (per la pagina impostazioni). */
function zw_shop_wrestler_list() {
	global $wpdb;
	$table = zw_shop_table();
	return $wpdb->get_results( "SELECT wrestler, COUNT(*) AS n FROM {$table} WHERE name_en <> '' GROUP BY wrestler ORDER BY n DESC", ARRAY_A ) ?: array();
}
