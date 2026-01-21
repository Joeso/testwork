<?php
/**
 * Мета-боксы для CPT Doctors
 *
 * @package DoctorsCPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Doctors_CPT_Meta_Boxes
 */
class Doctors_CPT_Meta_Boxes {

    const META_PREFIX = '_doctor_';

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_doctors', array( $this, 'save_meta_boxes' ), 10, 2 );
        add_action( 'init', array( $this, 'register_meta_fields' ) );
    }

    public function register_meta_fields() {
        $meta_fields = array(
            'experience' => array( 'type' => 'integer', 'description' => 'Стаж работы врача в годах', 'default' => 0 ),
            'price_from' => array( 'type' => 'integer', 'description' => 'Минимальная цена приема', 'default' => 0 ),
            'rating'     => array( 'type' => 'number', 'description' => 'Рейтинг врача от 0 до 5', 'default' => 0 ),
        );

        foreach ( $meta_fields as $key => $args ) {
            register_post_meta( 'doctors', self::META_PREFIX . $key, array(
                'type'              => $args['type'],
                'description'       => $args['description'],
                'single'            => true,
                'default'           => $args['default'],
                'show_in_rest'      => true,
                'sanitize_callback' => 'rating' === $key ? array( $this, 'sanitize_rating' ) : 'absint',
                'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
            ));
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'doctor_details',
            __( 'Данные врача', 'doctors-cpt' ),
            array( $this, 'render_meta_box' ),
            'doctors',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'doctor_meta_box', 'doctor_meta_box_nonce' );

        $experience = get_post_meta( $post->ID, self::META_PREFIX . 'experience', true );
        $price_from = get_post_meta( $post->ID, self::META_PREFIX . 'price_from', true );
        $rating     = get_post_meta( $post->ID, self::META_PREFIX . 'rating', true );
        ?>
        <style>
            .doctor-meta-fields { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
            .doctor-meta-field { display: flex; flex-direction: column; }
            .doctor-meta-field label { font-weight: 600; margin-bottom: 5px; }
            .doctor-meta-field input { padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; }
            .doctor-meta-field .description { color: #646970; font-size: 12px; margin-top: 4px; }
            .rating-stars { display: flex; align-items: center; gap: 5px; }
            .rating-value { font-weight: bold; min-width: 30px; }
            @media (max-width: 782px) { .doctor-meta-fields { grid-template-columns: 1fr; } }
        </style>

        <div class="doctor-meta-fields">
            <div class="doctor-meta-field">
                <label for="doctor_experience"><?php esc_html_e( 'Стаж врача (лет)', 'doctors-cpt' ); ?></label>
                <input type="number" id="doctor_experience" name="doctor_experience" 
                       value="<?php echo esc_attr( $experience ); ?>" min="0" max="100" step="1" placeholder="0" />
                <span class="description"><?php esc_html_e( 'Укажите количество лет опыта работы', 'doctors-cpt' ); ?></span>
            </div>

            <div class="doctor-meta-field">
                <label for="doctor_price_from"><?php esc_html_e( 'Цена от (руб)', 'doctors-cpt' ); ?></label>
                <input type="number" id="doctor_price_from" name="doctor_price_from" 
                       value="<?php echo esc_attr( $price_from ); ?>" min="0" step="100" placeholder="0" />
                <span class="description"><?php esc_html_e( 'Минимальная стоимость приема в рублях', 'doctors-cpt' ); ?></span>
            </div>

            <div class="doctor-meta-field">
                <label for="doctor_rating"><?php esc_html_e( 'Рейтинг (0-5)', 'doctors-cpt' ); ?></label>
                <div class="rating-stars">
                    <input type="range" id="doctor_rating" name="doctor_rating" 
                           value="<?php echo esc_attr( $rating ? $rating : 0 ); ?>" 
                           min="0" max="5" step="0.1" 
                           oninput="document.getElementById('rating_value').textContent = this.value" />
                    <span id="rating_value" class="rating-value"><?php echo esc_html( $rating ? $rating : '0' ); ?></span>
                    <span>*</span>
                </div>
                <span class="description"><?php esc_html_e( 'Оценка врача от пациентов', 'doctors-cpt' ); ?></span>
            </div>
        </div>
        <?php
    }

    public function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['doctor_meta_box_nonce'] ) || 
             ! wp_verify_nonce( $_POST['doctor_meta_box_nonce'], 'doctor_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['doctor_experience'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'experience', absint( $_POST['doctor_experience'] ) );
        }

        if ( isset( $_POST['doctor_price_from'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'price_from', absint( $_POST['doctor_price_from'] ) );
        }

        if ( isset( $_POST['doctor_rating'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'rating', $this->sanitize_rating( $_POST['doctor_rating'] ) );
        }
    }

    public function sanitize_rating( $value ) {
        $rating = floatval( $value );
        $rating = max( 0, min( 5, $rating ) );
        return round( $rating, 1 );
    }
}

// Хелпер-функции
function doctors_get_experience( $post_id = null ) {
    if ( null === $post_id ) $post_id = get_the_ID();
    return absint( get_post_meta( $post_id, '_doctor_experience', true ) );
}

function doctors_get_price_from( $post_id = null ) {
    if ( null === $post_id ) $post_id = get_the_ID();
    return absint( get_post_meta( $post_id, '_doctor_price_from', true ) );
}

function doctors_get_rating( $post_id = null ) {
    if ( null === $post_id ) $post_id = get_the_ID();
    return floatval( get_post_meta( $post_id, '_doctor_rating', true ) );
}

function doctors_format_experience( $years ) {
    $years = absint( $years );
    $cases = array( 2, 0, 1, 1, 1, 2 );
    $titles = array( 'год', 'года', 'лет' );
    $index = ( $years % 100 > 4 && $years % 100 < 20 ) ? 2 : $cases[ min( $years % 10, 5 ) ];
    return $years . ' ' . $titles[ $index ];
}

function doctors_format_price( $price ) {
    return number_format( absint( $price ), 0, '.', ' ' ) . ' руб.';
}

function doctors_render_rating_stars( $rating ) {
    $rating = floatval( $rating );
    $full_stars = floor( $rating );
    $half_star = ( $rating - $full_stars ) >= 0.5;
    $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
    
    $output = '<span class="doctor-rating-stars" title="' . esc_attr( $rating ) . ' из 5">';
    for ( $i = 0; $i < $full_stars; $i++ ) $output .= '<span class="star star-full">*</span>';
    if ( $half_star ) $output .= '<span class="star star-half">*</span>';
    for ( $i = 0; $i < $empty_stars; $i++ ) $output .= '<span class="star star-empty">o</span>';
    $output .= '<span class="rating-number">(' . esc_html( $rating ) . ')</span></span>';
    
    return $output;
}
