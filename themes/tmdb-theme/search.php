<?php
/**
 * Search results template.
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
        <h1 class="h2">
            <?php printf( esc_html__( 'Výsledky hledání pro: %s', 'tmdb-theme' ), '<span class="fw-semibold">' . get_search_query() . '</span>' ); ?>
        </h1>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <div class="col">
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card h-100' ); ?>>
                        <div class="card-body d-flex flex-column">
                            <h2 class="card-title h5">
                                <a class="stretched-link text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
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
        <div class="alert alert-warning">
            <?php esc_html_e( 'Omlouváme se, ale nic jsme nenašli. Zkuste hledat jinak.', 'tmdb-theme' ); ?>
        </div>
        <?php get_search_form(); ?>
    <?php endif; ?>
</div>

<?php
get_footer();
