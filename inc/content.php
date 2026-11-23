<?php
/**
 * Testo dell'articolo, tempo di lettura, condivisione, breadcrumb, podcast.
 */

defined( 'ABSPATH' ) || exit;

/** Minuti di lettura stimati (200 parole al minuto). */
function zw_reading_time( $post = null ) {
	$post  = get_post( $post );
	$words = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ), -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) round( $words / 200 ) );
}

/**
 * Nel testo dell'articolo: riquadro "In breve" dopo il primo paragrafo
 * e valutazione (se presente) in fondo.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$points = zw_in_breve();
	if ( $points ) {
		$box = '<div class="zw-in-breve not-prose bg-white rounded-[22px] nf-shadow-light p-5 sm:p-6 my-8"><div class="flex items-center gap-2.5 mb-4"><span class="w-2.5 h-6 rounded-full bg-zw-red inline-block nf-red-glow"></span><h2 class="font-sofia font-black text-xl text-zw-ink tracking-tight" style="margin:0">In breve</h2></div><ol class="space-y-3" style="list-style:none;padding:0;margin:0">';
		foreach ( $points as $i => $point ) {
			$box .= sprintf( '<li class="flex gap-3.5" style="margin:0 0 .7rem"><span class="font-sofia font-black text-lg text-zw-red leading-none pt-0.5">%02d</span><span class="text-sm sm:text-[15px] text-zw-ink font-medium leading-snug">%s</span></li>', $i + 1, esc_html( $point ) );
		}
		$box .= '</ol></div>';
		$pos  = stripos( $content, '</p>' );
		$content = false !== $pos ? substr_replace( $content, '</p>' . $box, $pos, 4 ) : $box . $content;
	}

	return $content . zw_review_box();
}, 20 );

// Video incorporati sempre in 16:9 e mai più larghi della colonna.
add_filter( 'embed_oembed_html', function ( $html, $url ) {
	if ( preg_match( '#(youtube\.com|youtu\.be|vimeo\.com|dailymotion\.com|twitch\.tv)#i', (string) $url ) && false === strpos( $html, 'zw-video-wrap' ) ) {
		return '<div class="zw-video-wrap">' . $html . '</div>';
	}
	return $html;
}, 10, 2 );

/*
 * YouTube nel testo: al posto del player (centinaia di KB di script, pesanti per l'INP)
 * c'è la copertina con il tasto play; il player vero si carica al clic, nello stesso
 * riquadro 16:9 (nessuno spostamento della pagina). Vale per ogni iframe di YouTube:
 * blocco embed, oEmbed e iframe incollati negli articoli vecchi. Senza JavaScript resta
 * il link al video. Gli articoli video hanno già il loro player grande e restano esclusi.
 */
add_filter( 'the_content', function ( $content ) {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_singular() || false === stripos( $content, 'youtube' ) ) {
		return $content;
	}
	if ( function_exists( 'zw_is_video_post' ) && zw_is_video_post( get_post() ) ) {
		return $content;
	}
	return preg_replace_callback( '#<iframe\b[^>]*\bsrc=["\']([^"\']*(?:youtube\.com|youtube-nocookie\.com)/embed/([A-Za-z0-9_-]{11})[^"\']*)["\'][^>]*>\s*</iframe>#i', 'zw_youtube_facade', $content );
}, 30 );

/** Copertina cliccabile al posto di un iframe di YouTube (callback di preg_replace_callback). */
function zw_youtube_facade( $m ) {
	$src   = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
	$src   = 0 === strpos( $src, '//' ) ? 'https:' . $src : $src;
	$id    = $m[2];
	$title = preg_match( '#\btitle=["\']([^"\']*)["\']#i', $m[0], $t ) ? html_entity_decode( $t[1], ENT_QUOTES, 'UTF-8' ) : 'Video';
	return sprintf(
		'<span class="zw-yt" data-zw-player="%1$s" data-title="%2$s"><button type="button" aria-label="%3$s"><img src="https://i.ytimg.com/vi/%4$s/hqdefault.jpg" alt="" width="480" height="360" loading="lazy" decoding="async" /><span class="zw-yt-play" aria-hidden="true"></span></button><noscript><a href="https://www.youtube.com/watch?v=%4$s">%2$s</a></noscript></span>',
		esc_url( add_query_arg( 'autoplay', '1', $src ) ),
		esc_attr( $title ),
		esc_attr( 'Guarda il video: ' . $title ),
		esc_attr( $id )
	);
}

