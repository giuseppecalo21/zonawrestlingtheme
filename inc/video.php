<?php
/**
 * Pagina dei video: il video diventa il contenuto principale.
 *
 * - Il primo video incorporato nel testo (YouTube, Rumble, Vimeo, Dailymotion,
 *   Twitch, Streamable, Facebook o un <video>) viene tolto dal testo e mostrato
 *   in alto in un player grande. Vale anche per gli articoli già pubblicati:
 *   il testo nel database non viene toccato, il video si sposta solo a video.
 * - Per YouTube (e Vimeo) il tema recupera titolo originale, canale, durata e
 *   visualizzazioni, in background e con cache di una settimana.
 * - Dati strutturati VideoObject per Google.
 */

defined( 'ABSPATH' ) || exit;

const ZW_VIDEO_META = '_zw_video_info';

/** Il post è un video? (categoria Video o formato "video"). */
function zw_is_video_post( $post = null ) {
	$post = get_post( $post );
	return $post && 'post' === $post->post_type && 'video' === zw_media_type( $post );
}

// Gli articoli video usano single-video.php.
add_filter( 'single_template', function ( $template ) {
	if ( is_singular( 'post' ) && zw_is_video_post( get_queried_object() ) ) {
		$video = locate_template( 'single-video.php' );
		if ( $video ) {
			return $video;
		}
	}
	return $template;
} );

/**
 * Riconosce un indirizzo di incorporamento.
 *
 * @return array|null [ provider, id, src ]
 */
function zw_video_parse_src( $src ) {
	$src = html_entity_decode( (string) $src, ENT_QUOTES, 'UTF-8' );
	if ( 0 === strpos( $src, '//' ) ) {
		$src = 'https:' . $src;
	}
	$patterns = array(
		'youtube'     => '#(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?v=|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#',
		'rumble'      => '#rumble\.com/embed/([A-Za-z0-9]+)#',
		'vimeo'       => '#(?:player\.)?vimeo\.com/(?:video/)?(\d+)#',
		'dailymotion' => '#(?:dailymotion\.com/(?:embed/)?video/|dai\.ly/)([A-Za-z0-9]+)#',
		'twitch'      => '#(?:player|clips)\.twitch\.tv/#',
		'streamable'  => '#streamable\.com/(?:e|o)/([A-Za-z0-9]+)#',
		'facebook'    => '#facebook\.com/plugins/video\.php#',
	);
	foreach ( $patterns as $provider => $pattern ) {
		if ( preg_match( $pattern, $src, $m ) ) {
			return array( 'provider' => $provider, 'id' => isset( $m[1] ) ? $m[1] : '', 'src' => $src );
		}
	}
	return null;
}

/**
 * Trova il primo video nell'HTML e lo toglie, insieme ai contenitori che
 * avvolgono solo lui (div dell'embed, figure del blocco con didascalia, paragrafo).
 *
 * @return array [ 'video' => array|null, 'html' => HTML senza il video ]
 */
