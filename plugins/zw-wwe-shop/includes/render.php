<?php
/**
 * Riquadro dei prodotti dentro l'articolo.
 */

defined( 'ABSPATH' ) || exit;

/** Va mostrato in questa pagina? (solo articoli singoli, testo principale) */
function zw_shop_should_render() {
	if ( ! zw_shop_setting( 'enabled' ) || is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return false;
	}
	return ! get_post_meta( get_the_ID(), '_zw_shop_off', true );
}

// Dopo i filtri del tema ("In breve" a 20, copertine YouTube a 30).
add_filter( 'the_content', function ( $content ) {
	if ( ! zw_shop_should_render() ) {
		return $content;
	}
	$box = zw_shop_box( get_post() );
	if ( '' === $box ) {
		return $content;
	}
	return zw_shop_insert( $content, $box, (int) zw_shop_setting( 'paragraph' ) );
}, 40 );

/**
 * Inserisce $box dopo un paragrafo "di primo livello" (non dentro citazioni,
 * embed di X/Instagram, tabelle, liste o altri riquadri).
 * $after = 0: a metà articolo. Mai prima del secondo paragrafo (dopo il primo c'è "In breve").
 */
function zw_shop_insert( $content, $box, $after = 0 ) {
	$ends  = array();
	$depth = 0;
	if ( preg_match_all( '#<(/?)(blockquote|figure|table|ul|ol|div|aside|section|iframe|script)\b[^>]*>|</p>#i', $content, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER ) ) {
		foreach ( $m as $tag ) {
			if ( '</p>' === strtolower( $tag[0][0] ) ) {
				if ( 0 === $depth ) {
					$ends[] = $tag[0][1] + 4;
				}
				continue;
			}
			if ( '/' === $tag[1][0] ) {
				$depth = max( 0, $depth - 1 );
			} elseif ( '/>' !== substr( $tag[0][0], -2 ) ) {
				$depth++;
			}
		}
	}
	$n = count( $ends );
	if ( $n < 3 ) {
		// Articolo molto breve: in fondo al testo.
		return $n ? substr_replace( $content, $box, $ends[ $n - 1 ], 0 ) : $content . $box;
	}
	$k = $after > 0 ? min( $after, $n - 1 ) : (int) ceil( $n / 2 );
	$k = max( 2, $k );
	// Non dopo un paragrafo che introduce qualcosa ("...ha detto:"): si va al successivo.
	while ( $k < $n && ':' === substr( rtrim( wp_strip_all_tags( substr( $content, max( 0, $ends[ $k - 1 ] - 300 ), 300 ) ) ), -1 ) ) {
		$k++;
	}
	return substr_replace( $content, $box, $ends[ $k - 1 ], 0 );
}

/** HTML del riquadro, o stringa vuota se non ci sono prodotti adatti. */
function zw_shop_box( $post ) {
	$pick = zw_shop_pick_products( $post );
	// Con filtri stretti può restare un solo prodotto: meglio nessun riquadro.
	if ( count( $pick['products'] ) < 2 ) {
		return '';
	}

	if ( 1 === count( $pick['wrestlers'] ) ) {
		$title = sprintf( zw_shop_setting( 'title_one' ), $pick['wrestlers'][0] );
	} else {
		$title = zw_shop_setting( $pick['generic'] ? 'title_generic' : 'title_many' );
	}

	$items = '';
	foreach ( $pick['products'] as $i => $p ) {
		$price = zw_shop_price( $p->price, $p->currency );
		$old   = ( $p->original_price > $p->price + 0.009 ) ? '<s>' . esc_html( zw_shop_price( $p->original_price, $p->currency ) ) . '</s> ' : '';
		$img   = zw_shop_image( $p->image, 300 );
		$img2  = zw_shop_image( $p->image, 600 );
		$items .= sprintf(
			'<a class="zw-shop__item" href="%1$s" target="_blank" rel="sponsored nofollow noopener" data-zw-shop="%2$s"><span class="zw-shop__img"><img src="%3$s" srcset="%3$s 300w, %4$s 600w" sizes="(min-width: 640px) 170px, 44vw" width="300" height="300" alt="" loading="lazy" decoding="async"></span><span class="zw-shop__name">%5$s</span><span class="zw-shop__price">%6$s%7$s</span></a>',
			esc_url( zw_shop_link( $p ) ),
			esc_attr( $p->wrestler ),
			esc_url( $img ),
			esc_url( $img2 ),
			esc_html( $p->name_en ),
			$old,
			esc_html( $price )
		);
	}

	return '<aside class="zw-shop" aria-label="' . esc_attr__( 'Prodotti WWE Shop', 'zw-wwe-shop' ) . '">'
		. '<div class="zw-shop__head"><span class="zw-shop__kicker">WWE Shop</span><span class="zw-shop__title">' . esc_html( $title ) . '</span></div>'
		. '<div class="zw-shop__grid">' . $items . '</div>'
		. '<p class="zw-shop__note">' . esc_html( zw_shop_setting( 'disclosure' ) ) . '</p>'
		. '</aside>';
}

