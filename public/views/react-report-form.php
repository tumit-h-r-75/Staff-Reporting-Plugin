<?php
/**
 * React Report Form Test View
 * This file demonstrates the new React-style report form integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
?>

<div class="wrap">
    <h1>Daily Report Form (React Components)</h1>
    
    <p>This page demonstrates the new React-style report form with modern UI components.</p>
    
    <div data-bsr-report-form 
         data-user-id="<?php echo esc_attr($user->ID); ?>" 
         data-user-name="<?php echo esc_attr($user->display_name); ?>" 
         data-user-role="<?php echo esc_attr($this->get_user_role()); ?>" 
         data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
         data-api-url="<?php echo esc_attr(rest_url('bsr/v1/')); ?>">
    </div>
    
    <hr style="margin: 2rem 0;">
    
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 1rem;">
        <h4 style="color: #166534; margin-top: 0;">New Features:</h4>
        <ul style="color: #166534;">
            <li>✅ Modern React-style UI components</li>
            <li>✅ Auto-save functionality</li>
            <li>✅ Dynamic task management</li>
            <li>✅ Beautiful status indicators</li>
            <li>✅ Responsive design</li>
            <li>✅ WordPress REST API integration</li>
            <li>✅ Real-time validation</li>
        </ul>
    </div>
    
    <div style="background: #fefce8; border: 1px solid #fde047; border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">
        <h4 style="color: #854d0e; margin-top: 0;">Integration Benefits:</h4>
        <ul style="color: #854d0e;">
            <li>🔧 Maintains existing WordPress functionality</li>
            <li>🔄 Backward compatible with old forms</li>
            <li>📱 Mobile-responsive design</li>
            <li>⚡ Better user experience</li>
            <li>🛡️ Secure WordPress nonce integration</li>
            <li>🎨 Modern, professional appearance</li>
        </ul>
    </div>
</div>
