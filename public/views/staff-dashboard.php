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

$current_user = wp_get_current_user();
$report_class = new Bassmah_Staff_Reports_Report();
$salary_class = new Bassmah_Staff_Reports_Salary();

// Get today's report status
$today_report = $report_class->get_today_report();

// Get dashboard statistics
$dashboard_stats = $salary_class->get_dashboard_stats($current_user->ID);

// Get recent reports
$recent_reports = $report_class->get_my_reports(array(
    'limit' => 5,
    'orderby' => 'report_date',
    'order' => 'DESC'
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
                    <strong><?php echo isset($dashboard_stats['monthly_salary']) ? number_format($dashboard_stats['monthly_salary'], 2) : '0.00'; ?> <?php echo $dashboard_stats['currency'] ?? 'CAD'; ?></strong>
                </div>
                <div class="bassmah-salary-row">
                    <span><?php _e('Daily Rate:', 'bassmah-staff-reports'); ?></span>
                    <strong><?php echo isset($dashboard_stats['daily_rate']) ? number_format($dashboard_stats['daily_rate'], 2) : '0.00'; ?> <?php echo $dashboard_stats['currency'] ?? 'CAD'; ?></strong>
                </div>
                <div class="bassmah-salary-row">
                    <span><?php _e('Expected Earnings:', 'bassmah-staff-reports'); ?></span>
                    <strong><?php echo isset($dashboard_stats['expected_earnings']) ? number_format($dashboard_stats['expected_earnings'], 2) : '0.00'; ?> <?php echo $dashboard_stats['currency'] ?? 'CAD'; ?></strong>
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
    // Redirect to reports page or load via AJAX
    window.location.href = '<?php echo home_url(); ?>/?bassmah_action=my_reports';
}

function exportReports() {
    // Implementation to export reports
    console.log('Export reports');
}

function viewCalendar() {
    // Implementation to view calendar
    console.log('View calendar');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bassmah-report-modal');
    if (event.target == modal) {
        hideReportForm();
    }
}
</script>
