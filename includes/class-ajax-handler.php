<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class IX_WPB_Ajax_Handler {

    private static $instance;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_ajax_ix_wpb_search_products', [$this, 'handle_product_search']);
        add_action('wp_ajax_nopriv_ix_wpb_search_products', [$this, 'handle_nopriv_access']);
    }

    public function handle_product_search() {
        check_ajax_referer('ix_wpb_search_products', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(
                __('Permission denied', 'ix-woo-pro-banner'),
                403
            );
        }

        $search = isset($_REQUEST['q']) ? sanitize_text_field($_REQUEST['q']) : '';
        $results = [];

        if (!empty($search)) {
            $products = get_posts([
                'post_type' => 'product',
                'posts_per_page' => 20,
                's' => $search,
                'post_status' => 'publish'
            ]);

            foreach ($products as $product) {
                $product_obj = wc_get_product($product->ID);
                $price = $product_obj ? $product_obj->get_price() : '';
                
                $results[] = [
                    'id' => $product->ID,
                    'text' => $product->post_title,
                    'price' => $price,
                    'display' => $this->format_product_display($product, $price)
                ];
            }
        }

        wp_send_json_success($results);
    }

    private function format_product_display($product, $price) {
        $price_display = $price ? wc_price($price) : __('N/A', 'ix-woo-pro-banner');
        return sprintf('%s (ID: %d) - %s',
            $product->post_title,
            $product->ID,
            $price_display
        );
    }

    public function handle_nopriv_access() {
        wp_send_json_error(
            __('Authentication required', 'ix-woo-pro-banner'),
            401
        );
    }
}