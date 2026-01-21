<?php
/**
 * Archive Template for Doctors CPT
 */

get_header();
developers_breadcrumbs();
?>

<header class="archive-header">
    <h1 class="page-title">
        <?php
        if ( is_tax( 'specialization' ) ) {
            printf( esc_html__( 'Доктора: %s', 'developers-theme' ), single_term_title( '', false ) );
        } elseif ( is_tax( 'city' ) ) {
            printf( esc_html__( 'Доктора в городе: %s', 'developers-theme' ), single_term_title( '', false ) );
        } else {
            esc_html_e( 'Наши доктора', 'developers-theme' );
        }
        ?>
    </h1>
    <p class="page-description">
        <?php
        $description = get_the_archive_description();
        if ( $description ) {
            echo wp_kses_post( $description );
        } else {
            esc_html_e( 'Выберите специалиста для записи на прием', 'developers-theme' );
        }
        ?>
    </p>
</header>

<?php
if ( function_exists( 'doctors_render_filter_form' ) ) {
    doctors_render_filter_form();
}
?>

<?php if ( have_posts() ) : ?>
    <div class="doctors-grid">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part( 'template-parts/doctor', 'card' ); ?>
        <?php endwhile; ?>
    </div>

    <?php
    if ( function_exists( 'doctors_pagination' ) ) {
        doctors_pagination();
    }
    ?>
<?php else : ?>
    <div class="no-posts">
        <h2><?php esc_html_e( 'Доктора не найдены', 'developers-theme' ); ?></h2>
        <p><?php esc_html_e( 'Попробуйте изменить параметры фильтрации', 'developers-theme' ); ?></p>
    </div>
<?php endif; ?>

<?php
get_footer();
