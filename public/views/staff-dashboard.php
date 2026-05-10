<?php
/**
 * Staff Dashboard View
 *
 * @package    Bassmah_Staff_Reports
 * @subpackage Public/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required classes
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary-calculator.php';

$current_user = wp_get_current_user();
$report_class = new Bassmah_Staff_Reports_Report();
$salary_calculator = new Bassmah_Staff_Reports_Salary_Calculator();

// Get today's report status
$today_report = $report_class->get_today_report();

// Get dashboard statistics with salary calculations
$dashboard_stats = $salary_calculator->get_dashboard_stats($current_user->ID);

// Get current month salary details
$current_month_salary = $salary_calculator->calculate_monthly_salary($current_user->ID);

// Get salary history
$salary_history = $salary_calculator->get_salary_history($current_user->ID, 3);

// Get recent reports
$recent_reports = $report_class->get_my_reports(array(
    'limit' => 5,
    'orderby' => 'report_date',
    'order' => 'DESC'
));

$current_month_start = date('Y-m-01', current_time('timestamp'));
$current_month_end = date('Y-m-t', current_time('timestamp'));
$monthly_calendar_reports = $report_class->get_my_reports(array(
    'date_from' => $current_month_start,
    'date_to' => $current_month_end,
    'orderby' => 'report_date',
    'order' => 'ASC',
    'limit' => 31,
));
?>

<div class="bassmah-staff-dashboard">
    <div class="bassmah-welcome-section">
        <h2><?php printf(__('Welcome, %s!', 'bassmah-staff-reports'), esc_html($current_user->display_name)); ?></h2>
        <p class="bassmah-current-date"><?php echo date_i18n('l, F j, Y'); ?></p>
    </div>

    <div class="bassmah-dashboard-grid">
        <!-- Today's Status Card -->
        <div class="bassmah-card bassmah-today-status">
            <h3><?php _e('Today\'s Report Status', 'bassmah-staff-reports'); ?></h3>
            <?php if ($today_report): ?>
                <div class="bassmah-status-completed">
                    <span class="bassmah-status-icon">✓</span>
                    <p><?php _e('Report Submitted', 'bassmah-staff-reports'); ?></p>
                    <small><?php echo date_i18n('g:i A', strtotime($today_report->submission_time)); ?></small>
                </div>
            <?php else: ?>
                <div class="bassmah-status-pending">
                    <span class="bassmah-status-icon">!</span>
                    <p><?php _e('Report Not Submitted', 'bassmah-staff-reports'); ?></p>
                    <button class="button button-primary" onclick="showReportForm()">
                        <?php _e('Submit Report', 'bassmah-staff-reports'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Card -->
        <div class="bassmah-card bassmah-stats">
            <h3><?php _e('This Month', 'bassmah-staff-reports'); ?></h3>
            <div class="bassmah-stats-grid">
                <div class="bassmah-stat-item">
                    <span class="bassmah-stat-number"><?php echo $dashboard_stats['monthly_reports'] ?? 0; ?></span>
                    <span class="bassmah-stat-label"><?php _e('Reports', 'bassmah-staff-reports'); ?></span>
                </div>
                <div class="bassmah-stat-item">
                    <span class="bassmah-stat-number"><?php echo $dashboard_stats['working_days'] ?? 0; ?></span>
                    <span class="bassmah-stat-label"><?php _e('Working Days', 'bassmah-staff-reports'); ?></span>
                </div>
                <div class="bassmah-stat-item">
                    <span class="bassmah-stat-number"><?php echo $dashboard_stats['attendance_rate'] ?? 0; ?>%</span>
                    <span class="bassmah-stat-label"><?php _e('Attendance', 'bassmah-staff-reports'); ?></span>
                </div>
            </div>
        </div>

        <!-- Salary Info Card -->
        <div class="bassmah-card bassmah-salary-info">
            <h3><?php _e('Salary Information', 'bassmah-staff-reports'); ?></h3>
            <div class="bassmah-salary-details">
                <div class="bassmah-salary-row">
                    <span><?php _e('Monthly Salary:', 'bassmah-staff-reports'); ?></span>
                    <strong><?php echo number_format($current_month_salary['monthly_salary'], 2); ?> <?php echo $current_month_salary['currency']; ?></strong>
                </div>
                <div class="bassmah-salary-row">
                    <span><?php _e('Daily Rate:', 'bassmah-staff-reports'); ?></span>
                    <strong><?php echo number_format($current_month_salary['daily_rate'], 2); ?> <?php echo $current_month_salary['currency']; ?></strong>
                </div>
                <div class="bassmah-salary-row">
                    <span><?php _e('Working Days:', 'bassmah-staff-reports'); ?></span>
                    <strong><?php echo $current_month_salary['working_days']; ?></strong>
                </div>
                <div class="bassmah-salary-row">
                    <span><?php _e('Present Days:', 'bassmah-staff-reports'); ?></span>
                    <strong class="bassmah-present"><?php echo $current_month_salary['present_days']; ?></strong>
                </div>
                <div class="bassmah-salary-row">
                    <span><?php _e('Absent Days:', 'bassmah-staff-reports'); ?></span>
                    <strong class="bassmah-absent"><?php echo $current_month_salary['absent_days']; ?></strong>
                </div>
                <div class="bassmah-salary-row bassmah-deduction">
                    <span><?php _e('Total Deduction:', 'bassmah-staff-reports'); ?></span>
                    <strong class="bassmah-deduction-amount">-<?php echo number_format($current_month_salary['total_deduction'], 2); ?> <?php echo $current_month_salary['currency']; ?></strong>
                </div>
                <div class="bassmah-salary-row bassmah-net-salary">
                    <span><?php _e('Net Salary:', 'bassmah-staff-reports'); ?></span>
                    <strong class="bassmah-net-amount"><?php echo number_format($current_month_salary['net_salary'], 2); ?> <?php echo $current_month_salary['currency']; ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports Section -->
    <div class="bassmah-card bassmah-recent-reports">
        <div class="bassmah-card-header">
            <h3><?php _e('My Recent Reports', 'bassmah-staff-reports'); ?></h3>
            <button class="button" onclick="viewAllReports()">
                <?php _e('View All', 'bassmah-staff-reports'); ?>
            </button>
        </div>
        
        <?php if (!empty($recent_reports)): ?>
            <div class="bassmah-reports-list">
                <?php foreach ($recent_reports as $report): ?>
                    <div class="bassmah-report-item">
                        <div class="bassmah-report-date">
                            <?php echo date_i18n('M j, Y', strtotime($report->report_date)); ?>
                        </div>
                        <div class="bassmah-report-status">
                            <span class="bassmah-status-badge bassmah-status-<?php echo $report->status; ?>">
                                <?php 
                                switch($report->status) {
                                    case 'submitted':
                                        _e('Submitted', 'bassmah-staff-reports');
                                        break;
                                    case 'approved':
                                        _e('Approved', 'bassmah-staff-reports');
                                        break;
                                    case 'rejected':
                                        _e('Rejected', 'bassmah-staff-reports');
                                        break;
                                    default:
                                        echo esc_html($report->status);
                                }
                                ?>
                            </span>
                        </div>
                        <div class="bassmah-report-tasks">
                            <?php echo count($report->tasks ?? array()); ?> <?php _e('tasks', 'bassmah-staff-reports'); ?>
                        </div>
                        <div class="bassmah-report-actions">
                            <button class="button button-small" onclick="viewReport(<?php echo $report->id; ?>)">
                                <?php _e('View', 'bassmah-staff-reports'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="bassmah-no-reports"><?php _e('No reports found.', 'bassmah-staff-reports'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Salary History Section -->
    <div class="bassmah-card bassmah-salary-history">
        <div class="bassmah-card-header">
            <h3><?php _e('Salary History', 'bassmah-staff-reports'); ?></h3>
            <button class="button button-small" onclick="exportSalaryHistory()">
                <?php _e('Export History', 'bassmah-staff-reports'); ?>
            </button>
        </div>
        
        <?php if (!empty($salary_history)): ?>
            <div class="bassmah-history-table">
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Month', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Gross Salary', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Deductions', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Net Salary', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Attendance', 'bassmah-staff-reports'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salary_history as $history): ?>
                            <?php $calc = $history['calculation']; ?>
                            <tr>
                                <td><?php echo $history['month_display']; ?></td>
                                <td><?php echo number_format($calc['monthly_salary'], 2); ?> <?php echo $calc['currency']; ?></td>
                                <td class="bassmah-deduction-cell">
                                    <?php if ($calc['total_deduction'] > 0): ?>
                                        <span class="bassmah-deduction-badge">-<?php echo number_format($calc['total_deduction'], 2); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="bassmah-net-salary-cell">
                                    <strong><?php echo number_format($calc['net_salary'], 2); ?> <?php echo $calc['currency']; ?></strong>
                                </td>
                                <td>
                                    <div class="bassmah-attendance-bar">
                                        <div class="bassmah-attendance-fill" style="width: <?php echo min(100, round(($calc['present_days'] / max(1, $calc['working_days'])) * 100)); ?>%"></div>
                                        <span><?php echo $calc['present_days']; ?>/<?php echo $calc['working_days']; ?> days</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="bassmah-no-history"><?php _e('No salary history available.', 'bassmah-staff-reports'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bassmah-quick-actions">
        <h3><?php _e('Quick Actions', 'bassmah-staff-reports'); ?></h3>
        <div class="bassmah-actions-grid">
            <?php if (!$today_report): ?>
                <button class="bassmah-action-btn bassmah-action-primary" onclick="showReportForm()">
                    <span class="bassmah-action-icon">📝</span>
                    <span><?php _e('Submit Today\'s Report', 'bassmah-staff-reports'); ?></span>
                </button>
            <?php endif; ?>
            
            <button class="bassmah-action-btn" onclick="viewAllReports()">
                <span class="bassmah-action-icon">📊</span>
                <span><?php _e('View All Reports', 'bassmah-staff-reports'); ?></span>
            </button>
            
            <button class="bassmah-action-btn" onclick="exportReports()">
                <span class="bassmah-action-icon">📥</span>
                <span><?php _e('Export Reports', 'bassmah-staff-reports'); ?></span>
            </button>
            
            <button class="bassmah-action-btn" onclick="viewCalendar()">
                <span class="bassmah-action-icon">📅</span>
                <span><?php _e('View Calendar', 'bassmah-staff-reports'); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Report Form Modal (hidden by default) -->
<div id="bassmah-report-modal" class="bassmah-modal" style="display: none;">
    <div class="bassmah-modal-content">
        <div class="bassmah-modal-header">
            <h3><?php _e('Submit Daily Report', 'bassmah-staff-reports'); ?></h3>
            <button class="bassmah-modal-close" onclick="hideReportForm()">&times;</button>
        </div>
        <div class="bassmah-modal-body">
            <?php echo do_shortcode('[bassmah_report_form]'); ?>
        </div>
    </div>
</div>

<!-- Calendar Modal -->
<div id="bassmah-calendar-modal" class="bassmah-modal" style="display: none;">
    <div class="bassmah-modal-content bassmah-calendar-content">
        <div class="bassmah-modal-header">
            <h3><?php _e('Monthly Report Calendar', 'bassmah-staff-reports'); ?></h3>
            <button class="bassmah-modal-close" onclick="hideCalendar()">&times;</button>
        </div>
        <div class="bassmah-modal-body">
            <p><?php printf(__('Showing reports from %s to %s', 'bassmah-staff-reports'), date_i18n('F j, Y', strtotime($current_month_start)), date_i18n('F j, Y', strtotime($current_month_end))); ?></p>
            <table class="bassmah-calendar-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Manager Comment', 'bassmah-staff-reports'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($monthly_calendar_reports)): ?>
                        <?php foreach ($monthly_calendar_reports as $calendar_report): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n('F j, Y', strtotime($calendar_report->report_date))); ?></td>
                                <td><?php echo esc_html(ucfirst($calendar_report->status)); ?></td>
                                <td><?php echo esc_html(implode(', ', wp_list_pluck(json_decode($calendar_report->tasks_json, true) ?: array(), 'task_description'))); ?></td>
                                <td><?php echo esc_html($calendar_report->manager_comment); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php _e('No reports found for this month.', 'bassmah-staff-reports'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.bassmah-staff-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.bassmah-welcome-section {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
}

.bassmah-welcome-section h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
}

.bassmah-current-date {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
}

.bassmah-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.bassmah-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-card h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 18px;
}

.bassmah-status-completed {
    text-align: center;
    padding: 20px;
    background: #d4edda;
    border-radius: 6px;
    color: #155724;
}

.bassmah-status-pending {
    text-align: center;
    padding: 20px;
    background: #fff3cd;
    border-radius: 6px;
    color: #856404;
}

.bassmah-status-icon {
    font-size: 24px;
    font-weight: bold;
    display: block;
    margin-bottom: 10px;
}

.bassmah-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    text-align: center;
}

.bassmah-stat-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.bassmah-stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.bassmah-stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bassmah-salary-details {
    space-y: 10px;
}

.bassmah-salary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.bassmah-salary-row:last-child {
    border-bottom: none;
}

.bassmah-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.bassmah-reports-list {
    space-y: 10px;
}

.bassmah-report-item {
    display: grid;
    grid-template-columns: 1fr auto auto auto;
    gap: 15px;
    align-items: center;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 10px;
}

.bassmah-status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.bassmah-status-submitted {
    background: #fff3cd;
    color: #856404;
}

.bassmah-status-approved {
    background: #d4edda;
    color: #155724;
}

.bassmah-status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.bassmah-quick-actions {
    margin-top: 30px;
}

.bassmah-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.bassmah-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
}

.bassmah-action-btn:hover {
    border-color: #0073aa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,115,170,0.15);
}

.bassmah-action-primary {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.bassmah-action-primary:hover {
    background: #005a87;
    border-color: #005a87;
}

.bassmah-action-icon {
    font-size: 20px;
}

.bassmah-present {
    color: #28a745;
    font-weight: bold;
}

.bassmah-absent {
    color: #dc3545;
    font-weight: bold;
}

.bassmah-deduction {
    border-top: 1px solid #e9ecef;
    margin-top: 10px;
    padding-top: 10px;
}

.bassmah-deduction-amount {
    color: #dc3545;
}

.bassmah-net-salary {
    background: #e9ecef;
    padding: 10px;
    border-radius: 4px;
    margin-top: 5px;
}

.bassmah-net-amount {
    color: #28a745;
    font-size: 18px;
}

.bassmah-deduction-cell {
    color: #dc3545;
    font-weight: bold;
}

.bassmah-deduction-badge {
    background: #f8d7da;
    color: #721c24;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.bassmah-net-salary-cell {
    color: #28a745;
    font-weight: bold;
}

.bassmah-attendance-bar {
    position: relative;
    background: #e9ecef;
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 5px;
}

.bassmah-attendance-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s ease;
}

.bassmah-attendance-bar span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    font-weight: bold;
    color: #495057;
    z-index: 1;
}

.bassmah-history-table {
    overflow-x: auto;
}

.bassmah-history-table table {
    margin: 0;
    min-width: 600px;
}

.bassmah-no-history {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
    font-style: italic;
}

.bassmah-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.bassmah-modal-content {
    position: relative;
    max-width: 600px;
    margin: 50px auto;
    background: white;
    border-radius: 8px;
    max-height: 80vh;
    overflow-y: auto;
}

.bassmah-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.bassmah-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.bassmah-modal-body {
    padding: 20px;
}

.bassmah-calendar-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.bassmah-calendar-table th,
.bassmah-calendar-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

.bassmah-calendar-table th {
    background: #f7f7f7;
}

@media (max-width: 768px) {
    .bassmah-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .bassmah-report-item {
        grid-template-columns: 1fr;
        gap: 10px;
        text-align: center;
    }
    
    .bassmah-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showReportForm() {
    document.getElementById('bassmah-report-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideReportForm() {
    document.getElementById('bassmah-report-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function viewReport(reportId) {
    // Implementation to view individual report
    console.log('View report:', reportId);
}

function viewAllReports() {
    // Redirect to the My Reports homepage page
    window.location.href = '<?php echo home_url('/'); ?>';
}

function exportReports() {
    // Implementation to export reports
    console.log('Export reports');
}

function exportSalaryHistory() {
    window.location.href = bassmah_public.ajax_url + '?action=bassmah_export_salary_history&user_id=<?php echo $current_user->ID; ?>&nonce=' + bassmah_public.nonce;
}

function viewCalendar() {
    document.getElementById('bassmah-calendar-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideCalendar() {
    document.getElementById('bassmah-calendar-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const reportModal = document.getElementById('bassmah-report-modal');
    const calendarModal = document.getElementById('bassmah-calendar-modal');
    if (event.target == reportModal) {
        hideReportForm();
    }
    if (event.target == calendarModal) {
        hideCalendar();
    }
}
</script>
