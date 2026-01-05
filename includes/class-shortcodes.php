<?php
/**
 * Shortcodes Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('zonatech_login', array($this, 'render_login'));
        add_shortcode('zonatech_register', array($this, 'render_register'));
        add_shortcode('zonatech_verify_email', array($this, 'render_verify_email'));
        add_shortcode('zonatech_dashboard', array($this, 'render_dashboard'));
        add_shortcode('zonatech_past_questions', array($this, 'render_past_questions'));
        add_shortcode('zonatech_nin_service', array($this, 'render_nin_service'));
        add_shortcode('zonatech_scratch_cards', array($this, 'render_scratch_cards'));
        add_shortcode('zonatech_payment', array($this, 'render_payment'));
        add_shortcode('zonatech_homepage', array($this, 'render_homepage'));
        add_shortcode('zonatech_feedback', array($this, 'render_feedback'));
        add_shortcode('zonatech_admin_dashboard', array($this, 'render_admin_dashboard'));
    }
    
    /**
     * Check if user is logged in, redirect to login if not
     */
    private function require_login($redirect_page = '') {
        if (!is_user_logged_in()) {
            $redirect_url = site_url('/zonatech-login/');
            if (!empty($redirect_page)) {
                $redirect_url .= '?redirect=' . urlencode($redirect_page);
            }
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    public function render_login() {
        if (is_user_logged_in()) {
            wp_redirect(site_url('/zonatech-dashboard/'));
            exit;
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/login.php';
        return ob_get_clean();
    }
    
    public function render_register() {
        if (is_user_logged_in()) {
            wp_redirect(site_url('/zonatech-dashboard/'));
            exit;
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/register.php';
        return ob_get_clean();
    }
    
    public function render_verify_email() {
        if (is_user_logged_in()) {
            wp_redirect(site_url('/zonatech-dashboard/'));
            exit;
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/verify-email.php';
        return ob_get_clean();
    }
    
    public function render_dashboard() {
        $this->require_login('dashboard');
        
        $user_data = ZonaTech_User_Auth::get_user_dashboard_data();
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }
    
    public function render_past_questions() {
        // Require login to view past questions
        $this->require_login('past-questions');
        
        $exam_types = ZonaTech_Past_Questions::get_exam_types();
        $is_guest = false;
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/past-questions.php';
        return ob_get_clean();
    }
    
    public function render_nin_service() {
        // Require login to use NIN service
        $this->require_login('nin-service');
        
        $is_guest = false;
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/nin-service.php';
        return ob_get_clean();
    }
    
    public function render_scratch_cards() {
        // Require login to purchase scratch cards
        $this->require_login('scratch-cards');
        
        $card_types = ZonaTech_Scratch_Cards::get_card_types();
        $is_guest = false;
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/scratch-cards.php';
        return ob_get_clean();
    }
    
    public function render_payment() {
        $this->require_login('payment');
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/payment.php';
        return ob_get_clean();
    }
    
    public function render_homepage() {
        $exam_types = ZonaTech_Past_Questions::get_exam_types();
        $card_types = ZonaTech_Scratch_Cards::get_card_types();
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/homepage.php';
        return ob_get_clean();
    }
    
    public function render_feedback() {
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/feedback.php';
        return ob_get_clean();
    }
    
    public function render_admin_dashboard() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_redirect(site_url('/zonatech-dashboard/'));
            exit;
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/admin-dashboard.php';
        return ob_get_clean();
    }
}