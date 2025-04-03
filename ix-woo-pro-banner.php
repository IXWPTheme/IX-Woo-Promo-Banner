<?php
/**
 * Plugin Name: IX Woo Pro Banner
 * Plugin URI: https://ixwptheme.com/plugin-ix-woo-pro-banner/
 * Description: Professional WooCommerce promotional banners with PDF generation capabilities.
 * Version: 1.0.0
 * Author: IXWPTheme.com
 * Author URI: https://ixwptheme.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ix-woo-pro-banner
 * Domain Path: /languages
 * Requires at least: WordPress 6.7.0
 * Requires PHP: 7.4
 * Requires WooCommerce: 9.7.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
if (!defined('IX_WPB_VERSION')) {
    define('IX_WPB_VERSION', '1.0.0');
}

if (!defined('IX_WPB_PLUGIN_DIR')) {
    define('IX_WPB_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('IX_WPB_PLUGIN_URL')) {
    define('IX_WPB_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('IX_WPB_BASENAME')) {
    define('IX_WPB_BASENAME', plugin_basename(__FILE__));
}

// Include the main plugin class
require_once IX_WPB_PLUGIN_DIR . 'includes/class-main.php';

/**
 * Check system requirements
 */
function ix_wpb_check_requirements() {
    $errors = [];
    
    if (!class_exists('WooCommerce')) {
        $errors[] = __('IX Woo Pro Banner requires WooCommerce to be installed and activated.', 'ix-woo-pro-banner');
    }
    
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(
            __('IX Woo Pro Banner requires PHP %s or higher. You are running version %s.', 'ix-woo-pro-banner'),
            '7.4',
            PHP_VERSION
        );
    }
    
    if (version_compare(get_bloginfo('version'), '6.7.0', '<')) {
        $errors[] = sprintf(
            __('IX Woo Pro Banner requires WordPress %s or higher. You are running version %s.', 'ix-woo-pro-banner'),
            '6.7.0',
            get_bloginfo('version')
        );
    }
    
    return $errors;
}

/**
 * Display admin notices for requirement errors
 */
function ix_wpb_admin_notices() {
    $errors = ix_wpb_check_requirements();
    
    if (!empty($errors)) {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            implode('</p><p>', $errors)
        );
    }
}
add_action('admin_notices', 'ix_wpb_admin_notices');

/**
 * Plugin activation
 */
function ix_wpb_activate() {
    $errors = ix_wpb_check_requirements();
    
    if (!empty($errors)) {
        wp_die(
            implode('<br>', $errors),
            __('Plugin Activation Error', 'ix-woo-pro-banner'),
            ['back_link' => true]
        );
    }
    
    require_once IX_WPB_PLUGIN_DIR . 'includes/class-activator.php';
    IX_WPB_Activator::activate();
}
register_activation_hook(__FILE__, 'ix_wpb_activate');

/**
 * Plugin deactivation
 */
function ix_wpb_deactivate() {
    require_once IX_WPB_PLUGIN_DIR . 'includes/class-deactivator.php';
    IX_WPB_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'ix_wpb_deactivate');

/**
 * Initialize the plugin
 */
function ix_wpb_init_plugin() {
    $errors = ix_wpb_check_requirements();
    
    if (!empty($errors)) {
        return;
    }
    
    // Initialize the plugin
    IX_WPB_Main::instance();
}
add_action('plugins_loaded', 'ix_wpb_init_plugin');