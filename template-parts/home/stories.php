<?php
/**
 * Barra delle stories: un cerchio per ogni blocco di Aspetto → Stories in home.
 * Senza JavaScript ogni cerchio è un normale link alla categoria.
 */

defined( 'ABSPATH' ) || exit;

$zw_stories = zw_stories_data();
if ( ! $zw_stories ) {
	return;
}
?>
<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-5 pb-3" aria-label="Stories per federazione">
	<div class="flex items-center gap-4 sm:gap-6 overflow-x-auto no-scrollbar pt-5 pb-8 -mt-4 -mb-7 px-5 -mx-5" id="storiesBar">
		<?php foreach ( $zw_stories as $zw_i => $zw_story ) : ?>
			<a href="<?php echo esc_url( $zw_story['url'] ); ?>" data-story="<?php echo (int) $zw_i; ?>" data-story-id="<?php echo (int) $zw_story['id']; ?>" data-story-latest="<?php echo (int) $zw_story['latest']; ?>"
			   class="story-btn flex flex-col items-center shrink-0 group focus:outline-none" title="<?php echo esc_attr( 'Le ultime notizie ' . $zw_story['label'] ); ?>">
				<span class="story-ring-gradient block">
					<?php $zw_cover = $zw_story['logo'] && 'cover' === $zw_story['fit']; ?>
					<span class="story-inner-avatar w-14 h-14 sm:w-16 sm:h-16 <?php echo $zw_cover ? 'p-0' : 'p-1.5'; ?> bg-zw-surfaceDark flex items-center justify-center relative overflow-hidden border-[3px] border-zw-velvet">
						<?php if ( $zw_story['logo'] ) : ?>
							<img src="<?php echo esc_url( $zw_story['logo'] ); ?>" alt="<?php echo esc_attr( $zw_story['alt'] ); ?>" width="64" height="64" class="w-full h-full <?php echo $zw_cover ? 'object-cover' : 'object-contain'; ?>" loading="eager" decoding="async" />
						<?php else : ?>
							<span class="font-sofiaCond font-black text-[15px] sm:text-[17px] text-white tracking-wide leading-none"><?php echo esc_html( $zw_story['short'] ); ?></span>
						<?php endif; ?>
					</span>
				</span>
				<span class="mt-2 text-[11px] font-sofia font-bold tracking-wide text-white/80 group-hover:text-white transition-colors"><?php echo esc_html( $zw_story['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
