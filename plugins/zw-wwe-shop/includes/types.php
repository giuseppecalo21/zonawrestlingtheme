<?php
/**
 * Tipi di prodotto. Il catalogo di Impact non li indica: si ricavano dal nome
 * inglese e, se non basta, dalla sezione del negozio nell'indirizzo (/en/t-shirts/...).
 * L'ordine delle regole conta: "Autographed T-Shirt" è un autografato,
 * "Statue T-Shirt" una maglietta, "Championship Wall Sticker" un oggetto per la casa.
 */

defined( 'ABSPATH' ) || exit;

/** Tipi con l'etichetta mostrata nelle impostazioni. */
function zw_shop_types() {
	return array(
		'tshirt'    => 'Magliette e canotte',
		'hoodie'    => 'Felpe e giacche',
		'apparel'   => 'Altro abbigliamento (pantaloncini, maglie da calcio, boxer)',
		'headwear'  => 'Cappelli',
		'figure'    => 'Action figure, statue e peluche',
		'belt'      => 'Cinture replica e side plate',
		'autograph' => 'Oggetti autografati',
		'photo'     => 'Foto, poster e quadri',
		'cards'     => 'Carte collezionabili',
		'legacy'    => 'Cinture delle squadre sportive (Legacy Title, collaborazioni con club e NFL)',
		'phone'     => 'Cover per telefono e accessori tech',
		'home'      => 'Casa e gadget (tazze, adesivi, sagome, portachiavi...)',
		'other'     => 'Altro (guanti, costumi, oggetti non classificati)',
	);
}

/** Tipi mostrati di serie: niente cover, gadget, carte e cinture dei club. */
function zw_shop_default_types() {
	return array( 'tshirt', 'hoodie', 'apparel', 'headwear', 'figure', 'belt', 'autograph', 'photo' );
}

function zw_shop_product_type( $name, $url = '' ) {
	$n     = strtolower( (string) $name );
	$rules = array(
		'phone'     => '/phone case|airpods|iphone|samsung|ipad|laptop sleeve/',
		'autograph' => '/autographed|\bsigned\b/',
		'legacy'    => '/\bwwe x .*legacy|\bx (rangers|psg|paris saint)/',
		'tshirt'    => '/t-shirt|\btee\b|tank top|\btank\b|long sleeve/',
		'hoodie'    => '/hoodie|sweatshirt|crewneck|pullover|quarter-zip|full-zip|jacket|fleece|windbreaker/',
		'home'      => '/sticker|cut ?out|\bmug\b|tumbler|\bglass\b|blanket|towel|pillow|clock|\bflag\b|ornament|\bsign\b|\brug\b|\bthrow\b|bottle|coaster|socks|keychain|key ?ring|\bpin\b|magnet|lanyard|wallet|\bbag\b|backpack|\bwatch\b|bracelet|necklace|\bchain\b|calendar|turnbuckle/',
		'belt'      => '/title belt|championship belt|replica (championship )?title|replica belt|side plate|mini belt|championship title/',
		'figure'    => '/action figure|\bfigure\b|figurine|funko|\bpop!|mattel|elite collection|statue|bobblehead|plush/',
		'photo'     => '/photograph|poster|\bprint\b|canvas|collage|plaque|framed|lithograph/',
		'cards'     => '/trading card|topps|\bcards?\b/',
		'headwear'  => '/\bhat\b|\bcap\b|beanie|snapback|\bknit\b/',
	);
	foreach ( $rules as $type => $rx ) {
		if ( preg_match( $rx, $n ) ) {
			return $type;
		}
	}
	$sections = array(
		't-shirts' => 'tshirt', 'tank-tops' => 'tshirt', 'hoodies-and-sweatshirts' => 'hoodie', 'jackets' => 'hoodie',
		'title-belts' => 'belt', 'headwear' => 'headwear', 'home-and-office' => 'home', 'jerseys' => 'apparel',
		'shorts' => 'apparel', 'shirts' => 'apparel', 'leggings-and-pants' => 'apparel', 'boxers' => 'apparel', 'apparel' => 'apparel',
	);
	$q = wp_parse_url( (string) $url, PHP_URL_QUERY );
	if ( $q ) {
		parse_str( $q, $args );
		if ( ! empty( $args['u'] ) ) {
			$parts = explode( '/', (string) wp_parse_url( $args['u'], PHP_URL_PATH ) );
			if ( isset( $parts[2], $sections[ $parts[2] ] ) ) {
				return $sections[ $parts[2] ];
			}
		}
	}
	return 'other';
}
