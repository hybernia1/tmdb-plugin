<?php
/**
 * Front page template for the TMDB theme.
 *
 * @package TMDB_Theme
 */

use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$movie_archive = post_type_exists( 'movie' ) ? get_post_type_archive_link( 'movie' ) : '';
$blog_page_id  = (int) get_option( 'page_for_posts' );
$blog_link     = $blog_page_id > 0 ? get_permalink( $blog_page_id ) : '';

$movie_query = null;

if ( post_type_exists( 'movie' ) ) {
    $movie_query = new WP_Query(
        array(
            'post_type'      => 'movie',
            'posts_per_page' => 8,
            'no_found_rows'  => true,
        )
    );
}

$blog_query = new WP_Query(
    array(
        'post_type'           => 'post',
        'posts_per_page'      => 4,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    )
);
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php if ( '' !== get_the_content() ) : ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'card shadow-sm border-0 mb-5' ); ?>>
                            <div class="card-body">
                                <?php if ( '' !== get_the_title() ) : ?>
                                    <h1 class="card-title h3 fw-bold mb-3"><?php the_title(); ?></h1>
                                <?php endif; ?>
                                <div class="card-text entry-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>
                        </article>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if ( $movie_query instanceof WP_Query && $movie_query->have_posts() ) : ?>
                <section class="mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 fw-semibold mb-0"><?php esc_html_e( 'Nejnovější filmy', 'tmdb-theme' ); ?></h2>
                        <?php if ( '' !== $movie_archive ) : ?>
                            <a class="link-primary" href="<?php echo esc_url( $movie_archive ); ?>"><?php esc_html_e( 'Zobrazit vše', 'tmdb-theme' ); ?></a>
                        <?php endif; ?>
                    </div>
                    <ul class="list-group list-group-flush movie-list">
                        <?php
                        while ( $movie_query->have_posts() ) :
                            $movie_query->the_post();

                            $post_id         = get_the_ID();
                            $release_date    = '';
                            $release_raw     = (string) get_post_meta( $post_id, 'TMDB_release_date', true );
                            $vote_average    = (float) get_post_meta( $post_id, 'TMDB_vote_average', true );
                            $genres          = get_the_terms( $post_id, TMDB_Taxonomies::GENRE );

                            if ( '' !== $release_raw ) {
                                $timestamp = strtotime( $release_raw );

                                if ( false !== $timestamp ) {
                                    $release_date = wp_date( get_option( 'date_format' ), $timestamp );
                                } else {
                                    $release_date = $release_raw;
                                }
                            }

                            $genre_names = array();

                            if ( $genres && ! is_wp_error( $genres ) ) {
                                foreach ( $genres as $genre ) {
                                    if ( $genre instanceof WP_Term ) {
                                        $genre_names[] = $genre->name;
                                    }
                                }
                            }
                            ?>
                            <li class="list-group-item movie-list-item">
                                <div class="d-flex gap-3 align-items-start">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <div class="movie-list-poster rounded overflow-hidden flex-shrink-0">
                                            <?php the_post_thumbnail( 'medium', array( 'class' => 'img-fluid object-fit-cover' ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 position-relative">
                                        <h3 class="h5 mb-1">
                                            <a class="stretched-link text-reset text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        <div class="small text-muted mb-1">
                                            <?php if ( '' !== $release_date ) : ?>
                                                <span><?php echo esc_html( $release_date ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( $vote_average > 0 ) : ?>
                                                <span class="ms-2">&#9733; <?php echo esc_html( number_format_i18n( $vote_average, 1 ) ); ?>/10</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ( ! empty( $genre_names ) ) : ?>
                                            <div class="small text-muted">
                                                <?php echo esc_html( implode( ', ', $genre_names ) ); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </ul>
                </section>
            <?php elseif ( post_type_exists( 'movie' ) ) : ?>
                <div class="alert alert-info mb-5">
                    <?php esc_html_e( 'Zatím nebyly přidány žádné filmy.', 'tmdb-theme' ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $blog_query->have_posts() ) : ?>
                <section class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 fw-semibold mb-0"><?php esc_html_e( 'Aktuální články', 'tmdb-theme' ); ?></h2>
                        <?php if ( '' !== $blog_link ) : ?>
                            <a class="link-primary" href="<?php echo esc_url( $blog_link ); ?>"><?php esc_html_e( 'Blog', 'tmdb-theme' ); ?></a>
                        <?php endif; ?>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php
                        while ( $blog_query->have_posts() ) :
                            $blog_query->the_post();
                            ?>
                            <li class="list-group-item">
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'd-flex flex-column flex-sm-row gap-3 align-items-sm-center' ); ?>>
                                    <div class="flex-grow-1">
                                        <h3 class="h5 mb-1">
                                            <a class="stretched-link text-reset text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        <div class="small text-muted mb-2"><?php echo esc_html( get_the_date() ); ?></div>
                                        <div class="mb-0 text-muted">
                                            <?php the_excerpt(); ?>
                                        </div>
                                    </div>
                                </article>
                            </li>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </ul>
                </section>
            <?php else : ?>
                <div class="alert alert-info">
                    <?php esc_html_e( 'Žádné články zatím nejsou k dispozici.', 'tmdb-theme' ); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php
get_footer();
