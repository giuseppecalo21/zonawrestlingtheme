<?php
/**
 * Scheda autore compatta: foto e nome portano alla pagina dell'autore.
 */

defined( 'ABSPATH' ) || exit;

$zw_author  = (int) get_post_field( 'post_author', get_the_ID() );
$zw_name    = get_the_author_meta( 'display_name', $zw_author );
$zw_url     = get_author_posts_url( $zw_author );
$zw_bio     = trim( wp_strip_all_tags( get_the_author_meta( 'description', $zw_author ) ) );
$zw_role    = trim( (string) get_the_author_meta( 'zw_role', $zw_author ) );
$zw_socials = zw_author_socials( $zw_author );
?>
<section id="autore" class="mt-8 pt-6 border-t border-zw-ink/10 flex items-start gap-4" aria-label="Autore">
	<a href="<?php echo esc_url( $zw_url ); ?>" class="shrink-0" aria-hidden="true" tabindex="-1">
		<?php echo get_avatar( $zw_author, 112, '', '', array( 'class' => 'zw-round w-14 h-14 object-cover bg-white', 'loading' => 'lazy' ) ); ?>
	</a>
	<div class="min-w-0 flex-1">
		<div class="flex flex-wrap items-center gap-x-3 gap-y-1">
			<a href="<?php echo esc_url( $zw_url ); ?>" rel="author" class="font-sofia font-black text-lg text-zw-ink hover:text-zw-red transition-colors leading-tight"><?php echo esc_html( $zw_name ); ?></a>
			<?php if ( $zw_role ) : ?>
				<span class="text-xs text-zw-muted"><?php echo esc_html( $zw_role ); ?></span>
			<?php endif; ?>
			<?php if ( $zw_socials ) : ?>
				<span class="flex items-center gap-1 ml-auto">
					<?php foreach ( $zw_socials as $zw_icon => $zw_link ) : ?>
						<a href="<?php echo esc_url( $zw_link ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( $zw_name . ' su ' . ucfirst( $zw_icon ) ); ?>" class="zw-round w-8 h-8 text-zw-muted hover:text-zw-red hover:bg-zw-red/10 flex items-center justify-center transition-colors"><?php echo zw_icon( $zw_icon, 'w-4 h-4' ); // phpcs:ignore ?></a>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</div>
		<?php if ( $zw_bio ) : ?>
			<p class="text-sm text-zw-muted leading-relaxed mt-1.5 line-clamp-3"><?php echo esc_html( $zw_bio ); ?></p>
		<?php endif; ?>
	</div>
</section>
