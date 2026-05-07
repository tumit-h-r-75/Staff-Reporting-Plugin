<?php
/**
 * Admin Dashboard View
 *
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

$report_class = new Bassmah_Staff_Reports_Report();
$salary_class = new Bassmah_Staff_Reports_Salary();

// Get current month statistics
$current_month = date('Y-m');
$statistics = $report_class->get_statistics(array(
    'date_from' => $current_month . '-01',
    'date_to' => $current_month . '-31'
));

// Get staff users
$staff_users = Bassmah_Staff_Reports_Roles::get_staff_users();

// Get recent reports
$recent_reports = $report_class->get_reports(array(
    'limit' => 10,
    'orderby' => 'submission_time',
    'order' => 'DESC'
));

// Get missing reports for today
$today = current_time('Y-m-d');
$missing_reports = array();

foreach ($staff_users as $staff) {
    $today_report = $report_class->get_report_by_date($staff->ID, $today);
    if (!$today_report) {
        $missing_reports[] = $staff;
    }
}
?>

<div class="wrap bassmah-admin">
    <h1><?php _e('Bassmah Staff Reports Dashboard', 'bassmah-staff-reports'); ?></h1>
    
    <div class="bassmah-dashboard">
        <div class="bassmah-stat-card">
            <h3><?php _e('Total Reports This Month', 'bassmah-staff-reports'); ?></h3>
            <p class="bassmah-stat-number bassmah-stat-total-reports"><?php echo isset($statistics['total_reports']) ? $statistics['total_reports'] : 0; ?></p>
            <p class="bassmah-stat-label"><?php echo date_i18n('F Y'); ?></p>
        </div>
        
        <div class="bassmah-stat-card">
            <h3><?php _e('Active Staff', 'bassmah-staff-reports'); ?></h3>
            <p class="bassmah-stat-number bassmah-stat-active-staff"><?php echo is_array($staff_users) ? count($staff_users) : 0; ?></p>
            <p class="bassmah-stat-label"><?php _e('Total staff members', 'bassmah-staff-reports'); ?></p>
        </div>
        
        <div class="bassmah-stat-card">
            <h3><?php _e('Submitted Today', 'bassmah-staff-reports'); ?></h3>
            <p class="bassmah-stat-number bassmah-stat-submitted-today"><?php echo isset($statistics['submitted_reports']) ? $statistics['submitted_reports'] : 0; ?></p>
            <p class="bassmah-stat-label"><?php _e('Reports submitted today', 'bassmah-staff-reports'); ?></p>
        </div>
        
        <div class="bassmah-stat-card">
            <h3><?php _e('Missing Today', 'bassmah-staff-reports'); ?></h3>
            <p class="bassmah-stat-number bassmah-stat-missing-today"><?php echo is_array($missing_reports) ? count($missing_reports) : 0; ?></p>
            <p class="bassmah-stat-label"><?php _e('Staff who haven\'t submitted', 'bassmah-staff-reports'); ?></p>
        </div>
    </div>

    <?php if (!empty($missing_reports)): ?>
    <div class="bassmah-notice bassmah-notice-warning">
        <h3><?php _e('Missing Reports Today', 'bassmah-staff-reports'); ?></h3>
        <p><?php _e('The following staff members have not submitted their reports today:', 'bassmah-staff-reports'); ?></p>
        <ul>
            <?php foreach ($missing_reports as $staff): ?>
                <li><?php echo esc_html($staff->display_name); ?> (<?php echo esc_html($staff->user_email); ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="bassmah-dashboard-sections">
        <div class="bassmah-section">
            <h2><?php _e('Recent Reports', 'bassmah-staff-reports'); ?></h2>
            
            <?php if (!empty($recent_reports)): ?>
                <table class="wp-list-table widefat fixed striped bassmah-table">
                    <thead>
                        <tr>
                            <th><?php _e('Staff Name', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Date', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_reports as $report): ?>
                            <?php 
                            $user = get_userdata($report->user_id);
                            $tasks = json_decode($report->tasks_json, true);
                            $task_count = count($tasks);
                            ?>
                            <tr>
                                <td><?php echo esc_html($user ? $user->display_name : 'Unknown'); ?></td>
                                <td><?php echo esc_html($report->report_date); ?></td>
                                <td>
                                    <span class="bassmah-status-<?php echo esc_attr($report->status); ?>">
                                        <?php echo ucfirst(esc_html($report->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo $task_count; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=bassmah-all-reports&view=report&id=' . $report->id); ?>" 
                                       class="button button-small">
                                        <?php _e('View', 'bassmah-staff-reports'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=bassmah-all-reports'); ?>" class="button">
                        <?php _e('View All Reports', 'bassmah-staff-reports'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p><?php _e('No reports found.', 'bassmah-staff-reports'); ?></p>
            <?php endif; ?>
        </div>

        <div class="bassmah-section">
            <h2><?php _e('Quick Actions', 'bassmah-staff-reports'); ?></h2>
            
            <div class="bassmah-quick-actions">
                <div class="bassmah-action-card">
                    <h3><?php _e('Manage Staff', 'bassmah-staff-reports'); ?></h3>
                    <p><?php _e('Add or remove staff members and manage their roles.', 'bassmah-staff-reports'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=bassmah-staff'); ?>" class="button button-primary">
                        <?php _e('Manage Staff', 'bassmah-staff-reports'); ?>
                    </a>
                </div>
                
                <div class="bassmah-action-card">
                    <h3><?php _e('Salary Settings', 'bassmah-staff-reports'); ?></h3>
                    <p><?php _e('Configure salary settings for staff members.', 'bassmah-staff-reports'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=bassmah-salary-settings'); ?>" class="button button-primary">
                        <?php _e('Configure Salaries', 'bassmah-staff-reports'); ?>
                    </a>
                </div>
                
                <div class="bassmah-action-card">
                    <h3><?php _e('Working Days', 'bassmah-staff-reports'); ?></h3>
                    <p><?php _e('Manage working days and holidays.', 'bassmah-staff-reports'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=bassmah-working-days'); ?>" class="button button-primary">
                        <?php _e('Manage Days', 'bassmah-staff-reports'); ?>
                    </a>
                </div>
                
                <div class="bassmah-action-card">
                    <h3><?php _e('Export Reports', 'bassmah-staff-reports'); ?></h3>
                    <p><?php _e('Export reports to CSV or Excel.', 'bassmah-staff-reports'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=bassmah-all-reports'); ?>" class="button button-secondary">
                        <?php _e('Export Data', 'bassmah-staff-reports'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bassmah-dashboard-sections {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-top: 30px;
}

.bassmah-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
}

.bassmah-section h2 {
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.bassmah-quick-actions {
    display: grid;
    gap: 15px;
}

.bassmah-action-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    background: #fafafa;
}

.bassmah-action-card h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #333;
}

.bassmah-action-card p {
    margin: 0 0 12px 0;
    font-size: 12px;
    color: #666;
}

.bassmah-status-submitted {
    background: #e7f3ff;
    color: #0073aa;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.bassmah-status-approved {
    background: #edfaef;
    color: #00a32a;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.bassmah-status-rejected {
    background: #fcf0f1;
    color: #d63638;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

@media screen and (max-width: 1024px) {
    .bassmah-dashboard-sections {
        grid-template-columns: 1fr;
    }
}
</style>
