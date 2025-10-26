<?php
/**
 * Sidebar template.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<aside id="secondary" class="sidebar widget-area">
    <?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
        <?php dynamic_sidebar( 'sidebar-1' ); ?>
    <?php else : ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3"><?php esc_html_e( 'About This Site', 'tmdb-theme' ); ?></h2>
                <p class="mb-0 text-muted"><?php esc_html_e( 'Use this widget area to share information about your site, add navigation, or display TMDB movie filters.', 'tmdb-theme' ); ?></p>
            </div>
        </div>
    <?php endif; ?>
</aside>
