<?php
/**
 * Plugin activator
 */

defined('ABSPATH') || exit;

class IX_WPB_Activator {
    
    public static function activate() {
        // Set default options
        $defaults = [
            'ix_wpb_settings' => [
                'default_image_source' => 'both',
                'default_image_size' => 'woocommerce_thumbnail'
            ],
            'ix_wpb_shop_pro_grid_settings' => [
                'limit' => 12,
                'columns' => 4,
                'orderby' => 'date',
                'order' => 'DESC',
                'category' => '',
                'selected_products' => [],
                'image_source' => 'both',
                'image_size' => 'woocommerce_thumbnail',
                'show_title' => true,
                'show_price' => true,
                'show_rating' => true,
                'show_add_to_cart' => true,
                'show_thumbnail' => true,
                'pagination' => false,
                'class' => ''
            ]
        ];
        
        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                update_option($option, $value);
            }
        }
        
        set_transient('ix_wpb_activated', true, 30);
    }
}