<?php
/**
 * Single post template.
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
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'card mb-4' ); ?>>
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'large', [ 'class' => 'card-img-top' ] ); ?>
            <?php endif; ?>

            <header class="card-body pb-0">
                <h1 class="card-title h2 mb-2"><?php the_title(); ?></h1>
                <div class="card-subtitle text-muted small">
                    <?php echo esc_html( get_the_date() ); ?>
                    &middot;
                    <?php the_author_posts_link(); ?>
                    <?php if ( has_category() ) : ?>
                        &middot;
                        <?php the_category( ', ' ); ?>
                    <?php endif; ?>
                </div>
            </header>

            <div class="card-body">
                <?php the_content(); ?>
            </div>

            <?php if ( get_the_tags() ) : ?>
                <footer class="card-body pt-0">
                    <p class="small mb-0"><?php the_tags( '', ', ', '' ); ?></p>
                </footer>
            <?php endif; ?>
        </article>

        <?php
        if ( comments_open() || get_comments_number() ) {
            echo '<div class="mt-5">';
            comments_template();
            echo '</div>';
        }

        the_post_navigation(
            array(
                'prev_text' => '<span class="d-block text-muted small">' . esc_html__( 'Předchozí', 'tmdb-theme' ) . '</span><span class="fw-semibold">%title</span>',
                'next_text' => '<span class="d-block text-muted small">' . esc_html__( 'Další', 'tmdb-theme' ) . '</span><span class="fw-semibold">%title</span>',
                'class'     => 'd-flex justify-content-between gap-3 mt-5 flex-column flex-md-row',
            )
        );
    endwhile;
    ?>
</div>

<?php
get_footer();
