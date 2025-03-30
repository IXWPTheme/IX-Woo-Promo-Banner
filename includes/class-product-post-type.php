<?php
/**
 * Product Post Type Modifications
 * 
 * Handles custom fields for WooCommerce products including promotional banner images
 * 
 * @package IX_Woo_Pro_Banner
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Product_Post_Type {
    
    /**
     * Plugin instance
     * @var IX_WPB_Product_Post_Type
     */
    private static $instance;
    
    /**
     * Main instance
     * @return IX_WPB_Product_Post_Type
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
        // Register fields after ACF is loaded
        add_action('acf/init', [$this, 'register_custom_fields']);
        
        // Show admin notice if ACF is missing
        add_action('admin_notices', [$this, 'check_acf_dependency']);
    }
    
    /**
     * Register custom fields for products
     */
    public function register_custom_fields() {
        // Only proceed if ACF function exists
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        // Define the promotional image field
        $fields = [
            [
                'key' => 'field_ix_wpb_pro_image',
                'label' => __('Promotional Image', 'ix-woo-pro-banner'),
                'name' => 'pro_image',
                'type' => 'image',
                'instructions' => __('Upload a promotional image for this product (used in banners and grids)', 'ix-woo-pro-banner'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => 2, // 2MB max
                'mime_types' => 'jpg,jpeg,png,gif',
            ]
        ];
        
        // Register the field group
        acf_add_local_field_group([
            'key' => 'group_ix_wpb_promo_banner',
            'title' => __('Promotional Banner Settings', 'ix-woo-pro-banner'),
            'fields' => $fields,
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
        ]);
    }
    
    /**
     * Check for ACF dependency and show notice if missing
     */
    public function check_acf_dependency() {
        // Only show on plugin admin page
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'ix-wpb-settings') === false) {
            return;
        }
        
        if (!function_exists('acf_add_local_field_group')) {
            $this->show_acf_notice();
        }
    }
    
    /**
     * Display ACF missing notice
     */
    private function show_acf_notice() {
        $message = sprintf(
            __('IX Woo Pro Banner requires %sAdvanced Custom Fields PRO%s to be installed and activated for full functionality.', 'ix-woo-pro-banner'),
            '<a href="https://www.advancedcustomfields.com/pro/" target="_blank">',
            '</a>'
        );
        
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            $message
        );
    }
    
    /**
     * Get promotional image for a product
     * 
     * @param int $product_id
     * @return array|false
     */
    public static function get_promo_image($product_id) {
        if (!function_exists('get_field')) {
            return false;
        }
        
        $image = get_field('pro_image', $product_id);
        
        // Validate image array structure
        if (!is_array($image) || empty($image['url'])) {
            return false;
        }
        
        return $image;
    }
}