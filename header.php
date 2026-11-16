<?php
/**
 * Apertura della pagina. La barra di navigazione vera e propria è in
 * template-parts/site-header.php, stampata da zw_stage_open() dentro la fascia scura.
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="theme-color" content="#090a0f" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text zw-skip-link" href="#contenuto">Vai al contenuto</a>
<?php if ( is_singular( 'post' ) ) : ?>
	<div class="fixed top-0 left-0 right-0 h-1 z-[60] pointer-events-none" aria-hidden="true"><div id="readProgress" class="h-full bg-zw-red" style="width:0%; box-shadow: 0 0 14px rgba(215,24,42,.8);"></div></div>
<?php endif; ?>
<div id="appShell">
<?php

if ( ! function_exists( 'zw_stage_open' ) ) {
	/**
	 * Apre la fascia scura in alto (alone rosso + barra di navigazione).
	 * Chi la apre deve chiuderla con un </div>.
	 */
	function zw_stage_open( $extra_class = '' ) {
		?>
		<div class="relative bg-zw-velvet text-white <?php echo esc_attr( $extra_class ); ?>">
			<div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
				<div id="heroAmbientGlow" class="absolute -top-[340px] left-1/2 -translate-x-1/2 w-[1500px] h-[900px] max-w-none opacity-[0.42] blur-[110px]" style="background: radial-gradient(circle, #d7182a 0%, #3b0d54 55%, transparent 75%);"></div>
				<div class="absolute inset-0" style="background: radial-gradient(ellipse 55% 38% at 4% 2%, rgba(215, 24, 42, 0.22), transparent 70%), radial-gradient(ellipse 45% 40% at 100% 58%, rgba(215, 24, 42, 0.15), transparent 70%);"></div>
			</div>
			<?php get_template_part( 'template-parts/site-header' ); ?>
		<?php
	}
}
