<?php
/**
 * Video: un articolo in evidenza e quattro in elenco. Ogni card apre l'articolo.
 */

defined( 'ABSPATH' ) || exit;

zw_fragment( 'home_video', 10 * MINUTE_IN_SECONDS, function () {
	if ( ! zw_cat_id( 'cat_video' ) ) {
		return;
	}
	$posts = zw_posts( array( 'cat' => zw_cat_id( 'cat_video' ), 'posts_per_page' => 5 ) );
	if ( ! $posts ) {
		return;
	}
	$lead  = array_shift( $posts );
	$label = function ( $post ) {
		$labels = zw_post_labels( $post );
		return $labels['fed'] ? $labels['fed'] : $labels['label'];
	};
	?>
	<div class="zw-cv zw-cv-video bg-zw-velvet text-white py-14">
		<section id="video" class="max-w-[1380px] mx-auto px-4 sm:px-6" aria-labelledby="videoTitle">
			<div class="flex items-center gap-3 mb-7">
				<span class="w-3 h-7 rounded-full bg-zw-red inline-block nf-red-glow"></span>
				<div>
					<h2 id="videoTitle" class="font-sofia font-black text-2xl sm:text-3xl text-white tracking-tight">Video</h2>
					<p class="text-xs text-white/55 mt-0.5">Highlights, interviste e show completi</p>
				</div>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
				<div class="lg:col-span-7 flex">
					<article class="nf-card rounded-[26px] bg-zw-surfaceDark overflow-hidden nf-shadow-dark flex flex-col w-full group relative">
						<div class="relative aspect-video bg-black overflow-hidden">
							<?php echo zw_post_image( $lead, 1100, array( 'class' => 'w-full h-full object-cover opacity-85 nf-img-zoom', 'sizes' => '(max-width: 1024px) 100vw, 780px' ) ); // phpcs:ignore ?>
							<div class="absolute inset-0 nf-vignette-card"></div>
							<div class="absolute inset-0 flex items-center justify-center z-10">
								<div class="zw-round w-16 h-16 sm:w-20 sm:h-20 bg-zw-red/90 group-hover:bg-zw-red group-hover:scale-110 backdrop-blur-md flex items-center justify-center nf-red-glow transition-all"><?php echo zw_icon( 'play', 'w-7 h-7 text-white ml-1' ); // phpcs:ignore ?></div>
							</div>
							<div class="absolute top-4 left-4 right-4 flex items-center justify-between z-10">
								<span class="px-3.5 py-1.5 rounded-full bg-zw-red text-white font-sofia font-extrabold text-xs uppercase nf-red-glow"><?php echo esc_html( $label( $lead ) ); ?></span>
								<span class="px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-white/80 text-xs font-medium"><?php echo esc_html( zw_date( $lead ) ); ?></span>
							</div>
						</div>
						<div class="p-5 sm:p-6 flex-1">
							<h3 class="font-sofia-title font-extrabold text-xl sm:text-2xl text-white group-hover:text-zw-redSoft transition-colors">
								<a href="<?php echo esc_url( get_permalink( $lead ) ); ?>" class="after:absolute after:inset-0 after:z-20 after:content-['']"><?php echo esc_html( get_the_title( $lead ) ); ?></a>
							</h3>
						</div>
					</article>
				</div>

				<div class="lg:col-span-5 flex flex-col justify-between gap-3.5">
					<div class="space-y-3">
						<?php foreach ( $posts as $post ) : ?>
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="nf-card rounded-[20px] p-3 bg-zw-surfaceDark hover:bg-zw-surfaceElevated flex items-center gap-3.5 group transition-all">
								<span class="relative w-32 h-20 rounded-xl overflow-hidden shrink-0 bg-black block">
									<?php echo zw_post_image( $post, 260, array( 'class' => 'w-full h-full object-cover opacity-85 nf-img-zoom', 'sizes' => '128px' ) ); // phpcs:ignore ?>
									<span class="absolute inset-0 bg-black/25 flex items-center justify-center">
										<span class="zw-round w-8 h-8 bg-zw-red/90 group-hover:scale-110 flex items-center justify-center text-white transition-transform"><?php echo zw_icon( 'play', 'w-3.5 h-3.5 ml-0.5' ); // phpcs:ignore ?></span>
									</span>
								</span>
								<span class="min-w-0 flex-1 pr-1">
									<span class="flex items-center justify-between gap-2">
										<span class="text-[10px] font-extrabold text-zw-redSoft uppercase truncate"><?php echo esc_html( $label( $post ) ); ?></span>
										<span class="text-[10px] text-white/45 shrink-0"><?php echo esc_html( zw_date( $post ) ); ?></span>
									</span>
									<span class="block font-sofia-title font-bold text-sm sm:text-[15px] text-white group-hover:text-zw-redSoft transition-colors line-clamp-2 mt-0.5 leading-snug"><?php echo esc_html( get_the_title( $post ) ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
					<a href="<?php echo esc_url( zw_cat_url( 'cat_video' ) ); ?>" class="w-full py-4 px-6 rounded-[20px] bg-gradient-to-r from-zw-red to-zw-redDark hover:from-zw-redSoft hover:to-zw-red text-white font-sofia font-black text-sm tracking-wider uppercase nf-red-glow flex items-center justify-center gap-2.5 transition-all">
						<span>Vai a tutti i video</span><span class="text-lg leading-none">→</span>
					</a>
				</div>
			</div>
		</section>
	</div>
	<?php
} );
