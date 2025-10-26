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
        <header class="mb-5 text-center text-md-start">
            <h1 class="display-5 fw-bold text-info mb-3"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description( '<p class="text-muted lead mb-0">', '</p>' ); ?>
        </header>

        <div class="row g-4">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <div class="col-sm-6 col-xl-4">
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card h-100 bg-dark-subtle bg-opacity-10 border-0 shadow-sm' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="ratio ratio-2x3">
                                <?php the_post_thumbnail( 'large', [ 'class' => 'w-100 h-100 object-fit-cover rounded-top' ] ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h2 class="card-title h4">
                                <a class="stretched-link text-info fw-semibold" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="card-text text-muted small mb-3"><?php echo esc_html( get_the_date() ); ?></div>
                            <div class="card-text text-light">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    </article>
                </div>
                <?php
            endwhile;
            ?>
        </div>

        <div class="mt-5">
            <?php tmdb_theme_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="alert alert-warning"><?php esc_html_e( 'Žádné filmy zatím nejsou k dispozici.', 'tmdb-theme' ); ?></div>
    <?php endif; ?>
</div>

<?php
get_footer();
