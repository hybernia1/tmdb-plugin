<?php
/**
 * Generic archive template.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="container py-5">
    <header class="mb-4">
        <?php the_archive_title( '<h1 class="display-5 fw-bold text-info">', '</h1>' ); ?>
        <?php the_archive_description( '<div class="text-muted">', '</div>' ); ?>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="row g-4">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <div class="col-md-6 col-xl-4">
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card shadow-sm h-100' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'large', [ 'class' => 'card-img-top' ] ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h2 class="card-title h4">
                                <a class="stretched-link text-light" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="card-text text-muted small mb-3"><?php echo esc_html( get_the_date() ); ?></div>
                            <div class="card-text"><?php the_excerpt(); ?></div>
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
        <div class="alert alert-warning"><?php esc_html_e( 'Nic jsme nenašli.', 'tmdb-theme' ); ?></div>
    <?php endif; ?>
</div>

<?php
get_footer();
