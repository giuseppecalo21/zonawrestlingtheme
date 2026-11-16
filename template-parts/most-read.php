<?php
/**
 * "Le più lette di oggi".
 *
 * @var array $args [ 'layout' => 'list' | 'rail' ]
 */

defined( 'ABSPATH' ) || exit;

$zw_layout = isset( $args['layout'] ) ? $args['layout'] : 'list';

zw_fragment( 'most_read_' . $zw_layout, 10 * MINUTE_IN_SECONDS, function () use ( $zw_layout ) {
	$posts = zw_most_read( 5 );
	if ( ! $posts ) {
		return;
	}
	if ( 'rail' === $zw_layout ) :
		?>
		<section class="lg:hidden mt-10">
			<?php zw_section_title( 'Le più lette di oggi' ); ?>
			<div class="flex gap-4 overflow-x-auto no-scrollbar pb-10 -mb-8 pt-2 -mt-2 px-4 -mx-4">
				<?php foreach ( $posts as $i => $post ) : ?>
					<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="group shrink-0 w-[250px] flex items-end">
						<span class="zw-rank shrink-0 -mr-4 relative z-0 pb-9" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
						<span class="relative z-10 flex-1 min-w-0">
							<span class="block aspect-video rounded-[14px] overflow-hidden bg-zw-velvet nf-shadow-dark"><?php echo zw_post_image( $post, 440, array( 'class' => 'w-full h-full object-cover', 'sizes' => '220px' ) ); // phpcs:ignore ?></span>
							<span class="block font-sofia-title font-extrabold text-[13px] text-zw-ink leading-snug line-clamp-2 mt-2 h-[34px]"><?php echo esc_html( get_the_title( $post ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	else :
		?>
		<div class="bg-white rounded-[26px] p-6 nf-shadow-light">
			<?php zw_section_title( 'Le più lette di oggi', array( 'size' => 'text-xl' ) ); ?>
			<div class="space-y-1 -mt-1">
				<?php foreach ( $posts as $i => $post ) : $labels = zw_post_labels( $post ); ?>
					<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="group flex items-center py-1.5">
						<span class="zw-rank w-12 shrink-0 flex justify-end -mr-3 relative z-0" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
						<span class="relative z-10 w-[118px] aspect-video rounded-[12px] overflow-hidden shrink-0 bg-zw-velvet nf-shadow-dark"><?php echo zw_post_image( $post, 240, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '118px' ) ); // phpcs:ignore ?></span>
						<span class="min-w-0 pl-3.5">
							<span class="block text-[10px] font-extrabold uppercase tracking-wide text-zw-red"><?php echo esc_html( $labels['label'] ); ?></span>
							<span class="block font-sofia-title font-extrabold text-[13px] text-zw-ink group-hover:text-zw-red transition-colors leading-snug line-clamp-3 mt-0.5"><?php echo esc_html( get_the_title( $post ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	endif;
} );
