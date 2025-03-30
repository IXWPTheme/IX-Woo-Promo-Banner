<?php
/**
 * Product Grid Shortcode - Table Version
 * 
 * Displays products in a responsive grid layout with promotional image support
 * 
 * @package IX_Woo_Pro_Banner
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Product_Grid {
    
    /**
     * Plugin instance
     * @var IX_WPB_Product_Grid
     */
    private static $instance;
     
    /**
     * Main instance
     * @return IX_WPB_Product_Grid
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_shortcode('wpb-product-grid', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'wpb-product-grid',
            IX_WPB_PLUGIN_URL . 'assets/css/wpb-product-grid.css',
            [],
            IX_WPB_VERSION
        );
    }
    
    /**
     * Render product grid shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_shortcode($atts) {
        // Check WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return $this->render_error(__('WooCommerce is required for this shortcode.', 'ix-woo-pro-banner'));
        }
        
        // Parse and validate attributes
        $atts = $this->parse_attributes($atts);
        
        // Query products
        $products = $this->get_products_query($atts);
        
        // Start output buffering
        ob_start();
        
        if ($products->have_posts()) {
            $this->render_product_grid($products, $atts);
        } else {
            $this->render_no_products();
        }
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Parse and validate shortcode attributes
     * 
     * @param array $atts Shortcode attributes
     * @return array Parsed attributes
     */
    protected function parse_attributes($atts) {
        $defaults = [
            'limit'           => '12',
            'columns'         => '4',
            'orderby'         => 'date',
            'order'           => 'DESC',
            'category'        => '',
            'ids'            => '',
            'skus'           => '',
            'show_title'      => 'yes',
            'show_price'      => 'yes',
            'show_rating'     => 'yes',
            'show_add_to_cart' => 'yes',
            'show_thumbnail'  => 'yes',
            'image_source'    => 'promo',	//both , promo , product
            'image_size'      => 'woocommerce_thumbnail',
            'pagination'      => 'no',
            'class'           => ''
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'wpb-product-grid');
        
        // Convert string booleans
        $bool_attrs = [
            'show_title', 'show_price', 'show_rating', 
            'show_add_to_cart', 'show_thumbnail', 'pagination'
        ];
        
        foreach ($bool_attrs as $attr) {
            $atts[$attr] = filter_var($atts[$attr], FILTER_VALIDATE_BOOLEAN);
        }
        
        return $atts;
    }
    
    /**
     * Get products query
     * 
     * @param array $atts Shortcode attributes
     * @return WP_Query
     */
    protected function get_products_query($atts) {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => (int) $atts['limit'],
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order']
        ];
        
        // Category filter
        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => array_map('trim', explode(',', $atts['category']))
                ]
            ];
        }
        
        // Specific IDs
        if (!empty($atts['ids'])) {
            $args['post__in'] = array_map('absint', explode(',', $atts['ids']));
            $args['orderby'] = 'post__in';
        }
        
        // SKU filter
        if (!empty($atts['skus'])) {
            $args['meta_query'] = [
                [
                    'key'     => '_sku',
                    'value'   => array_map('trim', explode(',', $atts['skus'])),
                    'compare' => 'IN'
                ]
            ];
        }
        
        // Pagination
        if ($atts['pagination']) {
            $args['paged'] = max(1, get_query_var('paged'));
        }
        
        return new WP_Query($args);
    }
    
    /**
     * Render product grid
     * 
     * @param WP_Query $products Products query
     * @param array $atts Shortcode attributes
     */
    protected function render_product_grid($products, $atts) {
        $columns = absint($atts['columns']);
        $total_products = $products->post_count;
        $rows = ceil($total_products / $columns);
        
        echo '<table class="wpb-product-grid ' . esc_attr($atts['class']) . '" data-columns="' . $columns . '">';
        
        $current_product = 0;
        for ($row = 0; $row < $rows; $row++) {
            echo '<tr class="wpb-product-row">';
            
            for ($col = 0; $col < $columns; $col++) {
                if ($products->have_posts()) {
                    $products->the_post();
                    $current_product++;
                    $this->render_product_cell(get_the_ID(), $atts);
                } else {
                    echo '<td class="wpb-product-cell wpb-empty-cell"></td>';
                }
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Pagination
        if ($atts['pagination'] && $products->max_num_pages > 1) {
            $this->render_pagination($products);
        }
    }
    
    /**
     * Render individual product cell
     * 
     * @param int $product_id Product ID
     * @param array $atts Shortcode attributes
     */
    protected function render_product_cell($product_id, $atts) {
        global $product;
        
        if (!$product instanceof WC_Product) {
            $product = wc_get_product($product_id);
        }
        
        echo '<td class="wpb-product-cell">';
        echo '<div class="wpb-product-item">';
        
        // Thumbnail
        if ($atts['show_thumbnail']) {
            $this->render_product_image($product, $atts);
        }
        
        // Title
        if ($atts['show_title']) {
            echo '<h3 class="wpb-product-title"><a href="' . esc_url(get_permalink($product_id)) . '">' . 
                 esc_html($product->get_name()) . '</a></h3>';
        }
        
        // Rating
        if ($atts['show_rating'] && wc_review_ratings_enabled()) {
            echo '<div class="wpb-product-rating">' . wc_get_rating_html($product->get_average_rating()) . '</div>';
        }
        
        // Price
        if ($atts['show_price']) {
            echo '<div class="wpb-product-price">' . $product->get_price_html() . '</div>';
        }
        
        // Add to Cart
        if ($atts['show_add_to_cart']) {
            echo '<div class="wpb-product-add-to-cart">';
            woocommerce_template_loop_add_to_cart();
            echo '</div>';
        }
        
        echo '</div></td>';
    }
    
    /**
     * Render product image
     * 
     * @param WC_Product $product Product object
     * @param array $atts Shortcode attributes
     */
    protected function render_product_image($product, $atts) {
        $image_source = $atts['image_source'];
        $size = $this->get_image_size($atts['image_size']);
        
        echo '<div class="wpb-product-image">';
        echo '<a href="' . esc_url(get_permalink($product->get_id())) . '">';
        
        // Try to get promotional image if enabled
        if (($image_source === 'both' || $image_source === 'promo')) {
            $promo_image = IX_WPB_Product_Post_Type::get_promo_image($product->get_id());
            
            if ($promo_image) {
                $this->render_promo_image($promo_image, $size, $product->get_name());
                echo '</a></div>';
                return;
            }
        }
        
        // Fall back to product image if promo not available or not selected
        if ($image_source === 'both' || $image_source === 'product') {
            $this->render_product_default_image($product, $size);
        }
        
        // Sale badge
        if ($product->is_on_sale()) {
            echo '<span class="wpb-onsale">' . esc_html__('Sale!', 'ix-woo-pro-banner') . '</span>';
        }
        
        echo '</a></div>';
    }
    
    /**
     * Render promotional image
     * 
     * @param array $promo_image Promo image data
     * @param string|array $size Image size
     * @param string $default_alt Default alt text
     */
    protected function render_promo_image($promo_image, $size, $default_alt) {
        $image_url = $this->get_image_url($promo_image, $size);
        $alt_text = !empty($promo_image['alt']) ? $promo_image['alt'] : $default_alt;
        
        echo '<img src="' . esc_url($image_url) . '" 
                  alt="' . esc_attr($alt_text) . '" 
                  class="wpb-promo-image" />';
    }
    
    /**
     * Render default product image
     * 
     * @param WC_Product $product Product object
     * @param string|array $size Image size
     */
    protected function render_product_default_image($product, $size) {
        if ($product->get_image_id()) {
            echo wp_get_attachment_image(
                $product->get_image_id(),
                $size,
                false,
                ['class' => 'wpb-product-thumbnail']
            );
        } else {
            echo '<img src="' . esc_url(wc_placeholder_img_src($size)) . '" 
                      alt="' . esc_attr__('Product placeholder', 'ix-woo-pro-banner') . '" />';
        }
    }
    
    /**
     * Get image URL based on size
     * 
     * @param array $image Image data
     * @param string|array $size Image size
     * @return string Image URL
     */
    protected function get_image_url($image, $size) {
        if (is_array($size) && isset($image['sizes'][$size[0] . 'x' . $size[1]])) {
            return $image['sizes'][$size[0] . 'x' . $size[1]];
        }
        return $image['url'];
    }
    
    /**
     * Get image size
     * 
     * @param string|int $size Image size
     * @return string|array
     */
    protected function get_image_size($size) {
        if (is_numeric($size)) {
            return [absint($size), absint($size)];
        }
        return $size;
    }
    
    /**
     * Render pagination
     * 
     * @param WP_Query $products Products query
     */
    protected function render_pagination($products) {
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
    
    /**
     * Render no products message
     */
    protected function render_no_products() {
        echo '<p class="wpb-no-products">' . esc_html__('No products found', 'ix-woo-pro-banner') . '</p>';
    }
    
    /**
     * Render error message
     * 
     * @param string $message Error message
     * @return string Error HTML
     */
    protected function render_error($message) {
        return '<p class="wpb-error">' . esc_html($message) . '</p>';
    }
}

// Initialize
IX_WPB_Product_Grid::instance();