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
    <header class="mb-5">
        <h1 class="display-6 fw-semibold">
            <?php printf( esc_html__( 'Výsledky hledání pro: %s', 'tmdb-theme' ), '<span class="fw-bold">' . get_search_query() . '</span>' ); ?>
        </h1>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="row g-4">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <div class="col-md-6">
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card shadow-sm h-100' ); ?>>
                        <div class="card-body d-flex flex-column">
                            <h2 class="card-title h4">
                                <a class="stretched-link text-reset text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="card-text text-muted small mb-3">
                                <?php echo esc_html( get_the_date() ); ?>
                            </div>
                            <div class="card-text">
                                <?php the_excerpt(); ?>
                            </div>
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
