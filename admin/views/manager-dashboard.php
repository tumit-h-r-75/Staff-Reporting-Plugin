<?php
/**
 * Manager Dashboard View
 *
 * @package    Bassmah_Staff_Reports
 * @subpackage Admin/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user has manager capabilities
if (!current_user_can('bassmah_view_all_reports')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
}

$report_class = new Bassmah_Staff_Reports_Report();
$salary_calculator = new Bassmah_Staff_Reports_Salary_Calculator();

// Get filter parameters
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;

// Get staff users for filter dropdown
$staff_users = get_users(array(
    'role__in' => array('bassmah_staff', 'bassmah_manager', 'administrator'),
    'orderby' => 'display_name'
));

// Build query arguments
$args = array(
    'limit' => $per_page,
    'offset' => ($page - 1) * $per_page,
    'orderby' => 'report_date',
    'order' => 'DESC'
);

if ($date_from) {
    $args['date_from'] = $date_from;
}
if ($date_to) {
    $args['date_to'] = $date_to;
}
if ($employee_id) {
    $args['user_id'] = $employee_id;
}
if ($status) {
    $args['status'] = $status;
}

// Get reports
$reports = $report_class->get_reports($args);
$total_reports = $report_class->get_reports_count($args);
$total_pages = ceil($total_reports / $per_page);

// Get dashboard statistics
$stats = $report_class->get_manager_statistics($args);
?>

<div class="wrap bassmah-manager-dashboard">
    <h1><?php _e('Manager Dashboard', 'bassmah-staff-reports'); ?></h1>
    
    <!-- Quick Stats -->
    <div class="bassmah-stats-overview">
        <div class="bassmah-stat-card">
            <h3><?php _e('Total Reports', 'bassmah-staff-reports'); ?></h3>
            <span class="bassmah-stat-number"><?php echo $stats['total_reports']; ?></span>
        </div>
        <div class="bassmah-stat-card">
            <h3><?php _e('Submitted', 'bassmah-staff-reports'); ?></h3>
            <span class="bassmah-stat-number bassmah-submitted"><?php echo $stats['submitted']; ?></span>
        </div>
        <div class="bassmah-stat-card">
            <h3><?php _e('Approved', 'bassmah-staff-reports'); ?></h3>
            <span class="bassmah-stat-number bassmah-approved"><?php echo $stats['approved']; ?></span>
        </div>
        <div class="bassmah-stat-card">
            <h3><?php _e('Rejected', 'bassmah-staff-reports'); ?></h3>
            <span class="bassmah-stat-number bassmah-rejected"><?php echo $stats['rejected']; ?></span>
        </div>
        <div class="bassmah-stat-card">
            <h3><?php _e('Pending Review', 'bassmah-staff-reports'); ?></h3>
            <span class="bassmah-stat-number bassmah-pending"><?php echo $stats['pending']; ?></span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bassmah-filters-section">
        <h3><?php _e('Filter Reports', 'bassmah-staff-reports'); ?></h3>
        <form method="get" class="bassmah-filter-form">
            <input type="hidden" name="page" value="bassmah-reports">
            
            <div class="bassmah-filter-grid">
                <div class="bassmah-filter-group">
                    <label for="date_from"><?php _e('From Date:', 'bassmah-staff-reports'); ?></label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="bassmah-filter-group">
                    <label for="date_to"><?php _e('To Date:', 'bassmah-staff-reports'); ?></label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>
                
                <div class="bassmah-filter-group">
                    <label for="employee_id"><?php _e('Employee:', 'bassmah-staff-reports'); ?></label>
                    <select id="employee_id" name="employee_id">
                        <option value=""><?php _e('All Employees', 'bassmah-staff-reports'); ?></option>
                        <?php foreach ($staff_users as $user): ?>
                            <option value="<?php echo $user->ID; ?>" <?php selected($employee_id, $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?> (<?php echo esc_html(Bassmah_Staff_Reports_Roles::get_user_role_display($user->ID)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="bassmah-filter-group">
                    <label for="status"><?php _e('Status:', 'bassmah-staff-reports'); ?></label>
                    <select id="status" name="status">
                        <option value=""><?php _e('All Status', 'bassmah-staff-reports'); ?></option>
                        <option value="submitted" <?php selected($status, 'submitted'); ?>><?php _e('Submitted', 'bassmah-staff-reports'); ?></option>
                        <option value="approved" <?php selected($status, 'approved'); ?>><?php _e('Approved', 'bassmah-staff-reports'); ?></option>
                        <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Rejected', 'bassmah-staff-reports'); ?></option>
                    </select>
                </div>
                
                <div class="bassmah-filter-group">
                    <label>&nbsp;</label>
                    <div class="bassmah-filter-buttons">
                        <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'bassmah-staff-reports'); ?></button>
                        <a href="?page=bassmah-reports" class="button"><?php _e('Clear', 'bassmah-staff-reports'); ?></a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Export Options -->
    <div class="bassmah-export-section">
        <h3><?php _e('Export Options', 'bassmah-staff-reports'); ?></h3>
        <div class="bassmah-export-buttons">
            <button class="button" onclick="exportCurrentReports()">
                <?php _e('Export Current Reports (CSV)', 'bassmah-staff-reports'); ?>
            </button>
            <button class="button" onclick="exportSalarySummary()">
                <?php _e('Export Salary Summary (CSV)', 'bassmah-staff-reports'); ?>
            </button>
            <button class="button" onclick="exportToExcel()">
                <?php _e('Export to Excel', 'bassmah-staff-reports'); ?>
            </button>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bassmah-reports-table-wrapper">
        <h3><?php _e('Reports', 'bassmah-staff-reports'); ?> (<?php echo $total_reports; ?>)</h3>
        
        <?php if (!empty($reports)): ?>
            <table class="wp-list-table widefat fixed striped bassmah-manager-reports-table">
                <thead>
                    <tr>
                        <th><?php _e('Employee', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Role', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Report Date', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Submission Time', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Manager Comment', 'bassmah-staff-reports'); ?></th>
                        <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($report->display_name); ?></strong>
                                <br>
                                <small><?php echo esc_html($report->user_email); ?></small>
                            </td>
                            <td><?php echo esc_html(Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id)); ?></td>
                            <td>
                                <?php echo date_i18n('M j, Y', strtotime($report->report_date)); ?>
                                <?php if ($report->report_date === date('Y-m-d')): ?>
                                    <span class="bassmah-today-badge"><?php _e('Today', 'bassmah-staff-reports'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date_i18n('g:i A', strtotime($report->submission_time)); ?></td>
                            <td>
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
                            </td>
                            <td>
                                <?php 
                                $tasks = is_array($report->tasks) ? $report->tasks : json_decode($report->tasks, true);
                                $task_count = is_array($tasks) ? count($tasks) : 0;
                                echo $task_count . ' ' . _n('task', 'tasks', $task_count, 'bassmah-staff-reports'); 
                                ?>
                            </td>
                            <td>
                                <?php if ($report->manager_comment): ?>
                                    <span class="bassmah-comment-preview" title="<?php echo esc_attr($report->manager_comment); ?>">
                                        <?php echo wp_trim_words($report->manager_comment, 5, '...'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bassmah-no-comment"><?php _e('No comment', 'bassmah-staff-reports'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="bassmah-action-buttons">
                                    <button class="button button-small" onclick="viewReportDetails(<?php echo $report->id; ?>)">
                                        <?php _e('View', 'bassmah-staff-reports'); ?>
                                    </button>
                                    <?php if ($report->status === 'submitted'): ?>
                                        <button class="button button-small button-primary" onclick="approveReport(<?php echo $report->id; ?>)">
                                            <?php _e('Approve', 'bassmah-staff-reports'); ?>
                                        </button>
                                        <button class="button button-small" onclick="rejectReport(<?php echo $report->id; ?>)">
                                            <?php _e('Reject', 'bassmah-staff-reports'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bassmah-pagination">
                    <?php
                    $current_url = remove_query_arg('paged');
                    if (strpos($current_url, '?') === false) {
                        $current_url .= '?';
                    } else {
                        $current_url .= '&';
                    }
                    
                    // Previous
                    if ($page > 1):
                        $prev_url = $current_url . 'paged=' . ($page - 1);
                    ?>
                        <a href="<?php echo esc_url($prev_url); ?>" class="button">&laquo; <?php _e('Previous', 'bassmah-staff-reports'); ?></a>
                    <?php endif; ?>

                    <!-- Page numbers -->
                    <?php
                    $show_pages = 5;
                    $start_page = max(1, $page - floor($show_pages / 2));
                    $end_page = min($total_pages, $start_page + $show_pages - 1);
                    
                    if ($start_page > 1) {
                        echo '<a href="' . esc_url($current_url . 'paged=1') . '" class="button">1</a>';
                        if ($start_page > 2) echo '<span class="bassmah-pagination-ellipsis">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $page_url = $current_url . 'paged=' . $i;
                        $class = ($i === $page) ? 'button-primary' : 'button';
                        echo '<a href="' . esc_url($page_url) . '" class="' . $class . '">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span class="bassmah-pagination-ellipsis">...</span>';
                        echo '<a href="' . esc_url($current_url . 'paged=' . $total_pages) . '" class="button">' . $total_pages . '</a>';
                    }
                    ?>

                    <!-- Next -->
                    <?php if ($page < $total_pages):
                        $next_url = $current_url . 'paged=' . ($page + 1);
                    ?>
                        <a href="<?php echo esc_url($next_url); ?>" class="button"><?php _e('Next', 'bassmah-staff-reports'); ?> &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="bassmah-no-reports">
                <h3><?php _e('No Reports Found', 'bassmah-staff-reports'); ?></h3>
                <p><?php _e('No reports found matching your criteria.', 'bassmah-staff-reports'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Report Details Modal -->
<div id="bassmah-report-details-modal" class="bassmah-modal" style="display: none;">
    <div class="bassmah-modal-content bassmah-large-modal">
        <div class="bassmah-modal-header">
            <h3><?php _e('Report Details', 'bassmah-staff-reports'); ?></h3>
            <button class="bassmah-modal-close" onclick="hideReportDetails()">&times;</button>
        </div>
        <div class="bassmah-modal-body" id="bassmah-report-details-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Comment Modal -->
<div id="bassmah-comment-modal" class="bassmah-modal" style="display: none;">
    <div class="bassmah-modal-content">
        <div class="bassmah-modal-header">
            <h3 id="bassmah-comment-title"><?php _e('Add Comment', 'bassmah-staff-reports'); ?></h3>
            <button class="bassmah-modal-close" onclick="hideCommentModal()">&times;</button>
        </div>
        <div class="bassmah-modal-body">
            <form id="bassmah-comment-form">
                <input type="hidden" id="bassmah-comment-report-id">
                <input type="hidden" id="bassmah-comment-action">
                
                <div class="bassmah-form-group">
                    <label for="bassmah-comment-text"><?php _e('Comment:', 'bassmah-staff-reports'); ?></label>
                    <textarea id="bassmah-comment-text" rows="5" class="large-text" required></textarea>
                </div>
                
                <div class="bassmah-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Submit', 'bassmah-staff-reports'); ?></button>
                    <button type="button" class="button" onclick="hideCommentModal()"><?php _e('Cancel', 'bassmah-staff-reports'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bassmah-manager-dashboard {
    max-width: 1400px;
    margin: 0 auto;
}

.bassmah-stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.bassmah-stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-stat-card h3 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bassmah-stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2c3e50;
}

.bassmah-submitted { color: #856404; }
.bassmah-approved { color: #155724; }
.bassmah-rejected { color: #721c24; }
.bassmah-pending { color: #004085; }

.bassmah-filters-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.bassmah-filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c3e50;
}

.bassmah-filter-group input,
.bassmah-filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.bassmah-filter-buttons {
    display: flex;
    gap: 10px;
}

.bassmah-export-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.bassmah-reports-table-wrapper {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-manager-reports-table {
    margin: 0;
}

.bassmah-manager-reports-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.bassmah-today-badge {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 8px;
    text-transform: uppercase;
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

.bassmah-comment-preview {
    cursor: help;
    color: #6c757d;
}

.bassmah-no-comment {
    color: #adb5bd;
    font-style: italic;
}

.bassmah-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.bassmah-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    padding: 20px;
    border-top: 1px solid #e9ecef;
}

.bassmah-pagination-ellipsis {
    padding: 0 10px;
    color: #6c757d;
}

.bassmah-no-reports {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.bassmah-large-modal .bassmah-modal-content {
    max-width: 900px;
}

.bassmah-form-group {
    margin-bottom: 15px;
}

.bassmah-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.bassmah-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .bassmah-stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .bassmah-filter-grid {
        grid-template-columns: 1fr;
    }
    
    .bassmah-export-buttons {
        flex-direction: column;
    }
    
    .bassmah-action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function viewReportDetails(reportId) {
    // Show loading
    const modal = document.getElementById('bassmah-report-details-modal');
    const content = document.getElementById('bassmah-report-details-content');
    content.innerHTML = '<div class="bassmah-loading-container"><span class="loading loading-infinity loading-xl"></span><span class="bassmah-loading-text"><?php _e('Loading...', 'bassmah-staff-reports'); ?></span></div>';
    modal.style.display = 'block';
    
    const params = new URLSearchParams({
        action: 'bassmah_admin_ajax',
        nonce: '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>',
        action_type: 'get_manager_report_details',
        report_id: reportId
    });

    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = data.data.html;
        } else {
            content.innerHTML = '<div class="bassmah-error">' + (data.data || '<?php _e('Unable to load report details', 'bassmah-staff-reports'); ?>') + '</div>';
        }
    })
    .catch(error => {
        content.innerHTML = '<div class="bassmah-error"><?php _e('Error loading report details', 'bassmah-staff-reports'); ?></div>';
    });
}

function hideReportDetails() {
    document.getElementById('bassmah-report-details-modal').style.display = 'none';
}

function approveReport(reportId) {
    showCommentModal(reportId, 'approve');
}

function rejectReport(reportId) {
    showCommentModal(reportId, 'reject');
}

function showCommentModal(reportId, action) {
    document.getElementById('bassmah-comment-report-id').value = reportId;
    document.getElementById('bassmah-comment-action').value = action;
    document.getElementById('bassmah-comment-title').textContent = 
        action === 'approve' ? '<?php _e('Approve Report', 'bassmah-staff-reports'); ?>' : 
        '<?php _e('Reject Report', 'bassmah-staff-reports'); ?>';
    document.getElementById('bassmah-comment-modal').style.display = 'block';
}

function hideCommentModal() {
    document.getElementById('bassmah-comment-modal').style.display = 'none';
    document.getElementById('bassmah-comment-form').reset();
}

function exportCurrentReports() {
    const form = document.querySelector('.bassmah-filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    params.append('action', 'bassmah_admin_ajax');
    params.append('nonce', '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>');
    params.append('action_type', 'export_manager_reports');
    
    window.open(ajaxurl + '?' + params.toString());
}

function exportSalarySummary() {
    const form = document.querySelector('.bassmah-filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    params.append('action', 'bassmah_admin_ajax');
    params.append('nonce', '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>');
    params.append('action_type', 'export_salary_summary');
    
    window.open(ajaxurl + '?' + params.toString());
}

function exportToExcel() {
    // Implementation for Excel export
    exportCurrentReports(); // For now, use CSV export
}

// Handle comment form submission
document.getElementById('bassmah-comment-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reportId = document.getElementById('bassmah-comment-report-id').value;
    const action = document.getElementById('bassmah-comment-action').value;
    const comment = document.getElementById('bassmah-comment-text').value;
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'bassmah_admin_ajax',
            nonce: '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>',
            action_type: 'update_report_status',
            report_id: reportId,
            status: action,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideCommentModal();
            location.reload();
        } else {
            alert(data.data || '<?php _e('Unable to update report', 'bassmah-staff-reports'); ?>');
        }
    })
    .catch(error => {
        alert('<?php _e('Error updating report', 'bassmah-staff-reports'); ?>');
    });
});

// Close modals when clicking outside
window.onclick = function(event) {
    const detailsModal = document.getElementById('bassmah-report-details-modal');
    const commentModal = document.getElementById('bassmah-comment-modal');
    
    if (event.target == detailsModal) {
        hideReportDetails();
    }
    if (event.target == commentModal) {
        hideCommentModal();
    }
}
</script>
