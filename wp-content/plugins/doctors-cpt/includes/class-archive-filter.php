<?php
/**
 * Фильтрация архива докторов
 *
 * @package DoctorsCPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Doctors_CPT_Archive_Filter
 * 
 * АРХИТЕКТУРНОЕ РЕШЕНИЕ: Используется pre_get_posts потому что:
 * 1. Модифицируем основной запрос - пагинация работает автоматически
 * 2. Лучшая производительность - один запрос вместо двух
 * 3. Правильная работа с шаблонами и conditional tags
 */
class Doctors_CPT_Archive_Filter {

    const ALLOWED_SORT = array(
        'rating_desc'     => array( 'meta_key' => '_doctor_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC' ),
        'price_asc'       => array( 'meta_key' => '_doctor_price_from', 'orderby' => 'meta_value_num', 'order' => 'ASC' ),
        'experience_desc' => array( 'meta_key' => '_doctor_experience', 'orderby' => 'meta_value_num', 'order' => 'DESC' ),
        'date_desc'       => array( 'orderby' => 'date', 'order' => 'DESC' ),
        'title_asc'       => array( 'orderby' => 'title', 'order' => 'ASC' ),
    );

    public function __construct() {
        add_action( 'pre_get_posts', array( $this, 'modify_archive_query' ) );
        add_filter( 'template_include', array( $this, 'archive_template' ) );
    }

    public function modify_archive_query( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( ! $this->is_doctors_archive( $query ) ) {
            return;
        }

        $query->set( 'posts_per_page', 9 );
        $this->apply_taxonomy_filter( $query, 'specialization' );
        $this->apply_taxonomy_filter( $query, 'city' );
        $this->apply_sorting( $query );
    }

    private function is_doctors_archive( $query ) {
        return $query->is_post_type_archive( 'doctors' ) ||
               $query->is_tax( 'specialization' ) ||
               $query->is_tax( 'city' );
    }

