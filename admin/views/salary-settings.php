<?php
/**
 * Salary Settings View
 *
 * @package    Bassmah_Staff_Reports
 * @subpackage Admin/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
if (!in_array('administrator', $current_user->roles) && !in_array('bassmah_manager', $current_user->roles)) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
}

// Handle form submission
if ($_POST && isset($_POST['save_salary_settings'])) {
    check_admin_referer('bassmah_save_salary_settings');
    
    $user_id = intval($_POST['user_id']);
    $monthly_salary = floatval($_POST['monthly_salary']);
    $working_days = intval($_POST['working_days_per_month']);
    $currency = sanitize_text_field($_POST['currency']);
    $effective_from = sanitize_text_field($_POST['effective_from']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'staff_salary_settings';
    
    // Check if setting already exists for this user and date
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d AND effective_from = %s",
        $user_id, $effective_from
    ));
    
    if ($existing) {
        // Update existing
        $wpdb->update(
            $table_name,
            array(
                'monthly_salary' => $monthly_salary,
                'working_days_per_month' => $working_days,
                'currency' => $currency,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $existing->id),
            array('%f', '%d', '%s', '%s'),
            array('%d')
        );
    } else {
        // Insert new
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'monthly_salary' => $monthly_salary,
                'working_days_per_month' => $working_days,
                'currency' => $currency,
                'effective_from' => $effective_from,
                'created_by' => $current_user->ID,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%d', '%s', '%s', '%d', '%s')
        );
    }
    
    echo '<div class="notice notice-success"><p>' . __('Salary settings saved successfully!', 'bassmah-staff-reports') . '</p></div>';
}

// Get all users with staff roles
$staff_users = get_users(array(
    'role__in' => array('bassmah_staff', 'bassmah_manager', 'administrator'),
    'orderby' => 'display_name'
));

// Get existing salary settings
global $wpdb;
$salary_table = $wpdb->prefix . 'staff_salary_settings';
$salary_settings = $wpdb->get_results(
    "SELECT s.*, u.display_name, u.user_email 
     FROM $salary_table s 
     LEFT JOIN {$wpdb->users} u ON s.user_id = u.id 
     ORDER BY u.display_name, s.effective_from DESC"
);
?>

<div class="wrap">
    <h1><?php _e('Salary Settings', 'bassmah-staff-reports'); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#add-setting" class="nav-tab nav-tab-active"><?php _e('Add Salary Setting', 'bassmah-staff-reports'); ?></a>
        <a href="#manage-settings" class="nav-tab"><?php _e('Manage Settings', 'bassmah-staff-reports'); ?></a>
    </div>
    
    <div id="add-setting" class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('bassmah_save_salary_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="user_id"><?php _e('Staff Member', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <select name="user_id" id="user_id" required>
                            <option value=""><?php _e('Select Staff Member', 'bassmah-staff-reports'); ?></option>
                            <?php foreach ($staff_users as $user): ?>
                                <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="monthly_salary"><?php _e('Monthly Salary', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="monthly_salary" id="monthly_salary" 
                               step="0.01" min="0" required 
                               placeholder="5000.00">
                        <p class="description"><?php _e('Enter the monthly salary amount', 'bassmah-staff-reports'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="working_days_per_month"><?php _e('Working Days Per Month', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="working_days_per_month" id="working_days_per_month" 
                               min="1" max="31" value="22" required>
                        <p class="description"><?php _e('Number of working days in a typical month', 'bassmah-staff-reports'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="currency"><?php _e('Currency', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <select name="currency" id="currency" required>
                            <option value="CAD" selected>CAD - Canadian Dollar</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="effective_from"><?php _e('Effective From', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="date" name="effective_from" id="effective_from" 
                               value="<?php echo date('Y-m-01'); ?>" required>
                        <p class="description"><?php _e('Date when this salary setting becomes effective', 'bassmah-staff-reports'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Salary Setting', 'bassmah-staff-reports'), 'primary', 'save_salary_settings'); ?>
        </form>
    </div>
    
    <div id="manage-settings" class="tab-content" style="display: none;">
        <h2><?php _e('Current Salary Settings', 'bassmah-staff-reports'); ?></h2>
        
        <?php if (!empty($salary_settings)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Staff Member', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Monthly Salary', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Daily Rate', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Working Days', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Currency', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Effective From', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salary_settings as $setting): ?>
                        <tr>
                            <td><?php echo esc_html($setting->display_name); ?></td>
                            <td><?php echo number_format($setting->monthly_salary, 2); ?></td>
                            <td><?php echo number_format($setting->daily_rate, 2); ?></td>
                            <td><?php echo $setting->working_days_per_month; ?></td>
                            <td><?php echo $setting->currency; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($setting->effective_from)); ?></td>
                            <td>
                                <button type="button" class="button button-small" 
                                        onclick="editSalarySetting(<?php echo $setting->id; ?>)">
                                    <?php _e('Edit', 'bassmah-staff-reports'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete" 
                                        onclick="deleteSalarySetting(<?php echo $setting->id; ?>)">
                                    <?php _e('Delete', 'bassmah-staff-reports'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No salary settings found.', 'bassmah-staff-reports'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        var target = $(this).attr('href').substring(1);
        $('.tab-content').hide();
        $('#' + target).show();
    });
    
    // Calculate daily rate
    $('#monthly_salary, #working_days_per_month').on('input', function() {
        var monthly = parseFloat($('#monthly_salary').val()) || 0;
        var days = parseInt($('#working_days_per_month').val()) || 1;
        var daily = monthly / days;
        
        // You could display this to the user if needed
        console.log('Daily rate: ' + daily.toFixed(2));
    });
});

function editSalarySetting(id) {
    // Implementation for editing salary setting
    console.log('Edit salary setting:', id);
}

function deleteSalarySetting(id) {
    if (confirm('<?php _e('Are you sure you want to delete this salary setting?', 'bassmah-staff-reports'); ?>')) {
        // Implementation for deleting salary setting
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=delete_salary_setting&id=' + id + '&_wpnonce=<?php echo wp_create_nonce('delete_salary_setting'); ?>';
    }
}
</script>

<style>
.tab-content {
    margin-top: 20px;
}

.form-table th {
    width: 200px;
}

.button-link-delete {
    color: #dc3232;
}

.button-link-delete:hover {
    color: #bc0b0b;
}
</style>
