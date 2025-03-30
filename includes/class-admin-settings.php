<?php
/**
 * Admin settings
 */

defined('ABSPATH') || exit;

class IX_WPB_Admin_Settings {
    
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
    
    private function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'activation_notice']);
    }
    
    public function enqueue_assets($hook) {
    if ($hook !== 'woocommerce_page_ix-wpb-settings') {
        return;
    }

    // Load Select2
    wp_enqueue_script(
        'ix-select2',
         'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        ['jquery'],
        '4.1.0-rc.0',
        true
    );
    
    wp_enqueue_style(
        'ix-select2',
         'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        [],
        '4.1.0-rc.0'
    );

    // Your admin script
    wp_enqueue_script(
        'ix-wpb-admin',
        IX_WPB_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery', 'ix-select2'],
        IX_WPB_VERSION,
        true
    );

    // Localization data
    wp_localize_script('ix-wpb-admin', 'ix_wpb_admin', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ix_wpb_search_products'),
        'i18n' => [
            'search_placeholder' => __('Search for products...', 'ix-woo-pro-banner'),
            'no_results' => __('No products found', 'ix-woo-pro-banner'),
            'loading' => __('Loading...', 'ix-woo-pro-banner')
        ]
    ]);
}

private function get_selected_products_data() {
    $options = get_option('ix_wpb_shop_pro_grid_settings', []);
    $selected_ids = isset($options['selected_products']) ? $options['selected_products'] : [];
    $products = [];

    if (!empty($selected_ids)) {
        $args = [
            'post_type' => 'product',
            'post__in' => $selected_ids,
            'posts_per_page' => -1,
        ];
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $products[] = [
                'id' => $post->ID,
                'text' => $post->post_title
            ];
        }
    }

    return $products;
}
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Pro Banner Settings', 'ix-woo-pro-banner'),
            __('Pro Banner', 'ix-woo-pro-banner'),
            'manage_options',
            'ix-wpb-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('ix_wpb_settings_group', 'ix_wpb_shop_pro_grid_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
        
        add_settings_section(
            'ix_wpb_catalog_settings',
            __('Catalog Settings', 'ix-woo-pro-banner'),
            [$this, 'render_section_header'],
            'ix-wpb-settings'
        );
        
        $this->add_settings_fields();
    }
    
    private function add_settings_fields() {
        $fields = [
            'limit' => [
                'title' => __('Products Limit', 'ix-woo-pro-banner'),
                'callback' => 'render_limit_field',
                'args' => [
                    'min' => 1,
                    'max' => 100,
                    'default' => 12
                ]
            ],
            'image_source' => [
                'title' => __('Default Image Source', 'ix-woo-pro-banner'),
                'callback' => 'render_image_source_field',
                'args' => [
                    'options' => [
                        'both' => __('Both (Product + Promo)', 'ix-woo-pro-banner'),
                        'product' => __('Product Image Only', 'ix-woo-pro-banner'),
                        'promo' => __('Promo Image Only', 'ix-woo-pro-banner')
                    ]
                ]
            ],
            'image_size' => [
                'title' => __('Default Image Size', 'ix-woo-pro-banner'),
                'callback' => 'render_image_size_field'
            ],
            'selected_products' => [
                'title' => __('Promotional Products', 'ix-woo-pro-banner'),
                'callback' => 'render_products_field'
            ],
            'columns' => [
                'title' => __('Default Columns', 'ix-woo-pro-banner'),
                'callback' => 'render_columns_field',
                'args' => [
                    'min' => 1,
                    'max' => 6,
                    'default' => 4
                ]
            ]
        ];
        
        foreach ($fields as $id => $field) {
            add_settings_field(
                "ix_wpb_{$id}",
                $field['title'],
                [$this, $field['callback']],
                'ix-wpb-settings',
                'ix_wpb_catalog_settings',
                $field['args'] ?? []
            );
        }
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        settings_errors('ix_wpb_messages');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('IX Woo Pro Banner Settings', 'ix-woo-pro-banner'); ?></h1>
            
            <form id="ix-wpb-settings-form" method="post" action="options.php">
                <?php
                settings_fields('ix_wpb_settings_group');
                do_settings_sections('ix-wpb-settings');
                submit_button(__('Save Settings', 'ix-woo-pro-banner'));
                ?>
            </form>
        </div>
        <?php
    }
    
    public function render_section_header() {
        echo '<p>' . esc_html__('Configure default settings for product grids and banners.', 'ix-woo-pro-banner') . '</p>';
    }
    
    public function render_limit_field($args) {
        $options = get_option('ix_wpb_shop_pro_grid_settings', []);
        $value = isset($options['limit']) ? $options['limit'] : $args['default'];
        ?>
        <input type="number" 
               name="ix_wpb_shop_pro_grid_settings[limit]" 
               min="<?php echo esc_attr($args['min']); ?>" 
               max="<?php echo esc_attr($args['max']); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="small-text">
        <p class="description"><?php esc_html_e('Number of products to display by default in the grid.', 'ix-woo-pro-banner'); ?></p>
        <?php
    }
    
    public function render_image_source_field($args) {
        $options = get_option('ix_wpb_shop_pro_grid_settings', []);
        $value = isset($options['image_source']) ? $options['image_source'] : 'both';
        ?>
        <select name="ix_wpb_shop_pro_grid_settings[image_source]" class="regular-text">
            <?php foreach ($args['options'] as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function render_image_size_field() {
        $options = get_option('ix_wpb_shop_pro_grid_settings', []);
        $value = isset($options['image_size']) ? $options['image_size'] : 'woocommerce_thumbnail';
        $sizes = wp_get_registered_image_subsizes();
        ?>
        <select name="ix_wpb_shop_pro_grid_settings[image_size]" class="regular-text">
            <?php foreach ($sizes as $size => $dimensions) : ?>
                <option value="<?php echo esc_attr($size); ?>" <?php selected($value, $size); ?>>
                    <?php echo esc_html("$size ({$dimensions['width']}×{$dimensions['height']})"); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function render_products_field() {
    $options = get_option('ix_wpb_shop_pro_grid_settings', []);
    $selected_ids = $options['selected_products'] ?? [];
    $selected_products = [];

    if (!empty($selected_ids)) {
        $products = get_posts([
            'post_type' => 'product',
            'post__in' => $selected_ids,
            'posts_per_page' => -1
        ]);

        foreach ($products as $product) {
            $selected_products[] = [
                'id' => $product->ID,
                'text' => $product->post_title
            ];
        }
    }
    ?>
    <select id="ix_wpb_selected_products" 
            name="ix_wpb_shop_pro_grid_settings[selected_products][]" 
            multiple="multiple"
            class="ix-select2-container"
            style="width: 50%;"
            data-placeholder="<?php echo esc_attr__('Search for products...', 'ix-woo-pro-banner'); ?>">
        <?php foreach ($selected_products as $product) : ?>
            <option value="<?php echo esc_attr($product['id']); ?>" selected>
                <?php echo esc_html($product['text']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e('Select products to feature in promotional grids.', 'ix-woo-pro-banner'); ?></p>
    <?php
}
    
    public function render_columns_field($args) {
        $options = get_option('ix_wpb_shop_pro_grid_settings', []);
        $value = isset($options['columns']) ? $options['columns'] : $args['default'];
        ?>
        <input type="number" 
               name="ix_wpb_shop_pro_grid_settings[columns]" 
               min="<?php echo esc_attr($args['min']); ?>" 
               max="<?php echo esc_attr($args['max']); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="small-text">
        <?php
    }
    
    public function sanitize_settings($input) {
        $output = [];
        
        // Products limit
        $output['limit'] = isset($input['limit']) 
            ? max(min(absint($input['limit']), 100), 1) 
            : 12;
        
        // Image source
        $output['image_source'] = isset($input['image_source']) && in_array($input['image_source'], ['both', 'product', 'promo'])
            ? $input['image_source']
            : 'both';
            
        // Image size
        $sizes = array_keys(wp_get_registered_image_subsizes());
        $output['image_size'] = isset($input['image_size']) && in_array($input['image_size'], $sizes)
            ? $input['image_size']
            : 'woocommerce_thumbnail';
            
        // Selected products
        $output['selected_products'] = [];
        if (isset($input['selected_products']) && is_array($input['selected_products'])) {
            foreach ($input['selected_products'] as $product_id) {
                $product_id = absint($product_id);
                if ($product_id > 0 && get_post_type($product_id) === 'product') {
                    $output['selected_products'][] = $product_id;
                }
            }
        }
        
        // Columns
        $output['columns'] = isset($input['columns'])
            ? max(min(absint($input['columns']), 6), 1)
            : 4;
        
        return $output;
    }
    
    public function activation_notice() {
        if (!get_transient('ix_wpb_activated')) {
            return;
        }
        
        delete_transient('ix_wpb_activated');
        
        $settings_url = admin_url('admin.php?page=ix-wpb-settings');
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                __('Thank you for activating IX Woo Pro Banner! Configure your settings <a href="%s">here</a>.', 'ix-woo-pro-banner'),
                esc_url($settings_url)
            )
        );
    }
}