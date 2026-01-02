<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-admin-settings"></span> ZonaTech NG Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('zonatech_settings'); ?>
        
        <div class="zonatech-admin-section">
            <h2>Paystack Integration</h2>
            <p class="description">
                Get your API keys from <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank">Paystack Dashboard</a>.
                Use Test keys for testing and Live keys for production.
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zonatech_paystack_public_key">Public Key</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="zonatech_paystack_public_key" 
                               name="zonatech_paystack_public_key" 
                               value="<?php echo esc_attr(get_option('zonatech_paystack_public_key')); ?>" 
                               class="regular-text"
                               placeholder="pk_test_xxxxx or pk_live_xxxxx">
                        <p class="description">Your Paystack public key (starts with pk_)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_paystack_secret_key">Secret Key</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="zonatech_paystack_secret_key" 
                               name="zonatech_paystack_secret_key" 
                               value="<?php echo esc_attr(get_option('zonatech_paystack_secret_key')); ?>" 
                               class="regular-text"
                               placeholder="sk_test_xxxxx or sk_live_xxxxx">
                        <p class="description">Your Paystack secret key (starts with sk_). Keep this secret!</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="zonatech-admin-section">
            <h2>Pricing</h2>
            <table class="form-table">
                <tr>
                    <th>Subject Access</th>
                    <td>₦<?php echo number_format(ZONATECH_SUBJECT_PRICE); ?> per subject</td>
                </tr>
                <tr>
                    <th>NIN Slip</th>
                    <td>₦<?php echo number_format(ZONATECH_NIN_SLIP_PRICE); ?></td>
                </tr>
                <tr>
                    <th>Scratch Cards</th>
                    <td>₦<?php echo number_format(ZONATECH_SCRATCH_CARD_PRICE); ?></td>
                </tr>
            </table>
            <p class="description">To change pricing, edit the constants in the main plugin file.</p>
        </div>
        
        <div class="zonatech-admin-section">
            <h2>Support Information</h2>
            <table class="form-table">
                <tr>
                    <th>WhatsApp</th>
                    <td><?php echo esc_html(ZONATECH_WHATSAPP_NUMBER); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo esc_html(ZONATECH_SUPPORT_EMAIL); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="zonatech-admin-section">
            <h2>Integration Setup Guide</h2>
            
            <h3>Paystack Setup</h3>
            <ol>
                <li>Create an account at <a href="https://paystack.com" target="_blank">paystack.com</a></li>
                <li>Go to Settings → API Keys & Webhooks</li>
                <li>Copy your Public and Secret keys (use Test keys for testing)</li>
                <li>Paste them in the fields above</li>
                <li>Set up webhook URL: <code><?php echo home_url('/wp-admin/admin-ajax.php?action=zonatech_paystack_webhook'); ?></code></li>
            </ol>
            
            <h3>NIN Verification (Advanced)</h3>
            <p>For real NIN verification, you'll need to integrate with:</p>
            <ul>
                <li><a href="https://nimc.gov.ng" target="_blank">NIMC API</a> (Official)</li>
                <li><a href="https://dojah.io" target="_blank">Dojah</a> (Third-party)</li>
                <li><a href="https://prembly.com" target="_blank">Prembly (Identitypass)</a></li>
                <li><a href="https://youverify.co" target="_blank">Youverify</a></li>
            </ul>
            
            <h3>Scratch Card Integration</h3>
            <p>For real scratch cards, partner with authorized resellers or integrate with:</p>
            <ul>
                <li><a href="https://vtpass.com" target="_blank">VTpass</a></li>
                <li><a href="https://baxi.ng" target="_blank">Baxi</a></li>
            </ul>
        </div>
        
        <?php submit_button('Save Settings'); ?>
    </form>
</div>