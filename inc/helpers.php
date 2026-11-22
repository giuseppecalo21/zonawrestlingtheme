<?php
/**
 * Funzioni di supporto: categorie, immagini, date, etichette, icone, cache.
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Categorie
 * ------------------------------------------------------------------------- */

/** Termine categoria a partire da un'opzione del tema (es. 'cat_results') o da uno slug. */
function zw_cat( $key_or_slug ) {
	static $cache = array();
	if ( isset( $cache[ $key_or_slug ] ) ) {
		return $cache[ $key_or_slug ];
	}
	$defaults = zw_option_defaults();
	$slug     = isset( $defaults[ $key_or_slug ] ) ? sanitize_title( zw_opt( $key_or_slug ) ) : $key_or_slug;
	$term     = $slug ? get_category_by_slug( $slug ) : false;
	$cache[ $key_or_slug ] = $term ? $term : null;
	return $cache[ $key_or_slug ];
}

function zw_cat_id( $key_or_slug ) {
	$term = zw_cat( $key_or_slug );
	return $term ? (int) $term->term_id : 0;
}

function zw_cat_url( $key_or_slug ) {
	$term = zw_cat( $key_or_slug );
	return $term ? get_category_link( $term ) : home_url( '/' );
}

/** Il post appartiene alla categoria (o a una sua sottocategoria)? */
function zw_in_cat( $post, $key_or_slug ) {
	$id = zw_cat_id( $key_or_slug );
	if ( ! $id ) {
		return false;
	}
	foreach ( (array) get_the_category( $post ) as $cat ) {
		if ( (int) $cat->term_id === $id || (int) $cat->parent === $id ) {
			return true;
		}
	}
	return false;
}

/**
 * Etichette di una card: 'label' (sezione o categoria principale) e 'fed' (federazione).
 */
function zw_post_labels( $post ) {
	$post = get_post( $post );
	$cats = (array) get_the_category( $post );
	$out  = array( 'label' => '', 'label_url' => '', 'fed' => '', 'fed_url' => '' );
	if ( ! $cats ) {
		return $out;
	}

	$hidden   = zw_opt_list( 'hidden_cats' );
	$feds     = zw_opt_list( 'fed_cats' );
	$analysis = zw_cat_id( 'cat_analysis' );
	$sections = array( zw_cat_id( 'cat_results' ), $analysis, zw_cat_id( 'cat_editorials' ), zw_cat_id( 'cat_podcast' ) );

	$section = null;
	$fed     = null;
	$other   = null;
	foreach ( $cats as $cat ) {
		if ( in_array( $cat->slug, $hidden, true ) ) {
			continue;
		}
		if ( $analysis && (int) $cat->parent === $analysis ) {
			$section = $cat; // Es. "Raw", "Dynamite": più utile di "Show Analysis".
		} elseif ( ! $section && in_array( (int) $cat->term_id, $sections, true ) ) {
			$section = $cat;
		} elseif ( ! $fed && in_array( $cat->slug, $feds, true ) ) {
			$fed = $cat;
		} elseif ( ! $other ) {
			$other = $cat;
		}
	}

	$label = $section ? $section : ( $fed ? $fed : $other );
	if ( $label ) {
		$out['label']     = $label->name;
		$out['label_url'] = get_category_link( $label );
	}
	if ( $fed && $fed !== $label ) {
		$out['fed']     = $fed->name;
		$out['fed_url'] = get_category_link( $fed );
	}
	return apply_filters( 'zw_post_labels', $out, $post );
}

/** Una diretta è riconosciuta dal prefisso del titolo (filtrabile con 'zw_is_live'). */
function zw_is_live( $post ) {
	$post   = get_post( $post );
	$prefix = trim( (string) zw_opt( 'live_prefix' ) );
	$live   = $prefix && 0 === stripos( ltrim( $post->post_title ), $prefix );
	return (bool) apply_filters( 'zw_is_live', $live, $post );
}

