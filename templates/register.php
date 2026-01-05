<?php
/**
 * Register Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="zonatech-container">
    <!-- Loading Screen -->
    <div id="zonatech-loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>ZonaTech NG</h2>
            <p>Loading...</p>
        </div>
    </div>

    <div class="zonatech-wrapper">
        <!-- Back to Home -->
        <div class="back-to-home">
            <a href="<?php echo site_url(); ?>" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <!-- Registration Form -->
        <div class="auth-card glass-effect" id="register-card">
            <div class="auth-header">
                <a href="<?php echo site_url(); ?>" class="zonatech-logo mb-2">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </a>
                <h2 class="text-white"><i class="fas fa-user-plus"></i> Create Account</h2>
                <p class="text-muted">Join thousands of students preparing for success</p>
            </div>
            
            <form id="zonatech-register-form">
                <div class="row">
                    <div class="col col-sm-12" style="flex: 1; min-width: 140px;">
                        <div class="form-group">
                            <label for="first_name" class="text-white"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="First name" required>
                        </div>
                    </div>
                    <div class="col col-sm-12" style="flex: 1; min-width: 140px;">
                        <div class="form-group">
                            <label for="last_name" class="text-white"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last name" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="text-white"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="text-white"><i class="fas fa-phone"></i> Phone Number <span class="text-muted">(Optional)</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="e.g., 08012345678">
                </div>
                
                <div class="form-group">
                    <label for="password" class="text-white"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Create a password (min. 6 characters)" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="text-white"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="terms" required style="width: auto; margin-top: 0.25rem; accent-color: #8b5cf6;">
                        <span style="font-size: 0.8rem; line-height: 1.5;" class="text-white">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> <span>Create Account</span>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <p class="text-center text-muted" style="font-size: 0.85rem;">
                Already have an account? 
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Sign in</a>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Hide loading screen - FAST!
    requestAnimationFrame(function() {
        $('#zonatech-loading-screen').addClass('fade-out');
        setTimeout(function() {
            $('#zonatech-loading-screen').hide();
        }, 100);
    });
    
    // Handle registration form submission
    $('#zonatech-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();
        var userEmail = $form.find('[name="email"]').val();
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating Account...');
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_register',
                nonce: zonatech_ajax.nonce,
                first_name: $form.find('[name="first_name"]').val(),
                last_name: $form.find('[name="last_name"]').val(),
                email: userEmail,
                phone: $form.find('[name="phone"]').val(),
                password: $form.find('[name="password"]').val(),
                confirm_password: $form.find('[name="confirm_password"]').val()
            },
            success: function(response) {
                if (response.success) {
                    if (typeof ZonaTechNotify !== 'undefined') {
                        ZonaTechNotify.success(response.data.message);
                    }
                    
                    // Redirect to dedicated verification page
                    var verifyUrl = '<?php echo site_url('/zonatech-verify-email/'); ?>';
                    verifyUrl += '?pending_id=' + response.data.pending_user_id;
                    verifyUrl += '&email=' + encodeURIComponent(userEmail);
                    
                    setTimeout(function() {
                        window.location.href = verifyUrl;
                    }, 1000);
                } else {
                    if (typeof ZonaTechNotify !== 'undefined') {
                        ZonaTechNotify.error(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                if (typeof ZonaTechNotify !== 'undefined') {
                    ZonaTechNotify.error('An error occurred. Please try again.');
                } else {
                    alert('An error occurred. Please try again.');
                }
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>