/** Link affiliato: EuroShop in inglese con prezzi in euro (link britannico + cur=EUR), oppure quello del catalogo prezzi. */
function zw_shop_link( $p ) {
	if ( 'en_eur' === zw_shop_setting( 'landing' ) && ! empty( $p->url_en ) ) {
		$parts = wp_parse_url( $p->url_en );
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $q );
			if ( ! empty( $q['u'] ) ) {
				// Stesso indirizzo provato da Giuseppe il 27/11/2026: inglese, prezzi in euro.
				$q['u'] = add_query_arg( 'cur', 'EUR', remove_query_arg( array( 'cur', 'loc' ), $q['u'] ) );
				return $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . http_build_query( $q, '', '&', PHP_QUERY_RFC3986 );
			}
		}
	}
	return $p->url;
}

/** Foto ridimensionata dal server di Fanatics (parametro w). */
function zw_shop_image( $url, $width ) {
	if ( false !== strpos( $url, 'frgimages.com' ) ) {
		$url = preg_match( '/([?&])w=\d+/', $url ) ? preg_replace( '/([?&])w=\d+/', '${1}w=' . (int) $width, $url ) : add_query_arg( 'w', (int) $width, $url );
	}
	return $url;
}

function zw_shop_price( $amount, $currency ) {
	$symbols = array( 'EUR' => '€', 'GBP' => '£', 'USD' => '$', 'AUD' => 'A$' );
	$s       = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency;
	return number_format( (float) $amount, 2, ',', '.' ) . ' ' . $s;
}

// Stile: poche regole in linea nella <head>, solo negli articoli.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! zw_shop_setting( 'enabled' ) || ! is_singular( 'post' ) ) {
		return;
	}
	wp_register_style( 'zw-shop', false, array(), ZW_SHOP_VERSION );
	wp_enqueue_style( 'zw-shop' );
	wp_add_inline_style( 'zw-shop', zw_shop_css() );
} );

function zw_shop_css() {
	return '.zw-shop{margin:2em 0;padding:18px;border-radius:22px;background:#fff;box-shadow:0 1px 2px rgba(15,17,23,.06),0 8px 24px rgba(15,17,23,.06);font-family:"Sofia Sans",sans-serif;line-height:1.3;clear:both}'
		. '.zw-shop__head{display:flex;flex-direction:column;gap:2px;margin:0 0 14px}'
		. '.zw-shop__kicker{font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#d7182a}'
		. '.zw-shop__title{font-size:20px;font-weight:900;color:#0f1117;letter-spacing:-.01em}'
		. '.zw-shop__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}'
		. '@media (min-width:640px){.zw-shop__grid{grid-template-columns:repeat(4,minmax(0,1fr))}}'
		. '.zw-prose .zw-shop a.zw-shop__item,.zw-shop a.zw-shop__item{display:flex;flex-direction:column;gap:6px;color:#0f1117;font-weight:600;text-decoration:none}'
		. '.zw-shop__img{display:block;aspect-ratio:1/1;border-radius:14px;overflow:hidden;background:#f2f4f8}'
		. '.zw-prose .zw-shop__img img,.zw-shop__img img{display:block;width:100%;height:100%;object-fit:contain;margin:0;border-radius:0;mix-blend-mode:multiply;transition:transform .2s}'
		. '.zw-shop__item:hover .zw-shop__img img{transform:scale(1.04)}'
		. '.zw-shop__name{font-size:13.5px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.6em}'
		. '.zw-shop__item:hover .zw-shop__name{color:#d7182a}'
		. '.zw-shop__price{font-size:15px;font-weight:900;color:#0f1117}'
		. '.zw-shop__price s{font-weight:600;color:#64687a;font-size:13px}'
		. '.zw-prose .zw-shop .zw-shop__note,.zw-shop__note{margin:14px 0 0;font-family:"Chivo",sans-serif;font-size:12px;line-height:1.5;color:#64687a;font-weight:400}';
}

// Nell'editor: spegnere il riquadro o scegliere a mano i wrestler.
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'zw-shop', 'Prodotti WWE Shop', function ( $post ) {
		wp_nonce_field( 'zw_shop_meta', 'zw_shop_meta_nonce' );
		printf(
			'<p><label><input type="checkbox" name="zw_shop_off" value="1" %s> Non mostrare prodotti in questo articolo</label></p><p><label for="zw_shop_wrestlers">Wrestler (al posto dei tag, separati da virgola)</label><input type="text" id="zw_shop_wrestlers" name="zw_shop_wrestlers" value="%s" class="widefat" placeholder="es. Cody Rhodes, Rhea Ripley"></p>',
			checked( (bool) get_post_meta( $post->ID, '_zw_shop_off', true ), true, false ),
			esc_attr( get_post_meta( $post->ID, '_zw_shop_wrestlers', true ) )
		);
	}, 'post', 'side', 'low' );
} );

add_action( 'save_post_post', function ( $post_id ) {
	if ( ! isset( $_POST['zw_shop_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['zw_shop_meta_nonce'] ), 'zw_shop_meta' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! empty( $_POST['zw_shop_off'] ) ) {
		update_post_meta( $post_id, '_zw_shop_off', 1 );
	} else {
		delete_post_meta( $post_id, '_zw_shop_off' );
	}
	$w = isset( $_POST['zw_shop_wrestlers'] ) ? sanitize_text_field( wp_unslash( $_POST['zw_shop_wrestlers'] ) ) : '';
	if ( '' !== $w ) {
		update_post_meta( $post_id, '_zw_shop_wrestlers', $w );
	} else {
		delete_post_meta( $post_id, '_zw_shop_wrestlers' );
	}
} );