/** 'video', 'gallery' oppure ''. */
function zw_media_type( $post ) {
	if ( zw_in_cat( $post, 'cat_video' ) || 'video' === get_post_format( $post ) ) {
		return 'video';
	}
	if ( zw_in_cat( $post, 'cat_gallery' ) || 'gallery' === get_post_format( $post ) ) {
		return 'gallery';
	}
	return '';
}

/* ---------------------------------------------------------------------------
 * Date
 * ------------------------------------------------------------------------- */

function zw_date( $post = null, $with_time = false ) {
	return get_the_date( $with_time ? 'd.m.Y • H:i' : 'd.m.Y', $post );
}

/* ---------------------------------------------------------------------------
 * Immagini
 * ------------------------------------------------------------------------- */

/**
 * <img> di un allegato scegliendo, tra le dimensioni già esistenti, la più piccola
 * che copre la larghezza richiesta. Evita di servire l'originale quando non serve.
 *
 * @param int   $id    ID allegato.
 * @param int   $width Larghezza utile in pixel (già comprensiva della densità desiderata).
 * @param array $attrs class, alt, sizes, loading, fetchpriority.
 */
function zw_image( $id, $width, $attrs = array() ) {
	$id   = (int) $id;
	$full = $id ? wp_get_attachment_url( $id ) : '';
	if ( ! $full ) {
		return '';
	}

	$meta = wp_get_attachment_metadata( $id );
	$src  = $full;
	$w    = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	$h    = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

	if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		$best = null;
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) || empty( $size['width'] ) || empty( $size['height'] ) ) {
				continue;
			}
			// Scarta i ritagli verticali e quelli troppo piccoli.
			if ( $size['width'] < $width || $size['height'] > $size['width'] * 0.9 ) {
				continue;
			}
			if ( ! $best || $size['width'] < $best['width'] ) {
				$best = $size;
			}
		}
		if ( $best && ( ! $w || $best['width'] < $w ) ) {
			$src = trailingslashit( dirname( $full ) ) . $best['file'];
			$w   = (int) $best['width'];
			$h   = (int) $best['height'];
		}
	}

	$alt = isset( $attrs['alt'] ) ? $attrs['alt'] : get_post_meta( $id, '_wp_attachment_image_alt', true );
	$out = array(
		'src'      => $src,
		'alt'      => $alt,
		'class'    => isset( $attrs['class'] ) ? $attrs['class'] : '',
		'loading'  => isset( $attrs['loading'] ) ? $attrs['loading'] : 'lazy',
		'decoding' => 'async',
	);
	if ( $w && $h ) {
		$out['width']  = $w;
		$out['height'] = $h;
		$srcset = is_array( $meta ) ? wp_calculate_image_srcset( array( $w, $h ), $src, $meta, $id ) : false;
		if ( $srcset ) {
			$out['srcset'] = $srcset;
			$out['sizes']  = isset( $attrs['sizes'] ) ? $attrs['sizes'] : sprintf( '(max-width: 640px) 100vw, %dpx', $width );
		}
	}
	if ( ! empty( $attrs['fetchpriority'] ) ) {
		$out['fetchpriority'] = $attrs['fetchpriority'];
		$out['loading']       = 'eager';
	}

	$html = '<img';
	foreach ( $out as $name => $value ) {
		$html .= sprintf( ' %s="%s"', $name, 'src' === $name ? esc_url( $value ) : esc_attr( $value ) );
	}
	return $html . ' />';
}

/** Immagine in evidenza di un post (stringa vuota se manca). */
function zw_post_image( $post, $width, $attrs = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	if ( ! isset( $attrs['alt'] ) ) {
		$attrs['alt'] = '';
	}
	return zw_image( get_post_thumbnail_id( $post ), $width, $attrs );
}

/* ---------------------------------------------------------------------------
 * Query e cache dei blocchi
 * ------------------------------------------------------------------------- */

