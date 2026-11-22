<?php
/**
 * Plugin Name: ZW Deploy (solo sito di prova)
 * Description: Aggiorna tema e plugin caricando uno zip via REST API e scrive i campi personalizzati degli articoli (autenticazione con password per le applicazioni). Da usare SOLO sul sito di prova, mai in produzione.
 * Version: 1.3.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/*
 * Rete di sicurezza: nelle richieste a /zw-deploy/v1/ il tema non viene caricato.
 * Così, se un aggiornamento del tema provoca un errore fatale, si può sempre
 * caricare la versione corretta. Le opzioni salvate non cambiano.
 */
$zw_deploy_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore
if ( false !== strpos( $zw_deploy_uri, '/zw-deploy/v1/' ) || false !== strpos( rawurldecode( $zw_deploy_uri ), 'rest_route=/zw-deploy/v1/' ) ) {
	add_filter( 'template', 'zw_deploy_no_theme' );
	add_filter( 'stylesheet', 'zw_deploy_no_theme' );
}
unset( $zw_deploy_uri );

function zw_deploy_no_theme() {
	return 'zw-deploy-nessun-tema';
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'zw-deploy/v1', '/theme', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => 'zw_deploy_can_theme',
			'callback'            => 'zw_deploy_status',
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => 'zw_deploy_can_theme',
			'callback'            => 'zw_deploy_theme',
		),
	) );
	register_rest_route( 'zw-deploy/v1', '/plugin', array(
		'methods'             => 'POST',
		'permission_callback' => function () {
			return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
		},
		'callback'            => 'zw_deploy_plugin',
	) );
	register_rest_route( 'zw-deploy/v1', '/meta', array(
		'methods'             => 'GET',
		'permission_callback' => function ( WP_REST_Request $request ) {
			return current_user_can( 'edit_post', (int) $request->get_param( 'post' ) );
		},
		'callback'            => function ( WP_REST_Request $request ) {
			return get_post_meta( (int) $request->get_param( 'post' ), sanitize_key( (string) $request->get_param( 'key' ) ), true );
		},
	) );
	register_rest_route( 'zw-deploy/v1', '/meta', array(
		'methods'             => 'POST',
		'permission_callback' => function ( WP_REST_Request $request ) {
			return current_user_can( 'edit_post', (int) $request->get_param( 'post' ) );
		},
		'callback'            => 'zw_deploy_meta',
	) );
} );

function zw_deploy_can_theme() {
	return current_user_can( 'install_themes' ) && current_user_can( 'switch_themes' );
}

function zw_deploy_status() {
	$theme = wp_get_theme( 'zonawrestling' );
	return array(
		'installed' => $theme->exists(),
		'version'   => $theme->exists() ? $theme->get( 'Version' ) : null,
		'active'    => get_option( 'stylesheet' ) === 'zonawrestling',
		'deployed'  => get_option( 'zw_deploy_last', null ),
		'plugin'    => '1.3.0',
		'wp'        => get_bloginfo( 'version' ),
		'php'       => PHP_VERSION,
	);
}

/**
 * Estrae lo zip del corpo della richiesta e sostituisce $dest con la cartella $folder dello zip.
 */
function zw_deploy_install_zip( WP_REST_Request $request, $folder, $dest, $marker ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$body = $request->get_body();
	if ( strlen( $body ) < 100 || 'PK' !== substr( $body, 0, 2 ) ) {
		return new WP_Error( 'zw_bad_zip', 'Il corpo della richiesta non è uno zip.', array( 'status' => 400 ) );
	}
	if ( ! WP_Filesystem( false, false, true ) ) {
		return new WP_Error( 'zw_fs', 'Accesso diretto ai file non disponibile.', array( 'status' => 500 ) );
	}
	global $wp_filesystem;

	$tmp_zip = wp_tempnam( 'zw-deploy.zip' );
	file_put_contents( $tmp_zip, $body );
	$tmp_dir = trailingslashit( get_temp_dir() ) . 'zw-deploy-' . wp_generate_password( 8, false );
	$unzip   = unzip_file( $tmp_zip, $tmp_dir );
	@unlink( $tmp_zip );
	if ( is_wp_error( $unzip ) ) {
		$wp_filesystem->delete( $tmp_dir, true );
		return $unzip;
	}

	$src = trailingslashit( $tmp_dir ) . $folder;
	if ( ! $wp_filesystem->exists( $src . '/' . $marker ) ) {
		$wp_filesystem->delete( $tmp_dir, true );
		return new WP_Error( 'zw_layout', "Lo zip deve contenere la cartella $folder/ con $marker.", array( 'status' => 400 ) );
	}

	if ( $wp_filesystem->exists( $dest ) ) {
		$wp_filesystem->delete( $dest, true );
	}
	$wp_filesystem->mkdir( $dest );
	$copy = copy_dir( $src, $dest );
	$wp_filesystem->delete( $tmp_dir, true );
	if ( function_exists( 'opcache_reset' ) ) {
		@opcache_reset();
	}
	return is_wp_error( $copy ) ? $copy : true;
}

function zw_deploy_theme( WP_REST_Request $request ) {
	$done = zw_deploy_install_zip( $request, 'zonawrestling', trailingslashit( get_theme_root() ) . 'zonawrestling', 'style.css' );
	if ( is_wp_error( $done ) ) {
		return $done;
	}

	wp_clean_themes_cache();
	if ( $request->get_param( 'activate' ) && get_option( 'stylesheet' ) !== 'zonawrestling' ) {
		remove_filter( 'template', 'zw_deploy_no_theme' );
		remove_filter( 'stylesheet', 'zw_deploy_no_theme' );
		switch_theme( 'zonawrestling' );
	}
	// Svuota le cache dei blocchi del tema (transient zw_*).
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_zw\\_%' OR option_name LIKE '\\_transient\\_timeout\\_zw\\_%'" );

	update_option( 'zw_deploy_last', array(
		'time' => current_time( 'mysql' ),
		'ref'  => sanitize_text_field( (string) $request->get_param( 'ref' ) ),
	), false );

	return zw_deploy_status();
}

/** Installa o sostituisce un plugin: lo zip contiene la cartella <slug>/ con <slug>.php. */
function zw_deploy_plugin( WP_REST_Request $request ) {
	$slug = sanitize_key( (string) $request->get_param( 'slug' ) );
	if ( ! $slug ) {
		return new WP_Error( 'zw_slug', 'Parametro slug mancante.', array( 'status' => 400 ) );
	}
	$done = zw_deploy_install_zip( $request, $slug, trailingslashit( WP_PLUGIN_DIR ) . $slug, $slug . '.php' );
	if ( is_wp_error( $done ) ) {
		return $done;
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	wp_clean_plugins_cache();
	$file = $slug . '/' . $slug . '.php';
	if ( $request->get_param( 'activate' ) && ! is_plugin_active( $file ) ) {
		$res = activate_plugin( $file );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
	}
	return array( 'plugin' => $file, 'active' => is_plugin_active( $file ) );
}

/** Scrive campi personalizzati: { "post": 123, "meta": { "chiave": valore, ... } }; null cancella. */
function zw_deploy_meta( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post' );
	$meta    = $request->get_param( 'meta' );
	if ( ! get_post( $post_id ) || ! is_array( $meta ) ) {
		return new WP_Error( 'zw_meta', 'Servono post e meta.', array( 'status' => 400 ) );
	}
	foreach ( $meta as $key => $value ) {
		$key = sanitize_key( $key );
		if ( null === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
	clean_post_cache( $post_id );
	return array( 'post' => $post_id, 'keys' => array_keys( $meta ) );
}
