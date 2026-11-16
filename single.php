<?php
/**
 * Articolo.
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$zw_labels   = zw_post_labels( get_the_ID() );
$zw_subtitle = zw_subtitle();
$zw_author   = (int) get_post_field( 'post_author', get_the_ID() );
$zw_thumb_id = get_post_thumbnail_id();
$zw_caption  = $zw_thumb_id ? wp_get_attachment_caption( $zw_thumb_id ) : '';
$zw_share    = zw_share_links();
$zw_tags     = get_the_tags();

zw_stage_open();
?>
	<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-5 sm:pt-12">
		<nav class="hidden sm:flex items-center gap-2 text-xs text-white/55 font-medium mb-5" aria-label="Percorso">
			<?php foreach ( zw_breadcrumb() as $zw_i => $zw_crumb ) : ?>
				<?php echo $zw_i ? '<span aria-hidden="true">/</span>' : ''; ?>
				<a href="<?php echo esc_url( $zw_crumb['url'] ); ?>" class="hover:text-white transition-colors"><?php echo esc_html( $zw_crumb['name'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="flex flex-wrap items-center gap-2 mb-3 sm:mb-6">
			<?php if ( zw_is_live( get_the_ID() ) ) : ?>
				<span class="px-3 py-1 sm:px-3.5 sm:py-1.5 rounded-full bg-white text-zw-ink font-sofia font-extrabold text-[11px] sm:text-xs uppercase flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-zw-red animate-ping"></span>Diretta live</span>
			<?php endif; ?>
			<?php if ( $zw_labels['label'] ) : ?>
				<a href="<?php echo esc_url( $zw_labels['label_url'] ); ?>" class="px-3 py-1 sm:px-3.5 sm:py-1.5 rounded-full bg-zw-red text-white font-sofia font-extrabold text-[11px] sm:text-xs tracking-wide uppercase nf-red-glow"><?php echo esc_html( $zw_labels['label'] ); ?></a>
			<?php endif; ?>
			<?php if ( $zw_labels['fed'] ) : ?>
				<a href="<?php echo esc_url( $zw_labels['fed_url'] ); ?>" class="px-3 py-1 sm:py-1.5 rounded-full bg-white/[0.08] hover:bg-white/[0.16] text-white/85 text-[11px] sm:text-xs font-sofia font-bold transition-colors"><?php echo esc_html( $zw_labels['fed'] ); ?></a>
			<?php endif; ?>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-10 items-start">
			<div class="<?php echo $zw_thumb_id ? 'lg:col-span-7' : 'lg:col-span-9'; ?> lg:pb-20">
				<h1 class="font-sofia-display font-black text-[28px] leading-[1.08] sm:text-5xl lg:text-[54px] sm:leading-[1.06] text-white"><?php the_title(); ?></h1>

				<?php if ( $zw_subtitle ) : ?>
					<p class="font-chivo text-[15px] sm:text-lg text-white/75 mt-3 sm:mt-5 max-w-2xl leading-relaxed"><?php echo esc_html( $zw_subtitle ); ?></p>
				<?php endif; ?>

				<?php $zw_stars = zw_review_stars(); ?>
				<?php if ( null !== $zw_stars ) : ?>
					<div class="mt-4 text-white"><?php echo zw_stars( $zw_stars, 'w-5 h-5' ); // phpcs:ignore ?></div>
				<?php endif; ?>

				<div class="flex items-center justify-between gap-3 mt-4 sm:mt-7">
					<div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
						<a href="<?php echo esc_url( get_author_posts_url( $zw_author ) ); ?>" class="shrink-0" tabindex="-1" aria-hidden="true">
							<?php echo get_avatar( $zw_author, 80, '', '', array( 'class' => 'zw-round w-8 h-8 sm:w-10 sm:h-10 object-cover', 'loading' => 'eager' ) ); ?>
						</a>
						<span class="leading-tight min-w-0">
							<span class="block text-[13px] sm:text-sm text-white/70">di <a href="<?php echo esc_url( get_author_posts_url( $zw_author ) ); ?>" rel="author" class="font-sofia font-extrabold text-white hover:text-zw-redSoft transition-colors"><?php echo esc_html( get_the_author_meta( 'display_name', $zw_author ) ); ?></a></span>
							<span class="block text-[11px] sm:text-xs text-white/55 truncate">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y' ) . ' • Ore ' . get_the_date( 'H:i' ) ); ?></time>
								• <?php echo (int) zw_reading_time(); ?> min di lettura
							</span>
						</span>
					</div>
					<button type="button" data-zw-share class="lg:hidden h-9 pl-3 pr-3.5 rounded-full bg-white/[0.08] hover:bg-zw-red text-white text-xs font-sofia font-bold flex items-center gap-1.5 shrink-0 transition-all"><?php echo zw_icon( 'share' ); // phpcs:ignore ?><span>Condividi</span></button>
				</div>
			</div>

			<?php if ( $zw_thumb_id ) : ?>
				<div class="lg:col-span-5 relative z-20 -mb-8 lg:-mb-24 lg:mt-1.5">
					<figure class="relative rounded-bento overflow-hidden nf-shadow-dark bg-zw-surfaceDark aspect-video lg:aspect-[4/3] m-0">
						<?php echo zw_image( $zw_thumb_id, 1100, array( 'class' => 'w-full h-full object-cover', 'sizes' => '(max-width: 1024px) 100vw, 540px', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
						<?php if ( $zw_caption ) : ?>
							<div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(9,10,15,0) 60%, rgba(9,10,15,.8) 100%);"></div>
							<figcaption class="absolute bottom-2.5 left-3 right-3 sm:bottom-3.5 sm:left-4 sm:right-4 text-[11px] text-white/80 font-medium line-clamp-2"><?php echo esc_html( wp_strip_all_tags( $zw_caption ) ); ?></figcaption>
						<?php endif; ?>
					</figure>
				</div>
			<?php else : ?>
				<div class="h-8"></div>
			<?php endif; ?>
		</div>
	</section>
</div><!-- fascia scura -->

<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 <?php echo $zw_thumb_id ? 'pt-14 lg:pt-12' : 'pt-8'; ?> pb-14">
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">

		<aside class="hidden lg:block lg:col-span-1" aria-label="Condividi">
			<div class="sticky top-8 flex flex-col items-center gap-2.5">
				<span class="text-[10px] font-sofia font-extrabold uppercase tracking-wider text-zw-muted mb-1">Condividi</span>
				<?php foreach ( $zw_share as $zw_key => $zw_link ) : ?>
					<a href="<?php echo esc_url( $zw_link['url'] ); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr( $zw_link['label'] ); ?>" aria-label="<?php echo esc_attr( $zw_link['label'] ); ?>" class="zw-round w-11 h-11 bg-white hover:bg-zw-red text-zw-ink hover:text-white flex items-center justify-center nf-shadow-light transition-all"><?php echo zw_icon( $zw_key ); // phpcs:ignore ?></a>
				<?php endforeach; ?>
				<button type="button" data-zw-copy title="Copia link" aria-label="Copia link" class="zw-round w-11 h-11 bg-white hover:bg-zw-red text-zw-ink hover:text-white flex items-center justify-center nf-shadow-light transition-all"><?php echo zw_icon( 'link' ); // phpcs:ignore ?></button>
			</div>
		</aside>

		<div class="lg:col-span-6 min-w-0">
			<article id="articleBody" <?php post_class(); ?>>
				<?php zw_ad_slot( 'before_content' ); ?>
				<div class="zw-prose">
					<?php
					the_content();
					wp_link_pages( array( 'before' => '<nav class="zw-pagination my-6"><div class="nav-links">', 'after' => '</div></nav>', 'link_before' => '<span class="page-numbers">', 'link_after' => '</span>' ) );
					?>
				</div>
				<?php zw_ad_slot( 'after_content' ); ?>

				<?php get_template_part( 'template-parts/single/event-block' ); ?>

				<?php if ( $zw_tags && ! is_wp_error( $zw_tags ) ) : ?>
					<div class="flex flex-wrap items-center gap-2 pt-2">
						<?php foreach ( $zw_tags as $zw_tag ) : ?>
							<a href="<?php echo esc_url( get_tag_link( $zw_tag ) ); ?>" rel="tag" class="px-3.5 py-1.5 rounded-full bg-white hover:bg-zw-ink text-zw-ink hover:text-white font-sofia font-bold text-xs nf-shadow-light transition-all"><?php echo esc_html( $zw_tag->name ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</article>

			<?php
			get_template_part( 'template-parts/single/author-box' );
			get_template_part( 'template-parts/single/google-buttons' );
			get_template_part( 'template-parts/most-read', null, array( 'layout' => 'rail' ) );
			?>

			<?php if ( comments_open() || get_comments_number() ) : ?>
				<section id="commenti" class="mt-10">
					<?php zw_section_title( 'Commenti' ); ?>
					<div class="bg-white rounded-[22px] nf-shadow-light p-4 sm:p-6 zw-comments">
						<?php comments_template(); ?>
					</div>
				</section>
			<?php endif; ?>

			<section class="mt-12" aria-label="Ultime notizie">
				<?php
				$zw_news_url = zw_cat( 'news' ) ? zw_cat_url( 'news' ) : home_url( '/#notizie' );
				zw_section_title( 'Ultime Notizie', array( 'link' => $zw_news_url, 'link_label' => 'Tutte →' ) );
				$zw_feed = zw_feed( array( 'per_page' => 10, 'exclude' => array( get_the_ID() ) ) );
				?>
				<div class="zw-feed space-y-4"><?php echo $zw_feed['html']; // phpcs:ignore ?></div>
				<div class="flex justify-center mt-7">
					<a href="<?php echo esc_url( $zw_news_url ); ?>" class="px-6 py-3 rounded-full bg-zw-ink hover:bg-zw-red text-white font-sofia font-extrabold text-sm transition-all">Vai a tutte le notizie →</a>
				</div>
			</section>
		</div>

		<aside class="hidden lg:block lg:col-span-5 xl:col-span-4 xl:col-start-9 <?php echo $zw_thumb_id ? 'lg:pt-32' : ''; ?>" aria-label="Da non perdere">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</main>

<?php
get_footer();
