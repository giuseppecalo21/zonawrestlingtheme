<?php
/**
 * Card notizia (feed della home, archivi, "Ultime notizie" sotto l'articolo).
 *
 * @var array $args [ 'post' => WP_Post ]
 */

defined( 'ABSPATH' ) || exit;

$zw_post = get_post( isset( $args['post'] ) ? $args['post'] : null );
if ( ! $zw_post ) {
	return;
}
$zw_labels = zw_post_labels( $zw_post );
$zw_live   = zw_is_live( $zw_post );
$zw_media  = zw_media_type( $zw_post );
$zw_thumb  = zw_post_image( $zw_post, 560, array( 'class' => 'absolute inset-0 w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 640px) 40vw, 275px' ) );
$zw_stars  = zw_review_stars( $zw_post->ID );
$zw_text   = has_excerpt( $zw_post ) ? wp_trim_words( wp_strip_all_tags( $zw_post->post_excerpt ), 30, '…' ) : '';
?>
<article class="zw-card nf-card bg-white rounded-[22px] p-3 sm:p-4 nf-shadow-light relative flex flex-row-reverse sm:flex-row items-stretch gap-3.5 sm:gap-5 group">

	<div class="zw-card-thumb relative w-[40%] sm:w-[255px] md:w-[275px] min-h-[116px] sm:min-h-0 sm:h-[175px] rounded-[16px] overflow-hidden shrink-0 bg-zw-velvet">
		<?php echo $zw_thumb; // phpcs:ignore ?>
		<div class="absolute top-2 left-2 sm:top-3 sm:left-3 flex items-center gap-1.5">
			<?php if ( $zw_live ) : ?>
				<span class="px-2 py-0.5 sm:px-3 sm:py-1 rounded-full bg-zw-red text-white font-sofia font-extrabold text-[10px] sm:text-[11px] uppercase flex items-center gap-1.5 nf-red-glow">
					<span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>Diretta Live
				</span>
			<?php endif; ?>
			<?php if ( $zw_labels['fed'] ) : ?>
				<span class="max-sm:hidden px-3 py-1 rounded-full bg-black/65 backdrop-blur-md text-white font-sofia font-extrabold text-[11px] uppercase"><?php echo esc_html( $zw_labels['fed'] ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $zw_media ) : ?>
			<span class="absolute top-2 right-2 sm:top-3 sm:right-3 text-white" style="filter: drop-shadow(0 1px 3px rgba(0,0,0,.6));" title="<?php echo esc_attr( 'video' === $zw_media ? 'Contiene video' : 'Contiene una gallery' ); ?>">
				<?php echo zw_icon( $zw_media, 'w-6 h-6' ); // phpcs:ignore ?>
			</span>
		<?php endif; ?>
	</div>

	<div class="flex-1 min-w-0 flex flex-col justify-center sm:justify-between py-1 sm:pr-1 pl-1 sm:pl-0">
		<div>
			<div class="flex flex-col items-start gap-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-2 mb-1.5">
				<span class="text-[10px] sm:text-xs font-extrabold text-zw-red uppercase tracking-wide"><?php echo esc_html( $zw_labels['label'] ); ?></span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $zw_post ) ); ?>" class="text-[11px] sm:text-xs text-zw-muted font-semibold flex items-center gap-1 max-sm:order-last">
					<?php echo zw_icon( 'clock', 'w-3.5 h-3.5 text-zw-red' ); // phpcs:ignore ?>
					<?php echo esc_html( zw_date( $zw_post, true ) ); ?>
				</time>
			</div>

			<h3 class="zw-card-title font-sofia-title font-extrabold text-base sm:text-[22px] text-zw-ink group-hover:text-zw-red transition-colors">
				<a href="<?php echo esc_url( get_permalink( $zw_post ) ); ?>" class="after:absolute after:inset-0 after:content-['']"><?php echo esc_html( get_the_title( $zw_post ) ); ?></a>
			</h3>

			<?php if ( null !== $zw_stars ) : ?>
				<div class="text-zw-red mt-1.5"><?php echo zw_stars( $zw_stars, 'w-3.5 h-3.5' ); // phpcs:ignore ?></div>
			<?php endif; ?>

			<?php if ( $zw_text ) : ?>
				<p class="zw-card-excerpt max-sm:hidden text-sm text-zw-muted mt-2 line-clamp-2 leading-relaxed"><?php echo esc_html( $zw_text ); ?></p>
			<?php endif; ?>
		</div>

		<div class="zw-card-more max-sm:hidden mt-4 pt-3 flex items-center justify-end text-xs" aria-hidden="true">
			<span class="text-zw-red font-sofia font-extrabold group-hover:translate-x-1 transition-transform">Leggi notizia →</span>
		</div>
	</div>

</article>
