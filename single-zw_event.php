<?php
/**
 * Pagina di un evento. Il riquadro "Dettagli evento" e l'elenco delle notizie
 * collegate sono aggiunti al testo dal plugin ZW Events Calendar.
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$zw_event = zw_event( get_the_ID() );
$zw_count = $zw_event ? zw_event_countdown( $zw_event, 'Mancano' ) : '';

zw_stage_open();
?>
	<section class="relative z-10 max-w-[1380px] mx-auto px-4 sm:px-6 pt-5 sm:pt-12">
		<nav class="hidden sm:flex items-center gap-2 text-xs text-white/55 font-medium mb-5" aria-label="Percorso">
			<?php foreach ( zw_breadcrumb() as $zw_i => $zw_crumb ) : ?>
				<?php echo $zw_i ? '<span aria-hidden="true">/</span>' : ''; ?>
				<a href="<?php echo esc_url( $zw_crumb['url'] ); ?>" class="hover:text-white transition-colors"><?php echo esc_html( $zw_crumb['name'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-10 items-start">
			<div class="lg:col-span-7 lg:pb-20">
				<div class="flex flex-wrap items-center gap-2 mb-4">
					<?php if ( $zw_event && $zw_event['fed'] ) : ?>
						<span class="px-3.5 py-1.5 rounded-full bg-zw-red text-white font-sofia font-extrabold text-xs tracking-wide uppercase nf-red-glow"><?php echo esc_html( $zw_event['fed'] ); ?></span>
					<?php endif; ?>
					<span data-zw-countdown="<?php echo esc_attr( $zw_event ? $zw_event['date'] : '' ); ?>" data-zw-tpl="mancano" data-zw-past="Evento concluso" class="px-3 py-1.5 rounded-full bg-white text-zw-ink font-sofia font-extrabold text-xs empty:hidden"><?php echo esc_html( $zw_count ? $zw_count : ( $zw_event && $zw_event['date'] ? 'Evento concluso' : '' ) ); ?></span>
				</div>
				<h1 class="font-sofia-display font-black text-[30px] leading-[1.08] sm:text-5xl lg:text-[58px] sm:leading-[1.06] text-white"><?php the_title(); ?></h1>
				<?php if ( $zw_event && ( $zw_event['date_txt'] || $zw_event['place'] ) ) : ?>
					<p class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm sm:text-base text-white/80 mt-4 sm:mt-6">
						<?php if ( $zw_event['date_txt'] ) : ?>
							<time datetime="<?php echo esc_attr( $zw_event['date'] ); ?>" class="flex items-center gap-2 font-semibold"><?php echo zw_icon( 'calendar', 'w-4 h-4 text-zw-redSoft' ); // phpcs:ignore ?><?php echo esc_html( $zw_event['date_txt'] ); ?></time>
						<?php endif; ?>
						<?php if ( $zw_event['place'] ) : ?>
							<span class="text-white/65"><?php echo esc_html( $zw_event['place'] ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="lg:col-span-5 relative z-20 -mb-8 lg:-mb-24 lg:mt-1.5">
					<figure class="relative rounded-bento overflow-hidden nf-shadow-dark bg-zw-surfaceDark aspect-video m-0">
						<?php echo zw_image( get_post_thumbnail_id(), 1100, array( 'class' => 'w-full h-full object-cover', 'sizes' => '(max-width: 1024px) 100vw, 540px', 'fetchpriority' => 'high', 'alt' => get_the_title() ) ); // phpcs:ignore ?>
					</figure>
				</div>
			<?php endif; ?>
		</div>
	</section>
</div><!-- fascia scura -->

<main id="contenuto" class="max-w-[1380px] mx-auto px-4 sm:px-6 pt-14 lg:pt-12 pb-14">
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
		<div class="lg:col-span-7 min-w-0">
			<article <?php post_class(); ?>>
				<div class="zw-prose"><?php the_content(); ?></div>
			</article>
			<?php get_template_part( 'template-parts/single/google-buttons' ); ?>
		</div>
		<aside class="lg:col-span-5 xl:col-span-4 xl:col-start-9 min-w-0 <?php echo has_post_thumbnail() ? 'lg:pt-24' : ''; ?>" aria-label="Da non perdere">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</main>
<?php
get_footer();
