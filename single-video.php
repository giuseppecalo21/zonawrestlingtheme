<?php
/**
 * Articolo video: player grande in alto (il video viene tolto dal testo),
 * titolo e dati del video, prossimi video a fianco, descrizione e altri video sotto.
 * Lo carica inc/video.php per i post della categoria Video (o formato "video").
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$zw_found    = zw_current_video();
$zw_video    = $zw_found['video'];
$zw_info     = $zw_video ? zw_video_info( get_the_ID(), $zw_video ) : array();
$zw_labels   = zw_post_labels( get_the_ID() );
$zw_subtitle = zw_subtitle();
$zw_author   = (int) get_post_field( 'post_author', get_the_ID() );
$zw_share    = zw_share_links();
$zw_tags     = get_the_tags();
$zw_related  = zw_related_videos( get_post(), 9 );
$zw_next     = array_slice( $zw_related, 0, 5 );
$zw_more     = array_slice( $zw_related, 5 );
$zw_poster   = $zw_video ? zw_video_poster( get_post(), $zw_video ) : '';
$zw_duration = ! empty( $zw_info['duration'] ) ? zw_video_duration( $zw_info['duration'] ) : '';
$zw_service  = $zw_video ? zw_video_provider_name( $zw_video['provider'] ) : '';
$zw_text     = trim( wp_strip_all_tags( $zw_found['html'] ) );
$zw_label    = $zw_labels['fed'] ? $zw_labels['fed'] : $zw_labels['label'];

// Se il sottotitolo ripete il testo (capita nei video brevi), non lo mostriamo due volte.
$zw_norm = function ( $text ) {
	return trim( preg_replace( '/\s+/u', ' ', mb_strtolower( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) ) );
};
if ( $zw_subtitle && $zw_text && false !== mb_strpos( $zw_norm( $zw_text ), $zw_norm( $zw_subtitle ) ) ) {
	$zw_subtitle = '';
}

/** Durata di un video correlato (se già nota). */
$zw_rel_duration = function ( $post ) {
	$info = get_post_meta( $post->ID, ZW_VIDEO_META, true );
	return is_array( $info ) && ! empty( $info['duration'] ) ? zw_video_duration( $info['duration'] ) : '';
};

