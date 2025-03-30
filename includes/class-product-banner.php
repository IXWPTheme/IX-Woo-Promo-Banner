<?php
/**
 * Handles the core banner functionality
 * 
 * @package IX_Woo_Pro_Banner
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Product_Banner {
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
        // Initialize only when WooCommerce is active
        add_action('woocommerce_loaded', [$this, 'register_hooks']);
    }

    public function register_hooks() {
        $this->register_public_hooks();
        
        if (is_admin()) {
            $this->register_admin_hooks();
        }
    }

    private function register_public_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_shortcode('ix-pro-banner', [$this, 'render_banner']);
    }

    private function register_admin_hooks() {
        // Admin-only hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    // ... rest of your methods ...
}