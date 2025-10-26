<?php
/**
 * Comments template.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title h4 mb-4">
            <?php
            $comment_count = get_comments_number();
            if ( '1' === $comment_count ) {
                printf( esc_html__( '1 komentář k “%s”', 'tmdb-theme' ), '<span>' . get_the_title() . '</span>' );
            } else {
                printf( esc_html__( '%1$s komentářů k “%2$s”', 'tmdb-theme' ), esc_html( $comment_count ), '<span>' . get_the_title() . '</span>' );
            }
            ?>
        </h2>

        <ol class="comment-list list-unstyled mb-5">
            <?php
            wp_list_comments(
                array(
                    'style'      => 'ol',
                    'avatar_size'=> 60,
                    'short_ping' => true,
                    'walker'     => null,
                )
            );
            ?>
        </ol>

        <?php the_comments_navigation(); ?>
    <?php endif; ?>

    <?php if ( ! comments_open() ) : ?>
        <p class="no-comments alert alert-info"><?php esc_html_e( 'Komentáře jsou uzavřeny.', 'tmdb-theme' ); ?></p>
    <?php endif; ?>

    <?php comment_form( array( 'class_form' => 'comment-form row g-3' ) ); ?>
</div>
