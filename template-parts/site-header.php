<?php
/**
 * Barra di navigazione "a vetro".
 */

defined( 'ABSPATH' ) || exit;

$zw_items  = zw_menu_items();
$zw_social = zw_social_links();
?>
<header class="relative z-40 max-w-[1380px] mx-auto px-4 sm:px-6 pt-3 sm:pt-4">
	<div class="bg-white/[0.05] backdrop-blur-xl rounded-full pl-3 pr-2.5 py-2 flex items-center justify-between gap-4">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3 shrink-0" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — Home">
			<?php if ( has_custom_logo() ) : ?>
				<?php echo zw_logo( 'h-7 sm:h-10 w-auto max-w-[180px] sm:max-w-[240px] object-contain' ); // phpcs:ignore ?>
			<?php else : ?>
				<span class="w-10 h-10 rounded-2xl bg-zw-red flex items-center justify-center font-sofia font-black text-xl text-white nf-red-glow">ZW</span>
				<span class="font-sofia font-black text-xl text-white tracking-tight hidden sm:inline">ZONA<span class="text-zw-red">WRESTLING</span><span class="text-white/45 text-sm">.NET</span></span>
			<?php endif; ?>
		</a>

		<nav class="zw-nav hidden lg:flex items-center gap-0.5 min-w-0" aria-label="Menu principale">
			<?php foreach ( $zw_items as $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" <?php echo $item['current'] ? 'aria-current="page"' : ''; ?> class="px-3.5 py-2 rounded-full text-xs font-sofia font-bold tracking-wide whitespace-nowrap transition-all <?php echo $item['current'] ? 'text-white bg-white/10' : 'text-white/75 hover:text-white hover:bg-white/10'; ?>"><?php echo esc_html( $item['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="flex items-center gap-2">
			<button type="button" data-zw-open="searchModal" class="h-10 px-4 rounded-full bg-white/[0.07] hover:bg-white/[0.14] text-white/85 hover:text-white flex items-center gap-2 text-xs font-sofia font-bold tracking-wide transition-all" aria-haspopup="dialog">
				<?php echo zw_icon( 'search', 'w-4 h-4 text-zw-redSoft' ); // phpcs:ignore ?>
				<span>Cerca</span>
			</button>

			<?php if ( $zw_social ) : ?>
				<div class="hidden xl:flex items-center gap-1.5 pl-2 ml-0.5 border-l border-white/10">
					<?php foreach ( array_slice( $zw_social, 0, 4, true ) as $key => $social ) : ?>
						<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $social['label'] ); ?>" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="w-10 h-10 rounded-full bg-white/[0.07] hover:bg-zw-red text-white/80 hover:text-white flex items-center justify-center transition-all"><?php echo zw_icon( $key ); // phpcs:ignore ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<button type="button" data-zw-open="mobileMenu" class="lg:hidden w-10 h-10 rounded-full bg-white/[0.07] hover:bg-white/[0.14] text-white flex items-center justify-center transition-all" aria-label="Apri il menu" aria-haspopup="dialog">
				<?php echo zw_icon( 'menu', 'w-5 h-5' ); // phpcs:ignore ?>
			</button>
		</div>
	</div>
</header>
<?php zw_ad_slot( 'after_header' ); ?>
