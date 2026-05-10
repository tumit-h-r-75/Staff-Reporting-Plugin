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
                                    <button type="button" class="button button-small" onclick="viewReportDetails(<?php echo $report->id; ?>)">
                                        <?php _e('View', 'bassmah-staff-reports'); ?>
                                    </button>
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

<!-- Report Details Modal -->
<div id="bassmah-report-details-modal" class="bassmah-modal" style="display:none;">
    <div class="bassmah-modal-content bassmah-large-modal">
        <div class="bassmah-modal-header">
            <h3><?php _e('Report Details', 'bassmah-staff-reports'); ?></h3>
            <button class="bassmah-modal-close" type="button" onclick="hideReportDetails()">&times;</button>
        </div>
        <div class="bassmah-modal-body" id="bassmah-report-details-content">
            <div class="bassmah-loading"><?php _e('Loading...', 'bassmah-staff-reports'); ?></div>
        </div>
    </div>
</div>

<script>
function viewReportDetails(reportId) {
    var modal = document.getElementById('bassmah-report-details-modal');
    var content = document.getElementById('bassmah-report-details-content');
    content.innerHTML = '<div class="bassmah-loading"><?php _e('Loading...', 'bassmah-staff-reports'); ?></div>';
    modal.style.display = 'block';

    var params = new URLSearchParams({
        action: 'bassmah_admin_ajax',
        nonce: '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>',
        action_type: 'get_manager_report_details',
        report_id: reportId
    });

    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            content.innerHTML = data.data.html;
        } else {
            content.innerHTML = '<div class="bassmah-error">' + (data.data || '<?php _e('Unable to load report details.', 'bassmah-staff-reports'); ?>') + '</div>';
        }
    })
    .catch(function() {
        content.innerHTML = '<div class="bassmah-error"><?php _e('Error loading report details.', 'bassmah-staff-reports'); ?></div>';
    });
}

function hideReportDetails() {
    document.getElementById('bassmah-report-details-modal').style.display = 'none';
}

function approveReport(reportId) {
    if (!confirm('Are you sure you want to approve this report?')) return;

    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Approving...';

    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'bassmah_admin_ajax',
            nonce: '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>',
            action_type: 'update_report_status',
            report_id: reportId,
            status: 'approve',
            comment: ''
        }).toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('bassmah-report-details-modal').style.display = 'none';
            alert('Report approved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.data || 'Could not approve report.'));
            btn.disabled = false;
            btn.textContent = 'Approve';
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Approve';
    });
}

function rejectReport(reportId) {
    var reason = prompt('Please enter rejection reason (required):');
    if (!reason || reason.trim() === '') {
        alert('Rejection reason is required.');
        return;
    }

    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Rejecting...';

    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'bassmah_admin_ajax',
            nonce: '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>',
            action_type: 'update_report_status',
            report_id: reportId,
            status: 'reject',
            comment: reason.trim()
        }).toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('bassmah-report-details-modal').style.display = 'none';
            alert('Report rejected successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.data || 'Could not reject report.'));
            btn.disabled = false;
            btn.textContent = 'Reject';
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Reject';
    });
}
</script>

<style>
.bassmah-modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.bassmah-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    max-width: 800px;
    position: relative;
}

.bassmah-large-modal .bassmah-modal-content {
    max-width: 900px;
}

.bassmah-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.bassmah-modal-close {
    background: transparent;
    border: none;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
}

.bassmah-modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.bassmah-loading {
    padding: 20px;
    text-align: center;
}

.bassmah-error {
    color: #dc3545;
    padding: 15px;
}

</style>

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
