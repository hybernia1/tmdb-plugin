<?php
/**
 * Default index template.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="container py-5">
    <?php if ( have_posts() ) : ?>
        <?php if ( ! is_front_page() ) : ?>
            <header class="mb-4">
                <h1 class="h2 mb-0"><?php single_post_title(); ?></h1>
            </header>
        <?php endif; ?>

        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'card mb-4' ); ?>>
                <?php if ( has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                        <?php the_post_thumbnail( 'large', [ 'class' => 'card-img-top' ] ); ?>
                    </a>
                <?php endif; ?>

                <div class="card-body">
                    <h2 class="card-title h5">
                        <a class="stretched-link text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>

                    <div class="card-text text-muted small mb-3">
                        <?php echo esc_html( get_the_date() ); ?> &middot; <?php the_author_posts_link(); ?>
                    </div>

                    <div class="card-text">
                        <?php the_excerpt(); ?>
                    </div>
                </div>
            </article>
            <?php
        endwhile;

        tmdb_theme_pagination();
    else :
        ?>
        <div class="alert alert-info">
            <?php esc_html_e( 'No posts found.', 'tmdb-theme' ); ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
