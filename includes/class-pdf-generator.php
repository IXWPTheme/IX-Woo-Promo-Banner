<?php 

class IX_WPB_PDF_Generator {
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
        $this->register_hooks();
    }

    public function register_hooks() {
        // PDF generation hooks
        add_action('ix_wpb_generate_pdf', [$this, 'generate_pdf']);
        
        // Admin hooks if needed
        if (is_admin()) {
            add_action('admin_post_ix_wpb_export_pdf', [$this, 'handle_pdf_export']);
        }
    }

    // ... rest of your PDF methods ...
}