function zw_video_extract( $html ) {
	$out = array( 'video' => null, 'html' => $html );
	if ( ! preg_match_all( '#<iframe\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>\s*</iframe>#i', $html, $frames, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		$frames = array();
	}
	$found = null;
	foreach ( $frames as $frame ) {
		$parsed = zw_video_parse_src( $frame[1][0] );
		if ( $parsed ) {
			$found = array( $frame[0][1], $frame[0][1] + strlen( $frame[0][0] ), $parsed, $frame[0][0] );
			break;
		}
	}
	if ( ! $found && preg_match( '#<video\b[^>]*>.*?</video>#is', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		$src = preg_match( '#\bsrc=["\']([^"\']+\.(?:mp4|webm|m4v|mov)[^"\']*)["\']#i', $m[0][0], $s ) ? $s[1] : '';
		if ( $src ) {
			$found = array( $m[0][1], $m[0][1] + strlen( $m[0][0] ), array( 'provider' => 'file', 'id' => '', 'src' => html_entity_decode( $src ) ), $m[0][0] );
		}
	}
	if ( ! $found ) {
		return $out;
	}

	list( $start, $end, $video ) = $found;
	if ( preg_match( '#\btitle=["\']([^"\']*)["\']#i', $found[3], $t ) ) {
		$video['title'] = html_entity_decode( $t[1], ENT_QUOTES, 'UTF-8' );
	}

	// Allarga la selezione ai contenitori che contengono solo il video.
	for ( $i = 0; $i < 4; $i++ ) {
		$before = substr( $html, 0, $start );
		$after  = substr( $html, $end );
		if ( ! preg_match( '#<(div|figure|p|span)\b[^>]*>\s*$#i', $before, $open ) ) {
			break;
		}
		$tag  = strtolower( $open[1] );
		$tail = '#^\s*(?:<figcaption\b[^>]*>.*?</figcaption>\s*)?</' . $tag . '>#is';
		if ( ! preg_match( $tail, $after, $close ) ) {
			break;
		}
		$start -= strlen( $open[0] );
		$end   += strlen( $close[0] );
	}

	$out['video'] = $video;
	$out['html']  = substr( $html, 0, $start ) . substr( $html, $end );
	return $out;
}

/** Video del post corrente, con il testo già filtrato e senza il video. */
function zw_current_video() {
	return zw_video_extract( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
}

/**
 * Primo video nel testo grezzo (iframe, blocco embed o indirizzo da solo):
 * serve dove non conviene far girare tutti i filtri del testo (head, cron).
 */
function zw_video_from_raw( $content ) {
	if ( preg_match_all( '#(?:https?:)?//[^\s"\'<>\]]+#i', (string) $content, $urls ) ) {
		foreach ( $urls[0] as $url ) {
			$video = zw_video_parse_src( $url );
			if ( $video ) {
				return $video;
			}
		}
	}
	return null;
}

/** Indirizzo per il player (con avvio automatico al clic sulla copertina). */
function zw_video_embed_url( $video, $autoplay = true ) {
	switch ( $video['provider'] ) {
		case 'youtube':
			return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video['id'] ) . '?rel=0&playsinline=1' . ( $autoplay ? '&autoplay=1' : '' );
		case 'vimeo':
			return 'https://player.vimeo.com/video/' . rawurlencode( $video['id'] ) . ( $autoplay ? '?autoplay=1' : '' );
		default:
			return $video['src'];
	}
}

/** Copertina: immagine in evidenza, altrimenti quella di YouTube. */
function zw_video_poster( $post, $video ) {
	$thumb = get_post_thumbnail_id( $post );
	if ( $thumb ) {
		$src = wp_get_attachment_image_src( $thumb, 'full' );
		if ( $src ) {
			return $src[0];
		}
	}
	if ( $video && 'youtube' === $video['provider'] ) {
		return 'https://i.ytimg.com/vi/' . rawurlencode( $video['id'] ) . '/hqdefault.jpg';
	}
	return '';
}

/* ---------------------------------------------------------------------------
 * Dati dal servizio video (titolo originale, canale, durata, visualizzazioni).
 * ------------------------------------------------------------------------- */

/**
 * Dati salvati del video del post. Se mancano o sono vecchi, li chiede in background.
 *
 * @return array [ title, author, author_url, duration (secondi), views, upload (AAAA-MM-GG) ]
 */
function zw_video_info( $post_id, $video ) {
	$info = get_post_meta( $post_id, ZW_VIDEO_META, true );
	$info = is_array( $info ) ? $info : array();
	$key  = $video ? $video['provider'] . ':' . $video['id'] : '';
	$old  = empty( $info['fetched'] ) || ( time() - (int) $info['fetched'] ) > WEEK_IN_SECONDS || ( isset( $info['key'] ) && $info['key'] !== $key );
	if ( $video && in_array( $video['provider'], array( 'youtube', 'vimeo' ), true ) && $old && ! wp_next_scheduled( 'zw_fetch_video_info', array( (int) $post_id ) ) ) {
		wp_schedule_single_event( time(), 'zw_fetch_video_info', array( (int) $post_id ) );
	}
	return ( isset( $info['key'] ) && $info['key'] === $key ) ? $info : array();
}

add_action( 'zw_fetch_video_info', function ( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}
	$video = zw_video_from_raw( $post->post_content );
	if ( ! $video ) {
		return;
	}
	$info = array( 'key' => $video['provider'] . ':' . $video['id'], 'fetched' => time() );

	if ( 'youtube' === $video['provider'] ) {
		$watch = 'https://www.youtube.com/watch?v=' . rawurlencode( $video['id'] );
		$res   = wp_remote_get( 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode( $watch ), array( 'timeout' => 8 ) );
		$json  = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( is_array( $json ) ) {
			$info['title']      = isset( $json['title'] ) ? sanitize_text_field( $json['title'] ) : '';
			$info['author']     = isset( $json['author_name'] ) ? sanitize_text_field( $json['author_name'] ) : '';
			$info['author_url'] = isset( $json['author_url'] ) ? esc_url_raw( $json['author_url'] ) : '';
		}
		// Durata, visualizzazioni e data: dalla pagina pubblica del video.
		$res  = wp_remote_get( $watch . '&hl=it&gl=IT', array(
			'timeout'    => 10,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
			'headers'    => array( 'Accept-Language' => 'it-IT,it;q=0.9', 'Cookie' => 'CONSENT=YES+1; SOCS=CAI' ),
		) );
		$page = wp_remote_retrieve_body( $res );
		if ( preg_match( '/"lengthSeconds":"(\d+)"/', $page, $m ) ) {
			$info['duration'] = (int) $m[1];
		}
		if ( preg_match( '/"viewCount":"(\d+)"/', $page, $m ) ) {
			$info['views'] = (int) $m[1];
		}
		if ( preg_match( '/"(?:uploadDate|publishDate)":"(\d{4}-\d{2}-\d{2})/', $page, $m ) ) {
			$info['upload'] = $m[1];
		}
	} elseif ( 'vimeo' === $video['provider'] ) {
		$res  = wp_remote_get( 'https://vimeo.com/api/oembed.json?url=' . rawurlencode( 'https://vimeo.com/' . $video['id'] ), array( 'timeout' => 8 ) );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( is_array( $json ) ) {
			$info['title']      = isset( $json['title'] ) ? sanitize_text_field( $json['title'] ) : '';
			$info['author']     = isset( $json['author_name'] ) ? sanitize_text_field( $json['author_name'] ) : '';
			$info['author_url'] = isset( $json['author_url'] ) ? esc_url_raw( $json['author_url'] ) : '';
			$info['duration']   = isset( $json['duration'] ) ? (int) $json['duration'] : 0;
		}
	}
	update_post_meta( $post_id, ZW_VIDEO_META, $info );
} );

// Alla pubblicazione o modifica di un video i dati vengono aggiornati.
add_action( 'save_post_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status || ! zw_is_video_post( $post ) ) {
		return;
	}
	delete_post_meta( $post_id, ZW_VIDEO_META );
	wp_schedule_single_event( time() + 5, 'zw_fetch_video_info', array( (int) $post_id ) );
}, 20, 2 );

