<?php
/**
 * Due pulsanti discreti a fine articolo: Google News e fonti preferite.
 */

defined( 'ABSPATH' ) || exit;

$zw_buttons = array_filter( array(
	array( 'url' => zw_opt( 'google_news_url' ), 'html' => '<strong class="font-bold">Segui</strong> Zona Wrestling su <strong class="font-bold">Google News</strong>' ),
	array( 'url' => zw_opt( 'google_pref_url' ), 'html' => '<strong class="font-bold">Aggiungi</strong> Zona Wrestling alle tue fonti preferite su <strong class="font-bold">Google</strong>' ),
), function ( $button ) {
	return ! empty( $button['url'] );
} );
if ( ! $zw_buttons ) {
	return;
}
?>
<div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap sm:justify-center gap-2.5">
	<?php foreach ( $zw_buttons as $zw_button ) : ?>
		<a href="<?php echo esc_url( $zw_button['url'] ); ?>" target="_blank" rel="noopener" class="zw-geo-img inline-flex items-center gap-3 px-4 py-2.5 bg-white hover:bg-[#e9ecf2] text-zw-ink text-[13px] leading-snug transition-colors" style="box-shadow: inset 0 0 0 1px rgba(15,17,23,.06);">
			<svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.45a5.52 5.52 0 0 1-2.39 3.62v3h3.87c2.26-2.09 3.57-5.16 3.57-8.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.91l-3.87-3c-1.07.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.1A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.27 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.27a12 12 0 0 0 0 10.76l4-3.1z"/><path fill="#EA4335" d="M12 4.77c1.76 0 3.34.61 4.59 1.8l3.43-3.43C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.27 6.62l4 3.1C6.22 6.87 8.87 4.77 12 4.77z"/></svg>
			<span><?php echo wp_kses( $zw_button['html'], array( 'strong' => array( 'class' => array() ) ) ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