/** Elenco di post senza conteggi inutili. */
function zw_posts( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	$query = new WP_Query( $args );
	return $query->posts;
}

/**
 * Esegue $callback (che stampa HTML) e ne conserva l'output per $ttl secondi.
 * La cache si azzera da sola a ogni pubblicazione o modifica.
 */
function zw_fragment( $key, $ttl, $callback ) {
	if ( is_customize_preview() || ( defined( 'ZW_NO_FRAGMENT_CACHE' ) && ZW_NO_FRAGMENT_CACHE ) ) {
		call_user_func( $callback );
		return;
	}
	$name = 'zw_f_' . md5( $key . '|' . get_option( 'zw_frag_v', '1' ) );
	$html = get_transient( $name );
	if ( false === $html ) {
		ob_start();
		call_user_func( $callback );
		$html = ob_get_clean();
		set_transient( $name, $html, $ttl );
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function zw_flush_fragments() {
	update_option( 'zw_frag_v', (string) microtime( true ), true );
	delete_transient( 'zw_event_tag_map' );
}

add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( in_array( $post->post_type, array( 'post', 'zw_event' ), true ) && ( 'publish' === $new || 'publish' === $old ) ) {
		zw_flush_fragments();
	}
}, 10, 3 );
add_action( 'edited_term', 'zw_flush_fragments' );
add_action( 'switch_theme', 'zw_flush_fragments' );
add_action( 'updated_post_meta', function ( $meta_id, $object_id, $meta_key ) {
	if ( 0 === strpos( (string) $meta_key, '_zw_event_' ) || '_thumbnail_id' === $meta_key ) {
		zw_flush_fragments();
	}
}, 10, 3 );

/* ---------------------------------------------------------------------------
 * Icone e piccoli componenti
 * ------------------------------------------------------------------------- */

