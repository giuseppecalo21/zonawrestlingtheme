<?php
/**
 * Valutazioni delle analisi, compatibili con tagDiv Newspaper.
 *
 * Newspaper salva la valutazione nel campo personalizzato `td_post_theme_settings`
 * (array serializzato) con queste chiavi:
 *   has_review        'rate_stars' | 'rate_percent' | 'rate_point'
 *   p_review_stars    righe [ 'desc' => ..., 'rate' => 0.5–5 ]
 *   p_review_percents righe [ 'desc' => ..., 'rate' => 0–100 ]
 *   p_review_points   righe [ 'desc' => ..., 'rate' => 0–10 ]
 *   review            testo del sommario
 * più il campo `td_review_key` con il voto riportato in quinti (usato per gli ordinamenti).
 *
 * Il tema legge e scrive gli stessi campi: nessuna migrazione, nessun dato perso,
 * e tornando a Newspaper le valutazioni sarebbero ancora al loro posto.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Impostazioni tagDiv del post (`td_post_theme_settings`) come array.
 * Regge anche i valori salvati due volte serializzati da vecchie importazioni.
 */
function zw_td_settings( $post_id ) {
	$settings = get_post_meta( (int) $post_id, 'td_post_theme_settings', true );
	if ( is_string( $settings ) && '' !== $settings ) {
		$settings = maybe_unserialize( $settings );
	}
	return is_array( $settings ) ? $settings : array();
}

function zw_review_types() {
	return array(
		'rate_stars'   => array( 'label' => 'Stelle (da 0,5 a 5)', 'field' => 'p_review_stars', 'max' => 5, 'step' => 0.5 ),
		'rate_percent' => array( 'label' => 'Percentuali (da 0 a 100)', 'field' => 'p_review_percents', 'max' => 100, 'step' => 1 ),
		'rate_point'   => array( 'label' => 'Punti (da 0 a 10)', 'field' => 'p_review_points', 'max' => 10, 'step' => 0.1 ),
	);
}

/**
 * Valutazione di un post, oppure null.
 *
 * @return array|null [ type, rows => [ [desc, rate] ], total, max, stars (0–5), summary ]
 */
function zw_review( $post_id = null ) {
	static $cache = array();
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( array_key_exists( $post_id, $cache ) ) {
		return $cache[ $post_id ];
	}
	$cache[ $post_id ] = null;

	$settings = zw_td_settings( $post_id );
	$types    = zw_review_types();
	if ( empty( $settings['has_review'] ) || ! isset( $types[ $settings['has_review'] ] ) ) {
		return null;
	}

	$type = $types[ $settings['has_review'] ];
	$raw  = isset( $settings[ $type['field'] ] ) && is_array( $settings[ $type['field'] ] ) ? $settings[ $type['field'] ] : array();
	$rows = array();
	$sum  = 0;
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['rate'] ) || '' === $row['rate'] ) {
			continue;
		}
		$rate   = (float) str_replace( ',', '.', (string) $row['rate'] );
		$rows[] = array( 'desc' => isset( $row['desc'] ) ? (string) $row['desc'] : '', 'rate' => $rate );
		$sum   += $rate;
	}
	if ( ! $rows ) {
		return null;
	}

	// Stesso calcolo di Newspaper: media arrotondata a un decimale.
	$total = round( $sum / count( $rows ), 1 );

	$cache[ $post_id ] = array(
		'type'    => $settings['has_review'],
		'rows'    => $rows,
		'total'   => $total,
		'max'     => $type['max'],
		'stars'   => $total / $type['max'] * 5,
		'summary' => isset( $settings['review'] ) ? (string) $settings['review'] : '',
	);
	return $cache[ $post_id ];
}

/** Voto in quinti (0–5) o null se il post non ha una valutazione. */
function zw_review_stars( $post_id = null ) {
	$review = zw_review( $post_id );
	return $review ? $review['stars'] : null;
}

