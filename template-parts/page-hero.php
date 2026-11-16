<?php
/**
 * Fascia scura con titolo per archivi, ricerca e pagine.
 *
 * @var array $args [ kicker, title, text, avatar (ID utente) ]
 */

defined( 'ABSPATH' ) || exit;

$zw_args = wp_parse_args( isset( $args ) ? $args : array(), array( 'kicker' => '', 'title' => '', 'text' => '', 'avatar' => 0 ) );

zw_stage_open( 'pb-10 sm:pb-14' );
?>
	<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-8 sm:pt-12">
		<div class="flex items-center gap-4 sm:gap-5">
			<?php if ( $zw_args['avatar'] ) : ?>
				<?php echo get_avatar( $zw_args['avatar'], 192, '', '', array( 'class' => 'zw-round w-16 h-16 sm:w-24 sm:h-24 object-cover shrink-0' ) ); ?>
			<?php endif; ?>
			<div class="min-w-0">
				<?php if ( $zw_args['kicker'] ) : ?>
					<span class="inline-block px-3 py-1 rounded-full bg-zw-red text-white font-sofia font-extrabold text-[11px] sm:text-xs tracking-wide uppercase nf-red-glow mb-3"><?php echo esc_html( $zw_args['kicker'] ); ?></span>
				<?php endif; ?>
				<h1 class="font-sofia-display font-black text-3xl sm:text-5xl text-white"><?php echo esc_html( $zw_args['title'] ); ?></h1>
				<?php if ( $zw_args['text'] ) : ?>
					<div class="text-sm sm:text-base text-white/70 mt-3 max-w-3xl leading-relaxed"><?php echo wp_kses_post( $zw_args['text'] ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div><!-- fascia scura -->
