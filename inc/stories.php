<?php
/**
 * Stories in home: un cerchio per ogni blocco configurato in Aspetto → Stories in home,
 * con le ultime notizie della categoria. Ogni storia porta all'articolo completo.
 *
 * Se non c'è nessun blocco si usano gli slug di Personalizza → Zona Wrestling → "Stories e filtri del feed".
 */

defined( 'ABSPATH' ) || exit;

const ZW_STORIES_PER_CAT  = 5;
const ZW_STORY_BLOCKS_OPT = 'zw_story_blocks';

/* ---------------------------------------------------------------------------
 * Blocchi
 * ------------------------------------------------------------------------- */

/** Blocchi salvati, così come sono (anche quelli disattivati o fuori periodo). */
function zw_story_blocks() {
	$blocks = get_option( ZW_STORY_BLOCKS_OPT, array() );
	return is_array( $blocks ) ? $blocks : array();
}

/**
 * Stato di un blocco rispetto a oggi (fuso orario del sito).
 *
 * @return string on | off | scheduled | expired
 */
function zw_story_block_status( $block, $today = null ) {
	$today = $today ? $today : wp_date( 'Y-m-d' );
	if ( empty( $block['on'] ) ) {
		return 'off';
	}
	if ( ! empty( $block['from'] ) && $today < $block['from'] ) {
		return 'scheduled';
	}
	if ( ! empty( $block['to'] ) && $today > $block['to'] ) {
		return 'expired';
	}
	return 'on';
}

/**
 * Voci visibili oggi, nell'ordine scelto. Usate da stories, filtri del feed e footer.
 *
 * @return array[] [ term => WP_Term, label, image_id, fit => contain|cover ]
 */
function zw_story_entries() {
	$entries = array();
	$blocks  = zw_story_blocks();

	if ( $blocks ) {
		$today = wp_date( 'Y-m-d' );
		foreach ( $blocks as $block ) {
			if ( 'on' !== zw_story_block_status( $block, $today ) ) {
				continue;
			}
			$term = get_term( (int) $block['cat'], 'category' );
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$entries[] = array(
				'term'     => $term,
				'label'    => '' !== $block['label'] ? $block['label'] : $term->name,
				'image_id' => (int) $block['image'],
				'fit'      => 'cover' === $block['fit'] ? 'cover' : 'contain',
			);
		}
		return $entries;
	}

	// Nessun blocco configurato: elenco di slug del Personalizza con il logo della categoria.
	foreach ( zw_opt_list( 'stories_cats' ) as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( ! $term ) {
			continue;
		}
		$entries[] = array(
			'term'     => $term,
			'label'    => $term->name,
			'image_id' => 0,
			'fit'      => 'contain',
		);
	}
	return $entries;
}

/** URL dell'immagine del cerchio: il logo resta intero, la foto piena è ritagliata quadrata. */
function zw_story_image( $entry ) {
	$image_id = $entry['image_id'];
	$fit      = $entry['fit'];
	if ( ! $image_id ) {
		// Ripiego: logo impostato nella categoria (Articoli → Categorie).
		$image_id = (int) get_term_meta( $entry['term']->term_id, 'zw_logo_id', true );
		$fit      = 'contain';
	}
	if ( ! $image_id ) {
		return array( '', 'contain' );
	}
	$src = wp_get_attachment_image_src( $image_id, 'cover' === $fit ? 'thumbnail' : 'medium' );
	return array( $src ? $src[0] : '', $fit );
}

/**
 * @return array[] [ id, slug, label, alt, short, logo, fit, url, latest, slides => [ id, title, summary, image, time, label, url ] ]
 */
