<?php
/**
 * Page template.
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
        <div class="col-lg-8">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'card shadow-sm border-0' ); ?>>
                    <header class="card-body pb-0">
                        <h1 class="card-title h2 fw-semibold mb-3"><?php the_title(); ?></h1>
                    </header>
                    <div class="card-body">
                        <?php the_content(); ?>
                    </div>
                </article>

                <?php
                if ( comments_open() || get_comments_number() ) {
                    echo '<div class="mt-5">';
                    comments_template();
                    echo '</div>';
                }
            endwhile;
            ?>
        </div>
        <div class="col-lg-4">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php
get_footer();
