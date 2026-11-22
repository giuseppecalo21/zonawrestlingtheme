<?php
/**
 * Plugin Name: ZW Deploy (solo sito di prova)
 * Description: Permette di aggiornare il tema Zona Wrestling caricando uno zip via REST API (autenticazione con password per le applicazioni). Da usare SOLO sul sito di prova, mai in produzione.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'zw-deploy/v1', '/theme', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => 'zw_deploy_can',
			'callback'            => 'zw_deploy_status',
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => 'zw_deploy_can',
			'callback'            => 'zw_deploy_upload',
		),
	) );
} );

function zw_deploy_can() {
	return current_user_can( 'install_themes' ) && current_user_can( 'switch_themes' );
}

function zw_deploy_status() {
	$theme = wp_get_theme( 'zonawrestling' );
	return array(
		'installed' => $theme->exists(),
		'version'   => $theme->exists() ? $theme->get( 'Version' ) : null,
		'active'    => get_stylesheet() === 'zonawrestling',
		'deployed'  => get_option( 'zw_deploy_last', null ),
		'wp'        => get_bloginfo( 'version' ),
		'php'       => PHP_VERSION,
	);
}

function zw_deploy_upload( WP_REST_Request $request ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$body = $request->get_body();
	if ( strlen( $body ) < 100 || 'PK' !== substr( $body, 0, 2 ) ) {
		return new WP_Error( 'zw_bad_zip', 'Il corpo della richiesta non è uno zip.', array( 'status' => 400 ) );
	}

	if ( ! WP_Filesystem( false, false, true ) ) {
		return new WP_Error( 'zw_fs', 'Accesso diretto ai file non disponibile.', array( 'status' => 500 ) );
	}
	global $wp_filesystem;

	$tmp_zip = wp_tempnam( 'zw-theme.zip' );
	file_put_contents( $tmp_zip, $body );

	$tmp_dir = trailingslashit( get_temp_dir() ) . 'zw-deploy-' . wp_generate_password( 8, false );
	$unzip   = unzip_file( $tmp_zip, $tmp_dir );
	@unlink( $tmp_zip );
	if ( is_wp_error( $unzip ) ) {
		$wp_filesystem->delete( $tmp_dir, true );
		return $unzip;
	}

	// Lo zip contiene la cartella "zonawrestling/".
	$src = trailingslashit( $tmp_dir ) . 'zonawrestling';
	if ( ! $wp_filesystem->exists( $src . '/style.css' ) ) {
		$wp_filesystem->delete( $tmp_dir, true );
		return new WP_Error( 'zw_layout', 'Lo zip deve contenere la cartella zonawrestling/ con style.css.', array( 'status' => 400 ) );
	}

	$dest = trailingslashit( get_theme_root() ) . 'zonawrestling';
	if ( $wp_filesystem->exists( $dest ) ) {
		$wp_filesystem->delete( $dest, true );
	}
	$wp_filesystem->mkdir( $dest );
	$copy = copy_dir( $src, $dest );
	$wp_filesystem->delete( $tmp_dir, true );
	if ( is_wp_error( $copy ) ) {
		return $copy;
	}

	wp_clean_themes_cache();
	if ( $request->get_param( 'activate' ) && get_stylesheet() !== 'zonawrestling' ) {
		switch_theme( 'zonawrestling' );
	}
	if ( function_exists( 'opcache_reset' ) ) {
		@opcache_reset();
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
