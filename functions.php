<?php
/**
 * Tema Zona Wrestling.
 */

defined( 'ABSPATH' ) || exit;

define( 'ZW_VERSION', '1.0.0' );
define( 'ZW_DIR', get_template_directory() );
define( 'ZW_URI', get_template_directory_uri() );

require ZW_DIR . '/inc/options.php';     // Impostazioni (Personalizza) e valori predefiniti.
require ZW_DIR . '/inc/setup.php';       // Supporti del tema, menu, pulizia head, script e stili.
require ZW_DIR . '/inc/helpers.php';     // Immagini, date, categorie, icone, cache dei blocchi.
require ZW_DIR . '/inc/reviews.php';     // Valutazioni compatibili con tagDiv Newspaper.
require ZW_DIR . '/inc/events.php';      // Eventi (plugin ZW Events Calendar).
require ZW_DIR . '/inc/stories.php';     // Stories per federazione.
require ZW_DIR . '/inc/views.php';       // Contatore "Le più lette di oggi".
require ZW_DIR . '/inc/feed.php';        // Feed notizie della home (filtri e "carica altre").
require ZW_DIR . '/inc/post-meta.php';   // Campi dell'articolo e dell'autore.
require ZW_DIR . '/inc/content.php';     // Filtri sul testo dell'articolo, breadcrumb, podcast.
require ZW_DIR . '/inc/video.php';       // Pagina dei video: player grande, dati del video, correlati.
require ZW_DIR . '/inc/seo.php';         // Meta description e anteprime social (se manca un plugin SEO).
