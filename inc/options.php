<?php
/**
 * Impostazioni del tema: Aspetto → Personalizza → Zona Wrestling.
 * I valori predefiniti rispecchiano la struttura attuale di zonawrestling.net.
 */

defined( 'ABSPATH' ) || exit;

function zw_option_defaults() {
	return array(
		// Slug delle categorie usate dalle sezioni.
		'cat_featured'   => 'featured',
		'cat_results'    => 'risultati',
		'cat_analysis'   => 'show-analysis',
		'cat_editorials' => 'editoriali',
		'cat_gallery'    => 'gallery',
		'cat_video'      => 'videonews',
		'cat_podcast'    => 'podcast',
		// Stories e filtri del feed se in Aspetto → Stories in home non c'è nessun blocco.
		'stories_cats'   => 'wwe,aew,njpw,tna,stardom,cmll,aaa,italia',
		// Categorie considerate "federazione" per l'etichetta sulle card.
		'fed_cats'       => 'wwe,aew,njpw,tna,roh,stardom,cmll,aaa,mlw,nwa,gcw,noah,progress,revpro-news,italia,news-italia',
		// Categorie mai usate come etichetta.
		'hidden_cats'    => 'featured,senza-categoria,news,altro',
		'live_prefix'    => 'DIRETTA LIVE',
		'feed_per_page'  => 10,
		// Social.
		'social_facebook'  => '',
		'social_instagram' => 'https://www.instagram.com/zona_wrestling/',
		'social_x'         => 'https://x.com/Zona_Wrestling',
		'social_youtube'   => 'https://www.youtube.com/user/zonawrestlingnet',
		'social_telegram'  => 'https://t.me/zonawrestling',
		'social_threads'   => 'https://www.threads.net/@zona_wrestling',
		// Google.
		'google_news_url'  => 'https://news.google.com/publications/CAAqLQgKIidDQklTRndnTWFoTUtFWHB2Ym1GM2NtVnpkR3hwYm1jdWJtVjBLQUFQAQ?ceid=IT:it&oc=3',
		'google_pref_url'  => 'https://www.google.com/preferences/source?q=zonawrestling.net',
		'footer_tagline'   => 'Il punto di riferimento italiano sul wrestling professionistico dal 2001.',
		'views_enabled'    => 1,
	);
}

function zw_opt( $key ) {
	$defaults = zw_option_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'zw_' . $key, $default );
}

/** Elenco di slug (da un'opzione "a, b, c"). */
function zw_opt_list( $key ) {
	return array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) zw_opt( $key ) ) ) ) );
}

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_panel( 'zw_panel', array( 'title' => 'Zona Wrestling', 'priority' => 30 ) );

	$sections = array(
		'zw_sec_cats'   => array(
			'title'  => 'Categorie delle sezioni',
			'desc'   => 'Inserisci lo slug della categoria usata da ogni sezione.',
			'fields' => array(
				'cat_featured'   => 'Primo piano (slider in home)',
				'cat_results'    => 'Risultati',
				'cat_analysis'   => 'Analisi degli show',
				'cat_editorials' => 'Editoriali',
				'cat_gallery'    => 'Gallery',
				'cat_video'      => 'Video',
				'cat_podcast'    => 'Podcast',
				'stories_cats'   => 'Stories e filtri del feed (slug separati da virgola). Usato solo finché non crei dei blocchi in Aspetto → Stories in home',
				'fed_cats'       => 'Categorie "federazione" per le etichette',
				'hidden_cats'    => 'Categorie da non mostrare mai come etichetta',
				'live_prefix'    => 'Prefisso del titolo che identifica una diretta',
				'feed_per_page'  => 'Notizie per pagina nel feed della home',
			),
		),
		'zw_sec_social' => array(
			'title'  => 'Social e Google',
			'desc'   => 'Lascia vuoto un campo per nascondere il relativo pulsante.',
			'fields' => array(
				'social_facebook'  => 'Facebook',
				'social_instagram' => 'Instagram',
				'social_x'         => 'X',
				'social_youtube'   => 'YouTube',
				'social_telegram'  => 'Telegram',
				'social_threads'   => 'Threads',
				'google_news_url'  => 'Link "Seguici su Google News"',
				'google_pref_url'  => 'Link "Aggiungi alle fonti preferite su Google"',
				'footer_tagline'   => 'Frase nel footer',
			),
		),
	);

	foreach ( $sections as $section_id => $section ) {
		$wp_customize->add_section( $section_id, array(
			'title'       => $section['title'],
			'description' => $section['desc'],
			'panel'       => 'zw_panel',
		) );
		foreach ( $section['fields'] as $key => $label ) {
			$is_url = 0 === strpos( $key, 'social_' ) || 0 === strpos( $key, 'google_' );
			$wp_customize->add_setting( 'zw_' . $key, array(
				'default'           => zw_option_defaults()[ $key ],
				'sanitize_callback' => $is_url ? 'esc_url_raw' : 'sanitize_text_field',
			) );
			$wp_customize->add_control( 'zw_' . $key, array(
				'label'   => $label,
				'section' => $section_id,
				'type'    => $is_url ? 'url' : 'text',
			) );
		}
	}

	$wp_customize->add_setting( 'zw_views_enabled', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'zw_views_enabled', array(
		'label'       => 'Conta le letture per "Le più lette di oggi"',
		'description' => 'Contatore interno, leggero e compatibile con la cache delle pagine.',
		'section'     => 'zw_sec_cats',
		'type'        => 'checkbox',
	) );
} );

// Cambiando le impostazioni, i blocchi in cache vanno rigenerati.
add_action( 'customize_save_after', function () {
	if ( function_exists( 'zw_flush_fragments' ) ) {
		zw_flush_fragments();
	}
} );
