<?php
/**
 * Archive template for TMDB movies.
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
        <header class="mb-4 text-center text-md-start">
            <h1 class="h2 mb-2"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description( '<p class="text-muted small mb-0">', '</p>' ); ?>
        </header>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <div class="col">
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card h-100' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'large', [ 'class' => 'card-img-top' ] ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h2 class="card-title h5">
                                <a class="stretched-link text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="card-text text-muted small mb-3"><?php echo esc_html( get_the_date() ); ?></div>
                            <div class="card-text mb-0"><?php the_excerpt(); ?></div>
                        </div>
                    </article>
                </div>
                <?php
            endwhile;
            ?>
        </div>

        <div class="mt-4">
            <?php tmdb_theme_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="alert alert-warning"><?php esc_html_e( 'Žádné filmy zatím nejsou k dispozici.', 'tmdb-theme' ); ?></div>
    <?php endif; ?>
</div>

<?php
get_footer();
