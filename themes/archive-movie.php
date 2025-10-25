<?php
/**
 * Default archive template for TMDB movies.
 *
 * @package TMDBPlugin\Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main tmdb-archive-movie">
    <?php if ( have_posts() ) : ?>
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
        </header>

        <div class="tmdb-movie-archive">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'tmdb-movie-card' ); ?>>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a class="tmdb-movie-card__poster" href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </a>
                    <?php endif; ?>

                    <div class="tmdb-movie-card__content">
                        <h2 class="tmdb-movie-card__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <div class="tmdb-movie-card__excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </div>
                </article>
                <?php
            endwhile;
            ?>
        </div>

        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e( 'No movies found.', 'tmdb-plugin' ); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
