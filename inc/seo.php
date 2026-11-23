<?php
/**
 * Meta description e anteprime social (Open Graph / X) per articoli, eventi e home.
 *
 * Il tema li stampa solo se mancano: se un plugin (per esempio quello SEO del sito)
 * li ha già messi nell'<head>, non li ripete. Con Yoast, Rank Math, SEOPress o
 * All in One SEO attivi il tema si fa da parte del tutto.
 */

defined( 'ABSPATH' ) || exit;

function zw_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/** Testo breve per description e anteprime (max ~155 caratteri, senza tagliare le parole). */
function zw_seo_snippet( $text, $max = 155 ) {
	$text = html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $text ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	$text = trim( preg_replace( '#(?:https?:)?//\S+#', '', $text ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max );
	$cut = preg_replace( '/\s+\S*$/u', '', $cut );
	return rtrim( $cut, ' ,;:.' ) . '…';
}

/**
 * Immagine per anteprime e dati strutturati: la variante più piccola larga almeno
 * 1200 px (minimo di Google Discover) con le stesse proporzioni dell'originale,
 * altrimenti l'originale.
 *
 * @return array|false [ url, width, height ]
 */
function zw_seo_image( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	$full          = $attachment_id ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;
	if ( ! $full ) {
		return false;
	}
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( ! empty( $meta['sizes'] ) && $full[1] > 1200 && $full[2] ) {
		$ratio = $full[1] / $full[2];
		$best  = null;
		foreach ( $meta['sizes'] as $name => $size ) {
			if ( empty( $size['width'] ) || empty( $size['height'] ) || $size['width'] < 1200 ) {
				continue;
			}
			if ( abs( $size['width'] / $size['height'] - $ratio ) > 0.02 ) {
				continue; // Ritaglio con proporzioni diverse.
			}
			if ( ! $best || $size['width'] < $best[1] ) {
				$src  = wp_get_attachment_image_src( $attachment_id, $name );
				$best = $src ? $src : $best;
			}
		}
		if ( $best ) {
			return array( $best[0], (int) $best[1], (int) $best[2] );
		}
	}
	return array( $full[0], (int) $full[1], (int) $full[2] );
}

/** Meta description, Open Graph e X della pagina corrente: [ nome => valore ]. */
function zw_seo_tags() {
	$tags = array();
	if ( is_singular( array( 'post', 'page', ZW_EVENT_TYPE ) ) ) {
		$post = get_queried_object();
		$desc = '';
		if ( 'post' === $post->post_type ) {
			$desc = zw_subtitle( $post );
		}
		if ( ! $desc ) {
			$desc = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		}
		$desc  = zw_seo_snippet( $desc );
		$image = zw_seo_image( get_post_thumbnail_id( $post ) );
		$tags  = array(
			'description'    => $desc,
			'og:type'        => 'post' === $post->post_type ? 'article' : 'website',
			'og:title'       => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
			'og:description' => $desc,
			'og:url'         => get_permalink( $post ),
		);
		if ( $image ) {
			$tags['og:image']        = $image[0];
			$tags['og:image:width']  = $image[1];
			$tags['og:image:height'] = $image[2];
		}
		if ( 'post' === $post->post_type ) {
			$tags['article:published_time'] = get_the_date( 'c', $post );
			$tags['article:modified_time']  = get_the_modified_date( 'c', $post );
		}
	} elseif ( is_front_page() ) {
		$desc = zw_seo_snippet( get_bloginfo( 'description' ) . '. ' . zw_opt( 'footer_tagline' ) );
		$tags = array(
			'description'    => $desc,
			'og:type'        => 'website',
			'og:title'       => get_bloginfo( 'name' ),
			'og:description' => $desc,
			'og:url'         => home_url( '/' ),
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = zw_seo_snippet( term_description() );
		if ( $desc ) {
			$tags['description'] = $desc;
		}
	}
	if ( ! $tags ) {
		return array();
	}
	$tags['og:site_name'] = get_bloginfo( 'name' );
	$tags['og:locale']    = 'it_IT';
	$tags['twitter:card'] = isset( $tags['og:image'] ) ? 'summary_large_image' : 'summary';
	$x = (string) zw_opt( 'social_x' );
	if ( preg_match( '#(?:x|twitter)\.com/([A-Za-z0-9_]+)#', $x, $m ) ) {
		$tags['twitter:site'] = '@' . $m[1];
	}
	return $tags;
}

/*
 * Il tema stampa solo ciò che manca. L'<head> prodotto da WordPress e dai plugin viene
 * raccolto e, alla fine, si aggiungono description, Open Graph, X e dati strutturati
 * soltanto se nessun altro li ha già stampati (per esempio il plugin SEO del sito):
 * niente doppioni, e niente buchi il giorno in cui un plugin viene spento.
 */
add_action( 'wp_head', function () {
	$GLOBALS['zw_head_ob_level'] = ob_get_level();
	ob_start();
}, 0 );

add_action( 'wp_head', function () {
	$head = '';
	if ( isset( $GLOBALS['zw_head_ob_level'] ) && ob_get_level() === $GLOBALS['zw_head_ob_level'] + 1 ) {
		$head = (string) ob_get_clean();
		echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	do_action( 'zw_head_missing', $head );
}, PHP_INT_MAX );

/** Nell'<head> già stampato c'è questo tag? (es. 'description', 'og:title', 'twitter:card') */
function zw_head_has_meta( $head, $name ) {
	return (bool) preg_match( '/<meta[^>]+(?:name|property)\s*=\s*["\']' . preg_quote( $name, '/' ) . '["\']/i', $head );
}

add_action( 'zw_head_missing', function ( $head ) {
	if ( zw_seo_plugin_active() ) {
		return;
	}
	$tags      = zw_seo_tags();
	$skip_desc = zw_head_has_meta( $head, 'description' );
	$skip_og   = zw_head_has_meta( $head, 'og:title' );
	$skip_x    = zw_head_has_meta( $head, 'twitter:card' );
	foreach ( $tags as $key => $value ) {
		if ( '' === (string) $value
			|| ( 'description' === $key && $skip_desc )
			|| ( $skip_og && ( 0 === strpos( $key, 'og:' ) || 0 === strpos( $key, 'article:' ) ) )
			|| ( $skip_x && 0 === strpos( $key, 'twitter:' ) ) ) {
			continue;
		}
		$attr = ( 0 === strpos( $key, 'og:' ) || 0 === strpos( $key, 'article:' ) ) ? 'property' : 'name';
		printf( '<meta %s="%s" content="%s" />' . "\n", $attr, esc_attr( $key ), esc_attr( $value ) );
	}
	// WordPress stampa il canonical solo sui contenuti singoli: con la home "ultimi articoli" manca.
	if ( is_front_page() && is_home() && ! is_paged() && ! preg_match( '/<link[^>]+rel=["\']canonical["\']/i', $head ) ) {
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( home_url( '/' ) ) );
	}
} );
