<?php
/**
 * Modulo di ricerca.
 */

defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
	<label class="screen-reader-text" for="zw-search-field">Cerca</label>
	<input id="zw-search-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Cerca…" class="zw-geo-pill flex-1 min-w-0 px-4 py-2.5 bg-white text-sm border-0 nf-shadow-light" />
	<button type="submit" class="px-5 py-2.5 rounded-full bg-zw-ink hover:bg-zw-red text-white font-sofia font-extrabold text-sm">Cerca</button>
</form>