zw_stage_open();
?>
	<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-4 sm:pt-8 pb-10 sm:pb-14">
		<nav class="hidden sm:flex items-center gap-2 text-xs text-white/55 font-medium mb-4" aria-label="Percorso">
			<?php foreach ( zw_breadcrumb() as $zw_i => $zw_crumb ) : ?>
				<?php echo $zw_i ? '<span aria-hidden="true">/</span>' : ''; ?>
				<a href="<?php echo esc_url( $zw_crumb['url'] ); ?>" class="hover:text-white transition-colors"><?php echo esc_html( $zw_crumb['name'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
			<div class="lg:col-span-8 min-w-0">

				<?php if ( $zw_video ) : ?>
					<div class="zw-player relative aspect-video rounded-[18px] sm:rounded-[24px] overflow-hidden bg-black nf-shadow-dark" data-zw-player="<?php echo esc_url( zw_video_embed_url( $zw_video ) ); ?>" data-title="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">
						<?php if ( in_array( $zw_video['provider'], array( 'youtube', 'vimeo' ), true ) && $zw_poster ) : ?>
							<button type="button" class="absolute inset-0 w-full h-full group cursor-pointer" aria-label="<?php echo esc_attr( 'Guarda il video: ' . wp_strip_all_tags( get_the_title() ) ); ?>">
								<img src="<?php echo esc_url( $zw_poster ); ?>" alt="" class="absolute inset-0 w-full h-full object-cover" fetchpriority="high" decoding="async" />
								<span class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-black/20"></span>
								<span class="absolute inset-0 flex items-center justify-center">
									<span class="zw-round w-20 h-20 sm:w-28 sm:h-28 bg-zw-red group-hover:scale-110 flex items-center justify-center nf-red-glow transition-transform"><?php echo zw_icon( 'play', 'w-9 h-9 sm:w-12 sm:h-12 text-white ml-1.5' ); // phpcs:ignore ?></span>
								</span>
								<span class="absolute left-4 right-4 bottom-4 sm:left-6 sm:right-6 sm:bottom-5 flex items-end justify-between gap-3 text-left">
									<?php if ( ! empty( $zw_info['title'] ) ) : ?>
										<span class="text-white/90 text-xs sm:text-sm font-semibold line-clamp-1 max-sm:hidden"><?php echo esc_html( $zw_info['title'] ); ?></span>
									<?php else : ?>
										<span></span>
									<?php endif; ?>
									<?php if ( $zw_duration ) : ?>
										<span class="px-2 py-1 rounded-md bg-black/80 text-white text-xs font-bold tabular-nums shrink-0"><?php echo esc_html( $zw_duration ); ?></span>
									<?php endif; ?>
								</span>
							</button>
						<?php elseif ( 'file' === $zw_video['provider'] ) : ?>
							<video src="<?php echo esc_url( $zw_video['src'] ); ?>" controls playsinline preload="metadata" <?php echo $zw_poster ? 'poster="' . esc_url( $zw_poster ) . '"' : ''; ?> class="absolute inset-0 w-full h-full"></video>
						<?php else : ?>
							<iframe src="<?php echo esc_url( zw_video_embed_url( $zw_video, false ) ); ?>" title="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>" class="absolute inset-0 w-full h-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="eager"></iframe>
						<?php endif; ?>
					</div>
				<?php elseif ( has_post_thumbnail() ) : ?>
					<figure class="relative aspect-video rounded-[24px] overflow-hidden bg-black nf-shadow-dark m-0">
						<?php echo zw_image( get_post_thumbnail_id(), 1100, array( 'class' => 'w-full h-full object-cover', 'sizes' => '(max-width: 1024px) 100vw, 880px', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
					</figure>
				<?php endif; ?>

				<div class="flex flex-wrap items-center gap-2 mt-5">
					<span class="px-3 py-1 rounded-full bg-zw-red text-white font-sofia font-extrabold text-[11px] sm:text-xs uppercase tracking-wide nf-red-glow flex items-center gap-1.5"><?php echo zw_icon( 'video', 'w-3 h-3' ); // phpcs:ignore ?>Video</span>
					<?php if ( $zw_label ) : ?>
						<a href="<?php echo esc_url( $zw_labels['fed'] ? $zw_labels['fed_url'] : $zw_labels['label_url'] ); ?>" class="px-3 py-1 rounded-full bg-white/[0.08] hover:bg-white/[0.16] text-white/85 text-[11px] sm:text-xs font-sofia font-bold transition-colors"><?php echo esc_html( $zw_label ); ?></a>
					<?php endif; ?>
				</div>

				<h1 class="font-sofia-display font-black text-2xl sm:text-[34px] leading-[1.12] text-white mt-3"><?php the_title(); ?></h1>

				<div class="flex flex-wrap items-center justify-between gap-4 mt-4">
					<p class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-white/65">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="flex items-center gap-1.5"><?php echo zw_icon( 'calendar', 'w-3.5 h-3.5 text-zw-redSoft' ); // phpcs:ignore ?><?php echo esc_html( mb_strtolower( get_the_date( 'j F Y' ) ) ); ?></time>
						<?php if ( $zw_duration ) : ?>
							<span class="flex items-center gap-1.5"><?php echo zw_icon( 'clock', 'w-3.5 h-3.5 text-zw-redSoft' ); // phpcs:ignore ?><?php echo esc_html( $zw_duration ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $zw_info['views'] ) ) : ?>
							<span class="flex items-center gap-1.5"><?php echo zw_icon( 'eye', 'w-3.5 h-3.5 text-zw-redSoft' ); // phpcs:ignore ?><?php echo esc_html( zw_video_views( $zw_info['views'] ) . ' visualizzazioni' . ( $zw_service ? ' su ' . $zw_service : '' ) ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $zw_info['author'] ) ) : ?>
							<span class="flex items-center gap-1.5">
								<?php echo zw_icon( 'youtube' === $zw_video['provider'] ? 'youtube' : 'video', 'w-3.5 h-3.5 text-zw-redSoft' ); // phpcs:ignore ?>
								<?php if ( ! empty( $zw_info['author_url'] ) ) : ?>
									<a href="<?php echo esc_url( $zw_info['author_url'] ); ?>" target="_blank" rel="noopener nofollow" class="font-semibold text-white/85 hover:text-white"><?php echo esc_html( $zw_info['author'] ); ?></a>
								<?php else : ?>
									<span class="font-semibold text-white/85"><?php echo esc_html( $zw_info['author'] ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</p>
					<div class="flex items-center gap-2">
						<?php foreach ( $zw_share as $zw_key => $zw_link ) : ?>
							<a href="<?php echo esc_url( $zw_link['url'] ); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr( $zw_link['label'] ); ?>" aria-label="<?php echo esc_attr( $zw_link['label'] ); ?>" class="zw-round w-9 h-9 bg-white/[0.08] hover:bg-zw-red text-white flex items-center justify-center transition-all"><?php echo zw_icon( $zw_key, 'w-4 h-4' ); // phpcs:ignore ?></a>
						<?php endforeach; ?>
						<button type="button" data-zw-copy title="Copia link" aria-label="Copia link" class="zw-round w-9 h-9 bg-white/[0.08] hover:bg-zw-red text-white flex items-center justify-center transition-all"><?php echo zw_icon( 'link', 'w-4 h-4' ); // phpcs:ignore ?></button>
					</div>
				</div>

				<?php if ( $zw_subtitle || $zw_text ) : ?>
					<div class="mt-5 rounded-[20px] bg-white/[0.06] border border-white/[0.06] p-4 sm:p-5">
						<?php if ( $zw_subtitle ) : ?>
							<p class="font-chivo text-[15px] sm:text-base text-white/85 leading-relaxed"><?php echo esc_html( $zw_subtitle ); ?></p>
						<?php endif; ?>
						<?php if ( $zw_text && mb_strlen( $zw_text ) < 600 ) : ?>
							<div class="zw-video-desc text-[15px] text-white/75 leading-relaxed <?php echo $zw_subtitle ? 'mt-2' : ''; ?>"><?php echo wp_kses_post( $zw_found['html'] ); ?></div>
						<?php elseif ( $zw_text ) : ?>
							<a href="#descrizione" class="inline-flex items-center gap-1.5 text-sm font-sofia font-bold text-zw-redSoft hover:text-white mt-2 transition-colors">Leggi l'articolo completo ↓</a>
						<?php endif; ?>
						<p class="text-xs text-white/50 mt-3">di <a href="<?php echo esc_url( get_author_posts_url( $zw_author ) ); ?>" rel="author" class="font-semibold text-white/75 hover:text-white"><?php echo esc_html( get_the_author_meta( 'display_name', $zw_author ) ); ?></a></p>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $zw_next ) : ?>
				<aside class="lg:col-span-4 min-w-0" aria-labelledby="nextVideosTitle">
					<h2 id="nextVideosTitle" class="font-sofia font-black text-lg text-white mb-3 flex items-center gap-2"><span class="w-2 h-5 rounded-full bg-zw-red inline-block nf-red-glow"></span>Prossimi video</h2>
					<div class="space-y-2.5">
						<?php foreach ( $zw_next as $zw_post ) : ?>
							<?php $zw_d = $zw_rel_duration( $zw_post ); ?>
							<a href="<?php echo esc_url( get_permalink( $zw_post ) ); ?>" class="flex gap-3 p-2 -mx-2 rounded-[16px] hover:bg-white/[0.06] group transition-colors">
								<span class="relative w-[150px] sm:w-[168px] aspect-video rounded-xl overflow-hidden shrink-0 bg-black block">
									<?php echo zw_post_image( $zw_post, 340, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '168px' ) ); // phpcs:ignore ?>
									<?php if ( $zw_d ) : ?>
										<span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded bg-black/85 text-white text-[11px] font-bold tabular-nums"><?php echo esc_html( $zw_d ); ?></span>
									<?php else : ?>
										<span class="absolute inset-0 flex items-center justify-center"><span class="zw-round w-8 h-8 bg-black/60 group-hover:bg-zw-red flex items-center justify-center text-white transition-colors"><?php echo zw_icon( 'play', 'w-3.5 h-3.5 ml-0.5' ); // phpcs:ignore ?></span></span>
									<?php endif; ?>
								</span>
								<span class="min-w-0 flex-1 py-0.5">
									<span class="block font-sofia-title font-bold text-sm text-white group-hover:text-zw-redSoft leading-snug line-clamp-3 transition-colors"><?php echo esc_html( get_the_title( $zw_post ) ); ?></span>
									<span class="block text-[11px] text-white/50 mt-1"><?php $zw_l = zw_post_labels( $zw_post ); echo esc_html( trim( ( $zw_l['fed'] ? $zw_l['fed'] : $zw_l['label'] ) . ' • ' . zw_date( $zw_post ), ' •' ) ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
					<a href="<?php echo esc_url( zw_cat_url( 'cat_video' ) ); ?>" class="mt-4 w-full py-3 px-5 rounded-[16px] bg-gradient-to-r from-zw-red to-zw-redDark hover:from-zw-redSoft hover:to-zw-red text-white font-sofia font-black text-xs tracking-wider uppercase nf-red-glow flex items-center justify-center gap-2 transition-all">Tutti i video <span aria-hidden="true">→</span></a>
				</aside>
			<?php endif; ?>
		</div>
	</section>
</div><!-- fascia scura -->

<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 pt-10 pb-14">
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
		<div class="lg:col-span-8 min-w-0">
			<?php if ( mb_strlen( $zw_text ) >= 600 ) : ?>
				<article id="descrizione" <?php post_class( 'bg-white rounded-[26px] nf-shadow-light p-5 sm:p-8 mb-8 scroll-mt-6' ); ?>>
					<?php zw_ad_slot( 'before_content' ); ?>
					<div class="zw-prose"><?php echo $zw_found['html']; // phpcs:ignore -- testo dell'articolo già filtrato da the_content. ?></div>
					<?php zw_ad_slot( 'after_content' ); ?>
				</article>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/single/event-block' ); ?>

			<?php if ( $zw_tags && ! is_wp_error( $zw_tags ) ) : ?>
				<div class="flex flex-wrap items-center gap-2 mb-8">
					<?php foreach ( $zw_tags as $zw_tag ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $zw_tag ) ); ?>" rel="tag" class="px-3.5 py-1.5 rounded-full bg-white hover:bg-zw-ink text-zw-ink hover:text-white font-sofia font-bold text-xs nf-shadow-light transition-all"><?php echo esc_html( $zw_tag->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $zw_more ) : ?>
				<section aria-label="Altri video">
					<?php zw_section_title( 'Altri video', array( 'link' => zw_cat_url( 'cat_video' ), 'link_label' => 'Tutti →' ) ); ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $zw_more as $zw_post ) : ?>
							<?php $zw_d = $zw_rel_duration( $zw_post ); ?>
							<a href="<?php echo esc_url( get_permalink( $zw_post ) ); ?>" class="nf-card bg-white rounded-[22px] p-3 nf-shadow-light group block">
								<span class="relative aspect-video rounded-[16px] overflow-hidden bg-zw-velvet block">
									<?php echo zw_post_image( $zw_post, 560, array( 'class' => 'w-full h-full object-cover nf-img-zoom', 'sizes' => '(max-width: 640px) 100vw, 420px' ) ); // phpcs:ignore ?>
									<span class="absolute inset-0 flex items-center justify-center"><span class="zw-round w-12 h-12 bg-zw-red/90 group-hover:scale-110 flex items-center justify-center text-white nf-red-glow transition-transform"><?php echo zw_icon( 'play', 'w-5 h-5 ml-0.5' ); // phpcs:ignore ?></span></span>
									<?php if ( $zw_d ) : ?>
										<span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/85 text-white text-[11px] font-bold tabular-nums"><?php echo esc_html( $zw_d ); ?></span>
									<?php endif; ?>
								</span>
								<span class="block px-1.5 pt-3 pb-1">
									<span class="block text-[10px] font-extrabold uppercase tracking-wide text-zw-red"><?php $zw_l = zw_post_labels( $zw_post ); echo esc_html( $zw_l['fed'] ? $zw_l['fed'] : $zw_l['label'] ); ?></span>
									<span class="block font-sofia-title font-extrabold text-base text-zw-ink group-hover:text-zw-red leading-snug line-clamp-2 mt-0.5 transition-colors"><?php echo esc_html( get_the_title( $zw_post ) ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php
			get_template_part( 'template-parts/single/google-buttons' );
			if ( comments_open() || get_comments_number() ) :
				?>
				<section id="commenti" class="mt-10">
					<?php zw_section_title( 'Commenti' ); ?>
					<div class="bg-white rounded-[22px] nf-shadow-light p-4 sm:p-6 zw-comments"><?php comments_template(); ?></div>
				</section>
			<?php endif; ?>
		</div>

		<aside class="lg:col-span-4 min-w-0" aria-label="Da non perdere">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</main>
<?php
get_footer();