function zw_review_format( $value, $type ) {
	$text = str_replace( '.', ',', (string) ( floor( $value ) == $value ? (int) $value : $value ) );
	return 'rate_percent' === $type ? $text . '%' : $text;
}

/** Riquadro della valutazione in fondo all'analisi. */
function zw_review_box( $post_id = null ) {
	$review = zw_review( $post_id );
	if ( ! $review ) {
		return '';
	}
	ob_start();
	?>
	<section class="zw-review not-prose my-9 rounded-[22px] bg-zw-velvet text-white nf-shadow-dark overflow-hidden relative" aria-label="Valutazione">
		<div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse 60% 90% at 100% 0%, rgba(215,24,42,.32), transparent 65%);"></div>
		<div class="relative p-5 sm:p-7">
			<div class="flex items-center gap-2.5 mb-5">
				<span class="w-2.5 h-6 rounded-full bg-zw-red inline-block nf-red-glow"></span>
				<h2 class="font-sofia font-black text-xl text-white tracking-tight">La valutazione</h2>
			</div>
			<div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
				<div class="sm:col-span-8 space-y-4">
					<?php foreach ( $review['rows'] as $row ) : ?>
						<div>
							<div class="flex items-baseline justify-between gap-3 mb-1.5">
								<span class="text-sm text-white/85 font-medium"><?php echo esc_html( $row['desc'] ); ?></span>
								<span class="font-sofia font-black text-base text-white"><?php echo esc_html( zw_review_format( $row['rate'], $review['type'] ) ); ?></span>
							</div>
							<div class="h-2 rounded-full bg-white/10 overflow-hidden">
								<div class="h-full rounded-full bg-zw-red" style="width: <?php echo esc_attr( max( 0, min( 100, round( $row['rate'] / $review['max'] * 100 ) ) ) ); ?>%;"></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="sm:col-span-4 flex sm:flex-col items-center justify-center gap-4 sm:gap-2 rounded-[16px] bg-white/[0.06] p-5 text-center">
					<span class="font-sofia-display font-black text-6xl text-white leading-none"><?php echo esc_html( zw_review_format( $review['total'], $review['type'] ) ); ?></span>
					<span>
						<span class="block text-white"><?php echo zw_stars( $review['stars'], 'w-5 h-5' ); // phpcs:ignore ?></span>
						<span class="block text-[11px] font-sofia font-extrabold uppercase tracking-wider text-zw-redSoft mt-1.5">Punteggio</span>
					</span>
				</div>
			</div>
			<?php if ( '' !== trim( $review['summary'] ) ) : ?>
				<div class="mt-6 pt-5 border-t border-white/10">
					<span class="block text-[11px] font-sofia font-extrabold uppercase tracking-wider text-zw-redSoft mb-1.5">Considerazioni finali</span>
					<p class="text-sm sm:text-[15px] text-white/80 leading-relaxed m-0"><?php echo wp_kses_post( nl2br( $review['summary'] ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * Riquadro di modifica nell'editor (sostituisce quello di Newspaper).
 * ------------------------------------------------------------------------- */

add_action( 'add_meta_boxes', function () {
	// Se Newspaper è attivo c'è già il suo riquadro: evitiamo doppioni.
	if ( class_exists( 'td_review' ) || defined( 'TD_THEME_NAME' ) ) {
		return;
	}
	add_meta_box( 'zw_review_box', 'Valutazione (stelline)', 'zw_review_metabox', 'post', 'normal', 'default' );
} );

function zw_review_metabox( $post ) {
	$settings = zw_td_settings( $post->ID );
	$current  = isset( $settings['has_review'] ) ? $settings['has_review'] : '';
	$types    = zw_review_types();
	$field    = isset( $types[ $current ] ) ? $types[ $current ]['field'] : 'p_review_points';
	$rows     = isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ? array_values( $settings[ $field ] ) : array();
	$rows     = array_merge( $rows, array_fill( 0, 3, array( 'desc' => '', 'rate' => '' ) ) );
	wp_nonce_field( 'zw_review_save', 'zw_review_nonce' );
	?>
	<p>
		<label for="zw_review_type"><strong>Tipo di valutazione</strong></label><br />
		<select name="zw_review[type]" id="zw_review_type">
			<option value="">Nessuna valutazione</option>
			<?php foreach ( $types as $key => $type ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $type['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<table class="widefat striped" style="max-width:720px">
		<thead><tr><th>Criterio (es. "Qualità dei match")</th><th style="width:140px">Voto</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $i => $row ) : ?>
			<tr>
				<td><input type="text" class="widefat" name="zw_review[rows][<?php echo (int) $i; ?>][desc]" value="<?php echo esc_attr( isset( $row['desc'] ) ? $row['desc'] : '' ); ?>" /></td>
				<td><input type="number" step="0.1" min="0" max="100" class="widefat" name="zw_review[rows][<?php echo (int) $i; ?>][rate]" value="<?php echo esc_attr( isset( $row['rate'] ) ? $row['rate'] : '' ); ?>" /></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">Il voto finale è la media dei criteri. Per aggiungere altre righe salva l'articolo: ne compaiono sempre tre vuote. Una riga senza voto viene ignorata.</p>
	<p>
		<label for="zw_review_summary"><strong>Considerazioni finali</strong></label> <span class="description">(è il "Sommario" della recensione di Newspaper: stesso campo)</span><br />
		<textarea name="zw_review[summary]" id="zw_review_summary" rows="4" class="widefat" style="max-width:720px"><?php echo esc_textarea( isset( $settings['review'] ) ? $settings['review'] : '' ); ?></textarea>
	</p>
	<?php
}

add_action( 'save_post_post', function ( $post_id ) {
	if ( ! isset( $_POST['zw_review_nonce'], $_POST['zw_review'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zw_review_nonce'] ) ), 'zw_review_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$input    = wp_unslash( $_POST['zw_review'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$types    = zw_review_types();
	$type     = isset( $input['type'] ) && isset( $types[ $input['type'] ] ) ? $input['type'] : '';
	$settings = zw_td_settings( $post_id );

	if ( ! $type ) {
		// Tolta la valutazione: resta il resto delle impostazioni tagDiv del post.
		unset( $settings['has_review'] );
		delete_post_meta( $post_id, 'td_review_key' );
	} else {
		$rows = array();
		$sum  = 0;
		foreach ( isset( $input['rows'] ) && is_array( $input['rows'] ) ? $input['rows'] : array() as $row ) {
			$rate = isset( $row['rate'] ) ? str_replace( ',', '.', trim( (string) $row['rate'] ) ) : '';
			if ( '' === $rate || ! is_numeric( $rate ) ) {
				continue;
			}
			$rate   = max( 0, min( $types[ $type ]['max'], (float) $rate ) );
			$rows[] = array( 'desc' => sanitize_text_field( isset( $row['desc'] ) ? $row['desc'] : '' ), 'rate' => (string) $rate );
			$sum   += $rate;
		}
		$settings['has_review']             = $type;
		$settings[ $types[ $type ]['field'] ] = $rows;
		$settings['review']                 = sanitize_textarea_field( isset( $input['summary'] ) ? $input['summary'] : '' );

		if ( $rows ) {
			update_post_meta( $post_id, 'td_review_key', round( ( $sum / count( $rows ) ) / $types[ $type ]['max'] * 5, 2 ) );
		} else {
			delete_post_meta( $post_id, 'td_review_key' );
		}
	}

	if ( $settings ) {
		update_post_meta( $post_id, 'td_post_theme_settings', $settings );
	} else {
		delete_post_meta( $post_id, 'td_post_theme_settings' );
	}
} );
