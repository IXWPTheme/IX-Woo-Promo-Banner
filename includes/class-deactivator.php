<?php
/**
 * Plugin deactivator
 */

defined('ABSPATH') || exit;

class IX_WPB_Deactivator {
    
    public static function deactivate() {
        // Cleanup temporary options
        delete_transient('ix_wpb_activated');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}