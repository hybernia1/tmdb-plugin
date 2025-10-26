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
                    <div class="section-heading d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 fw-semibold mb-0"><?php esc_html_e( 'Nejnovější filmy', 'tmdb-theme' ); ?></h2>
                        <?php if ( '' !== $movie_archive ) : ?>
                            <a class="link-primary fw-semibold" href="<?php echo esc_url( $movie_archive ); ?>"><?php esc_html_e( 'Zobrazit vše', 'tmdb-theme' ); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="movie-grid row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-4">
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
                            <div class="col">
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'movie-card card h-100 border-0 shadow-sm' ); ?>>
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <a class="movie-card__poster ratio ratio-2x3" href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'medium_large', array( 'class' => 'movie-card__image', 'loading' => 'lazy' ) ); ?>
                                        </a>
                                    <?php else : ?>
                                        <a class="movie-card__poster ratio ratio-2x3 movie-card__poster--placeholder" href="<?php the_permalink(); ?>">
                                            <span class="fw-semibold text-muted text-uppercase small"><?php esc_html_e( 'Bez plakátu', 'tmdb-theme' ); ?></span>
                                        </a>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h3 class="h5 card-title mb-2">
                                            <a class="stretched-link text-reset text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        <div class="movie-card__meta d-flex flex-wrap align-items-center gap-2 mb-3">
                                            <?php if ( '' !== $release_date ) : ?>
                                                <span class="movie-card__meta-item badge rounded-pill text-body-secondary fw-semibold">
                                                    <?php echo esc_html( $release_date ); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ( $vote_average > 0 ) : ?>
                                                <span class="movie-card__rating badge rounded-pill">
                                                    &#9733; <?php echo esc_html( number_format_i18n( $vote_average, 1 ) ); ?>/10
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ( ! empty( $genre_names ) ) : ?>
                                            <ul class="movie-card__genres list-unstyled d-flex flex-wrap gap-2 mb-0">
                                                <?php foreach ( $genre_names as $genre_name ) : ?>
                                                    <li class="movie-card__genre badge rounded-pill bg-light text-secondary fw-semibold"><?php echo esc_html( $genre_name ); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </section>
            <?php elseif ( post_type_exists( 'movie' ) ) : ?>
                <div class="alert alert-info mb-5">
                    <?php esc_html_e( 'Zatím nebyly přidány žádné filmy.', 'tmdb-theme' ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $blog_query->have_posts() ) : ?>
                <section class="mb-4">
                    <div class="section-heading d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 fw-semibold mb-0"><?php esc_html_e( 'Aktuální články', 'tmdb-theme' ); ?></h2>
                        <?php if ( '' !== $blog_link ) : ?>
                            <a class="link-primary fw-semibold" href="<?php echo esc_url( $blog_link ); ?>"><?php esc_html_e( 'Blog', 'tmdb-theme' ); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="blog-grid row row-cols-1 row-cols-md-2 g-4">
                        <?php
                        while ( $blog_query->have_posts() ) :
                            $blog_query->the_post();
                            ?>
                            <div class="col">
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-card card h-100 border-0 shadow-sm' ); ?>>
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <a class="blog-card__thumbnail" href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'large', array( 'class' => 'card-img-top object-fit-cover', 'loading' => 'lazy' ) ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <div class="small text-muted mb-2"><?php echo esc_html( get_the_date() ); ?></div>
                                        <h3 class="h5 card-title mb-2">
                                            <a class="stretched-link text-reset text-decoration-none" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        <p class="card-text text-muted mb-0">
                                            <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 24 ) ); ?>
                                        </p>
                                    </div>
                                </article>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
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
