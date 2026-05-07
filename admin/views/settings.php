<?php
/**
 * Settings View
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get current settings
$task_categories = get_option('bassmah_task_categories', array());
$task_statuses = get_option('bassmah_task_statuses', array());
$email_notifications = get_option('bassmah_email_notifications', 1);
$default_currency = get_option('bassmah_default_currency', 'CAD');
$default_working_days = get_option('bassmah_default_working_days', 22);
$reminder_time = get_option('bassmah_reminder_time', '09:00');
?>

<div class="wrap bassmah-admin">
    <h1><?php _e('Bassmah Staff Reports Settings', 'bassmah-staff-reports'); ?></h1>
    
    <div class="bassmah-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'bassmah-staff-reports'); ?></a>
            <a href="#email"><?php _e('Email', 'bassmah-staff-reports'); ?></a>
        </nav>
    </div>

    <div class="tab-content">
        <!-- General Settings Tab -->
        <div id="general" class="tab-pane active">
            <form method="post" action="options.php">
                <?php wp_nonce_field('bassmah_settings_update', 'bassmah_settings_nonce'); ?>
                
                <table class="form-table bassmah-settings-table">
                    <tr>
                        <th scope="row"><?php _e('Task Categories', 'bassmah-staff-reports'); ?></th>
                        <td>
                            <textarea name="bassmah_task_categories" rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $task_categories)); ?></textarea>
                            <p class="description"><?php _e('Enter one category per line. These will appear as options in the task dropdown.', 'bassmah-staff-reports'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Task Statuses', 'bassmah-staff-reports'); ?></th>
                        <td>
                            <textarea name="bassmah_task_statuses" rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $task_statuses)); ?></textarea>
                            <p class="description"><?php _e('Enter one status per line. These will appear as options in the task status dropdown.', 'bassmah-staff-reports'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Currency', 'bassmah-staff-reports'); ?></th>
                        <td>
                            <select name="bassmah_default_currency" class="regular-text">
                                <option value="CAD" <?php selected($default_currency, 'CAD') ? 'selected' : ''; ?>><?php _e('CAD - Canadian Dollar', 'bassmah-staff-reports'); ?></option>
                                <option value="USD" <?php selected($default_currency, 'USD') ? 'selected' : ''; ?>><?php _e('USD - US Dollar', 'bassmah-staff-reports'); ?></option>
                                <option value="EUR" <?php selected($default_currency, 'EUR') ? 'selected' : ''; ?>><?php _e('EUR - Euro', 'bassmah-staff-reports'); ?></option>
                                <option value="GBP" <?php selected($default_currency, 'GBP') ? 'selected' : ''; ?>><?php _e('GBP - British Pound', 'bassmah-staff-reports'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select the default currency for salary calculations.', 'bassmah-staff-reports'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Working Days', 'bassmah-staff-reports'); ?></th>
                        <td>
                            <input type="number" name="bassmah_default_working_days" value="<?php echo esc_attr($default_working_days); ?>" class="regular-text" min="1" max="31">
                            <p class="description"><?php _e('Default number of working days per month for salary calculations.', 'bassmah-staff-reports'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'bassmah-staff-reports'); ?>">
                </p>
            </form>
        </div>

        <!-- Email Settings Tab -->
        <div id="email" class="tab-pane">
            <form method="post" action="options.php">
                <?php wp_nonce_field('bassmah_settings_update', 'bassmah_settings_nonce'); ?>
                
                <table class="form-table bassmah-settings-table">
                    <tr>
                        <th scope="row">
                            <label for="bassmah_email_notifications">
                                <?php _e('Enable Email Notifications', 'bassmah-staff-reports'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="bassmah_email_notifications" value="1" <?php checked($email_notifications); ?> class="regular-checkbox">
                            <p class="description"><?php _e('Enable email notifications for report submissions and updates.', 'bassmah-staff-reports'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bassmah_reminder_time">
                                <?php _e('Daily Reminder Time', 'bassmah-staff-reports'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="time" name="bassmah_reminder_time" value="<?php echo esc_attr($reminder_time); ?>" class="regular-text">
                            <p class="description"><?php _e('Time to send daily report reminders to staff members.', 'bassmah-staff-reports'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'bassmah-staff-reports'); ?>">
                </p>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-pane').removeClass('active');
        
        var target = $(this).attr('href').substring(1);
        $(this).addClass('nav-tab-active');
        $(target).addClass('active');
    });
    
    // Load active tab from URL hash
    var hash = window.location.hash.substring(1);
    if (hash && $('#' + hash).length) {
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-pane').removeClass('active');
        $('.nav-tab[href="#' + hash + '"]').addClass('nav-tab-active');
        $('#' + hash).addClass('active');
    }
});
</script>

<style>
.bassmah-settings-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.bassmah-settings-table th {
    text-align: left;
    padding: 10px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
    width: 200px;
}

.bassmah-settings-table td {
    padding: 10px;
    vertical-align: top;
}

.large-text {
    width: 100%;
    min-height: 100px;
    font-family: monospace;
}

.regular-text {
    width: 100%;
}

.regular-checkbox {
    margin-right: 10px;
}

.submit {
    text-align: center;
    margin-top: 20px;
}

.description {
    font-style: italic;
    color: #666;
    margin-top: 5px;
    font-size: 12px;
}
</style>
