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

<div class="container py-5 text-center">
    <h1 class="display-3 fw-bold text-info mb-3"><?php esc_html_e( '404', 'tmdb-theme' ); ?></h1>
    <p class="lead mb-4"><?php esc_html_e( 'Stránku, kterou hledáte, se nepodařilo nalézt.', 'tmdb-theme' ); ?></p>
    <?php get_search_form(); ?>
    <a class="btn btn-outline-info mt-4" href="<?php echo esc_url( home_url( '/' ) ); ?>">
        <?php esc_html_e( 'Zpět na úvod', 'tmdb-theme' ); ?>
    </a>
</div>

<?php
get_footer();
