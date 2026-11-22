<?php
/**
 * Meta description e anteprime social (Open Graph / X) per articoli, eventi e home.
 *
 * Oggi il sito non ha un plugin SEO e queste informazioni mancano: senza, Google
 * sceglie da sé lo snippet e i social pescano un'immagine a caso. Se in futuro si
 * attiva Yoast, Rank Math, SEOPress o All in One SEO, il tema si fa da parte.
 */

defined( 'ABSPATH' ) || exit;

function zw_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/** Testo breve per description e anteprime (max ~155 caratteri, senza tagliare le parole). */
function zw_seo_snippet( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );
	$text = trim( preg_replace( '#(?:https?:)?//\S+#', '', $text ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max );
	$cut = preg_replace( '/\s+\S*$/u', '', $cut );
	return rtrim( $cut, ' ,;:.' ) . '…';
}

add_action( 'wp_head', function () {
	if ( zw_seo_plugin_active() ) {
		return;
	}
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
		$image = get_post_thumbnail_id( $post ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'full' ) : false;
		$tags  = array(
			'description'    => $desc,
			'og:type'        => 'post' === $post->post_type ? 'article' : 'website',
			'og:title'       => wp_strip_all_tags( get_the_title( $post ) ),
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
		return;
	}
	$tags['og:site_name'] = get_bloginfo( 'name' );
	$tags['og:locale']    = 'it_IT';
	$tags['twitter:card'] = isset( $tags['og:image'] ) ? 'summary_large_image' : 'summary';
	$x = (string) zw_opt( 'social_x' );
	if ( preg_match( '#(?:x|twitter)\.com/([A-Za-z0-9_]+)#', $x, $m ) ) {
		$tags['twitter:site'] = '@' . $m[1];
	}
	foreach ( $tags as $key => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		$attr = ( 0 === strpos( $key, 'og:' ) || 0 === strpos( $key, 'article:' ) ) ? 'property' : 'name';
		printf( '<meta %s="%s" content="%s" />' . "\n", $attr, esc_attr( $key ), esc_attr( $value ) );
	}
}, 5 );
