<?php
/**
 * Theme footer.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    </main><!-- #content -->

    <footer id="colophon" class="site-footer mt-auto py-4 border-top bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <span class="text-muted">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer',
                            'menu_class'     => 'nav justify-content-center justify-content-md-end',
                            'container'      => false,
                            'fallback_cb'    => false,
                            'depth'          => 1,
                        )
                    );
                    ?>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