/** 75 → "1:15", 3725 → "1:02:05". */
function zw_video_duration( $seconds ) {
	$seconds = (int) $seconds;
	if ( $seconds <= 0 ) {
		return '';
	}
	$h = intdiv( $seconds, 3600 );
	$m = intdiv( $seconds % 3600, 60 );
	$s = $seconds % 60;
	return $h ? sprintf( '%d:%02d:%02d', $h, $m, $s ) : sprintf( '%d:%02d', $m, $s );
}

/** 3725 → "PT1H2M5S" (formato ISO 8601 per i dati strutturati). */
function zw_video_duration_iso( $seconds ) {
	$seconds = (int) $seconds;
	$h       = intdiv( $seconds, 3600 );
	$m       = intdiv( $seconds % 3600, 60 );
	$s       = $seconds % 60;
	return 'PT' . ( $h ? $h . 'H' : '' ) . ( $m ? $m . 'M' : '' ) . ( $s || ( ! $h && ! $m ) ? $s . 'S' : '' );
}

/** 1520 → "1.520", 15300 → "15 mila", 1250000 → "1,3 mln". */
function zw_video_views( $views ) {
	$views = (int) $views;
	if ( $views >= 1000000 ) {
		return number_format_i18n( $views / 1000000, 1 ) . ' mln';
	}
	if ( $views >= 10000 ) {
		return number_format_i18n( $views / 1000 ) . ' mila';
	}
	return number_format_i18n( $views );
}

