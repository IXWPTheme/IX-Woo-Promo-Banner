<?php
// includes/class-frontend-controls.php
class IX_WPB_Frontend_Controls {

    public static function init() {
        add_shortcode('wpb-control-panel', [__CLASS__, 'render_control_panel']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_wpb_update_grid', [__CLASS__, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_wpb_update_grid', [__CLASS__, 'handle_ajax_request']);
    }

    public static function enqueue_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'wpb-control-panel')) {
            wp_enqueue_style(
                'ix-wpb-controls',
                IX_WPB_PLUGIN_URL . 'assets/css/controls.css',
                [],
                IX_WPB_VERSION
            );
            
            wp_enqueue_script(
                'ix-wpb-controls',
                IX_WPB_PLUGIN_URL . 'assets/js/controls.js',
                ['jquery'],
                IX_WPB_VERSION,
                true
            );

            wp_localize_script('ix-wpb-controls', 'wpbControls', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpb_controls_nonce')
            ]);
        }
    }

    public static function render_control_panel() {
        if (!is_user_logged_in()) {
            return '<div class="wpb-notice">' . __('Please log in to access controls', 'ix-woo-pro-banner') . '</div>';
        }

        ob_start();
        ?>
        <div class="wpb-control-panel">
            <div class="wpb-control-section">
                <h3><?php esc_html_e('Grid Layout', 'ix-woo-pro-banner'); ?></h3>
                <div class="wpb-control-group">
                    <label for="wpb-columns"><?php esc_html_e('Columns:', 'ix-woo-pro-banner'); ?></label>
                    <select id="wpb-columns" class="wpb-control">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="wpb-control-group">
                    <label for="wpb-limit"><?php esc_html_e('Products Limit:', 'ix-woo-pro-banner'); ?></label>
                    <input type="number" id="wpb-limit" class="wpb-control" min="1" max="50" value="12">
                </div>
            </div>

            <div class="wpb-control-section">
                <h3><?php esc_html_e('Display Options', 'ix-woo-pro-banner'); ?></h3>
                <div class="wpb-control-group">
                    <label>
                        <input type="checkbox" id="wpb-show-title" class="wpb-control" checked>
                        <?php esc_html_e('Show Product Titles', 'ix-woo-pro-banner'); ?>
                    </label>
                </div>
                <div class="wpb-control-group">
                    <label>
                        <input type="checkbox" id="wpb-show-price" class="wpb-control" checked>
                        <?php esc_html_e('Show Prices', 'ix-woo-pro-banner'); ?>
                    </label>
                </div>
            </div>

            <button id="wpb-apply-changes" class="wpb-button">
                <?php esc_html_e('Apply Changes', 'ix-woo-pro-banner'); ?>
            </button>
        </div>
        
        <div id="wpb-grid-preview" class="wpb-grid-container"></div>
        <?php
        return ob_get_clean();
    }

    public static function handle_ajax_request() {
        check_ajax_referer('wpb_controls_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Authentication required', 'ix-woo-pro-banner'));
        }

        $atts = [
            'columns' => isset($_POST['columns']) ? absint($_POST['columns']) : 4,
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : 12,
            'show_title' => isset($_POST['show_title']) ? 'yes' : 'no',
            'show_price' => isset($_POST['show_price']) ? 'yes' : 'no',
            // Add more parameters as needed
        ];

        $grid_html = do_shortcode('[wpb-shop-pro-grid ' . $this->build_shortcode_atts($atts) . ']');

        wp_send_json_success([
            'html' => $grid_html,
            'atts' => $atts
        ]);
    }

    private static function build_shortcode_atts($atts) {
        $output = [];
        foreach ($atts as $key => $value) {
            $output[] = $key . '="' . esc_attr($value) . '"';
        }
        return implode(' ', $output);
    }
}

IX_WPB_Frontend_Controls::init();