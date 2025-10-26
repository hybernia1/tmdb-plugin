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
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'card shadow-sm bg-transparent border-0' ); ?>>
            <header class="card-body pb-0">
                <h1 class="card-title display-5 fw-bold text-light"><?php the_title(); ?></h1>
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

<?php
get_footer();
