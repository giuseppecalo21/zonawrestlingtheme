<?php
/**
 * Commenti nativi di WordPress. Con il plugin Disqus attivo questo file non viene
 * usato: è Disqus a sostituirlo con il proprio modulo.
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<div id="comments">
	<?php if ( have_comments() ) : ?>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'avatar_size' => 44, 'short_ping' => true ) ); ?></ol>
		<?php the_comments_pagination( array( 'class' => 'zw-pagination my-6' ) ); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</div>