function zw_stories_data() {
	$entries = zw_story_entries();
	$sig     = array();
	foreach ( $entries as $entry ) {
		$sig[] = array( $entry['term']->term_id, $entry['label'], $entry['image_id'], $entry['fit'] );
	}
	$key    = 'zw_stories_' . md5( get_option( 'zw_frag_v', '1' ) . wp_json_encode( $sig ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$stories = array();
	foreach ( $entries as $entry ) {
		$term  = $entry['term'];
		$posts = zw_posts( array( 'cat' => $term->term_id, 'posts_per_page' => ZW_STORIES_PER_CAT ) );
		if ( ! $posts ) {
			continue;
		}
		$slides = array();
		foreach ( $posts as $post ) {
			$labels   = zw_post_labels( $post );
			$image    = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'large' );
			$slides[] = array(
				'id'      => $post->ID,
				'title'   => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
				'summary' => html_entity_decode( wp_trim_words( get_the_excerpt( $post ), 26, '…' ), ENT_QUOTES, 'UTF-8' ),
				'image'   => $image ? $image[0] : '',
				'time'    => zw_date( $post, true ),
				'label'   => $labels['label'] ? $labels['label'] : $entry['label'],
				'url'     => get_permalink( $post ),
			);
		}
		list( $logo, $fit ) = zw_story_image( $entry );
		$stories[]          = array(
			'id'     => (int) $term->term_id,
			'slug'   => $term->slug,
			'label'  => html_entity_decode( $entry['label'], ENT_QUOTES, 'UTF-8' ),
			'alt'    => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
			'short'  => mb_strtoupper( mb_substr( html_entity_decode( $entry['label'], ENT_QUOTES, 'UTF-8' ), 0, 7 ) ),
			'logo'   => $logo,
			'fit'    => $fit,
			'url'    => get_category_link( $term ),
			'latest' => $posts[0]->ID,
			'slides' => $slides,
		);
	}

	set_transient( $key, $stories, 10 * MINUTE_IN_SECONDS );
	return $stories;
}

/* ---------------------------------------------------------------------------
 * Pagina di gestione: Aspetto → Stories in home.
 * ------------------------------------------------------------------------- */

add_action( 'admin_init', function () {
	register_setting( 'zw_stories', ZW_STORY_BLOCKS_OPT, array(
		'type'              => 'array',
		'default'           => array(),
		'sanitize_callback' => 'zw_story_blocks_sanitize',
		'show_in_rest'      => false,
	) );
} );

// La pagina si salva con options.php: basta il permesso di modificare l'aspetto del sito.
add_filter( 'option_page_capability_zw_stories', function () {
	return 'edit_theme_options';
} );

function zw_story_blocks_sanitize( $input ) {
	$out = array();
	if ( ! is_array( $input ) ) {
		return $out;
	}
	$date = function ( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	};
	foreach ( $input as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$cat  = isset( $row['cat'] ) ? absint( $row['cat'] ) : 0;
		$term = $cat ? get_term( $cat, 'category' ) : null;
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$from = $date( isset( $row['from'] ) ? $row['from'] : '' );
		$to   = $date( isset( $row['to'] ) ? $row['to'] : '' );
		if ( $from && $to && $from > $to ) {
			list( $from, $to ) = array( $to, $from );
		}
		$image = isset( $row['image'] ) ? absint( $row['image'] ) : 0;
		$out[] = array(
			'cat'   => $cat,
			'label' => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '',
			'image' => ( $image && wp_attachment_is_image( $image ) ) ? $image : 0,
			'fit'   => ( isset( $row['fit'] ) && 'cover' === $row['fit'] ) ? 'cover' : 'contain',
			'from'  => $from,
			'to'    => $to,
			'on'    => empty( $row['on'] ) ? 0 : 1,
		);
	}
	return $out;
}

// Salvando i blocchi, stories e filtri in cache vanno rigenerati.
add_action( 'add_option_' . ZW_STORY_BLOCKS_OPT, 'zw_flush_fragments' );
add_action( 'update_option_' . ZW_STORY_BLOCKS_OPT, 'zw_flush_fragments' );

add_action( 'admin_menu', function () {
	$hook = add_theme_page( 'Stories in home', 'Stories in home', 'edit_theme_options', 'zw-stories', 'zw_story_blocks_page' );
	add_action( 'load-' . $hook, function () {
		wp_enqueue_media();
	} );
} );

/** Una riga dell'elenco. Con $block = null stampa il modello usato da "Aggiungi blocco". */
function zw_story_block_row( $index, $block, $categories ) {
	$block = wp_parse_args( (array) $block, array(
		'cat'   => 0,
		'label' => '',
		'image' => 0,
		'fit'   => 'contain',
		'from'  => '',
		'to'    => '',
		'on'    => 1,
	) );
	$name    = ZW_STORY_BLOCKS_OPT . '[' . $index . ']';
	$src     = $block['image'] ? wp_get_attachment_image_url( (int) $block['image'], 'medium' ) : '';
	$status  = $block['cat'] ? zw_story_block_status( $block ) : '';
	$badges  = array(
		'on'        => array( 'Visibile ora', 'is-on' ),
		'off'       => array( 'Disattivato', 'is-off' ),
		'scheduled' => array( 'Programmato', 'is-scheduled' ),
		'expired'   => array( 'Periodo finito', 'is-off' ),
	);
	?>
	<div class="zw-sb-row" data-row>
		<div class="zw-sb-media">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo esc_attr( $block['image'] ? $block['image'] : '' ); ?>" data-field="image" />
			<span class="zw-sb-circle fit-<?php echo esc_attr( $block['fit'] ); ?>" data-preview>
				<img src="<?php echo esc_url( $src ); ?>" alt="" <?php echo $src ? '' : 'hidden'; ?> />
				<em <?php echo $src ? 'hidden' : ''; ?>>Nessuna<br>immagine</em>
			</span>
			<button type="button" class="button button-small" data-pick>Scegli immagine</button>
			<button type="button" class="button-link zw-sb-danger" data-clear <?php echo $src ? '' : 'hidden'; ?>>Rimuovi immagine</button>
		</div>

		<div class="zw-sb-fields">
			<div class="zw-sb-head">
				<?php if ( $status ) : ?>
					<span class="zw-sb-badge <?php echo esc_attr( $badges[ $status ][1] ); ?>"><?php echo esc_html( $badges[ $status ][0] ); ?></span>
				<?php endif; ?>
				<span class="zw-sb-actions">
					<button type="button" class="button button-small" data-move="-1" aria-label="Sposta su">↑</button>
					<button type="button" class="button button-small" data-move="1" aria-label="Sposta giù">↓</button>
					<button type="button" class="button button-small zw-sb-danger" data-remove>Elimina</button>
				</span>
			</div>

			<div class="zw-sb-grid">
				<label>Categoria
					<select name="<?php echo esc_attr( $name ); ?>[cat]" required>
						<option value="">— Scegli —</option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo (int) $cat->term_id; ?>" <?php selected( (int) $block['cat'], (int) $cat->term_id ); ?>><?php echo esc_html( $cat->name . ' (' . $cat->slug . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Nome sotto il cerchio
					<input type="text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $block['label'] ); ?>" placeholder="Come la categoria" />
				</label>
				<label>Come mostrare l'immagine
					<select name="<?php echo esc_attr( $name ); ?>[fit]" data-field="fit">
						<option value="contain" <?php selected( $block['fit'], 'contain' ); ?>>Logo intero (PNG trasparente)</option>
						<option value="cover" <?php selected( $block['fit'], 'cover' ); ?>>Immagine piena, ritagliata nel cerchio</option>
					</select>
				</label>
				<label>Visibile dal
					<input type="date" name="<?php echo esc_attr( $name ); ?>[from]" value="<?php echo esc_attr( $block['from'] ); ?>" />
				</label>
				<label>Visibile fino al
					<input type="date" name="<?php echo esc_attr( $name ); ?>[to]" value="<?php echo esc_attr( $block['to'] ); ?>" />
				</label>
				<label class="zw-sb-check">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[on]" value="1" <?php checked( ! empty( $block['on'] ) ); ?> /> Attivo
				</label>
			</div>
		</div>
	</div>
	<?php
}

function zw_story_blocks_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$blocks     = zw_story_blocks();
	$categories = get_terms( array(
		'taxonomy'   => 'category',
		'hide_empty' => false,
		'orderby'    => 'name',
	) );
	$categories = is_array( $categories ) ? $categories : array();
	?>
	<div class="wrap zw-sb">
		<h1>Stories in home</h1>
		<?php settings_errors(); ?>
		<p class="description" style="max-width:760px">
			Ogni blocco è un cerchio delle stories in home, con le ultime notizie della categoria scelta. Gli stessi blocchi,
			nello stesso ordine, diventano i filtri di "Ultime notizie" e i link del footer. Le date sono facoltative:
			servono per mostrare un blocco solo in un certo periodo (per esempio WrestleMania o la Royal Rumble).
			Una categoria senza articoli non compare. Il testo alternativo dell'immagine è sempre il nome della categoria.
		</p>
		<?php if ( ! $blocks ) : ?>
			<div class="notice notice-info inline"><p>Non hai ancora creato blocchi: per ora la home usa le categorie indicate in Personalizza → Zona Wrestling (<code><?php echo esc_html( zw_opt( 'stories_cats' ) ); ?></code>).</p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'zw_stories' ); ?>
			<input type="hidden" name="<?php echo esc_attr( ZW_STORY_BLOCKS_OPT ); ?>" value="" data-empty />
			<div id="zwStoryRows">
				<?php
				foreach ( $blocks as $i => $block ) {
					zw_story_block_row( $i, $block, $categories );
				}
				?>
			</div>
			<p>
				<button type="button" class="button" id="zwStoryAdd">+ Aggiungi blocco</button>
			</p>
			<?php submit_button( 'Salva le stories' ); ?>
		</form>

		<template id="zwStoryTpl"><?php zw_story_block_row( '__i__', null, $categories ); ?></template>
	</div>

	<style>
		.zw-sb-row { display:flex; gap:20px; background:#fff; border:1px solid #dcdcde; border-radius:8px; padding:16px; margin:0 0 12px; max-width:1000px; }
		.zw-sb-media { display:flex; flex-direction:column; align-items:center; gap:6px; width:120px; flex-shrink:0; text-align:center; }
		.zw-sb-circle { width:80px; height:80px; border-radius:50%; background:#12141c; border:3px solid #d7182a; overflow:hidden; display:flex; align-items:center; justify-content:center; box-sizing:border-box; }
		.zw-sb-circle img { width:100%; height:100%; display:block; }
		.zw-sb-circle img[hidden], .zw-sb-circle em[hidden] { display:none; }
		.zw-sb-circle.fit-contain { padding:8px; }
		.zw-sb-circle.fit-contain img { object-fit:contain; }
		.zw-sb-circle.fit-cover img { object-fit:cover; }
		.zw-sb-circle em { color:#fff9; font-size:10px; font-style:normal; line-height:1.2; }
		.zw-sb-fields { flex:1; min-width:0; }
		.zw-sb-head { display:flex; align-items:center; gap:8px; margin-bottom:10px; min-height:26px; }
		.zw-sb-actions { margin-left:auto; display:flex; gap:4px; }
		.zw-sb-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px 16px; }
		.zw-sb-grid label { display:flex; flex-direction:column; gap:4px; font-weight:600; font-size:12px; }
		.zw-sb-grid select, .zw-sb-grid input[type=text], .zw-sb-grid input[type=date] { width:100%; max-width:none; font-weight:400; }
		.zw-sb-grid .zw-sb-check { flex-direction:row; align-items:center; align-self:end; padding-bottom:6px; font-size:13px; }
		.zw-sb-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:99px; }
		.zw-sb-badge.is-on { background:#d1f5dc; color:#0a5c27; }
		.zw-sb-badge.is-off { background:#f0f0f1; color:#50575e; }
		.zw-sb-badge.is-scheduled { background:#fcf0d0; color:#7a5300; }
		.zw-sb .zw-sb-danger { color:#b32d2e; }
		.zw-sb-row:first-child [data-move="-1"], .zw-sb-row:last-child [data-move="1"] { visibility:hidden; }
		@media (max-width: 900px) { .zw-sb-grid { grid-template-columns:1fr 1fr; } }
		@media (max-width: 600px) { .zw-sb-row { flex-direction:column; } .zw-sb-media { width:auto; } .zw-sb-grid { grid-template-columns:1fr; } }
	</style>

	<script>
	( function () {
		var list = document.getElementById( 'zwStoryRows' );
		var tpl = document.getElementById( 'zwStoryTpl' );
		var base = <?php echo wp_json_encode( ZW_STORY_BLOCKS_OPT ); ?>;
		var frame = null, current = null;

		// Rinumera i campi nell'ordine in cui appaiono, così si salva l'ordine scelto.
		function renumber() {
			Array.prototype.forEach.call( list.children, function ( row, i ) {
				row.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( /^[^\[]+\[[^\]]*\]/, base + '[' + i + ']' );
				} );
			} );
			// Senza righe si invia un valore vuoto, così l'elenco si svuota davvero.
			document.querySelector( '[data-empty]' ).disabled = list.children.length > 0;
		}

		function setImage( row, id, url ) {
			var img = row.querySelector( '[data-preview] img' );
			row.querySelector( '[data-field="image"]' ).value = id || '';
			img.src = url || '';
			img.hidden = ! url;
			row.querySelector( '[data-preview] em' ).hidden = !! url;
			row.querySelector( '[data-clear]' ).hidden = ! url;
		}

		document.getElementById( 'zwStoryAdd' ).addEventListener( 'click', function () {
			list.appendChild( tpl.content.cloneNode( true ) );
			renumber();
			list.lastElementChild.querySelector( 'select' ).focus();
		} );

		list.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( '[data-field="fit"]' ) ) {
				var preview = event.target.closest( '[data-row]' ).querySelector( '[data-preview]' );
				preview.className = 'zw-sb-circle fit-' + event.target.value;
			}
		} );

		list.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest( 'button' );
			if ( ! btn ) { return; }
			var row = btn.closest( '[data-row]' );

			if ( btn.hasAttribute( 'data-remove' ) ) {
				if ( window.confirm( 'Eliminare questo blocco?' ) ) { row.remove(); renumber(); }
			} else if ( btn.hasAttribute( 'data-move' ) ) {
				if ( '-1' === btn.getAttribute( 'data-move' ) && row.previousElementSibling ) {
					list.insertBefore( row, row.previousElementSibling );
				} else if ( '1' === btn.getAttribute( 'data-move' ) && row.nextElementSibling ) {
					list.insertBefore( row.nextElementSibling, row );
				}
				renumber();
				btn.focus();
			} else if ( btn.hasAttribute( 'data-clear' ) ) {
				setImage( row, '', '' );
			} else if ( btn.hasAttribute( 'data-pick' ) ) {
				current = row;
				if ( ! frame ) {
					frame = wp.media( { title: 'Immagine del blocco', button: { text: 'Usa questa immagine' }, multiple: false, library: { type: 'image' } } );
					frame.on( 'select', function () {
						var item = frame.state().get( 'selection' ).first().toJSON();
						var url = item.sizes && item.sizes.medium ? item.sizes.medium.url : item.url;
						setImage( current, item.id, url );
						// Proposta automatica: i formati con trasparenza come logo intero, il resto come foto ritagliata.
						var fit = /png|svg|webp|gif/.test( item.mime || '' ) ? 'contain' : 'cover';
						var select = current.querySelector( '[data-field="fit"]' );
						select.value = fit;
						select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					} );
				}
				frame.open();
			}
		} );

		renumber();
	} )();
	</script>
	<?php
}

