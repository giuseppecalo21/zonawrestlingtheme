<?php
/**
 * Primo piano: slider a sinistra (ultimi tre articoli in evidenza) e tre card a destra
 * (ultima analisi con stelline, ultimo editoriale, quarto articolo in evidenza).
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_hero', 10 * MINUTE_IN_SECONDS, function () {
	$featured = zw_posts( array( 'cat' => zw_cat_id( 'cat_featured' ), 'posts_per_page' => 6 ) );
	if ( ! $featured ) {
		$featured = zw_posts( array( 'posts_per_page' => 6 ) );
	}
	if ( ! $featured ) {
		return;
	}
	$slides = array_slice( $featured, 0, 3 );
	$used   = wp_list_pluck( $slides, 'ID' );

	// Card a destra: analisi, editoriale, e un altro articolo in evidenza.
	$side = array();
	foreach ( array( 'cat_analysis' => 'Analisi', 'cat_editorials' => 'Editoriale' ) as $key => $badge ) {
		$found = zw_cat_id( $key ) ? zw_posts( array( 'cat' => zw_cat_id( $key ), 'posts_per_page' => 1, 'post__not_in' => $used ) ) : array();
		if ( $found ) {
			$side[] = array( 'post' => $found[0], 'badge' => $badge );
			$used[] = $found[0]->ID;
		}
	}
	foreach ( $featured as $post ) {
		if ( count( $side ) >= 3 ) {
			break;
		}
		if ( ! in_array( $post->ID, $used, true ) ) {
			$side[] = array( 'post' => $post, 'badge' => '' );
			$used[] = $post->ID;
		}
	}
	?>
	<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-2 pb-5" aria-label="In primo piano">
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

			<div id="heroBillboard" class="lg:col-span-7 relative h-[410px] sm:h-[525px] rounded-bento overflow-hidden bg-zw-surfaceDark nf-shadow-dark" aria-roledescription="carousel">
				<?php foreach ( $slides as $i => $post ) : $labels = zw_post_labels( $post ); ?>
					<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="zw-hero-slide <?php echo 0 === $i ? 'is-active' : ''; ?> group absolute inset-0 block overflow-hidden" data-hero-slide="<?php echo (int) $i; ?>" <?php echo 0 === $i ? '' : 'tabindex="-1" aria-hidden="true"'; ?>>
						<?php
						$img = zw_post_image( $post, 1100, array(
							'class'         => 'w-full h-full object-cover nf-img-zoom',
							'sizes'         => '(max-width: 1024px) 100vw, 780px',
							'fetchpriority' => 0 === $i ? 'high' : '',
						) );
						if ( $i > 0 ) {
							// Slide nascoste ma dentro lo schermo: "lazy" non basta, il browser le scaricherebbe
							// subito togliendo banda alla prima (LCP). Le carica lo script, dopo il caricamento.
							$img = str_replace( array( ' src="', ' srcset="' ), array( ' data-zw-src="', ' data-zw-srcset="' ), $img );
						}
						echo $img; // phpcs:ignore
						?>
						<span class="absolute inset-0 nf-vignette-hero"></span>

						<span class="absolute top-5 left-5 right-5 flex items-center justify-between gap-2">
							<span class="flex items-center gap-2 min-w-0">
								<span class="px-3.5 py-1.5 rounded-full bg-zw-red text-white font-sofia font-extrabold text-xs tracking-wide uppercase nf-red-glow truncate">Primo piano<?php echo $labels['label'] ? ' • ' . esc_html( $labels['label'] ) : ''; ?></span>
								<?php if ( zw_is_live( $post ) ) : ?>
									<span class="px-3 py-1.5 rounded-full bg-black/50 backdrop-blur-md text-white/85 text-xs font-medium shrink-0">In diretta</span>
								<?php endif; ?>
							</span>
							<span class="max-sm:hidden px-3.5 py-1 rounded-full bg-black/55 backdrop-blur-md text-white/80 text-xs font-medium shrink-0"><?php echo esc_html( zw_date( $post, true ) ); ?></span>
						</span>

						<span class="absolute bottom-16 left-5 right-5 sm:left-8 sm:right-8 block">
							<span role="heading" aria-level="2" class="block font-sofia-display font-black text-2xl sm:text-4xl lg:text-[42px] text-white group-hover:text-zw-redSoft transition-colors"><?php echo esc_html( get_the_title( $post ) ); ?></span>
							<?php $deck = zw_subtitle( $post ); ?>
							<?php if ( $deck ) : ?>
								<span class="block font-chivo text-xs sm:text-sm text-white/75 mt-3 max-w-2xl line-clamp-2 leading-relaxed"><?php echo esc_html( $deck ); ?></span>
							<?php endif; ?>
							<span class="sm:hidden block mt-3 text-xs text-white/80 font-semibold"><?php echo esc_html( zw_date( $post, true ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>

				<?php if ( count( $slides ) > 1 ) : ?>
					<div class="absolute bottom-4 left-5 right-5 sm:left-8 sm:right-8 flex items-center justify-between z-20 pointer-events-none">
						<div class="flex items-center gap-2 pointer-events-auto bg-black/40 backdrop-blur-md px-3 py-2 rounded-full">
							<?php foreach ( $slides as $i => $post ) : ?>
								<button type="button" data-hero-dot="<?php echo (int) $i; ?>" class="h-1.5 rounded-full transition-all <?php echo 0 === $i ? 'w-9 bg-zw-red' : 'w-4 bg-white/30 hover:bg-white/60'; ?>" aria-label="<?php echo esc_attr( 'Vai alla notizia ' . ( $i + 1 ) ); ?>"></button>
							<?php endforeach; ?>
						</div>
						<div class="flex items-center gap-2 pointer-events-auto">
							<button type="button" data-hero-step="-1" class="w-9 h-9 rounded-full bg-black/50 hover:bg-zw-red backdrop-blur-md text-white flex items-center justify-center transition-all" aria-label="Notizia precedente">‹</button>
							<button type="button" data-hero-step="1" class="w-9 h-9 rounded-full bg-black/50 hover:bg-zw-red backdrop-blur-md text-white flex items-center justify-center transition-all" aria-label="Notizia successiva">›</button>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="lg:col-span-5 flex flex-col gap-5">
				<?php
				foreach ( $side as $i => $item ) :
					$post   = $item['post'];
					$labels = zw_post_labels( $post );
					$stars  = zw_review_stars( $post->ID );
					// "Analisi • Raw", "Editoriale", oppure la sola categoria.
					$section_cat = 'Analisi' === $item['badge'] ? zw_cat( 'cat_analysis' ) : ( 'Editoriale' === $item['badge'] ? zw_cat( 'cat_editorials' ) : null );
					$extra       = ( $labels['label'] && ( ! $section_cat || $labels['label'] !== $section_cat->name ) ) ? $labels['label'] : '';
					$badge       = implode( ' • ', array_filter( array( $item['badge'], $extra ) ) );
					$wide   = 0 === $i;
					if ( 1 === $i ) {
						echo '<div class="max-lg:hidden grid grid-cols-2 gap-5 flex-1">';
					}
					?>
					<article class="nf-card relative <?php echo $wide ? 'h-[245px] sm:h-[252px]' : 'h-[252px]'; ?> rounded-[22px] overflow-hidden bg-zw-surfaceDark nf-shadow-dark group">
						<?php echo zw_post_image( $post, $wide ? 800 : 440, array( 'class' => 'w-full h-full object-cover nf-img-zoom opacity-85', 'sizes' => $wide ? '(max-width: 1024px) 100vw, 550px' : '270px', 'loading' => $wide ? 'eager' : 'lazy' ) ); // phpcs:ignore ?>
						<div class="absolute inset-0 nf-vignette-card"></div>
						<div class="absolute top-4 left-4 right-4 flex items-center justify-between gap-2">
							<?php if ( $badge ) : ?>
								<span class="px-3 py-1 rounded-full <?php echo 'Editoriale' === $item['badge'] ? 'bg-white text-zw-ink' : 'bg-zw-red text-white'; ?> font-sofia font-extrabold text-xs tracking-wide uppercase truncate"><?php echo esc_html( $badge ); ?></span>
							<?php endif; ?>
							<?php if ( null !== $stars ) : ?>
								<span class="px-3 py-1 rounded-full bg-black/55 backdrop-blur-md text-white shrink-0"><?php echo zw_stars( $stars, 'w-3.5 h-3.5' ); // phpcs:ignore ?></span>
							<?php endif; ?>
						</div>
						<div class="absolute bottom-4 left-4 right-4 sm:bottom-5 sm:left-5 sm:right-5">
							<h2 class="font-sofia-title font-extrabold <?php echo $wide ? 'text-xl sm:text-2xl' : 'text-lg leading-snug'; ?> text-white group-hover:text-zw-redSoft transition-colors line-clamp-3">
								<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="after:absolute after:inset-[-999px] after:content-['']"><?php echo esc_html( get_the_title( $post ) ); ?></a>
							</h2>
							<span class="text-[11px] text-white/60 mt-1.5 block"><?php echo esc_html( zw_date( $post, true ) ); ?></span>
						</div>
					</article>
					<?php
					if ( $i >= 1 && $i === count( $side ) - 1 ) {
						echo '</div>';
					}
				endforeach;
				?>
			</div>

		</div>
	</section>
	<?php
} );