/**
 * Script di un blocco di HTML resi "in attesa": li esegue main.js quando il blocco
 * si avvicina allo schermo. Usato per i commenti (Disqus carica embed.js appena la
 * pagina si apre, anche per chi non arriva mai in fondo all'articolo).
 */
function zw_defer_scripts( $html ) {
	return preg_replace_callback( '#<script\b([^>]*)>#i', function ( $m ) {
		$attrs = $m[1];
		if ( preg_match( '#\btype\s*=\s*["\']?([^"\'\s>]+)#i', $attrs, $type ) ) {
			if ( ! in_array( strtolower( $type[1] ), array( 'text/javascript', 'application/javascript', 'module' ), true ) ) {
				return $m[0]; // JSON-LD, template e simili restano come sono.
			}
			$attrs = preg_replace( '#\btype\s*=\s*["\']?[^"\'\s>]+["\']?#i', '', $attrs );
		}
		return '<script type="text/zw-deferred"' . $attrs . '>';
	}, $html );
}

/** Link di condivisione dell'articolo corrente. */
function zw_share_links( $post = null ) {
	$url   = rawurlencode( get_permalink( $post ) );
	$title = rawurlencode( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );
	return array(
		'facebook' => array( 'label' => 'Condividi su Facebook', 'url' => 'https://www.facebook.com/sharer.php?u=' . $url ),
		'x'        => array( 'label' => 'Condividi su X', 'url' => 'https://x.com/intent/post?text=' . $title . '&url=' . $url ),
		'telegram' => array( 'label' => 'Condividi su Telegram', 'url' => 'https://t.me/share/url?url=' . $url . '&text=' . $title ),
		'whatsapp' => array( 'label' => 'Condividi su WhatsApp', 'url' => 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url ),
	);
}

/** Percorso di navigazione: [ [ nome, url ] ]. */
function zw_breadcrumb( $post = null ) {
	$post  = get_post( $post );
	$trail = array( array( 'name' => 'Home', 'url' => home_url( '/' ) ) );
	if ( $post && 'post' === $post->post_type ) {
		$labels = zw_post_labels( $post );
		if ( $labels['fed'] ) {
			$trail[] = array( 'name' => $labels['fed'], 'url' => $labels['fed_url'] );
		}
		if ( $labels['label'] ) {
			$trail[] = array( 'name' => $labels['label'], 'url' => $labels['label_url'] );
		}
	} elseif ( $post && ZW_EVENT_TYPE === $post->post_type ) {
		$trail[] = array( 'name' => 'Eventi', 'url' => get_post_type_archive_link( ZW_EVENT_TYPE ) );
	}
	return $trail;
}

// Dati strutturati del percorso (prima li stampava Newspaper).
add_action( 'wp_head', function () {
	if ( ! is_singular( array( 'post', ZW_EVENT_TYPE ) ) || class_exists( 'WPSEO_Options' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$items = array();
	$trail = zw_breadcrumb();
	$trail[] = array( 'name' => wp_strip_all_tags( get_the_title() ), 'url' => get_permalink() );
	foreach ( $trail as $i => $crumb ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['url'] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 20 );

/**
 * File audio di un episodio del podcast: allegato "enclosure", blocco audio
 * oppure primo link a un file mp3/m4a nel testo. Stringa vuota se non c'è.
 */
function zw_podcast_audio( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$enclosure = get_post_meta( $post->ID, 'enclosure', true );
	if ( $enclosure ) {
		$parts = preg_split( '/\s+/', trim( $enclosure ) );
		if ( ! empty( $parts[0] ) && preg_match( '#^https?://#i', $parts[0] ) ) {
			return esc_url_raw( $parts[0] );
		}
	}
	if ( preg_match( '#https?://[^\s"\'<>\]]+\.(?:mp3|m4a|ogg)(?:\?[^\s"\'<>\]]*)?#i', $post->post_content, $match ) ) {
		return esc_url_raw( $match[0] );
	}
	return '';
}