/* ---------------------------------------------------------------------------
 * Logo della categoria (Articoli → Categorie → Modifica).
 * Usato quando un blocco non ha un'immagine propria.
 * ------------------------------------------------------------------------- */

function zw_term_logo_field( $term = null ) {
	$logo_id = is_object( $term ) ? (int) get_term_meta( $term->term_id, 'zw_logo_id', true ) : 0;
	$src     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	wp_enqueue_media();
	?>
	<input type="hidden" name="zw_logo_id" id="zw_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
	<img id="zw_logo_preview" src="<?php echo esc_url( $src ); ?>" alt="" style="width:72px;height:72px;object-fit:contain;background:#12141c;border-radius:50%;padding:6px;<?php echo $src ? '' : 'display:none;'; ?>" />
	<p>
		<button type="button" class="button" id="zw_logo_pick">Scegli il logo</button>
		<button type="button" class="button-link" id="zw_logo_clear">Rimuovi</button>
	</p>
	<p class="description">Logo mostrato nel cerchio delle stories in home quando il blocco (Aspetto → Stories in home) non ha un'immagine propria. Meglio un'immagine quadrata con sfondo trasparente.</p>
	<script>
	( function () {
		var frame, pick = document.getElementById( 'zw_logo_pick' ), clear = document.getElementById( 'zw_logo_clear' ),
			input = document.getElementById( 'zw_logo_id' ), preview = document.getElementById( 'zw_logo_preview' );
		if ( ! pick ) { return; }
		pick.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( { title: 'Logo della categoria', multiple: false, library: { type: 'image' } } );
				frame.on( 'select', function () {
					var item = frame.state().get( 'selection' ).first().toJSON();
					input.value = item.id;
					preview.src = ( item.sizes && item.sizes.medium ? item.sizes.medium.url : item.url );
					preview.style.display = '';
				} );
			}
			frame.open();
		} );
		clear.addEventListener( 'click', function () { input.value = ''; preview.style.display = 'none'; } );
	} )();
	</script>
	<?php
}

add_action( 'category_add_form_fields', function () {
	echo '<div class="form-field"><label>Logo per le stories</label>';
	zw_term_logo_field();
	echo '</div>';
} );
add_action( 'category_edit_form_fields', function ( $term ) {
	echo '<tr class="form-field"><th scope="row"><label>Logo per le stories</label></th><td>';
	zw_term_logo_field( $term );
	echo '</td></tr>';
} );

function zw_term_logo_save( $term_id ) {
	if ( ! isset( $_POST['zw_logo_id'] ) || ! current_user_can( 'manage_categories' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$logo_id = absint( $_POST['zw_logo_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $logo_id ) {
		update_term_meta( $term_id, 'zw_logo_id', $logo_id );
	} else {
		delete_term_meta( $term_id, 'zw_logo_id' );
	}
}
add_action( 'created_category', 'zw_term_logo_save' );
add_action( 'edited_category', 'zw_term_logo_save' );
