<?php
/**
 * Pagina.
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();
get_template_part( 'template-parts/page-hero', null, array( 'title' => get_the_title() ) );
?>
<main id="contenuto" class="max-w-[860px] mx-auto px-4 sm:px-6 py-10">
	<article <?php post_class( 'bg-white rounded-[22px] nf-shadow-light p-5 sm:p-9' ); ?>>
		<div class="zw-prose"><?php the_content(); ?></div>
	</article>
	<?php if ( comments_open() || get_comments_number() ) : ?>
		<section id="commenti" class="mt-10 bg-white rounded-[22px] nf-shadow-light p-4 sm:p-6 zw-comments"><?php comments_template(); ?></section>
	<?php endif; ?>
</main>
<?php
get_footer();
