<?php
/**
 * NIN Service Template - Multiple Services
 */

if (!defined('ABSPATH')) exit;
$is_guest = !is_user_logged_in();

// Get prices
$slip_price = defined('ZONATECH_NIN_SLIP_DOWNLOAD_PRICE') ? ZONATECH_NIN_SLIP_DOWNLOAD_PRICE : 1300;
$modification_price = defined('ZONATECH_NIN_MODIFICATION_PRICE') ? ZONATECH_NIN_MODIFICATION_PRICE : 3800;
$dob_price = defined('ZONATECH_NIN_DOB_CORRECTION_PRICE') ? ZONATECH_NIN_DOB_CORRECTION_PRICE : 5300;
?>

<div class="zonatech-container">
    <div class="zonatech-wrapper">
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <div class="zonatech-logo">
                <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                <span>ZonaTech NG</span>
            </div>
            <nav class="zonatech-nav">
                <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
                <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
                <a href="<?php echo site_url('/zonatech-nin-service/'); ?>" class="active"><i class="fas fa-id-card"></i> NIN Service</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </nav>
            
            <div class="hamburger-menu" id="hamburger-menu">
                <span></span><span></span><span></span>
            </div>
        </div>
        
        <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
        <nav class="mobile-nav" id="mobile-nav">
            <div class="mobile-nav-header">
                <div class="zonatech-logo"><img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img"><span>ZonaTech NG</span></div>
                <button class="mobile-nav-close" id="mobile-nav-close"><i class="fas fa-times"></i></button>
            </div>
            <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
            <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
            <a href="<?php echo site_url('/zonatech-nin-service/'); ?>" class="active"><i class="fas fa-id-card"></i> NIN Service</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </nav>
        
        <?php if ($is_guest): ?>
        <div class="glass-card glass-effect-purple" style="max-width: 700px; margin: 0 auto 2rem; text-align: center;">
            <div style="width: 80px; height: 80px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: rgba(139, 92, 246, 0.2); border-radius: 50%; font-size: 2rem; color: var(--zona-purple-light);">
                <i class="fas fa-user-lock"></i>
            </div>
            <h3 class="text-white"><i class="fas fa-lock"></i> Login Required</h3>
            <p class="text-muted" style="margin-bottom: 1.5rem;">Create an account or login to access NIN services.</p>
            <div class="d-flex justify-center gap-2" style="flex-wrap: wrap;">
                <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Account</a>
                <a href="<?php echo site_url('/zonatech-login/'); ?>" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mb-3">
            <h1 class="text-white" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-id-card" style="color: #8b5cf6;"></i> NIN Services</h1>
            <p class="text-muted">Choose from our professional NIN services below</p>
        </div>
        
        <!-- Service Cards -->
        <div class="cards-grid" style="max-width: 1000px; margin: 0 auto 2rem; <?php echo $is_guest ? 'opacity: 0.6; pointer-events: none;' : ''; ?>">
            
            <div class="service-card animate-card" onclick="showServiceForm('slip_download')" style="cursor: pointer;">
                <div class="service-card-icon" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e;">
                    <i class="fas fa-download"></i>
                </div>
                <h3 class="service-card-title">NIN Slip Download</h3>
                <p class="service-card-desc">Download your official NIN slip with photo and all details</p>
                <p class="service-card-price" style="color: #22c55e;">₦<?php echo number_format($slip_price); ?></p>
                <button class="btn btn-primary btn-sm" style="margin-top: 1rem;"><i class="fas fa-arrow-right"></i> Get Started</button>
            </div>
            
            <div class="service-card animate-card" onclick="showServiceForm('modification')" style="cursor: pointer;">
                <div class="service-card-icon" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.1)); color: #3b82f6;">
                    <i class="fas fa-edit"></i>
                </div>
                <h3 class="service-card-title">NIN Data Modification</h3>
                <p class="service-card-desc">Correct your name, gender, or other details on your NIN</p>
                <p class="service-card-price" style="color: #3b82f6;">₦<?php echo number_format($modification_price); ?></p>
                <button class="btn btn-primary btn-sm" style="margin-top: 1rem;"><i class="fas fa-arrow-right"></i> Get Started</button>
            </div>
            
            <div class="service-card animate-card" onclick="showServiceForm('dob_correction')" style="cursor: pointer;">
                <div class="service-card-icon" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1)); color: #f59e0b;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="service-card-title">Date of Birth Correction</h3>
                <p class="service-card-desc">Update your date of birth on your NIN record</p>
                <p class="service-card-price" style="color: #f59e0b;">₦<?php echo number_format($dob_price); ?></p>
                <button class="btn btn-primary btn-sm" style="margin-top: 1rem;"><i class="fas fa-arrow-right"></i> Get Started</button>
            </div>
        </div>
        
        <!-- Service Forms Container -->
        <div id="service-forms-container" style="display: none; max-width: 700px; margin: 0 auto;">
            
            <!-- Form 1: NIN Slip Download -->
            <div id="form-slip_download" class="glass-card service-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 class="text-white" style="margin: 0;"><i class="fas fa-download" style="color: #22c55e;"></i> NIN Slip Download</h3>
                    <button onclick="hideServiceForm()" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-times"></i> Close</button>
                </div>
                <form id="slip-download-form" onsubmit="submitNINService(event, 'nin_slip_download', <?php echo $slip_price; ?>)">
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-id-badge"></i> NIN Number *</label>
                        <input type="text" name="nin" class="form-control" placeholder="Enter your 11-digit NIN" maxlength="11" pattern="\d{11}" required>
                    </div>
                    <div class="row" style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="full_name" class="form-control" placeholder="As it appears on NIN" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-phone"></i> Phone *</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Your phone number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>" required>
                    </div>
                    <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 1rem; margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="text-white"><strong>Service Fee:</strong></span>
                            <span style="color: #22c55e; font-size: 1.5rem; font-weight: 700;">₦<?php echo number_format($slip_price); ?></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;"><i class="fas fa-credit-card"></i> Pay & Submit Request</button>
                </form>
            </div>
            
            <!-- Form 2: NIN Modification -->
            <div id="form-modification" class="glass-card service-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 class="text-white" style="margin: 0;"><i class="fas fa-edit" style="color: #3b82f6;"></i> NIN Data Modification</h3>
                    <button onclick="hideServiceForm()" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-times"></i> Close</button>
                </div>
                <form id="modification-form" onsubmit="submitNINService(event, 'nin_modification', <?php echo $modification_price; ?>)">
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-id-badge"></i> NIN Number *</label>
                        <input type="text" name="nin" class="form-control" placeholder="Enter your 11-digit NIN" maxlength="11" pattern="\d{11}" required>
                    </div>
                    <div class="row" style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-user"></i> Current Name *</label>
                            <input type="text" name="current_name" class="form-control" placeholder="Current name on NIN" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-user-edit"></i> New Name *</label>
                            <input type="text" name="new_name" class="form-control" placeholder="Corrected name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-venus-mars"></i> Gender Correction</label>
                        <select name="gender" class="form-control">
                            <option value="">No change needed</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-sticky-note"></i> Other Details</label>
                        <textarea name="other_details" class="form-control" rows="2" placeholder="Describe any other changes needed"></textarea>
                    </div>
                    <div class="row" style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-phone"></i> Phone *</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Your phone" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>" required>
                        </div>
                    </div>
                    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 10px; padding: 1rem; margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="text-white"><strong>Service Fee:</strong></span>
                            <span style="color: #3b82f6; font-size: 1.5rem; font-weight: 700;">₦<?php echo number_format($modification_price); ?></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;"><i class="fas fa-credit-card"></i> Pay & Submit Request</button>
                </form>
            </div>
            
            <!-- Form 3: DOB Correction -->
            <div id="form-dob_correction" class="glass-card service-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 class="text-white" style="margin: 0;"><i class="fas fa-calendar-alt" style="color: #f59e0b;"></i> Date of Birth Correction</h3>
                    <button onclick="hideServiceForm()" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-times"></i> Close</button>
                </div>
                <form id="dob-form" onsubmit="submitNINService(event, 'nin_dob_correction', <?php echo $dob_price; ?>)">
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-id-badge"></i> NIN Number *</label>
                        <input type="text" name="nin" class="form-control" placeholder="Enter your 11-digit NIN" maxlength="11" pattern="\d{11}" required>
                    </div>
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" name="full_name" class="form-control" placeholder="As it appears on NIN" required>
                    </div>
                    <div class="row" style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-calendar-times"></i> Current DOB (Wrong) *</label>
                            <input type="date" name="current_dob" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-calendar-check"></i> Correct DOB *</label>
                            <input type="date" name="new_dob" class="form-control" required>
                        </div>
                    </div>
                    <div class="row" style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-phone"></i> Phone *</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Your phone" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="text-white"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>" required>
                        </div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; padding: 1rem; margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="text-white"><strong>Service Fee:</strong></span>
                            <span style="color: #f59e0b; font-size: 1.5rem; font-weight: 700;">₦<?php echo number_format($dob_price); ?></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;"><i class="fas fa-credit-card"></i> Pay & Submit Request</button>
                </form>
            </div>
        </div>
        
        <!-- Features -->
        <div class="section" style="margin-top: 2rem;">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-check-circle"></i> Why Choose Us?</h2>
            </div>
            <div class="cards-grid" style="max-width: 900px; margin: 0 auto;">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <div class="feature-content">
                        <h4 class="text-white">Fast Delivery</h4>
                        <p>Documents delivered within 24 hours via email</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-content">
                        <h4 class="text-white">100% Secure</h4>
                        <p>Your data is encrypted and securely handled</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <div class="feature-content">
                        <h4 class="text-white">24/7 Support</h4>
                        <p>Get help via WhatsApp or email anytime</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="zonatech-footer">
            <div class="footer-content">
                <div class="footer-logo"><img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="footer-logo-img"><span>ZonaTech NG</span></div>
                <div class="footer-social">
                    <a href="https://wa.me/234<?php echo substr(ZONATECH_WHATSAPP_NUMBER, 1); ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    <a href="mailto:<?php echo ZONATECH_SUPPORT_EMAIL; ?>"><i class="fas fa-envelope"></i></a>
                </div>
                <p class="footer-copyright">© <?php echo date('Y'); ?> ZonaTech NG. All rights reserved.</p>
            </div>
        </footer>
    </div>
