<?php
/**
 * Shop Manager Grid Shortcode - Grid Version with Catalog Mode
 * 
 * @package IX Woo Pro Banner
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Shop_Manager_Grid {

    /**
     * Plugin version
     */
    const VERSION = IX_WPB_VERSION;

    /**
     * The single instance of the class
     */
    private static $instance = null;

    /**
     * Get class instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
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
        add_shortcode('wpb-manager-grid', array($this, 'render_manager_grid'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('woocommerce_is_purchasable', array($this, 'catalog_mode_purchasable'), 10, 2);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        if ($this->is_shortcode_present()) {
            wp_enqueue_style(
                'ix-wpb-manager-grid',
                IX_WPB_PLUGIN_URL . 'assets/css/wpb-manager-grid.css',
                array(),
                self::VERSION
            );

            wp_enqueue_script(
                'ix-wpb-manager-grid',
                IX_WPB_PLUGIN_URL . 'assets/js/wpb-manager-grid.js',
                array('jquery'),
                self::VERSION,
                true
            );
        }
    }

    /**
     * Check if shortcode is present in current page
     */
    private function is_shortcode_present() {
        global $post;
        return (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wpb-manager-grid'));
    }

    /**
     * Handle catalog mode for products
     */
    public function catalog_mode_purchasable($purchasable, $product) {
        $settings = $this->get_settings();
        if (isset($settings['catalog_mode']) && $settings['catalog_mode']) {
            return false;
        }
        return $purchasable;
    }

    /**
     * Render manager grid shortcode
     */
    public function render_manager_grid($atts) {
        if (!current_user_can('edit_products')) {
            return $this->render_error(__('Permission denied', 'ix-woo-pro-banner'));
        }

        $settings = $this->get_settings();
        $atts = shortcode_atts($this->get_default_atts($settings), $atts, 'wpb-manager-grid');

        // Set columns for grid layout
        $columns = absint($atts['columns']);
        $grid_class = 'columns-' . $columns;

        $products = $this->get_products($atts);
        
        ob_start();
        
        echo '<div class="ix-wpb-manager-grid-container ' . esc_attr($grid_class) . '">';
        
        if ($products->have_posts()) {
            woocommerce_product_loop_start();
            
            while ($products->have_posts()) {
                $products->the_post();
                $product = wc_get_product(get_the_ID());
                $this->render_product_card($product, $atts, $settings);
            }
            
            woocommerce_product_loop_end();
        } else {
            $this->render_no_products();
        }
        
        $this->render_pagination($products, $atts);
        
        wp_reset_postdata();
        
        echo '</div>';
        
        return ob_get_clean();
    }

    /**
     * Render individual product card
     */
    private function render_product_card($product, $atts, $settings) {
        $edit_url = add_query_arg(
            array(
                'product_id' => $product->get_id(),
                'form' => 'edit'
            ),
            get_permalink()
        );
        ?>
        <li <?php wc_product_class('', $product); ?>>
            <div class="ix-wpb-product-card">
                
                <?php if ('yes' === $atts['show_thumbnail']) : ?>
                <div class="ix-wpb-product-thumbnail">
                    <a href="<?php echo esc_url($edit_url); ?>">
                        <?php echo $product->get_image($atts['image_size']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="ix-wpb-product-details">
                    <?php if ('yes' === $atts['show_title']) : ?>
                    <h3 class="ix-wpb-product-title">
                        <a href="<?php echo esc_url($edit_url); ?>">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    </h3>
                    <?php endif; ?>
                    
                    <?php if ('yes' === $atts['show_price']) : ?>
                    <div class="ix-wpb-product-price">
                        <?php echo $product->get_price_html(); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ('yes' === $atts['show_rating']) : ?>
                    <div class="ix-wpb-product-rating">
                        <?php echo wc_get_rating_html($product->get_average_rating()); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ix-wpb-product-meta">
                        <span class="ix-wpb-product-stock">
                            <?php echo wc_get_stock_html($product); ?>
                        </span>
                        <span class="ix-wpb-product-status">
                            <?php echo esc_html(ucfirst($product->get_status())); ?>
                        </span>
                        <span class="ix-wpb-product-sales">
                            <?php echo esc_html($product->get_total_sales()); ?> sold
                        </span>
                    </div>
                    
                    <div class="ix-wpb-product-actions">
                        <a href="<?php echo esc_url($edit_url); ?>" class="button ix-wpb-edit">
                            <?php esc_html_e('Edit', 'ix-woo-pro-banner'); ?>
                        </a>
                        <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="button ix-wpb-view" target="_blank">
                            <?php esc_html_e('View', 'ix-woo-pro-banner'); ?>
                        </a>
                        
                        <?php if ('yes' === $atts['show_add_to_cart'] && !$settings['catalog_mode']) : ?>
                            <?php woocommerce_template_loop_add_to_cart(); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </li>
        <?php
    }

    /**
     * Get plugin settings
     */
    private function get_settings() {
        return wp_parse_args(
            get_option('ix_wpb_manager_grid_settings', array()),
            $this->get_default_settings()
        );
    }

    /**
     * Default shortcode attributes
     */
    private function get_default_atts($settings) {
        return array(
            'limit'           => $settings['limit'],
            'columns'         => $settings['columns'],
            'orderby'         => $settings['orderby'],
            'order'           => $settings['order'],
            'category'        => $settings['category'],
            'ids'             => implode(',', $settings['selected_products']),
            'image_source'    => $settings['image_source'],
            'image_size'      => $settings['image_size'],
            'show_title'      => $settings['show_title'] ? 'yes' : 'no',
            'show_price'      => $settings['show_price'] ? 'yes' : 'no',
            'show_rating'     => $settings['show_rating'] ? 'yes' : 'no',
            'show_add_to_cart' => ($settings['show_add_to_cart'] && !$settings['catalog_mode']) ? 'yes' : 'no',

            'show_thumbnail'  => $settings['show_thumbnail'] ? 'yes' : 'no',
            'pagination'      => $settings['pagination'] ? 'yes' : 'no',
            'class'           => $settings['class'],
            'catalog_mode'    => $settings['catalog_mode'] ? 'yes' : 'no'
        );
    }

    /**
     * Default settings
     */
    private function get_default_settings() {
        return array(
            'limit'           => 12,
            'columns'         => 4,
            'orderby'         => 'date',
            'order'           => 'DESC',
            'category'        => '',
            'selected_products' => array(),
            'image_source'    => 'both',
            'image_size'      => 'woocommerce_thumbnail',
            'show_title'      => true,
            'show_price'      => true,
            'show_rating'     => false,
            'show_add_to_cart' => true,
            'show_thumbnail'  => true,
            'pagination'      => false,
            'class'           => '',
            'catalog_mode'    => false
        );
    }

    /**
     * Query products
     */
    private function get_products($atts) {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $atts['limit'],
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'author'         => get_current_user_id(),
            'post_status'    => array('publish', 'pending', 'draft')
        );

        if (!empty($atts['ids'])) {
            $args['post__in'] = array_map('absint', explode(',', $atts['ids']));
        }

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => array_map('absint', explode(',', $atts['category']))
                )
            );
        }

        return new WP_Query($args);
    }

    /**
     * Render no products message
     */
    private function render_no_products() {
        echo '<div class="ix-wpb-no-products">';
        echo esc_html__('No products found', 'ix-woo-pro-banner');
        echo '</div>';
    }

    /**
     * Render pagination
     */
    private function render_pagination($products, $atts) {
        if ('yes' === $atts['pagination'] && $products->max_num_pages > 1) {
            echo '<div class="ix-wpb-pagination">';
            echo paginate_links(array(
                'base'    => add_query_arg('paged', '%#%'),
                'format'  => '',
                'current' => max(1, get_query_var('paged')),
                'total'   => $products->max_num_pages
            ));
            echo '</div>';
        }
    }

    /**
     * Render error message
     */
    private function render_error($message) {
        return '<div class="ix-wpb-error">' . esc_html($message) . '</div>';
    }
}

// Initialize the class
IX_WPB_Shop_Manager_Grid::instance();