    private function apply_taxonomy_filter( $query, $taxonomy ) {
        if ( empty( $_GET[ $taxonomy ] ) ) {
            return;
        }

        $term_slug = sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) );
        if ( empty( $term_slug ) ) {
            return;
        }

        $term = get_term_by( 'slug', $term_slug, $taxonomy );
        if ( ! $term ) {
            return;
        }

        $tax_query = $query->get( 'tax_query' );
        if ( ! is_array( $tax_query ) ) {
            $tax_query = array();
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $term_slug,
        );

        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        $query->set( 'tax_query', $tax_query );
    }

    private function apply_sorting( $query ) {
        if ( empty( $_GET['sort'] ) ) {
            $query->set( 'meta_key', '_doctor_rating' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'DESC' );
            return;
        }

        $sort_key = sanitize_key( wp_unslash( $_GET['sort'] ) );

        if ( ! array_key_exists( $sort_key, self::ALLOWED_SORT ) ) {
            return;
        }

        $sort_params = self::ALLOWED_SORT[ $sort_key ];

        if ( isset( $sort_params['meta_key'] ) ) {
            $query->set( 'meta_key', $sort_params['meta_key'] );
        }
        
        $query->set( 'orderby', $sort_params['orderby'] );
        $query->set( 'order', $sort_params['order'] );
    }

    public function archive_template( $template ) {
        if ( is_post_type_archive( 'doctors' ) || is_tax( 'specialization' ) || is_tax( 'city' ) ) {
            $custom_template = locate_template( 'archive-doctors.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }
        return $template;
    }
}

function doctors_render_filter_form() {
    $specializations = get_terms( array( 'taxonomy' => 'specialization', 'hide_empty' => true ) );
    $cities = get_terms( array( 'taxonomy' => 'city', 'hide_empty' => true ) );

    $current_specialization = isset( $_GET['specialization'] ) ? sanitize_title( wp_unslash( $_GET['specialization'] ) ) : '';
    $current_city = isset( $_GET['city'] ) ? sanitize_title( wp_unslash( $_GET['city'] ) ) : '';
    $current_sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'rating_desc';

    $sort_options = array(
        'rating_desc'     => __( 'По рейтингу (высокий)', 'doctors-cpt' ),
        'price_asc'       => __( 'По цене (низкая)', 'doctors-cpt' ),
        'experience_desc' => __( 'По стажу (большой)', 'doctors-cpt' ),
        'date_desc'       => __( 'По дате добавления', 'doctors-cpt' ),
        'title_asc'       => __( 'По имени (А-Я)', 'doctors-cpt' ),
    );
    ?>
    <div class="doctors-filter-wrap">
        <form class="doctors-filter-form" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'doctors' ) ); ?>">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter-specialization"><?php esc_html_e( 'Специализация', 'doctors-cpt' ); ?></label>
                    <select name="specialization" id="filter-specialization">
                        <option value=""><?php esc_html_e( 'Все специализации', 'doctors-cpt' ); ?></option>
                        <?php if ( ! is_wp_error( $specializations ) && ! empty( $specializations ) ) : ?>
                            <?php foreach ( $specializations as $term ) : ?>
                                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_specialization, $term->slug ); ?>>
                                    <?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-city"><?php esc_html_e( 'Город', 'doctors-cpt' ); ?></label>
                    <select name="city" id="filter-city">
                        <option value=""><?php esc_html_e( 'Все города', 'doctors-cpt' ); ?></option>
                        <?php if ( ! is_wp_error( $cities ) && ! empty( $cities ) ) : ?>
                            <?php foreach ( $cities as $term ) : ?>
                                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_city, $term->slug ); ?>>
                                    <?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-sort"><?php esc_html_e( 'Сортировка', 'doctors-cpt' ); ?></label>
                    <select name="sort" id="filter-sort">
                        <?php foreach ( $sort_options as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_sort, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group filter-submit">
                    <button type="submit" class="btn btn-filter"><?php esc_html_e( 'Применить', 'doctors-cpt' ); ?></button>
                    <?php if ( $current_specialization || $current_city || ( $current_sort && 'rating_desc' !== $current_sort ) ) : ?>
                        <a href="<?php echo esc_url( get_post_type_archive_link( 'doctors' ) ); ?>" class="btn btn-reset">
                            <?php esc_html_e( 'Сбросить', 'doctors-cpt' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function doctors_pagination() {
    global $wp_query;

    $total_pages = $wp_query->max_num_pages;
    if ( $total_pages <= 1 ) {
        return;
    }

    $current_page = max( 1, get_query_var( 'paged' ) );
    $base_url = get_post_type_archive_link( 'doctors' );
    $query_args = array();

    if ( ! empty( $_GET['specialization'] ) ) {
        $query_args['specialization'] = sanitize_title( wp_unslash( $_GET['specialization'] ) );
    }
    if ( ! empty( $_GET['city'] ) ) {
        $query_args['city'] = sanitize_title( wp_unslash( $_GET['city'] ) );
    }
    if ( ! empty( $_GET['sort'] ) ) {
        $query_args['sort'] = sanitize_key( wp_unslash( $_GET['sort'] ) );
    }

    echo '<nav class="doctors-pagination" aria-label="' . esc_attr__( 'Навигация по страницам', 'doctors-cpt' ) . '">';
    echo '<ul class="pagination-list">';

    if ( $current_page > 1 ) {
        $prev_url = add_query_arg( array_merge( $query_args, array( 'paged' => $current_page - 1 ) ), $base_url );
        echo '<li class="pagination-item pagination-prev"><a href="' . esc_url( $prev_url ) . '">&laquo;</a></li>';
    }

    $range = 2;
    for ( $i = 1; $i <= $total_pages; $i++ ) {
        if ( $i === 1 || $i === $total_pages || ( $i >= $current_page - $range && $i <= $current_page + $range ) ) {
            $page_url = add_query_arg( array_merge( $query_args, array( 'paged' => $i ) ), $base_url );
            $is_current = ( $i === $current_page );
            
            echo '<li class="pagination-item' . ( $is_current ? ' active' : '' ) . '">';
            if ( $is_current ) {
                echo '<span class="current">' . esc_html( $i ) . '</span>';
            } else {
                echo '<a href="' . esc_url( $page_url ) . '">' . esc_html( $i ) . '</a>';
            }
            echo '</li>';
        } elseif ( $i === $current_page - $range - 1 || $i === $current_page + $range + 1 ) {
            echo '<li class="pagination-item pagination-dots"><span>...</span></li>';
        }
    }

    if ( $current_page < $total_pages ) {
        $next_url = add_query_arg( array_merge( $query_args, array( 'paged' => $current_page + 1 ) ), $base_url );
        echo '<li class="pagination-item pagination-next"><a href="' . esc_url( $next_url ) . '">&raquo;</a></li>';
    }

    echo '</ul></nav>';
}
