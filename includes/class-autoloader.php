<?php
/**
 * Autoloader class
 */

defined('ABSPATH') || exit;

class IX_WPB_Autoloader {
    
    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload classes
     */
    private static function autoload($class) {
        $prefix = 'IX_WPB_';
        $base_dir = IX_WPB_PLUGIN_DIR . 'includes/';
        
        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('_', '-', strtolower($relative_class)) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
}