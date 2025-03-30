<?php
/**
 * Enhanced Product Grid Shortcode - Table Version
 * 
 * @package IX_Woo_Pro_Banner
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Shop_Pro_Grid {
    private static $instance;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    protected function init() {
        add_shortcode('wpb-shop-pro-grid', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'wpb-shop-pro-grid',
            IX_WPB_PLUGIN_URL . 'assets/css/wpb-shop-pro-grid.css',
            array(),
            IX_WPB_VERSION
        );
    }

    public function render_shortcode($atts) {
        if (!class_exists('WooCommerce')) {
            return '<p class="wpb-error">' . esc_html__('WooCommerce is required for this shortcode.', 'ix-woo-pro-banner') . '</p>';
        }

        // Get admin settings with defaults
        $admin_settings = wp_parse_args(
            get_option('ix_wpb_shop_pro_grid_settings', []),
            [
                'limit' => 12,
                'columns' => 4,
                'orderby' => 'date',
                'order' => 'DESC',
                'category' => '',
                'selected_products' => [],
                'image_source' => 'both',
                'image_size' => 'woocommerce_thumbnail',
                'show_title' => true,
                'show_price' => true,
                'show_rating' => true,
                'show_add_to_cart' => true,
                'show_thumbnail' => true,
                'pagination' => false,
                'class' => ''
            ]
        );

        // Merge shortcode atts with admin settings
        $atts = shortcode_atts([
            'limit'           => $admin_settings['limit'],
            'columns'         => $admin_settings['columns'],
            'orderby'         => $admin_settings['orderby'],
            'order'           => $admin_settings['order'],
            'category'        => $admin_settings['category'],
            'ids'             => implode(',', $admin_settings['selected_products']),
            'image_source'    => $admin_settings['image_source'],
            'image_size'      => $admin_settings['image_size'],
            'show_title'      => $admin_settings['show_title'] ? 'yes' : 'no',
            'show_price'      => $admin_settings['show_price'] ? 'yes' : 'no',
            'show_rating'     => $admin_settings['show_rating'] ? 'yes' : 'no',
            'show_add_to_cart' => $admin_settings['show_add_to_cart'] ? 'yes' : 'no',
            'show_thumbnail'  => $admin_settings['show_thumbnail'] ? 'yes' : 'no',
            'pagination'      => $admin_settings['pagination'] ? 'yes' : 'no',
            'class'           => $admin_settings['class']
        ], $atts, 'wpb-shop-pro-grid');

        // Convert string booleans
        $bool_attrs = ['show_title', 'show_price', 'show_rating', 'show_add_to_cart', 'show_thumbnail', 'pagination'];
        foreach ($bool_attrs as $attr) {
            $atts[$attr] = filter_var($atts[$attr], FILTER_VALIDATE_BOOLEAN);
        }

        // Build query args
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => (int) $atts['limit'],
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order']
        ];

        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => array_map('trim', explode(',', $atts['category']))
                ]
            ];
        }

        if (!empty($atts['ids'])) {
            $args['post__in'] = array_map('trim', explode(',', $atts['ids']));
            $args['orderby'] = 'post__in';
        }

        if (!empty($atts['skus'])) {
            $args['meta_query'] = [
                [
                    'key'     => '_sku',
                    'value'   => array_map('trim', explode(',', $atts['skus'])),
                    'compare' => 'IN'
                ]
            ];
        }

        if ($atts['pagination']) {
            $args['paged'] = max(1, get_query_var('paged'));
        }

        $products = new WP_Query($args);
        ob_start();

        if ($products->have_posts()) {
            $columns = absint($atts['columns']);
            $total_products = $products->post_count;
            $rows = ceil($total_products / $columns);
            
            echo '<table class="wpb-shop-pro-grid ' . esc_attr($atts['class']) . '" data-columns="' . $columns . '">';
            
            $current_product = 0;
            for ($row = 0; $row < $rows; $row++) {
                echo '<tr class="wpb-shop-pro-row">';
                
                for ($col = 0; $col < $columns; $col++) {
                    if ($products->have_posts()) {
                        $products->the_post();

                        $current_product++;
                        global $product;
                        
                        echo '<td class="wpb-shop-pro-cell">';
                        echo '<div class="wpb-shop-pro-item">';
                        
                        // Thumbnail
                        if ($atts['show_thumbnail']) {
                            $this->render_product_image($product, $atts);
                        }
                        
                        // Title
                        if ($atts['show_title']) {
                            echo '<h3 class="wpb-shop-pro-title"><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></h3>';
                        }
                        
                        // Rating
                        if ($atts['show_rating'] && wc_review_ratings_enabled()) {
                            echo '<div class="wpb-shop-pro-rating">' . wc_get_rating_html($product->get_average_rating()) . '</div>';
                        }
                        
                        // Price
                        if ($atts['show_price']) {
                            echo '<div class="wpb-shop-pro-price">' . $product->get_price_html() . '</div>';
                        }
                        
                        // Add to Cart
                        if ($atts['show_add_to_cart']) {
                            echo '<div class="wpb-shop-pro-add-to-cart">';
                            woocommerce_template_loop_add_to_cart();
                            echo '</div>';
                        }
                        
                        echo '</div>'; // .wpb-shop-pro-item
                        echo '</td>'; // .wpb-shop-pro-cell
                    } else {
                        // Fill empty cells if needed
                        echo '<td class="wpb-shop-pro-cell wpb-empty-cell"></td>';
                    }
                }
                
                echo '</tr>'; // .wpb-shop-pro-row
            }
            
            echo '</table>'; // .wpb-shop-pro-grid
            
            // Pagination
            if ($atts['pagination'] && $products->max_num_pages > 1) {
                echo '<div class="wpb-pagination">';
                echo paginate_links([
                    'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format'    => '?paged=%#%',
                    'current'   => max(1, get_query_var('paged')),
                    'total'     => $products->max_num_pages,
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                ]);
                echo '</div>';
            }
        } else {

            echo '<p class="wpb-no-products">' . esc_html__('No products found', 'ix-woo-pro-banner') . '</p>';
        }
        
        wp_reset_postdata();
        return ob_get_clean();
    }

    protected function render_product_image($product, $atts) {
        $image_source = $atts['image_source'];
        $promo_image = ($image_source === 'both' || $image_source === 'promo') && function_exists('get_field') ? get_field('pro_image', $product->get_id()) : false;
        
        echo '<div class="wpb-shop-pro-image">';
        echo '<a href="' . esc_url(get_permalink($product->get_id())) . '">';
        
        // Handle image size
        $size = $this->get_image_size($atts['image_size']);
        
        if ($promo_image && ($image_source === 'both' || $image_source === 'promo')) {
            // Show promotional image
            $image_url = $this->get_promo_image_url($promo_image, $size);
            $alt_text = $promo_image['alt'] ?: $product->get_name();
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="wpb-promo-image" />';
        } elseif ($image_source === 'both' || $image_source === 'product') {
            // Show product image
            if ($product->get_image_id()) {
                echo wp_get_attachment_image(
                    $product->get_image_id(),
                    $size,
                    false,
                    ['class' => 'wpb-shop-pro-thumbnail']
                );
            } else {
                // Fallback to placeholder
                echo '<img src="' . esc_url(wc_placeholder_img_src($size)) . '" alt="' . esc_attr__('Product placeholder', 'ix-woo-pro-banner') . '" />';
            }
        }
        
        // Sale badge
        if ($product->is_on_sale()) {
            echo '<span class="wpb-onsale">' . esc_html__('Sale!', 'ix-woo-pro-banner') . '</span>';
        }
        
        echo '</a>';
        echo '</div>';
    }

    protected function get_image_size($size) {
        if (is_numeric($size)) {
            return [absint($size), absint($size)];
        }
        return $size;
    }

    protected function get_promo_image_url($promo_image, $size) {
        if (is_array($size) && isset($promo_image['sizes'][$size[0] . 'x' . $size[1]])) {
            return $promo_image['sizes'][$size[0] . 'x' . $size[1]];
        }
        return $promo_image['url'];
    }
}
IX_WPB_Shop_Pro_Grid::instance();