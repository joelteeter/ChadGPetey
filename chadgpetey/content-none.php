<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Chad G Petey
 */
?>

<section class="no-results not-found">
	<header class="page-header alignwide">
		<?php if ( is_search() ) : ?>

			<h1 class="page-title">
				<span>Nothing found for the search term <?php esc_html( get_search_query() ) ?> </span>
			</h1>

		<?php else : ?>

			<h1 class="page-title"><?php esc_html_e( 'Nothing here', 'twentytwentyone' ); ?></h1>

		<?php endif; ?>
	</header><!-- .page-header -->

	<div class="page-content default-max-width">
		<?php
		// Linking to the admin-ajax.php file. Nonce check included for extra security. Note the "chad_post" class for JS enabled clients.
		$nonce = wp_create_nonce("chad_post_nonce");
		?>
		<div id="generate-new-post" data-nonce="<?php echo $nonce; ?>" data-search-term="<?php echo the_search_query(  )?>">No posts found, click this to have chad generate one</div>
		<div id="put-response-here"></div>

	</div><!-- .page-content -->
</section><!-- .no-results -->
