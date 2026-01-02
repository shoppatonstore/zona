<?php
/**
 * AJAX Handlers Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Ajax_Handlers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Dashboard data handlers
        add_action('wp_ajax_zonatech_get_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('wp_ajax_zonatech_get_payment_history', array($this, 'get_payment_history'));
        add_action('wp_ajax_zonatech_get_downloaded_documents', array($this, 'get_downloaded_documents'));
    }
    
    public function get_dashboard_data() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_data = ZonaTech_User_Auth::get_user_dashboard_data();
        $quiz_stats = ZonaTech_Quiz_System::get_user_quiz_stats(get_current_user_id());
        $accessible_subjects = ZonaTech_Past_Questions::get_user_accessible_subjects(get_current_user_id());
        
        wp_send_json_success(array(
            'user' => $user_data['user'],
            'stats' => $user_data['stats'],
            'quiz_stats' => $quiz_stats,
            'accessible_subjects' => $accessible_subjects
        ));
    }
    
    public function get_payment_history() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_purchases WHERE user_id = %d",
            $user_id
        ));
        
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_purchases 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
        
        foreach ($payments as &$payment) {
            $payment->formatted_amount = '₦' . number_format($payment->amount);
            $payment->formatted_date = date('M j, Y g:i A', strtotime($payment->created_at));
            $payment->status_class = $payment->status === 'completed' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'error');
        }
        
        wp_send_json_success(array(
            'payments' => $payments,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    public function get_downloaded_documents() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_downloads = $wpdb->prefix . 'zonatech_downloads';
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_downloads WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        foreach ($documents as &$doc) {
            $doc->formatted_date = date('M j, Y', strtotime($doc->created_at));
            $doc->icon = $this->get_document_icon($doc->document_type);
        }
        
        wp_send_json_success(array('documents' => $documents));
    }
    
    private function get_document_icon($type) {
        $icons = array(
            'nin_slip' => 'fas fa-id-card',
            'scratch_card' => 'fas fa-credit-card',
            'receipt' => 'fas fa-receipt',
            'certificate' => 'fas fa-certificate'
        );
        
        return $icons[$type] ?? 'fas fa-file';
    }
}