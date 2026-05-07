<?php
/**
 * Role-based Dashboard View
 * 
 * This file handles different dashboard views based on user roles:
 * - Administrator/Manager: WordPress Admin Dashboard
 * - Staff: Staff Dashboard UI
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Fix: Add session and cookie validation
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
}

// Clear any problematic sessions
wp_cache_flush();

// Get current user with session validation
$current_user = wp_get_current_user();

// Check if user is actually logged in properly
if (!is_user_logged_in() || !$current_user->exists()) {
    echo '<div class="bassmah-login-required">';
    echo '<h3>' . __('Login Required', 'bassmah-staff-reports') . '</h3>';
    echo '<p>' . __('Please log in to access your dashboard.', 'bassmah-staff-reports') . '</p>';
    echo '<p><small>' . __('If you just logged in, please refresh the page.', 'bassmah-staff-reports') . '</small></p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="button button-primary">' . __('Login', 'bassmah-staff-reports') . '</a>';
    echo '</div>';
    return;
}

// Force session regeneration if needed
$last_activity = get_user_meta($current_user->ID, 'last_activity', true);
if ($last_activity && strtotime($last_activity) < strtotime('-30 minutes')) {
    // Regenerate session
    wp_set_current_user($current_user);
    update_user_meta($current_user->ID, 'last_activity', current_time('mysql'));
}

// Check user role and show appropriate dashboard
if (current_user_can('administrator') || current_user_can('bassmah_manager')) {
    // Admin/Manager Dashboard - Redirect to WordPress Admin
    ?>
    <div class="bassmah-admin-dashboard">
        <div class="bassmah-welcome-section">
            <h2><?php printf(__('Welcome, %s!', 'bassmah-staff-reports'), esc_html($current_user->display_name)); ?></h2>
            <p class="bassmah-role-info">
                <?php 
                if (current_user_can('administrator')) {
                    _e('You are logged in as an Administrator. You have full access to all features.', 'bassmah-staff-reports');
                } else {
                    _e('You are logged in as a Manager. You can view and manage staff reports.', 'bassmah-staff-reports');
                }
                ?>
            </p>
        </div>

        <div class="bassmah-quick-actions">
            <h3><?php _e('Quick Actions', 'bassmah-staff-reports'); ?></h3>
            <div class="bassmah-action-grid">
                <a href="<?php echo admin_url('admin.php?page=bassmah-reports'); ?>" class="bassmah-action-card">
                    <div class="bassmah-action-icon">📊</div>
                    <h4><?php _e('View Reports', 'bassmah-staff-reports'); ?></h4>
                    <p><?php _e('View and manage all staff reports', 'bassmah-staff-reports'); ?></p>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=bassmah-salary-settings'); ?>" class="bassmah-action-card">
                    <div class="bassmah-action-icon">💰</div>
                    <h4><?php _e('Salary Settings', 'bassmah-staff-reports'); ?></h4>
                    <p><?php _e('Manage staff salary configurations', 'bassmah-staff-reports'); ?></p>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=bassmah-working-days'); ?>" class="bassmah-action-card">
                    <div class="bassmah-action-icon">📅</div>
                    <h4><?php _e('Working Days', 'bassmah-staff-reports'); ?></h4>
                    <p><?php _e('Manage working days and holidays', 'bassmah-staff-reports'); ?></p>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=bassmah-staff-management'); ?>" class="bassmah-action-card">
                    <div class="bassmah-action-icon">👥</div>
                    <h4><?php _e('Staff Management', 'bassmah-staff-reports'); ?></h4>
                    <p><?php _e('Manage staff roles and permissions', 'bassmah-staff-reports'); ?></p>
                </a>
            </div>
        </div>

        <div class="bassmah-admin-link">
            <a href="<?php echo admin_url(); ?>" class="button button-primary">
                <?php _e('Go to WordPress Admin Dashboard', 'bassmah-staff-reports'); ?>
            </a>
        </div>
    </div>

    <style>
    .bassmah-admin-dashboard {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .bassmah-welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .bassmah-welcome-section h2 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }
    
    .bassmah-role-info {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .bassmah-quick-actions h3 {
        margin-bottom: 20px;
        color: #333;
    }
    
    .bassmah-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .bassmah-action-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 25px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
        display: block;
        text-align: center;
    }
    
    .bassmah-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    
    .bassmah-action-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .bassmah-action-card h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 18px;
    }
    
    .bassmah-action-card p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .bassmah-admin-link {
        text-align: center;
        margin-top: 20px;
    }
    </style>
    <?php

} elseif (current_user_can('bassmah_staff')) {
    // Staff Dashboard - Show staff interface
    // Include the existing staff dashboard
    include_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/staff-dashboard.php';

} else {
    // User has no appropriate role
    ?>
    <div class="bassmah-access-denied">
        <h3><?php _e('Access Denied', 'bassmah-staff-reports'); ?></h3>
        <p><?php _e('You do not have the required permissions to access the Staff Reporting System.', 'bassmah-staff-reports'); ?></p>
        <p><?php _e('Please contact your administrator to get the appropriate role assigned.', 'bassmah-staff-reports'); ?></p>
        <a href="<?php echo home_url(); ?>" class="button"><?php _e('Go to Homepage', 'bassmah-staff-reports'); ?></a>
    </div>

    <style>
    .bassmah-access-denied {
        text-align: center;
        padding: 50px 20px;
        max-width: 500px;
        margin: 50px auto;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    
    .bassmah-access-denied h3 {
        color: #dc3545;
        margin-bottom: 20px;
    }
    </style>
    <?php
}
?>
