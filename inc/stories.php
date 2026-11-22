<?php
/**
 * Stories per federazione: un cerchio per ogni categoria scelta, con le ultime notizie.
 * Ogni storia porta all'articolo completo.
 */

defined( 'ABSPATH' ) || exit;

const ZW_STORIES_PER_CAT = 5;

/**
 * @return array[] [ id, slug, label, short, logo, url, latest, slides => [ id, title, summary, image, time, label, url ] ]
 */
function zw_stories_data() {
	$key    = 'zw_stories_' . md5( get_option( 'zw_frag_v', '1' ) . zw_opt( 'stories_cats' ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$stories = array();
	foreach ( zw_opt_list( 'stories_cats' ) as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( ! $term ) {
			continue;
		}
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
				'label'   => $labels['label'] ? $labels['label'] : $term->name,
				'url'     => get_permalink( $post ),
			);
		}
		$logo_id   = (int) get_term_meta( $term->term_id, 'zw_logo_id', true );
		$logo      = $logo_id ? wp_get_attachment_image_src( $logo_id, 'thumbnail' ) : false;
		$stories[] = array(
			'id'     => (int) $term->term_id,
			'slug'   => $term->slug,
			'label'  => $term->name,
			'short'  => mb_strtoupper( mb_substr( $term->name, 0, 7 ) ),
			'logo'   => $logo ? $logo[0] : '',
			'url'    => get_category_link( $term ),
			'latest' => $posts[0]->ID,
			'slides' => $slides,
		);
	}

	set_transient( $key, $stories, 10 * MINUTE_IN_SECONDS );
	return $stories;
}

/* ---------------------------------------------------------------------------
 * Logo della categoria (Articoli → Categorie → Modifica).
 * ------------------------------------------------------------------------- */

function zw_term_logo_field( $term = null ) {
	$logo_id = is_object( $term ) ? (int) get_term_meta( $term->term_id, 'zw_logo_id', true ) : 0;
	$src     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
	wp_enqueue_media();
	?>
	<input type="hidden" name="zw_logo_id" id="zw_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
	<img id="zw_logo_preview" src="<?php echo esc_url( $src ); ?>" alt="" style="width:72px;height:72px;object-fit:contain;background:#12141c;border-radius:50%;padding:6px;<?php echo $src ? '' : 'display:none;'; ?>" />
	<p>
		<button type="button" class="button" id="zw_logo_pick">Scegli il logo</button>
		<button type="button" class="button-link" id="zw_logo_clear">Rimuovi</button>
	</p>
	<p class="description">Logo mostrato nel cerchio delle stories in home. Meglio un'immagine quadrata con sfondo trasparente.</p>
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
					preview.src = ( item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url );
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
