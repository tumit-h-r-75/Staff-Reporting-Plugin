<?php
/**
 * React Dashboard Test View
 * This file demonstrates the new React-style components integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
?>

<div class="wrap">
    <h1>Staff Dashboard (React Components)</h1>
    
    <p>This page demonstrates the new React-style UI components integrated with WordPress.</p>
    
    <div data-bsr-dashboard 
         data-user-id="<?php echo esc_attr($user->ID); ?>" 
         data-user-name="<?php echo esc_attr($user->display_name); ?>" 
         data-user-role="<?php echo esc_attr($this->get_user_role()); ?>" 
         data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
         data-report-form-url="<?php echo esc_attr(home_url('/report-form')); ?>"
         data-my-reports-url="<?php echo esc_attr(home_url('/my-reports')); ?>"
         data-salary-url="<?php echo esc_attr(home_url('/salary')); ?>">
    </div>
    
    <hr style="margin: 2rem 0;">
    
    <h2>Component Test Examples</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <!-- Button Examples -->
        <div>
            <h3>Button Components</h3>
            <button class="bsr-button" data-variant="primary">Primary Button</button>
            <button class="bsr-button" data-variant="secondary">Secondary Button</button>
            <button class="bsr-button" data-variant="outline">Outline Button</button>
            <button class="bsr-button" data-variant="ghost">Ghost Button</button>
        </div>
        
        <!-- Card Examples -->
        <div>
            <h3>Card Components</h3>
            <div class="bsr-card" data-hover="true">
                <div class="bsr-card__header">
                    <h3 class="bsr-card__title">Test Card</h3>
                    <p class="bsr-card__description">This is a test card component</p>
                </div>
                <div class="bsr-card__content">
                    <p>Card content goes here.</p>
                </div>
            </div>
        </div>
        
        <!-- Form Examples -->
        <div>
            <h3>Form Components</h3>
            <form class="bsr-form" data-bsr-form>
                <div class="bsr-form__group">
                    <label class="bsr-label">Test Input</label>
                    <input class="bsr-input" type="text" placeholder="Enter text here...">
                </div>
                <div class="bsr-form__group">
                    <label class="bsr-label">Test Textarea</label>
                    <textarea class="bsr-input bsr-textarea" placeholder="Enter longer text..."></textarea>
                </div>
            </form>
        </div>
    </div>
    
    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 0.5rem; padding: 1rem;">
        <h4>Integration Notes:</h4>
        <ul>
            <li>✅ React-style components are loaded via WordPress enqueue system</li>
            <li>✅ Components auto-initialize with data attributes</li>
            <li>✅ WordPress data (user info, nonces) passed to JavaScript</li>
            <li>✅ Components work with existing WordPress functionality</li>
            <li>✅ Backward compatibility maintained</li>
        </ul>
    </div>
</div>
