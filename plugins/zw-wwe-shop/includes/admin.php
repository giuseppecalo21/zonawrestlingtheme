<?php
/**
 * Impostazioni → WWE Shop.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_options_page( 'WWE Shop', 'WWE Shop', 'manage_options', 'zw-shop', 'zw_shop_admin_page' );
} );

add_action( 'admin_post_zw_shop_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Permesso negato' );
	}
	check_admin_referer( 'zw_shop_save' );
	$old  = zw_shop_settings();
	$in   = wp_unslash( $_POST );
	$cats = zw_shop_catalogs();
	$new  = array(
		'enabled'       => empty( $in['enabled'] ) ? 0 : 1,
		'account_sid'   => sanitize_text_field( isset( $in['account_sid'] ) ? $in['account_sid'] : '' ),
		// Il token non viene mai rimostrato: campo vuoto = resta quello salvato.
		'auth_token'    => ( isset( $in['auth_token'] ) && '' !== trim( $in['auth_token'] ) ) ? sanitize_text_field( $in['auth_token'] ) : $old['auth_token'],
		'offer_catalog' => isset( $in['offer_catalog'], $cats[ $in['offer_catalog'] ] ) ? $in['offer_catalog'] : $old['offer_catalog'],
		'name_catalog'  => isset( $in['name_catalog'], $cats[ $in['name_catalog'] ] ) ? $in['name_catalog'] : $old['name_catalog'],
		'landing'       => ( isset( $in['landing'] ) && 'en_eur' === $in['landing'] ) ? 'en_eur' : 'catalog',
		'count'         => max( 2, min( 8, (int) ( isset( $in['count'] ) ? $in['count'] : 4 ) ) ),
		'paragraph'     => max( 0, min( 30, (int) ( isset( $in['paragraph'] ) ? $in['paragraph'] : 0 ) ) ),
	);
	foreach ( array( 'aliases', 'ignored', 'generic' ) as $k ) {
		$new[ $k ] = sanitize_textarea_field( isset( $in[ $k ] ) ? $in[ $k ] : '' );
	}
	foreach ( array( 'title_one', 'title_many', 'title_generic', 'disclosure' ) as $k ) {
		$new[ $k ] = sanitize_text_field( isset( $in[ $k ] ) ? $in[ $k ] : '' );
		if ( '' === $new[ $k ] ) {
			$new[ $k ] = zw_shop_defaults()[ $k ];
		}
	}
	update_option( 'zw_shop_settings', $new, false );
	wp_safe_redirect( admin_url( 'options-general.php?page=zw-shop&saved=1' ) );
	exit;
} );

add_action( 'admin_post_zw_shop_sync', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Permesso negato' );
	}
	check_admin_referer( 'zw_shop_sync' );
	$ok = zw_shop_sync_start();
	if ( $ok ) {
		zw_shop_sync_step(); // primo passo subito, il resto con WP-Cron
	}
	wp_safe_redirect( admin_url( 'options-general.php?page=zw-shop&sync=' . ( $ok ? '1' : '0' ) ) );
	exit;
} );

function zw_shop_admin_page() {
	$s     = zw_shop_settings();
	$stats = zw_shop_stats();
	$last  = get_option( 'zw_shop_last_sync' );
	$state = get_option( 'zw_shop_sync' );
	$cats  = zw_shop_catalogs();
	?>
	<div class="wrap">
		<h1>WWE Shop – prodotti negli articoli</h1>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success"><p>Impostazioni salvate.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['sync'] ) ) : ?><div class="notice notice-<?php echo '1' === $_GET['sync'] ? 'info' : 'error'; ?>"><p><?php echo '1' === $_GET['sync'] ? 'Aggiornamento avviato: prosegue in background (qualche minuto).' : 'Inserisci prima Account SID e token di Impact.'; ?></p></div><?php endif; ?>

		<h2>Stato</h2>
		<p>
			<strong><?php echo esc_html( number_format_i18n( $stats['with_name'] ) ); ?></strong> prodotti pronti (con nome inglese) su <?php echo esc_html( number_format_i18n( $stats['products'] ) ); ?>, <?php echo esc_html( number_format_i18n( $stats['wrestlers'] ) ); ?> wrestler/categorie.<br>
			Ultimo aggiornamento: <?php echo $last ? esc_html( wp_date( 'd/m/Y H:i', $last['time'] ) . ' (' . $last['count'] . ' prodotti, ' . $last['removed'] . ' rimossi)' ) : 'mai'; ?>.
			<?php if ( is_array( $state ) && isset( $state['phase'] ) && 'done' !== $state['phase'] ) : ?>
				<br><em>Aggiornamento in corso: fase «<?php echo esc_html( $state['phase'] ); ?>», <?php echo (int) $state['count']; ?> prodotti finora.</em>
			<?php endif; ?>
			<br>Prossimo aggiornamento automatico: <?php $n = wp_next_scheduled( 'zw_shop_daily_sync' ); echo $n ? esc_html( wp_date( 'd/m/Y H:i', $n ) ) : '—'; ?>.
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'zw_shop_sync' ); ?><input type="hidden" name="action" value="zw_shop_sync">
			<?php submit_button( 'Aggiorna il catalogo adesso', 'secondary', 'submit', false ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'zw_shop_save' ); ?><input type="hidden" name="action" value="zw_shop_save">
			<h2>Impostazioni</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Riquadro negli articoli</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Attivo</label></td></tr>
				<tr><th scope="row"><label for="account_sid">Impact Account SID</label></th><td><input type="text" id="account_sid" name="account_sid" value="<?php echo esc_attr( $s['account_sid'] ); ?>" class="regular-text" autocomplete="off"></td></tr>
				<tr><th scope="row"><label for="auth_token">Impact Auth Token</label></th><td><input type="password" id="auth_token" name="auth_token" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $s['auth_token'] ? 'salvato: lascia vuoto per non cambiarlo' : ''; ?>"><p class="description">Impact → Settings → API: token con permesso di lettura su <em>Catalogs</em>.</p></td></tr>
				<tr><th scope="row"><label for="offer_catalog">Prezzi, link e foto</label></th><td><select id="offer_catalog" name="offer_catalog"><?php foreach ( $cats as $id => $label ) : ?><option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['offer_catalog'], $id ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><p class="description">Non c'è un catalogo italiano né uno inglese in euro: da qui arrivano i prezzi mostrati nel riquadro.</p></td></tr>
				<tr><th scope="row"><label for="name_catalog">Nomi dei prodotti</label></th><td><select id="name_catalog" name="name_catalog"><?php foreach ( $cats as $id => $label ) : ?><option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['name_catalog'], $id ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
				<tr><th scope="row">Pagina di destinazione</th><td>
					<label><input type="radio" name="landing" value="catalog" <?php checked( $s['landing'], 'catalog' ); ?>> Link del catalogo prezzi (EuroShop nella lingua di quel catalogo)</label><br>
					<label><input type="radio" name="landing" value="en_eur" <?php checked( $s['landing'], 'en_eur' ); ?>> EuroShop in inglese con prezzi in euro (consigliato)</label>
				</td></tr>
				<tr><th scope="row"><label for="count">Prodotti nel riquadro</label></th><td><input type="number" id="count" name="count" min="2" max="8" value="<?php echo (int) $s['count']; ?>" class="small-text"></td></tr>
				<tr><th scope="row"><label for="paragraph">Posizione</label></th><td>dopo il paragrafo <input type="number" id="paragraph" name="paragraph" min="0" max="30" value="<?php echo (int) $s['paragraph']; ?>" class="small-text"><p class="description">0 = a metà articolo. Negli articoli con meno di 3 paragrafi il riquadro va in fondo.</p></td></tr>
				<tr><th scope="row"><label for="aliases">Corrispondenze tag → wrestler</label></th><td><textarea id="aliases" name="aliases" rows="8" class="large-text code"><?php echo esc_textarea( $s['aliases'] ); ?></textarea><p class="description">Una per riga: <code>tag = nome nel catalogo</code>. Servono quando il tag del sito è scritto diversamente (es. <code>undertaker = The Undertaker</code>).</p></td></tr>
				<tr><th scope="row"><label for="ignored">Da ignorare</label></th><td><textarea id="ignored" name="ignored" rows="3" class="large-text code"><?php echo esc_textarea( $s['ignored'] ); ?></textarea><p class="description">Tag o categorie del catalogo da non usare mai, uno per riga.</p></td></tr>
				<tr><th scope="row"><label for="generic">Show ed eventi</label></th><td><textarea id="generic" name="generic" rows="6" class="large-text code"><?php echo esc_textarea( $s['generic'] ); ?></textarea><p class="description">Usati solo se l'articolo non nomina nessun wrestler presente nel catalogo.</p></td></tr>
				<tr><th scope="row"><label for="title_one">Titolo (un wrestler)</label></th><td><input type="text" id="title_one" name="title_one" value="<?php echo esc_attr( $s['title_one'] ); ?>" class="regular-text"><p class="description"><code>%s</code> = nome del wrestler.</p></td></tr>
				<tr><th scope="row"><label for="title_many">Titolo (più wrestler)</label></th><td><input type="text" id="title_many" name="title_many" value="<?php echo esc_attr( $s['title_many'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="title_generic">Titolo (show/eventi)</label></th><td><input type="text" id="title_generic" name="title_generic" value="<?php echo esc_attr( $s['title_generic'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="disclosure">Avviso affiliazione</label></th><td><input type="text" id="disclosure" name="disclosure" value="<?php echo esc_attr( $s['disclosure'] ); ?>" class="large-text"></td></tr>
			</table>
			<?php submit_button( 'Salva' ); ?>
		</form>

		<details><summary><strong>Wrestler presenti nel catalogo</strong> (nome da usare nelle corrispondenze)</summary>
			<p><?php echo esc_html( implode( ' · ', array_map( function ( $r ) { return $r['wrestler'] . ' (' . $r['n'] . ')'; }, zw_shop_wrestler_list() ) ) ); ?></p>
		</details>
		<details><summary><strong>Registro</strong></summary><pre><?php echo esc_html( implode( "\n", array_reverse( (array) get_option( 'zw_shop_log', array() ) ) ) ); ?></pre></details>
	</div>
	<?php
}
