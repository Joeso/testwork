<?php
/**
 * Single Template for Doctors CPT
 */

get_header();
developers_breadcrumbs();

while ( have_posts() ) :
    the_post();
    
    $experience = function_exists( 'doctors_get_experience' ) ? doctors_get_experience() : 0;
    $price_from = function_exists( 'doctors_get_price_from' ) ? doctors_get_price_from() : 0;
    $rating     = function_exists( 'doctors_get_rating' ) ? doctors_get_rating() : 0;
    
    $specializations = get_the_terms( get_the_ID(), 'specialization' );
    $cities = get_the_terms( get_the_ID(), 'city' );
    ?>

    <article id="doctor-<?php the_ID(); ?>" <?php post_class( 'single-doctor' ); ?>>
        <div class="doctor-header">
            <div class="doctor-image">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'doctor-single' ); ?>
                <?php else : ?>
                    <div style="background: #e9ecef; height: 400px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 100px; color: #adb5bd;">[doctor]</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="doctor-info">
                <h1 class="doctor-title"><?php the_title(); ?></h1>
                
                <div class="doctor-taxonomies">
                    <?php if ( $specializations && ! is_wp_error( $specializations ) ) : ?>
                        <?php foreach ( $specializations as $term ) : ?>
                            <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="doctor-taxonomy-badge specialization">
                                <?php echo esc_html( $term->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ( $cities && ! is_wp_error( $cities ) ) : ?>
                        <?php foreach ( $cities as $term ) : ?>
                            <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="doctor-taxonomy-badge city">
                                <?php echo esc_html( $term->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="doctor-meta-info">
                    <div class="doctor-meta-box">
                        <div class="doctor-meta-box__label">Стаж</div>
                        <div class="doctor-meta-box__value">
                            <?php echo function_exists( 'doctors_format_experience' ) ? esc_html( doctors_format_experience( $experience ) ) : esc_html( $experience . ' лет' ); ?>
                        </div>
                    </div>
                    
                    <div class="doctor-meta-box">
                        <div class="doctor-meta-box__label">Цена от</div>
                        <div class="doctor-meta-box__value price">
                            <?php echo function_exists( 'doctors_format_price' ) ? esc_html( doctors_format_price( $price_from ) ) : esc_html( number_format( $price_from, 0, '.', ' ' ) . ' руб.' ); ?>
                        </div>
                    </div>
                    
                    <div class="doctor-meta-box">
                        <div class="doctor-meta-box__label">Рейтинг</div>
                        <div class="doctor-meta-box__value">
                            <?php echo function_exists( 'doctors_render_rating_stars' ) ? doctors_render_rating_stars( $rating ) : esc_html( $rating . ' / 5' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ( has_excerpt() ) : ?>
            <div class="doctor-excerpt"><?php the_excerpt(); ?></div>
        <?php endif; ?>
        
        <div class="doctor-content"><?php the_content(); ?></div>
    </article>

<?php endwhile; ?>

<?php
get_footer();
