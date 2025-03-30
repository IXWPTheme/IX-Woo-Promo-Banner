<?php
/**
 * Main plugin class
 */

defined('ABSPATH') || exit;

class IX_WPB_Main {
    
    private static $instance;
    
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }
    
    private function init() {
        $this->load_dependencies();
        $this->init_components();
        $this->set_locale();
    }
    
    private function load_dependencies() {
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-product-post-type.php';
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-product-banner.php';
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-product-grid-short-code.php';
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-shop-pro-grid-short-code.php';
		require_once IX_WPB_PLUGIN_DIR . 'includes/class-shop-manager-grid-shortcode.php';
        require_once IX_WPB_PLUGIN_DIR . 'includes/class-debug.php';
		require_once IX_WPB_PLUGIN_DIR . 'includes/class-frontend-controls.php';
		require_once IX_WPB_PLUGIN_DIR . 'includes/class-grid-editor.php';	
		require_once IX_WPB_PLUGIN_DIR . 'includes/class-shop-manager-form.php';
    	require_once IX_WPB_PLUGIN_DIR . 'includes/class-ajax-handler.php'; // Load AJAX handler    
    }
    
    private function init_components() {
        IX_WPB_Product_Post_Type::instance();
        IX_WPB_Admin_Settings::instance();
        IX_WPB_Product_Banner::instance();
        IX_WPB_PDF_Generator::instance();
        IX_WPB_Product_Grid::instance();
        IX_WPB_Shop_Pro_Grid::instance();
		IX_WPB_Shop_Manager_Grid::instance();
		IX_WPB_Frontend_Controls::init();
		IX_WPB_Grid_Editor::init();
		IX_WPB_Shop_Manager_Form::instance();
		IX_WPB_Ajax_Handler::instance();  // Load AJAX handler
    }
    
    private function set_locale() {
        load_plugin_textdomain(
            'ix-woo-pro-banner',
            false,
            dirname(IX_WPB_BASENAME) . '/languages'
        );
    }
}
