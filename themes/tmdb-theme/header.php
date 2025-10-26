<?php
/**
 * Theme header.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-100">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'd-flex flex-column min-vh-100 bg-light text-body' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site flex-grow-1 d-flex flex-column">
    <header id="masthead" class="site-header py-3 border-bottom">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <?php
                    if ( has_custom_logo() ) {
                        the_custom_logo();
                    } else {
                        bloginfo( 'name' );
                    }
                    ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primary-menu" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle navigation', 'tmdb-theme' ); ?>">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="primary-menu">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'primary',
                            'menu_class'     => 'navbar-nav ms-auto mb-2 mb-lg-0',
                            'container'      => false,
                            'fallback_cb'    => '__return_false',
                            'depth'          => 2,
                        )
                    );
                    ?>
                </div>
            </div>
        </nav>
    </header>

    <main id="content" class="site-content flex-grow-1">
