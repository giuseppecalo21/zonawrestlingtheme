<?php
/**
 * Dati strutturati (JSON-LD) prima stampati da Newspaper, ricavati da ciò che il sito
 * ha già: nessun campo in più per la redazione.
 *
 * - Home: NewsMediaOrganization (la testata) e WebSite.
 * - Articoli: NewsArticle, solo se nessun altro lo stampa già (per esempio il plugin SEO del sito).
 * - Pagine autore: ProfilePage con la Person (ruolo, biografia, profili social).
 *
 * Con Yoast, Rank Math, SEOPress o All in One SEO attivi il tema si fa da parte.
 * Ogni blocco si può spegnere o modificare con il filtro 'zw_schema_graph'.
 */

defined( 'ABSPATH' ) || exit;

function zw_schema_org_id() {
	return home_url( '/#organization' );
}

function zw_schema_website_id() {
	return home_url( '/#website' );
}

/** La testata. */
function zw_schema_organization() {
	$org = array(
		'@type'        => 'NewsMediaOrganization',
		'@id'          => zw_schema_org_id(),
		'name'         => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
		'url'          => home_url( '/' ),
		'foundingDate' => '2001',
	);
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;
	if ( ! $logo && has_site_icon() ) {
		$logo = array( get_site_icon_url( 512 ), 512, 512 );
	}
	if ( $logo ) {
		$org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo[0], 'width' => (int) $logo[1], 'height' => (int) $logo[2] );
	}
	$same_as = array_values( wp_list_pluck( zw_social_links(), 'url' ) );
	if ( $same_as ) {
		$org['sameAs'] = $same_as;
	}
	return $org;
}

/** Autore come Person (con @id stabile, riusato dagli articoli). */
function zw_schema_person( $user_id, $full = false ) {
	$url    = get_author_posts_url( $user_id );
	$person = array(
		'@type' => 'Person',
		'@id'   => $url . '#person',
		'name'  => get_the_author_meta( 'display_name', $user_id ),
		'url'   => $url,
	);
	if ( $full ) {
		$role = trim( (string) get_the_author_meta( 'zw_role', $user_id ) );
		$bio  = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', $user_id ) ) );
		if ( $role ) {
			$person['jobTitle'] = $role;
		}
		if ( $bio ) {
			$person['description'] = $bio;
		}
		$avatar = get_avatar_url( $user_id, array( 'size' => 256 ) );
		if ( $avatar ) {
			$person['image'] = $avatar;
		}
		$same_as = array_values( zw_author_socials( $user_id ) );
		$website = trim( (string) get_the_author_meta( 'user_url', $user_id ) );
		if ( $website ) {
			$same_as[] = $website;
		}
		if ( $same_as ) {
			$person['sameAs'] = array_values( array_unique( $same_as ) );
		}
		$person['worksFor'] = array( '@id' => zw_schema_org_id() );
	}
	return $person;
}

function zw_schema_article( $post ) {
	$post     = get_post( $post );
	$author   = (int) $post->post_author;
	$desc     = zw_subtitle( $post );
	$desc     = zw_seo_snippet( $desc ? $desc : ( has_excerpt( $post ) ? $post->post_excerpt : $post->post_content ), 300 );
	$labels   = zw_post_labels( $post );
	$article  = array(
		'@type'            => 'NewsArticle',
		'@id'              => get_permalink( $post ) . '#article',
		'headline'         => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
		'description'      => $desc,
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'mainEntityOfPage' => get_permalink( $post ),
		'url'              => get_permalink( $post ),
		'inLanguage'       => 'it-IT',
		'author'           => array( zw_schema_person( $author ) ),
		'publisher'        => array( '@id' => zw_schema_org_id() ),
		'isPartOf'         => array( '@id' => zw_schema_website_id() ),
	);
	$image = zw_seo_image( get_post_thumbnail_id( $post ) );
	if ( $image ) {
		$article['image'] = array( '@type' => 'ImageObject', 'url' => $image[0], 'width' => $image[1], 'height' => $image[2] );
	}
	if ( $labels['label'] ) {
		$article['articleSection'] = $labels['label'];
	}
	return $article;
}

/** Il JSON-LD della pagina corrente: elenco di nodi (vuoto se non serve nulla). */
function zw_schema_graph( $head ) {
	$has   = function ( $type ) use ( $head ) {
		return (bool) preg_match( '/"@type"\s*:\s*(?:\[[^\]]*)?"' . preg_quote( $type, '/' ) . '"/', $head );
	};
	$graph = array();

	if ( is_front_page() ) {
		if ( ! $has( 'NewsMediaOrganization' ) && ! $has( 'Organization' ) ) {
			$graph[] = zw_schema_organization();
		}
		if ( ! $has( 'WebSite' ) ) {
			$graph[] = array(
				'@type'      => 'WebSite',
				'@id'        => zw_schema_website_id(),
				'url'        => home_url( '/' ),
				'name'       => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
				'inLanguage' => 'it-IT',
				'publisher'  => array( '@id' => zw_schema_org_id() ),
			);
		}
	} elseif ( is_singular( 'post' ) ) {
		if ( ! $has( 'NewsArticle' ) && ! $has( 'Article' ) && ! $has( 'BlogPosting' ) ) {
			$graph[] = zw_schema_article( get_queried_object() );
			$graph[] = zw_schema_organization(); // Il publisher a cui rimanda l'articolo.
		}
	} elseif ( is_author() ) {
		if ( ! $has( 'ProfilePage' ) ) {
			$user    = get_queried_object();
			$graph[] = array(
				'@type'      => 'ProfilePage',
				'url'        => get_author_posts_url( $user->ID ),
				'mainEntity' => zw_schema_person( $user->ID, true ),
				'isPartOf'   => array( '@id' => zw_schema_website_id() ),
			);
			$graph[] = zw_schema_organization();
		}
	}
	return apply_filters( 'zw_schema_graph', $graph, $head );
}

add_action( 'zw_head_missing', function ( $head ) {
	if ( zw_seo_plugin_active() ) {
		return;
	}
	$graph = zw_schema_graph( $head );
	if ( ! $graph ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array_values( $graph ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 20 );
