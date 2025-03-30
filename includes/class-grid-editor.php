<?php
if (!defined('ABSPATH')) exit;

class IX_WPB_Grid_Editor {

    public static function init() {
        add_shortcode('wpb-grid-builder', [__CLASS__, 'render_editor']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_wpb_generate_grid_preview', [__CLASS__, 'ajax_generate_preview']);
    }

    public static function enqueue_assets() {
        if (is_singular() && has_shortcode(get_post()->post_content, 'wpb-grid-builder')) {
            // Enqueue Select2
            wp_enqueue_style(
                'ix-select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css',
                [],
                '4.1.0'
            );
            wp_enqueue_script(
                'ix-select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js',
                ['jquery'],
                '4.1.0',
                true
            );

            // Editor assets
            wp_enqueue_style(
                'ix-wpb-grid-editor',
                IX_WPB_PLUGIN_URL . 'assets/css/grid-editor.css',
                [],
                IX_WPB_VERSION
            );
            wp_enqueue_script(
                'ix-wpb-grid-editor',
                IX_WPB_PLUGIN_URL . 'assets/js/grid-editor.js',
                ['jquery', 'ix-select2'],
                IX_WPB_VERSION,
                true
            );

            // Localize script with default values
            wp_localize_script('ix-wpb-grid-editor', 'wpbGridEditor', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpb_grid_editor_nonce'),
                'defaults' => self::get_default_attributes(),
                'image_sizes' => self::get_image_sizes(),
                'i18n' => [
                    'select_products' => __('Select products...', 'ix-woo-pro-banner'),
                    'generating_preview' => __('Generating preview...', 'ix-woo-pro-banner')
                ]
            ]);
        }
    }

