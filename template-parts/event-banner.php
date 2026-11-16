<?php
/**
 * Banner 16:9 di un evento (home e archivio eventi).
 *
 * @var array $args [ 'event' => array da zw_event() ]
 */

defined( 'ABSPATH' ) || exit;

$zw_event = isset( $args['event'] ) ? $args['event'] : null;
if ( ! $zw_event ) {
	return;
}
$zw_count = zw_event_countdown( $zw_event, 'Mancano' );
?>
<article class="nf-card rounded-[20px] overflow-hidden bg-zw-velvet text-white nf-shadow-light group relative">
	<div class="relative aspect-video overflow-hidden">
		<?php echo zw_image( $zw_event['thumb'], 640, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 1024px) 100vw, 400px', 'alt' => '' ) ); // phpcs:ignore ?>
		<div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-black/30"></div>

		<div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2">
			<div class="flex items-center gap-1.5 min-w-0">
				<?php if ( $zw_event['fed'] ) : ?>
					<span class="px-2.5 py-0.5 rounded-full bg-zw-red text-white font-sofia font-extrabold text-[10px] uppercase shrink-0"><?php echo esc_html( $zw_event['fed'] ); ?></span>
				<?php endif; ?>
				<?php if ( $zw_event['date_num'] ) : ?>
					<time datetime="<?php echo esc_attr( $zw_event['date'] ); ?>" class="px-2.5 py-0.5 rounded-full bg-black/70 backdrop-blur-md text-white font-sofia font-bold text-[11px] flex items-center gap-1.5 shrink-0">
						<?php echo zw_icon( 'calendar', 'w-3 h-3' ); // phpcs:ignore ?><?php echo esc_html( $zw_event['date_num'] ); ?>
					</time>
				<?php endif; ?>
			</div>
			<span data-zw-countdown="<?php echo esc_attr( $zw_event['date'] ); ?>" data-zw-tpl="mancano" class="px-2.5 py-0.5 rounded-full bg-white text-zw-ink font-sofia font-extrabold text-[11px] shrink-0 empty:hidden"><?php echo esc_html( $zw_count ); ?></span>
		</div>

		<div class="absolute bottom-3 left-3.5 right-3.5">
			<h3 class="font-sofia-title font-extrabold text-base text-white group-hover:text-zw-redSoft transition-colors leading-tight">
				<a href="<?php echo esc_url( $zw_event['url'] ); ?>" class="after:absolute after:inset-[-999px] after:content-['']"><?php echo esc_html( $zw_event['title'] ); ?></a>
			</h3>
			<?php if ( $zw_event['place'] ) : ?>
				<span class="text-[11px] text-white/65 block mt-0.5"><?php echo esc_html( $zw_event['place'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</article>