function zw_icon( $name, $class = 'w-4 h-4' ) {
	$stroke = 'fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"';
	$icons  = array(
		'search'    => array( $stroke, '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>' ),
		'menu'      => array( $stroke, '<path d="M4 7h16M4 12h16M4 17h16"/>' ),
		'close'     => array( $stroke, '<path d="M6 6l12 12M18 6 6 18"/>' ),
		'clock'     => array( 'fill="none" stroke="currentColor" stroke-width="2"', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>' ),
		'calendar'  => array( $stroke, '<rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/>' ),
		'chevron'   => array( 'fill="none" stroke="currentColor" stroke-width="2.5"', '<path d="m6 9 6 6 6-6"/>' ),
		'share'     => array( $stroke, '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>' ),
		'link'      => array( $stroke, '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/>' ),
		'mic'       => array( $stroke, '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/>' ),
		'play'      => array( 'fill="currentColor"', '<path d="M8 5v14l11-7z"/>' ),
		'pause'     => array( 'fill="currentColor"', '<path d="M6 5h4v14H6zM14 5h4v14h-4z"/>' ),
		'video'     => array( 'fill="currentColor"', '<path d="M7.2 4.3A1.5 1.5 0 0 0 5 5.6v12.8a1.5 1.5 0 0 0 2.2 1.3l11.6-6.4a1.5 1.5 0 0 0 0-2.6z"/>' ),
		'gallery'   => array( 'fill="currentColor"', '<path d="M8.5 2h9A4.5 4.5 0 0 1 22 6.5v9a4.5 4.5 0 0 1-2.2 3.87V8.2A4 4 0 0 0 15.8 4.2H4.63A4.5 4.5 0 0 1 8.5 2z"/><rect x="2" y="6" width="16" height="16" rx="3.6"/>' ),
		'flame'     => array( 'fill="currentColor"', '<path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>' ),
		'facebook'  => array( 'fill="currentColor"', '<path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.99 3.66 9.13 8.44 9.88v-6.99H7.9V12h2.54V9.8c0-2.51 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.77l-.44 2.89h-2.33v6.99C18.34 21.13 22 16.99 22 12z"/>' ),
		'instagram' => array( 'fill="none" stroke="currentColor" stroke-width="2"', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"/>' ),
		'x'         => array( 'fill="currentColor"', '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>' ),
		'youtube'   => array( 'fill="currentColor"', '<path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/>' ),
		'telegram'  => array( 'fill="currentColor"', '<path d="M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.5 13.2l-4.7-1.5c-1-.3-1-1 .2-1.5L20.6 3.1c.9-.3 1.6.2 1.3 1.2z"/>' ),
		'threads'   => array( 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"', '<path d="M17.5 8.2C16.7 5.6 14.8 4 12 4 8.4 4 6 6.8 6 12s2.4 8 6 8c3 0 5.2-1.7 5.2-4.300 0-2.500-2-3.900-4.900-3.900-2.100 0-3.500 1-3.500 2.500 0 1.400 1.200 2.300 2.800 2.300 2.300 0 3.600-1.700 3.400-4.600-.1-1.900-1.200-3-3-3-1.200 0-2.100.5-2.600 1.300"/>' ),
		'whatsapp'  => array( 'fill="currentColor"', '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.300A10 10 0 1 0 12 2zm0 18.200a8.200 8.200 0 0 1-4.200-1.200l-.3-.200-3 .800.800-2.900-.200-.300A8.200 8.200 0 1 1 12 20.200zm4.500-6.100c-.200-.100-1.500-.700-1.700-.800s-.400-.100-.600.100-.600.800-.800 1-.300.200-.500.100a6.700 6.700 0 0 1-2-1.200 7.400 7.400 0 0 1-1.400-1.700c-.100-.200 0-.400.100-.500l.400-.400.200-.400a.500.500 0 0 0 0-.400l-.800-1.800c-.200-.500-.400-.400-.600-.400h-.500a.900.900 0 0 0-.700.300 2.800 2.800 0 0 0-.900 2.100 4.900 4.900 0 0 0 1 2.600 11.200 11.200 0 0 0 4.300 3.800c.600.300 1.100.400 1.400.500a3.400 3.400 0 0 0 1.600.100 2.600 2.600 0 0 0 1.700-1.200 2.100 2.100 0 0 0 .100-1.200c0-.100-.200-.200-.400-.300z"/>' ),
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return sprintf( '<svg class="%s" %s viewBox="0 0 24 24" aria-hidden="true">%s</svg>', esc_attr( $class ), $icons[ $name ][0], $icons[ $name ][1] );
}

/** Profili social configurati: array [ chiave => url ]. */
function zw_social_links() {
	$out = array();
	foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'x' => 'X', 'youtube' => 'YouTube', 'telegram' => 'Telegram', 'threads' => 'Threads' ) as $key => $label ) {
		$url = zw_opt( 'social_' . $key );
		if ( $url ) {
			$out[ $key ] = array( 'url' => $url, 'label' => $label );
		}
	}
	return $out;
}

/** Cinque stelline (piene, mezze, vuote) per un voto da 0 a 5. */
function zw_stars( $rating, $class = 'w-4 h-4' ) {
	// Arrotondato alla mezza stella più vicina, come Newspaper (7,7/10 = 4 stelle).
	$rating = round( max( 0, min( 5, (float) $rating ) ) * 2 ) / 2;
	$path   = 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z';
	$full   = (int) floor( $rating );
	$half   = ( $rating - $full ) >= 0.5 ? 1 : 0;
	$html   = sprintf( '<span class="inline-flex items-center gap-1" role="img" aria-label="%s">', esc_attr( sprintf( 'Voto: %s su 5', str_replace( '.', ',', (string) ( $full + $half * 0.5 ) ) ) ) );
	for ( $i = 1; $i <= 5; $i++ ) {
		if ( $i <= $full ) {
			$html .= sprintf( '<svg class="%s" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="%s"/></svg>', esc_attr( $class ), $path );
		} elseif ( $half && $i === $full + 1 ) {
			$html .= sprintf( '<svg class="%s" viewBox="0 0 24 24" aria-hidden="true"><path d="%s" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 2 8.91 8.26 2 9.27l5 4.87-1.18 6.88L12 17.77z" fill="currentColor"/></svg>', esc_attr( $class ), $path );
		} else {
			$html .= sprintf( '<svg class="%s" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true" style="opacity:.85"><path d="%s"/></svg>', esc_attr( $class ), $path );
		}
	}
	return $html . '</span>';
}

/** Titolo di sezione con la barretta rossa. */
function zw_section_title( $title, $args = array() ) {
	$args = wp_parse_args( $args, array( 'tag' => 'h2', 'dark' => false, 'size' => 'text-xl sm:text-2xl', 'link' => '', 'link_label' => '' ) );
	printf(
		'<div class="flex items-center justify-between gap-3 mb-5"><div class="flex items-center gap-2.5"><span class="w-2.5 h-6 rounded-full bg-zw-red inline-block nf-red-glow"></span><%1$s class="font-sofia font-black %2$s %3$s tracking-tight">%4$s</%1$s></div>%5$s</div>',
		tag_escape( $args['tag'] ),
		esc_attr( $args['size'] ),
		$args['dark'] ? 'text-white' : 'text-zw-ink',
		esc_html( $title ),
		$args['link'] ? sprintf( '<a href="%s" class="px-3 py-1 rounded-full bg-zw-red/10 hover:bg-zw-red text-zw-red hover:text-white font-sofia font-bold text-xs transition-colors shrink-0">%s</a>', esc_url( $args['link'] ), esc_html( $args['link_label'] ) ) : ''
	);
}

/** Punto di aggancio per i banner (Ad Inserter può usare questi hook personalizzati). */
function zw_ad_slot( $slot ) {
	do_action( 'zw_ad_' . $slot );
	do_action( 'zw_ad', $slot );
}

/** Voci del menu: dal menu "Menu principale" oppure, se non è impostato, dalle sezioni del tema. */
function zw_menu_items() {
	$items     = array();
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		foreach ( (array) wp_get_nav_menu_items( $locations['primary'] ) as $item ) {
			if ( empty( $item->menu_item_parent ) ) {
				$items[] = array( 'title' => $item->title, 'url' => $item->url, 'current' => (int) $item->object_id === (int) get_queried_object_id() && is_singular() === ( 'post_type' === $item->type ) );
			}
		}
	}
	if ( ! $items ) {
		$defaults = array( 'News' => 'news', 'Risultati' => 'cat_results', 'Analisi' => 'cat_analysis', 'Editoriali' => 'cat_editorials', 'Podcast' => 'cat_podcast', 'Gallery' => 'cat_gallery', 'Video' => 'cat_video' );
		foreach ( $defaults as $title => $key ) {
			if ( zw_cat( $key ) ) {
				$items[] = array( 'title' => $title, 'url' => zw_cat_url( $key ), 'current' => is_category( zw_cat_id( $key ) ) );
			}
		}
		if ( zw_events_active() ) {
			$items[] = array( 'title' => 'Eventi', 'url' => get_post_type_archive_link( ZW_EVENT_TYPE ), 'current' => is_post_type_archive( ZW_EVENT_TYPE ) );
		}
	}
	return $items;
}

/** Separa "RISULTATI: WWE SmackDown 13.11.2026" in show e data. */
function zw_parse_result_title( $title ) {
	$title = trim( preg_replace( '/^\s*RISULTATI\s*[:\-–]?\s*/iu', '', wp_strip_all_tags( $title ) ) );
	if ( preg_match( '/\s(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})$/u', $title, $m, PREG_OFFSET_CAPTURE ) ) {
		return array( 'show' => trim( substr( $title, 0, $m[0][1] ) ), 'date' => str_replace( array( '/', '-' ), '.', $m[1][0] ) );
	}
	return array( 'show' => $title, 'date' => '' );
}