</div>

<!-- Success Modal -->
<div id="nin-success-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #1a1a2e, #16213e); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; padding: 40px; max-width: 500px; text-align: center; margin: 20px;">
        <div style="width: 80px; height: 80px; background: rgba(34, 197, 94, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-check-circle" style="font-size: 40px; color: #22c55e;"></i>
        </div>
        <h2 style="color: #ffffff; margin-bottom: 15px;">Request Submitted!</h2>
        <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">
            Your request has been received. You will receive your document via email within <strong style="color: #22c55e;">24 hours</strong>.
        </p>
        <button onclick="closeSuccessModal()" class="btn btn-primary" style="padding: 12px 40px;"><i class="fas fa-check"></i> Got It!</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var hamburger = $('#hamburger-menu');
    var mobileNav = $('#mobile-nav');
    var mobileNavOverlay = $('#mobile-nav-overlay');
    var mobileNavClose = $('#mobile-nav-close');
    
    hamburger.on('click', function() {
        hamburger.toggleClass('active');
        mobileNav.toggleClass('active');
        mobileNavOverlay.toggleClass('active');
    });
    mobileNavClose.on('click', function() {
        hamburger.removeClass('active');
        mobileNav.removeClass('active');
        mobileNavOverlay.removeClass('active');
    });
    mobileNavOverlay.on('click', function() {
        hamburger.removeClass('active');
        mobileNav.removeClass('active');
        mobileNavOverlay.removeClass('active');
    });
});

