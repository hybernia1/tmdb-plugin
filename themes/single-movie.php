<?php
/**
 * Default single template for TMDB movies.
 *
 * @package TMDBPlugin\Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main tmdb-single-movie">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <?php if ( has_post_thumbnail() ) : ?>
                <div class="tmdb-movie-poster">
                    <?php the_post_thumbnail( 'large' ); ?>
                </div>
            <?php endif; ?>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
        if ( comments_open() || get_comments_number() ) {
            comments_template();
        }
    endwhile;
    ?>
</main>

<?php
get_footer();
