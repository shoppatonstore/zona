<?php
/**
 * User Authentication Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_User_Auth {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_nopriv_zonatech_register', array($this, 'handle_register'));
        add_action('wp_ajax_zonatech_register', array($this, 'handle_register')); // Also for logged-in users
        add_action('wp_ajax_nopriv_zonatech_verify_email', array($this, 'handle_verify_email'));
        add_action('wp_ajax_zonatech_verify_email', array($this, 'handle_verify_email')); // Also for logged-in users
        add_action('wp_ajax_nopriv_zonatech_resend_verification', array($this, 'handle_resend_verification'));
        add_action('wp_ajax_zonatech_resend_verification', array($this, 'handle_resend_verification')); // Also for logged-in users
        add_action('wp_ajax_nopriv_zonatech_login', array($this, 'handle_login'));
        add_action('wp_ajax_zonatech_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_zonatech_reset_password', array($this, 'handle_reset_password'));
        add_action('wp_ajax_zonatech_update_profile', array($this, 'handle_update_profile'));
        add_action('wp_ajax_zonatech_change_password', array($this, 'handle_change_password'));
        add_action('wp_ajax_zonatech_upload_avatar', array($this, 'handle_upload_avatar'));
    }
    
    /**
     * Handle avatar upload
     */
    public function handle_upload_avatar() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in.'));
            return;
        }
        
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded or upload error.'));
            return;
        }
        
        $file = $_FILES['avatar'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Invalid file type. Only JPG, PNG, GIF and WebP allowed.'));
            return;
        }
        
        // Max 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large. Maximum size is 2MB.'));
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('avatar', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Delete old avatar if exists
        $old_avatar_id = get_user_meta($user_id, 'zonatech_avatar_id', true);
        if ($old_avatar_id) {
            wp_delete_attachment($old_avatar_id, true);
        }
        
        // Save new avatar
        update_user_meta($user_id, 'zonatech_avatar_id', $attachment_id);
        
        $avatar_url = wp_get_attachment_url($attachment_id);
        
        wp_send_json_success(array(
            'message' => 'Avatar updated successfully!',
            'avatar_url' => $avatar_url
        ));
    }
    
    /**
     * Generate a 6-digit verification code
     */
    private function generate_verification_code() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get email HTML template with glassmorphism design
     */
    private function get_email_template($title, $content, $button_text = '', $button_url = '') {
        $button_html = '';
        if ($button_text && $button_url) {
            $button_html = '
            <a href="' . esc_url($button_url) . '" style="
                display: inline-block;
                background: linear-gradient(135deg, #9333ea 0%, #7c3aed 50%, #6366f1 100%);
                color: #ffffff !important;
                text-decoration: none;
                padding: 14px 32px;
                border-radius: 50px;
                font-weight: 600;
                font-size: 16px;
                margin: 20px 0;
                box-shadow: 0 8px 25px rgba(147, 51, 234, 0.4);
            ">' . esc_html($button_text) . '</a>';
        }
        
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%); min-height: 100vh;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <div style="
                                background: rgba(147, 51, 234, 0.2);
                                backdrop-filter: blur(10px);
                                border-radius: 20px;
                                padding: 20px 40px;
                                border: 1px solid rgba(147, 51, 234, 0.3);
                                display: inline-block;
                            ">
                                <span style="font-size: 28px; font-weight: 700; color: #ffffff;">
                                    🎓 ZonaTech NG
                                </span>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Main Card -->
                    <tr>
                        <td>
                            <div style="
                                background: rgba(255, 255, 255, 0.08);
                                backdrop-filter: blur(20px);
                                border-radius: 24px;
                                padding: 40px;
                                border: 1px solid rgba(255, 255, 255, 0.1);
                                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                            ">
                                <h1 style="
                                    color: #ffffff;
                                    font-size: 24px;
                                    font-weight: 700;
                                    margin: 0 0 20px 0;
                                    text-align: center;
                                ">' . esc_html($title) . '</h1>
                                
                                <div style="color: rgba(255, 255, 255, 0.9); font-size: 16px; line-height: 1.7;">
                                    ' . $content . '
                                </div>
                                
                                <div style="text-align: center; margin: 30px 0;">
                                    ' . $button_html . '
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <div style="
                                background: rgba(255, 255, 255, 0.05);
                                border-radius: 16px;
                                padding: 25px;
                                border: 1px solid rgba(255, 255, 255, 0.08);
                            ">
                                <p style="color: rgba(255, 255, 255, 0.7); font-size: 14px; margin: 0 0 10px 0;">
                                    Need help? Contact us:
                                </p>
                                <p style="color: #9333ea; font-size: 14px; margin: 0;">
                                    📧 ' . ZONATECH_SUPPORT_EMAIL . '<br>
                                    📱 WhatsApp: ' . ZONATECH_WHATSAPP_NUMBER . '
                                </p>
                                <p style="color: rgba(255, 255, 255, 0.5); font-size: 12px; margin: 20px 0 0 0;">
                                    © ' . date('Y') . ' ZonaTech NG. All rights reserved.
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Send verification email
     */
    private function send_verification_email($email, $first_name, $code) {
        $subject = 'Verify Your Email - ZonaTech NG';
        
        $content = '
            <p style="margin: 0 0 15px 0;">Hi <strong>' . esc_html($first_name) . '</strong>,</p>
            <p style="margin: 0 0 15px 0;">Thank you for registering with ZonaTech NG! 🎉</p>
            <p style="margin: 0 0 20px 0;">Your verification code is:</p>
            <div style="
                background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
                border-radius: 16px;
                padding: 25px;
                text-align: center;
                margin: 20px 0;
            ">
                <span style="
                    font-size: 36px;
                    font-weight: 700;
                    color: #ffffff;
                    letter-spacing: 8px;
                    font-family: monospace;
                ">' . esc_html($code) . '</span>
            </div>
            <p style="margin: 20px 0 15px 0;">Enter this code on the verification page to complete your registration.</p>
            <p style="
                color: rgba(255, 255, 255, 0.6);
                font-size: 14px;
                margin: 15px 0 0 0;
                padding: 15px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 10px;
            ">
                ⏰ This code will expire in 30 minutes.
            </p>
        ';
        
        $message = $this->get_email_template($subject, $content);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <support@zonatechng.com>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send account approved email
     */
    private function send_approval_email($email, $first_name) {
        $subject = 'Account Approved - Welcome to ZonaTech NG!';
        
        $content = '
            <p style="margin: 0 0 15px 0;">Hi <strong>' . esc_html($first_name) . '</strong>,</p>
            <p style="margin: 0 0 15px 0;">Great news! Your ZonaTech NG account has been verified and approved. 🎉</p>
            <p style="margin: 0 0 20px 0;">You can now log in and access:</p>
            <ul style="
                list-style: none;
                padding: 0;
                margin: 0 0 20px 0;
            ">
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ JAMB, WAEC, and NECO past questions</li>
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ Scratch cards and PINs</li>
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ NIN verification services</li>
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ And much more!</li>
            </ul>
            <p style="margin: 20px 0 0 0;">Click the button below to log in to your account:</p>
        ';
        
        $message = $this->get_email_template($subject, $content, 'Login Now', home_url('/zonatech-login/'));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <support@zonatechng.com>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    public function handle_register() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email address already exists.'));
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error(array('message' => 'Password must be at least 6 characters.'));
        }
        
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Passwords do not match.'));
        }
        
        // Check if there's already a pending verification for this email
        global $wpdb;
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        
        // Create pending users table if not exists
        $this->create_pending_users_table();
        
        // Delete any existing pending registration for this email
        $wpdb->delete($table_name, array('email' => $email));
        
        // Generate verification code
        $verification_code = $this->generate_verification_code();
        
        // Store pending registration
        $insert_result = $wpdb->insert($table_name, array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => wp_hash_password($password),
            'verification_code' => $verification_code,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'created_at' => current_time('mysql')
        ), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        
        if ($insert_result === false) {
            // Log the database error for debugging
            error_log('ZonaTech Registration DB Error: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Failed to create registration. Please try again later.'));
        }
        
        $pending_user_id = $wpdb->insert_id;
        
        if (!$pending_user_id) {
            wp_send_json_error(array('message' => 'Failed to create registration. Please try again.'));
        }
        
        // Send verification email
        $email_sent = $this->send_verification_email($email, $first_name, $verification_code);
        
        if (!$email_sent) {
            $wpdb->delete($table_name, array('id' => $pending_user_id));
            wp_send_json_error(array('message' => 'Failed to send verification email. Please try again.'));
        }
        
        // Build the redirect URL to the verification page
        $redirect_url = home_url('/zonatech-verify-email/') . '?pending_id=' . $pending_user_id . '&email=' . urlencode($email);
        
        wp_send_json_success(array(
            'message' => 'Verification code sent to your email!',
            'pending_user_id' => $pending_user_id,
            'redirect' => $redirect_url
        ));
    }
    
    public function handle_verify_email() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $pending_user_id = intval($_POST['pending_user_id'] ?? 0);
        $verification_code = sanitize_text_field($_POST['verification_code'] ?? '');
        
        // Log the verification attempt for debugging
        error_log('ZonaTech: Verification attempt - pending_user_id: ' . $pending_user_id . ', code: ' . $verification_code);
        
        if (empty($pending_user_id) || empty($verification_code)) {
            error_log('ZonaTech: Verification failed - missing pending_user_id or code');
            wp_send_json_error(array('message' => 'Verification code is required.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log('ZonaTech: Verification failed - pending_users table does not exist');
            wp_send_json_error(array('message' => 'System error. Please contact support.'));
            return;
        }
        
        // Get pending registration
        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND verification_code = %s",
            $pending_user_id,
            $verification_code
        ));
        
        if (!$pending) {
            // Try to get just by ID to give better error message
            $pending_by_id = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $pending_user_id
            ));
            
            if (!$pending_by_id) {
                error_log('ZonaTech: Verification failed - no pending registration found for id: ' . $pending_user_id);
                wp_send_json_error(array('message' => 'Registration not found. Please register again.'));
            } else {
                error_log('ZonaTech: Verification failed - invalid code for pending_user_id: ' . $pending_user_id);
                wp_send_json_error(array('message' => 'Invalid verification code. Please check and try again.'));
            }
            return;
        }
        
        // Check if expired
        if (strtotime($pending->expires_at) < time()) {
            $wpdb->delete($table_name, array('id' => $pending_user_id));
            error_log('ZonaTech: Verification failed - code expired for pending_user_id: ' . $pending_user_id);
            wp_send_json_error(array('message' => 'Verification code has expired. Please register again.'));
            return;
        }
        
        // Create the actual user
        $username = sanitize_user(strtolower($pending->first_name . $pending->last_name) . wp_rand(100, 999));
        
        error_log('ZonaTech: Creating user with username: ' . $username . ', email: ' . $pending->email);
        
        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $pending->email,
            'user_pass' => '', // Empty because we'll set it manually
            'first_name' => $pending->first_name,
            'last_name' => $pending->last_name,
            'display_name' => $pending->first_name . ' ' . $pending->last_name
        ));
        
        if (is_wp_error($user_id)) {
            error_log('ZonaTech: User creation failed - ' . $user_id->get_error_message());
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }
        
        // Set the password directly (it's already hashed)
        $wpdb->update(
            $wpdb->users,
            array('user_pass' => $pending->password),
            array('ID' => $user_id)
        );
        
        // Update user meta
        update_user_meta($user_id, 'phone', $pending->phone);
        update_user_meta($user_id, 'zonatech_registered', current_time('mysql'));
        update_user_meta($user_id, 'zonatech_email_verified', true);
        
        // Delete pending registration
        $wpdb->delete($table_name, array('id' => $pending_user_id));
        
        // Log activity
        if (class_exists('ZonaTech_Activity_Log')) {
            ZonaTech_Activity_Log::log($user_id, 'registration', 'User registered and verified email');
        }
        
        // Send approval email
        $this->send_approval_email($pending->email, $pending->first_name);
        
        error_log('ZonaTech: User created successfully - user_id: ' . $user_id);
        
        wp_send_json_success(array(
            'message' => 'Email verified successfully! You can now log in.',
            'redirect' => home_url('/zonatech-login/')
        ));
    }
    
    public function handle_resend_verification() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $pending_user_id = intval($_POST['pending_user_id'] ?? 0);
        
        if (empty($pending_user_id)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        
        // Get pending registration
        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $pending_user_id
        ));
        
        if (!$pending) {
            wp_send_json_error(array('message' => 'Registration not found. Please register again.'));
        }
        
        // Generate new verification code
        $new_code = $this->generate_verification_code();
        
        // Update pending registration
        $wpdb->update($table_name, array(
            'verification_code' => $new_code,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ), array('id' => $pending_user_id));
        
        // Send new verification email
        $email_sent = $this->send_verification_email($pending->email, $pending->first_name, $new_code);
        
        if (!$email_sent) {
            wp_send_json_error(array('message' => 'Failed to send verification email. Please try again.'));
        }
        
        wp_send_json_success(array(
            'message' => 'New verification code sent to your email!'
        ));
    }
    
    /**
     * Create pending users table
     */
    private function create_pending_users_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        
        // Check if table already exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                first_name varchar(100) NOT NULL,
                last_name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                phone varchar(20) DEFAULT '',
                password varchar(255) NOT NULL,
                verification_code varchar(6) NOT NULL,
                expires_at datetime NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY email (email)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Log table creation for debugging
            error_log('ZonaTech: Created pending_users table');
        }
    }
    
    public function handle_login() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
        if (empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Email and password are required.'));
        }
        
        $user = get_user_by('email', $email);
        
        if (!$user) {
            wp_send_json_error(array('message' => 'Invalid email or password.'));
        }
        
        $credentials = array(
            'user_login' => $user->user_login,
            'user_password' => $password,
            'remember' => $remember
        );
        
        $login = wp_signon($credentials, is_ssl());
        
        if (is_wp_error($login)) {
            wp_send_json_error(array('message' => 'Invalid email or password.'));
        }
        
        // Log activity
        ZonaTech_Activity_Log::log($user->ID, 'login', 'User logged in');
        
        wp_send_json_success(array(
            'message' => 'Login successful!',
            'redirect' => home_url('/zonatech-dashboard/')
        ));
    }
    
    public function handle_logout() {
        $user_id = get_current_user_id();
        
        if ($user_id) {
            ZonaTech_Activity_Log::log($user_id, 'logout', 'User logged out');
        }
        
        wp_logout();
        
        wp_send_json_success(array(
            'message' => 'Logged out successfully.',
            'redirect' => home_url('/zonatech-login/')
        ));
    }
    
    public function handle_reset_password() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email address is required.'));
        }
        
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Don't reveal if email exists or not for security
            wp_send_json_success(array(
                'message' => 'If this email exists, a password reset link will be sent.'
            ));
        }
        
        // Generate reset key
        $reset_key = get_password_reset_key($user);
        
        if (is_wp_error($reset_key)) {
            wp_send_json_error(array('message' => 'Error generating reset link. Please try again.'));
        }
        
        // Send reset email
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
        
        $message = "Hi " . $user->display_name . ",\n\n";
        $message .= "You requested a password reset for your ZonaTech NG account.\n\n";
        $message .= "Click the link below to reset your password:\n";
        $message .= $reset_url . "\n\n";
        $message .= "If you didn't request this, please ignore this email.\n\n";
        $message .= "Thanks,\nZonaTech NG Team";
        
        $sent = wp_mail($email, 'Password Reset - ZonaTech NG', $message);
        
        ZonaTech_Activity_Log::log($user->ID, 'password_reset_request', 'Password reset requested');
        
        wp_send_json_success(array(
            'message' => 'If this email exists, a password reset link will be sent.'
        ));
    }
    
    public function handle_update_profile() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to update your profile.'));
        }
        
        $user_id = get_current_user_id();
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($first_name) || empty($last_name)) {
            wp_send_json_error(array('message' => 'First name and last name are required.'));
        }
        
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        update_user_meta($user_id, 'phone', $phone);
        
        ZonaTech_Activity_Log::log($user_id, 'profile_update', 'Profile updated');
        
        wp_send_json_success(array('message' => 'Profile updated successfully!'));
    }
    
    public function handle_change_password() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to change your password.'));
        }
        
        $user_id = get_current_user_id();
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        $user = get_user_by('id', $user_id);
        
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'Current password is incorrect.'));
        }
        
        if (strlen($new_password) < 6) {
            wp_send_json_error(array('message' => 'New password must be at least 6 characters.'));
        }
        
        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => 'New passwords do not match.'));
        }
        
        wp_set_password($new_password, $user_id);
        
        // Re-login user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        ZonaTech_Activity_Log::log($user_id, 'password_change', 'Password changed');
        
        wp_send_json_success(array('message' => 'Password changed successfully!'));
    }
    
    public static function get_user_dashboard_data() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Get purchase count
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        $purchase_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_purchases WHERE user_id = %d AND status = 'completed'",
            $user_id
        ));
        
        // Get quiz count - ensure table exists and handle null
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $quiz_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_quiz WHERE user_id = %d",
            $user_id
        ));
        // Handle case where table doesn't exist (returns null)
        if ($quiz_count === null) {
            $quiz_count = 0;
        }
        
        // Get total spent
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_purchases WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?? 0;
        
        // Get accessible subjects count
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        $subjects_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_access WHERE user_id = %d",
            $user_id
        ));
        
        // Get custom avatar or default
        $custom_avatar_id = get_user_meta($user_id, 'zonatech_avatar_id', true);
        if ($custom_avatar_id) {
            $avatar_url = wp_get_attachment_url($custom_avatar_id);
        } else {
            $avatar_url = get_avatar_url($user_id, array('size' => 150));
        }
        
        return array(
            'user' => array(
                'id' => $user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user_id, 'phone', true),
                'avatar' => $avatar_url,
                'registered' => get_user_meta($user_id, 'zonatech_registered', true)
            ),
            'stats' => array(
                'purchases' => (int) $purchase_count,
                'quizzes' => (int) $quiz_count,
                'total_spent' => (float) $total_spent,
                'subjects' => (int) $subjects_count
            )
        );
    }
}