<?php
/**
 * Template Part: Doctor Card
 */

$experience = function_exists( 'doctors_get_experience' ) ? doctors_get_experience() : 0;
$price_from = function_exists( 'doctors_get_price_from' ) ? doctors_get_price_from() : 0;
$rating     = function_exists( 'doctors_get_rating' ) ? doctors_get_rating() : 0;
$specializations = get_the_terms( get_the_ID(), 'specialization' );
?>

<article class="doctor-card" id="doctor-<?php the_ID(); ?>">
    <div class="doctor-card__image">
        <?php if ( has_post_thumbnail() ) : ?>
            <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'doctor-card' ); ?></a>
        <?php else : ?>
            <span class="doctor-card__image-placeholder">[doctor]</span>
        <?php endif; ?>
    </div>
    
    <div class="doctor-card__content">
        <h3 class="doctor-card__name">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
        <?php if ( $specializations && ! is_wp_error( $specializations ) ) : ?>
            <div class="doctor-card__specialization">
                <?php
                $specs = array_slice( $specializations, 0, 2 );
                $spec_links = array();
                foreach ( $specs as $term ) {
                    $spec_links[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
                }
                echo implode( ', ', $spec_links );
                if ( count( $specializations ) > 2 ) {
                    echo ' <span class="more">+' . ( count( $specializations ) - 2 ) . '</span>';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="doctor-card__meta">
            <div class="doctor-card__meta-item">
                <span><?php echo function_exists( 'doctors_format_experience' ) ? esc_html( doctors_format_experience( $experience ) ) : esc_html( $experience . ' лет' ); ?></span>
            </div>
            <div class="doctor-card__meta-item">
                <span>от <?php echo function_exists( 'doctors_format_price' ) ? esc_html( doctors_format_price( $price_from ) ) : esc_html( number_format( $price_from, 0, '.', ' ' ) . ' руб.' ); ?></span>
            </div>
        </div>
        
        <div class="doctor-card__rating">
            <?php echo function_exists( 'doctors_render_rating_stars' ) ? doctors_render_rating_stars( $rating ) : '<span>Рейтинг: ' . esc_html( $rating ) . '</span>'; ?>
        </div>
        
        <a href="<?php the_permalink(); ?>" class="doctor-card__link">Подробнее</a>
    </div>
</article>
