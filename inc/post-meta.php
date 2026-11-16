<?php
/**
 * Campi dell'articolo (sottotitolo, "In breve") e dell'autore (ruolo, social).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sottotitolo: stesso campo usato da Newspaper (`td_subtitle` dentro
 * `td_post_theme_settings`); in mancanza, il riassunto scritto a mano.
 */
function zw_subtitle( $post = null ) {
	$post     = get_post( $post );
	$settings = get_post_meta( $post->ID, 'td_post_theme_settings', true );
	if ( is_array( $settings ) && ! empty( $settings['td_subtitle'] ) ) {
		return trim( (string) $settings['td_subtitle'] );
	}
	return has_excerpt( $post ) ? trim( wp_strip_all_tags( $post->post_excerpt ) ) : '';
}

/** Punti del riquadro "In breve" (uno per riga nel campo dell'editor). */
function zw_in_breve( $post = null ) {
	$post  = get_post( $post );
	$lines = preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $post->ID, 'zw_in_breve', true ) );
	return array_values( array_filter( array_map( 'trim', $lines ) ) );
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'zw_post_box', 'Zona Wrestling: sottotitolo e "In breve"', 'zw_post_metabox', 'post', 'normal', 'high' );
} );

function zw_post_metabox( $post ) {
	$settings = get_post_meta( $post->ID, 'td_post_theme_settings', true );
	$subtitle = is_array( $settings ) && isset( $settings['td_subtitle'] ) ? $settings['td_subtitle'] : '';
	$newspaper = class_exists( 'td_review' ) || defined( 'TD_THEME_NAME' );
	wp_nonce_field( 'zw_post_save', 'zw_post_nonce' );
	?>
	<?php if ( ! $newspaper ) : ?>
	<p>
		<label for="zw_subtitle"><strong>Sottotitolo</strong></label><br />
		<input type="text" class="widefat" id="zw_subtitle" name="zw_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" />
		<span class="description">Compare sotto il titolo dell'articolo. Se vuoto viene usato il riassunto, se compilato.</span>
	</p>
	<?php endif; ?>
	<p>
		<label for="zw_in_breve"><strong>In breve</strong></label><br />
		<textarea class="widefat" rows="4" id="zw_in_breve" name="zw_in_breve"><?php echo esc_textarea( get_post_meta( $post->ID, 'zw_in_breve', true ) ); ?></textarea>
		<span class="description">Facoltativo. Un punto per riga (massimo cinque): il riquadro compare dopo il primo paragrafo. Lasciando vuoto, il riquadro non viene mostrato.</span>
	</p>
	<?php
}

add_action( 'save_post_post', function ( $post_id ) {
	if ( ! isset( $_POST['zw_post_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zw_post_nonce'] ) ), 'zw_post_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['zw_in_breve'] ) ) {
		$lines = array_slice( array_filter( array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', wp_unslash( $_POST['zw_in_breve'] ) ) ) ), 0, 5 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( $lines ) {
			update_post_meta( $post_id, 'zw_in_breve', implode( "\n", $lines ) );
		} else {
			delete_post_meta( $post_id, 'zw_in_breve' );
		}
	}

	if ( isset( $_POST['zw_subtitle'] ) ) {
		$settings = get_post_meta( $post_id, 'td_post_theme_settings', true );
		$settings = is_array( $settings ) ? $settings : array();
		$subtitle = sanitize_text_field( wp_unslash( $_POST['zw_subtitle'] ) );
		if ( '' !== $subtitle ) {
			$settings['td_subtitle'] = $subtitle;
		} else {
			unset( $settings['td_subtitle'] );
		}
		if ( $settings ) {
			update_post_meta( $post_id, 'td_post_theme_settings', $settings );
		} else {
			delete_post_meta( $post_id, 'td_post_theme_settings' );
		}
	}
}, 9 );

/* ---------------------------------------------------------------------------
 * Autori: ruolo in redazione e profili social (stesse chiavi usate da Newspaper,
 * così i profili già compilati restano validi).
 * ------------------------------------------------------------------------- */

add_filter( 'user_contactmethods', function ( $methods ) {
	$methods['zw_role'] = 'Ruolo in redazione';
	foreach ( array( 'instagram' => 'Instagram (URL)', 'twitter' => 'X / Twitter (URL)', 'facebook' => 'Facebook (URL)', 'youtube' => 'YouTube (URL)', 'tiktok' => 'TikTok (URL)' ) as $key => $label ) {
		if ( ! isset( $methods[ $key ] ) ) {
			$methods[ $key ] = $label;
		}
	}
	return $methods;
} );

/** Profili social dell'autore: [ icona => url ]. */
function zw_author_socials( $user_id ) {
	$out = array();
	foreach ( array( 'instagram' => 'instagram', 'twitter' => 'x', 'facebook' => 'facebook', 'youtube' => 'youtube' ) as $key => $icon ) {
		$value = trim( (string) get_the_author_meta( $key, $user_id ) );
		if ( '' === $value ) {
			continue;
		}
		if ( ! preg_match( '#^https?://#i', $value ) ) {
			// Solo il nome utente: lo trasformiamo nell'indirizzo del profilo.
			$bases = array( 'instagram' => 'https://www.instagram.com/', 'twitter' => 'https://x.com/', 'facebook' => 'https://www.facebook.com/', 'youtube' => 'https://www.youtube.com/@' );
			$value = $bases[ $key ] . ltrim( $value, '@/' );
		}
		$out[ $icon ] = $value;
	}
	return $out;
}