    public static function render_editor() {
        if (!current_user_can('edit_posts')) {
            return '<div class="wpb-editor-notice">' . __('You need editing permissions to access this editor', 'ix-woo-pro-banner') . '</div>';
        }

        $attributes = self::get_default_attributes();
        ob_start();
        ?>
        <div class="wpb-grid-builder">
            <div class="wpb-control-panel">
                <h2><?php _e('Product Grid Builder', 'ix-woo-pro-banner'); ?></h2>
                
                <!-- Layout Settings -->
                <div class="wpb-control-section">
                    <h3><?php _e('Layout Settings', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="wpb-control-group">
                        <label for="wpb-columns"><?php _e('Columns:', 'ix-woo-pro-banner'); ?></label>
                        <select id="wpb-columns" class="wpb-control" data-attribute="columns">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, $attributes['columns']); ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="wpb-control-group">
                        <label for="wpb-limit"><?php _e('Products Limit:', 'ix-woo-pro-banner'); ?></label>
                        <input type="number" id="wpb-limit" class="wpb-control" 
                               min="1" max="50" value="<?php echo $attributes['limit']; ?>"
                               data-attribute="limit">
                    </div>
                </div>
                
                <!-- Product Selection -->
                <div class="wpb-control-section">
                    <h3><?php _e('Product Selection', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="wpb-control-group">
                        <label for="wpb-products"><?php _e('Specific Products:', 'ix-woo-pro-banner'); ?></label>
                        <select id="wpb-products" class="wpb-control" 
                                multiple="multiple" data-attribute="ids">
                            <?php 
                            if (!empty($attributes['ids'])) {
                                $product_ids = explode(',', $attributes['ids']);
                                foreach ($product_ids as $product_id) {
                                    $product = wc_get_product($product_id);
                                    if ($product) {
                                        echo '<option value="' . $product_id . '" selected>' 
                                            . esc_html($product->get_name()) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="wpb-control-group">
                        <label for="wpb-category"><?php _e('Product Category:', 'ix-woo-pro-banner'); ?></label>
                        <select id="wpb-category" class="wpb-control" data-attribute="category">
                            <option value=""><?php _e('All Categories', 'ix-woo-pro-banner'); ?></option>
                            <?php
                            $categories = get_terms(['taxonomy' => 'product_cat']);
                            foreach ($categories as $category) {
                                echo '<option value="' . $category->slug . '">' 
                                    . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <!-- Display Options -->
                <div class="wpb-control-section">
                    <h3><?php _e('Display Options', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="wpb-control-group">
                        <label for="wpb-image-size"><?php _e('Image Size:', 'ix-woo-pro-banner'); ?></label>
                        <select id="wpb-image-size" class="wpb-control" data-attribute="image_size">
                            <?php foreach (self::get_image_sizes() as $size => $dimensions): ?>
                                <option value="<?php echo esc_attr($size); ?>">
                                    <?php echo esc_html($size) . ' (' . $dimensions['width'] . '×' . $dimensions['height'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="wpb-control-group">
                        <label>
                            <input type="checkbox" id="wpb-show-title" 
                                   data-attribute="show_title" <?php checked($attributes['show_title'], 'yes'); ?>>
                            <?php _e('Show Product Titles', 'ix-woo-pro-banner'); ?>
                        </label>
                    </div>
                    
                    <div class="wpb-control-group">
                        <label>
                            <input type="checkbox" id="wpb-show-price" 
                                   data-attribute="show_price" <?php checked($attributes['show_price'], 'yes'); ?>>
                            <?php _e('Show Prices', 'ix-woo-pro-banner'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Shortcode Output -->
                <div class="wpb-control-section">
                    <h3><?php _e('Shortcode', 'ix-woo-pro-banner'); ?></h3>
                    <textarea id="wpb-shortcode-output" class="wpb-control" readonly></textarea>
                    <button id="wpb-copy-shortcode" class="wpb-button">
                        <?php _e('Copy Shortcode', 'ix-woo-pro-banner'); ?>
                    </button>
                </div>
            </div>
            
            <div class="wpb-preview-area">
                <h3><?php _e('Live Preview', 'ix-woo-pro-banner'); ?></h3>
                <div id="wpb-grid-preview" class="wpb-grid-preview">
                    <?php echo do_shortcode('[wpb-product-grid]'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_generate_preview() {
        check_ajax_referer('wpb_grid_editor_nonce', 'nonce');
        
        $attributes = [];
        parse_str($_POST['form_data'], $attributes);
        
        // Sanitize and prepare attributes
        $shortcode_atts = [
            'columns' => isset($attributes['columns']) ? absint($attributes['columns']) : 4,
            'limit' => isset($attributes['limit']) ? absint($attributes['limit']) : 12,
            'ids' => isset($attributes['ids']) ? implode(',', array_map('absint', explode(',', $attributes['ids']))) : '',
            'category' => isset($attributes['category']) ? sanitize_text_field($attributes['category']) : '',
            'image_size' => isset($attributes['image_size']) ? sanitize_text_field($attributes['image_size']) : 'woocommerce_thumbnail',
            'show_title' => isset($attributes['show_title']) ? 'yes' : 'no',
            'show_price' => isset($attributes['show_price']) ? 'yes' : 'no'
        ];
        
        // Generate shortcode
        $shortcode = '[wpb-product-grid';
        foreach ($shortcode_atts as $key => $value) {
            if (!empty($value)) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';
        
        // Generate preview
        $preview = do_shortcode($shortcode);
        
        wp_send_json_success([
            'shortcode' => $shortcode,
            'preview' => $preview
        ]);
    }

    private static function get_default_attributes() {
        return [
            'columns' => 4,
            'limit' => 12,
            'ids' => '',
            'category' => '',
            'image_size' => 'woocommerce_thumbnail',
            'show_title' => 'yes',
            'show_price' => 'yes',
            'show_rating' => 'no',
            'show_add_to_cart' => 'no'
        ];
    }

    private static function get_image_sizes() {
        $sizes = wp_get_registered_image_subsizes();
        $woo_sizes = [
            'woocommerce_thumbnail' => $sizes['woocommerce_thumbnail'],
            'woocommerce_single' => $sizes['woocommerce_single'],
            'woocommerce_gallery_thumbnail' => $sizes['woocommerce_gallery_thumbnail']
        ];
        
        return array_merge($woo_sizes, $sizes);
    }
}

IX_WPB_Grid_Editor::init();