<?php
/**
 * Instagram Aside Template
 *
 * @link https://github.com/PremiseWP/simplicity/blob/master/content-aside.php
 *
 * @package Premise Social Media Blogger
 */

get_header();

?>

<section id="psmb-single-instagram" class="premise-block premise-clear-float">

	<div class="psmb-container premise-clear-float">

		<?php if ( have_posts() ) :

			while ( have_posts() ) : the_post(); ?>

			<article <?php post_class( 'psmb-post-instagram' ); ?>>

				<div class="psmb-post-title">
					<h1><?php the_title(); ?></h1>
				</div>

				<div class="psmb-post-content">
					<?php the_content(); ?>
				</div>

				<?php if ( get_the_terms( get_the_id(), get_post_type() . '-category' ) ) : ?>
					<div class="psmb-post-category">
						<h5 class="psmb-post-term">Categories</h5>
						<?php if ( get_post_type() !== 'post' ) : ?>
							<?php the_terms( get_the_id(), get_post_type() . '-category' ); ?>
						<?php else : ?>
							<?php the_category(); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( get_the_terms( get_the_id(), get_post_type() . '-tag' ) ) : ?>
					<div class="psmb-post-tags">
						<h5 class="psmb-post-term">Tags</h5>
						<?php if ( get_post_type() !== 'post' ) : ?>
							<?php the_terms( get_the_id(), get_post_type() . '-tag' ); ?>
						<?php else : ?>
							<?php the_tags(); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</article>

			<?php endwhile;

		else :

			// TO DO - what happens if there are no posts?

		endif; ?>

	</div>

</section>

<?php get_footer(); ?>