/** Nome del servizio da mostrare. */
function zw_video_provider_name( $provider ) {
	$names = array( 'youtube' => 'YouTube', 'rumble' => 'Rumble', 'vimeo' => 'Vimeo', 'dailymotion' => 'Dailymotion', 'twitch' => 'Twitch', 'streamable' => 'Streamable', 'facebook' => 'Facebook', 'file' => '' );
	return isset( $names[ $provider ] ) ? $names[ $provider ] : '';
}

/* ---------------------------------------------------------------------------
 * Video correlati: prima quelli della stessa federazione, poi gli ultimi.
 * ------------------------------------------------------------------------- */

function zw_related_videos( $post, $count = 8 ) {
	$post  = get_post( $post );
	$video = zw_cat_id( 'cat_video' );
	if ( ! $post || ! $video ) {
		return array();
	}
	$feds = array();
	foreach ( (array) get_the_category( $post ) as $cat ) {
		if ( in_array( $cat->slug, zw_opt_list( 'fed_cats' ), true ) ) {
			$feds[] = (int) $cat->term_id;
		}
	}
	$posts = array();
	if ( $feds ) {
		$posts = zw_posts( array(
			'category__and'  => array( $video, $feds[0] ),
			'posts_per_page' => $count,
			'post__not_in'   => array( $post->ID ),
		) );
	}
	if ( count( $posts ) < $count ) {
		$posts = array_merge( $posts, zw_posts( array(
			'cat'            => $video,
			'posts_per_page' => $count - count( $posts ),
			'post__not_in'   => array_merge( array( $post->ID ), wp_list_pluck( $posts, 'ID' ) ),
		) ) );
	}
	return $posts;
}

/* ---------------------------------------------------------------------------
 * Dati strutturati VideoObject.
 * ------------------------------------------------------------------------- */

add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) || ! zw_is_video_post( get_queried_object() ) ) {
		return;
	}
	$post  = get_queried_object();
	$video = zw_video_from_raw( $post->post_content );
	if ( ! $video ) {
		return;
	}
	$info   = zw_video_info( $post->ID, $video );
	$poster = zw_video_poster( $post, $video );
	$text   = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( has_excerpt( $post ) ? $post->post_excerpt : $post->post_content ) ) ) );
	$text   = trim( preg_replace( '#(?:https?:)?//\S+#', '', $text ) );
	$data   = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'VideoObject',
		'name'         => wp_strip_all_tags( get_the_title( $post ) ),
		'description'  => $text ? wp_trim_words( $text, 50, '…' ) : wp_strip_all_tags( get_the_title( $post ) ),
		'uploadDate'   => get_the_date( 'c', $post ),
		'thumbnailUrl' => $poster ? array( $poster ) : array(),
		'embedUrl'     => zw_video_embed_url( $video, false ),
		'publisher'    => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
	);
	if ( ! empty( $info['duration'] ) ) {
		$data['duration'] = zw_video_duration_iso( $info['duration'] );
	}
	if ( 'file' === $video['provider'] ) {
		$data['contentUrl'] = $video['src'];
		unset( $data['embedUrl'] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 21 );
