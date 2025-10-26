<?php
/**
 * 404 template.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8 text-center text-lg-start">
            <div class="card border-0 shadow-sm p-4">
                <h1 class="h2 fw-semibold text-primary mb-3"><?php esc_html_e( '404', 'tmdb-theme' ); ?></h1>
                <p class="mb-4 text-muted"><?php esc_html_e( 'Stránku, kterou hledáte, se nepodařilo nalézt.', 'tmdb-theme' ); ?></p>
                <div class="mb-4">
                    <?php get_search_form(); ?>
                </div>
                <a class="btn btn-outline-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <?php esc_html_e( 'Zpět na úvod', 'tmdb-theme' ); ?>
                </a>
            </div>
        </div>
        <div class="col-lg-4">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php
get_footer();