function showServiceForm(serviceType) {
    document.querySelectorAll('.service-form').forEach(function(form) { form.style.display = 'none'; });
    document.getElementById('service-forms-container').style.display = 'block';
    document.getElementById('form-' + serviceType).style.display = 'block';
    document.getElementById('service-forms-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideServiceForm() {
    document.getElementById('service-forms-container').style.display = 'none';
}

function submitNINService(event, serviceType, amount) {
    event.preventDefault();
    var form = event.target;
    var formData = new FormData(form);
    var metaData = {};
    formData.forEach(function(value, key) { metaData[key] = value; });
    
    if (typeof ZonaTechPayment !== 'undefined' && typeof ZonaTechPayment.initiatePayment === 'function') {
        ZonaTechPayment.initiatePayment(serviceType, amount, metaData);
    } else {
        jQuery.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_initialize_payment',
                nonce: zonatech_ajax.nonce,
                payment_type: serviceType,
                amount: amount,
                meta_data: JSON.stringify(metaData)
            },
            success: function(response) {
                if (response.success) {
                    var handler = PaystackPop.setup({
                        key: response.data.public_key,
                        email: response.data.email,
                        amount: response.data.amount,
                        currency: response.data.currency,
                        ref: response.data.reference,
                        metadata: response.data.metadata,
                        callback: function(r) { verifyNINPayment(r.reference); }
                    });
                    handler.openIframe();
                } else {
                    alert(response.data.message || 'Payment initialization failed');
                }
            }
        });
    }
}

function verifyNINPayment(reference) {
    jQuery.ajax({
        url: zonatech_ajax.ajax_url,
        type: 'POST',
        data: { action: 'zonatech_verify_payment', nonce: zonatech_ajax.nonce, reference: reference },
        success: function(response) {
            if (response.success) { showSuccessModal(); hideServiceForm(); }
            else { alert(response.data.message || 'Payment verification failed'); }
        }
    });
}

function showSuccessModal() { document.getElementById('nin-success-modal').style.display = 'flex'; }
function closeSuccessModal() { document.getElementById('nin-success-modal').style.display = 'none'; }
</script>