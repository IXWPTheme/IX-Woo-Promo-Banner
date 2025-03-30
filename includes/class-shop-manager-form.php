<?php
/**
 * Shop Manager Form Shortcode
 * 
 * @package IX Woo Pro Banner
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Shop_Manager_Form {

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
        add_shortcode('wpb-shop-manager-form', array($this, 'render_manager_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_ix_wpb_manager_search_products', array($this, 'handle_product_search'));
        add_action('wp_ajax_ix_wpb_manager_save_settings', array($this, 'handle_save_settings'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
       // if ($this->is_shortcode_present()) {
            // Select2 for product selection
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);

            // Form styles and scripts
            wp_enqueue_style(
                'ix-wpb-manager-form',
                IX_WPB_PLUGIN_URL . 'assets/css/wpb-manager-form.css',
                array(),
                self::VERSION
            );

            wp_enqueue_script(
                'ix-wpb-manager-form',
                IX_WPB_PLUGIN_URL . 'assets/js/wpb-manager-form.js',
                array('jquery', 'select2'),
                self::VERSION,
                true
            );

            // Localize script with AJAX URL and nonce
            wp_localize_script('ix-wpb-manager-form', 'ix_wpb_manager_form', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ix_wpb_manager_form_nonce'),
                'i18n' => array(
                    'select_products' => __('Select products...', 'ix-woo-pro-banner'),
                    'no_results' => __('No products found', 'ix-woo-pro-banner'),
                    'loading' => __('Loading...', 'ix-woo-pro-banner'),
                    'saving' => __('Saving...', 'ix-woo-pro-banner'),
                    'save' => __('Save Settings', 'ix-woo-pro-banner'),
                    'error' => __('Error saving settings', 'ix-woo-pro-banner')
                )
            ));
        
    }

    /**
     * Handle product search for Select2
     */
    public function handle_product_search() {
        check_ajax_referer('ix_wpb_manager_form_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(
                __('Permission denied', 'ix-woo-pro-banner'),
                403
            );
        }

        $search = isset($_REQUEST['q']) ? sanitize_text_field($_REQUEST['q']) : '';
        $results = array();

        if (!empty($search)) {
            $products = get_posts(array(
                'post_type' => 'product',
                'posts_per_page' => 20,
                's' => $search,
                'post_status' => 'publish',
                'author' => get_current_user_id() // Only show current manager's products
            ));

            foreach ($products as $product) {
                $product_obj = wc_get_product($product->ID);
                $price = $product_obj ? $product_obj->get_price() : '';
                
                $results[] = array(
                    'id' => $product->ID,
                    'text' => $product->post_title,
                    'price' => $price,
                    'display' => $this->format_product_display($product, $price)
                );
            }
        }

        wp_send_json_success($results);
    }

    /**
     * Format product display for Select2
     */
    private function format_product_display($product, $price) {
        $price_display = $price ? wc_price($price) : __('N/A', 'ix-woo-pro-banner');
        return sprintf('%s (ID: %d) - %s',
            $product->post_title,
            $product->ID,
            $price_display
        );
    }

    /**
     * Handle settings save
     */
    public function handle_save_settings() {
        check_ajax_referer('ix_wpb_manager_form_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(
                __('Permission denied', 'ix-woo-pro-banner'),
                403
            );
        }

        $settings = array(
            'image_source' => isset($_POST['image_source']) ? sanitize_text_field($_POST['image_source']) : 'both',
            'image_size' => isset($_POST['image_size']) ? sanitize_text_field($_POST['image_size']) : 'woocommerce_thumbnail',
            'selected_products' => isset($_POST['selected_products']) ? array_map('absint', (array)$_POST['selected_products']) : array()
        );

        update_option('ix_wpb_manager_grid_settings', $settings);

        wp_send_json_success(__('Settings saved successfully!', 'ix-woo-pro-banner'));
    }

    /**
     * Check if shortcode is present in current page
     */
    private function is_shortcode_present() {
        global $post;
        return (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wpb-shop-manager-form'));
    }

    /**
     * Render manager form shortcode
     */
    public function render_manager_form() {
        if (!current_user_can('edit_products')) {
            return $this->render_error(__('Permission denied', 'ix-woo-pro-banner'));
        }

        $settings = $this->get_settings();
        $image_sizes = wp_get_registered_image_subsizes();

        ob_start();
        ?>
        <div class="ix-wpb-manager-form-container">
            <h2><?php esc_html_e('Shop Manager Grid Settings', 'ix-woo-pro-banner'); ?></h2>
            
            <form id="ix-wpb-manager-form" method="post">
                <?php wp_nonce_field('ix_wpb_save_manager_settings', 'ix_wpb_manager_nonce'); ?>
                
                <div class="ix-wpb-form-section">
                    <h3><?php esc_html_e('Image Settings', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="ix-wpb-form-row">
                        <label for="ix-wpb-image-source">
                            <?php esc_html_e('Image Source', 'ix-woo-pro-banner'); ?>
                        </label>
                        <select id="ix-wpb-image-source" name="image_source" class="ix-wpb-form-control">
                            <option value="both" <?php selected($settings['image_source'], 'both'); ?>>
                                <?php esc_html_e('Both (Product + Promo)', 'ix-woo-pro-banner'); ?>
                            </option>
                            <option value="product" <?php selected($settings['image_source'], 'product'); ?>>
                                <?php esc_html_e('Product Image Only', 'ix-woo-pro-banner'); ?>
                            </option>
                            <option value="promo" <?php selected($settings['image_source'], 'promo'); ?>>
                                <?php esc_html_e('Promo Image Only', 'ix-woo-pro-banner'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="ix-wpb-form-row">
                        <label for="ix-wpb-image-size">
                            <?php esc_html_e('Image Size', 'ix-woo-pro-banner'); ?>
                        </label>
                        <select id="ix-wpb-image-size" name="image_size" class="ix-wpb-form-control">
                            <?php foreach ($image_sizes as $size => $dimensions) : ?>
                                <option value="<?php echo esc_attr($size); ?>" <?php selected($settings['image_size'], $size); ?>>
                                    <?php echo esc_html("$size ({$dimensions['width']}×{$dimensions['height']})"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="ix-wpb-form-section">
                    <h3><?php esc_html_e('Product Selection', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="ix-wpb-form-row">
                        <label for="ix-wpb-selected-products">
                            <?php esc_html_e('Featured Products', 'ix-woo-pro-banner'); ?>
                        </label>
                        <select id="ix-wpb-selected-products" 
                                name="selected_products[]" 
                                class="ix-wpb-form-control ix-wpb-product-select" 
                                multiple="multiple"
                                data-placeholder="<?php esc_attr_e('Select products...', 'ix-woo-pro-banner'); ?>">
                            <?php foreach ($this->get_selected_products_data($settings['selected_products']) as $product) : ?>
                                <option value="<?php echo esc_attr($product['id']); ?>" selected>
                                    <?php echo esc_html($product['text']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select products to feature in your grid.', 'ix-woo-pro-banner'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="ix-wpb-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Settings', 'ix-woo-pro-banner'); ?>
                    </button>
                    <div class="ix-wpb-form-message"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get selected products data for select2
     */
    private function get_selected_products_data($selected_ids) {
        $products = array();
        
        if (!empty($selected_ids)) {
            $posts = get_posts(array(
                'post_type' => 'product',
                'post__in' => $selected_ids,
                'posts_per_page' => -1,
                'author' => get_current_user_id() // Only show current manager's products
            ));
            
            foreach ($posts as $post) {
                $products[] = array(
                    'id' => $post->ID,
                    'text' => $post->post_title
                );
            }
        }
        
        return $products;
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
     * Default settings
     */
    private function get_default_settings() {
        return array(
            'image_source' => 'both',
            'image_size' => 'woocommerce_thumbnail',
            'selected_products' => array()
        );
    }

    /**
     * Render error message
     */
    private function render_error($message) {
        return '<div class="ix-wpb-error">' . esc_html($message) . '</div>';
    }
}

// Initialize the class
IX_WPB_Shop_Manager_Form::instance();