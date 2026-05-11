<?php
/**
 * My Reports View
 *
 * @package    Bassmah_Staff_Reports
 * @subpackage Public/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$report_class = new Bassmah_Staff_Reports_Report();

// Get filter parameters
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 10;

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
if ($status) {
    $args['status'] = $status;
}

// Get reports
$reports = $report_class->get_my_reports($args);
$total_reports = $report_class->get_my_reports_count($args);
$total_pages = ceil($total_reports / $per_page);
?>

<div class="bassmah-my-reports">
    <div class="bassmah-reports-header">
        <h2><?php _e('My Reports', 'bassmah-staff-reports'); ?></h2>
        <div class="bassmah-reports-actions">
            <button class="button" onclick="exportReports()">
                <?php _e('Export Reports', 'bassmah-staff-reports'); ?>
            </button>
            <a href="<?php echo home_url('/dashboard/'); ?>" class="button">
                <?php _e('Back to Dashboard', 'bassmah-staff-reports'); ?>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bassmah-filters-card">
        <h3><?php _e('Filter Reports', 'bassmah-staff-reports'); ?></h3>
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="bassmah-filter-form">
            <input type="hidden" name="bassmah_action" value="my_reports">
            
            <div class="bassmah-filter-row">
                <div class="bassmah-filter-group">
                    <label for="date_from"><?php _e('From Date:', 'bassmah-staff-reports'); ?></label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="bassmah-filter-group">
                    <label for="date_to"><?php _e('To Date:', 'bassmah-staff-reports'); ?></label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
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
                    <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'bassmah-staff-reports'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="bassmah-summary-cards">
        <div class="bassmah-summary-card">
            <h4><?php _e('Total Reports', 'bassmah-staff-reports'); ?></h4>
            <span class="bassmah-summary-number"><?php echo $total_reports; ?></span>
        </div>
        <div class="bassmah-summary-card">
            <h4><?php _e('Submitted', 'bassmah-staff-reports'); ?></h4>
            <span class="bassmah-summary-number bassmah-submitted"><?php echo $report_class->get_my_reports_count(array_merge($args, array('status' => 'submitted'))); ?></span>
        </div>
        <div class="bassmah-summary-card">
            <h4><?php _e('Approved', 'bassmah-staff-reports'); ?></h4>
            <span class="bassmah-summary-number bassmah-approved"><?php echo $report_class->get_my_reports_count(array_merge($args, array('status' => 'approved'))); ?></span>
        </div>
        <div class="bassmah-summary-card">
            <h4><?php _e('Rejected', 'bassmah-staff-reports'); ?></h4>
            <span class="bassmah-summary-number bassmah-rejected"><?php echo $report_class->get_my_reports_count(array_merge($args, array('status' => 'rejected'))); ?></span>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bassmah-reports-table-wrapper">
        <?php if (!empty($reports)): ?>
            <table class="wp-list-table widefat fixed striped bassmah-reports-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'bassmah-staff-reports'); ?></th>
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
                                <strong><?php echo date_i18n('M j, Y', strtotime($report->report_date)); ?></strong>
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
                                    <button class="button button-small" onclick="viewReportDetails(<?php echo isset($report->id) ? intval($report->id) : 'null'; ?>)">
                                        <?php _e('View', 'bassmah-staff-reports'); ?>
                                    </button>
                                    <?php if ($report->status === 'submitted' && $report->report_date === date('Y-m-d')): ?>
                                        <button class="button button-small" onclick="editReport(<?php echo isset($report->id) ? intval($report->id) : 'null'; ?>)">
                                            <?php _e('Edit', 'bassmah-staff-reports'); ?>
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
                <?php if (date('Y-m-d') >= $date_from && date('Y-m-d') <= $date_to): ?>
                    <a href="<?php echo home_url('/report/'); ?>" class="button button-primary">
                        <?php _e('Submit Today\'s Report', 'bassmah-staff-reports'); ?>
                    </a>
                <?php endif; ?>
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

<style>
.bassmah-my-reports {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.bassmah-reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.bassmah-reports-actions {
    display: flex;
    gap: 10px;
}

.bassmah-filters-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-filter-form {
    margin: 0;
}

.bassmah-filter-row {
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

.bassmah-summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.bassmah-summary-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-summary-card h4 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bassmah-summary-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2c3e50;
}

.bassmah-submitted { color: #856404; }
.bassmah-approved { color: #155724; }
.bassmah-rejected { color: #721c24; }

.bassmah-reports-table-wrapper {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.bassmah-reports-table {
    margin: 0;
}

.bassmah-reports-table th {
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

.bassmah-no-reports h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.bassmah-large-modal .bassmah-modal-content {
    max-width: 800px;
}

@media (max-width: 768px) {
    .bassmah-reports-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .bassmah-reports-actions {
        justify-content: center;
    }
    
    .bassmah-filter-row {
        grid-template-columns: 1fr;
    }
    
    .bassmah-summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .bassmah-action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function viewReportDetails(reportId) {
    const modal = document.getElementById('bassmah-report-details-modal');
    const content = document.getElementById('bassmah-report-details-content');
    content.innerHTML = '<div class="bassmah-loading-container"><span class="loading loading-infinity loading-xl"></span><span class="bassmah-loading-text"><?php _e('Loading...', 'bassmah-staff-reports'); ?></span></div>';
    modal.style.display = 'block';

    // JWT token নাও localStorage থেকে
    var jwtToken = localStorage.getItem('bassmah_jwt_token');

    var headers = {
        'Content-Type': 'application/x-www-form-urlencoded',
    };

    // Token থাকলে Authorization header যোগ করো
    if (jwtToken) {
        headers['Authorization'] = 'Bearer ' + jwtToken;
    }

    fetch(bassmah_public.ajaxurl, {
        method: 'POST',
        headers: headers,
        body: new URLSearchParams({
            action: 'bassmah_get_report_details',
            nonce: bassmah_public.nonce,
            report_id: reportId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = data.data.html;
        } else {
            content.innerHTML = '<div class="bassmah-error">' + data.data + '</div>';
        }
    })
    .catch(error => {
        content.innerHTML = '<div class="bassmah-error"><?php _e('Error loading report details', 'bassmah-staff-reports'); ?></div>';
    });
}

function hideReportDetails() {
    document.getElementById('bassmah-report-details-modal').style.display = 'none';
}

function editReport(reportId) {
    if (!reportId) {
        console.error('Report ID is undefined');
        return;
    }
    
    // Redirect to report form with edit parameter
    window.location.href = bassmah_public.report_form_url + '?edit=' + reportId;
}

function exportReports() {
    const form = document.querySelector('.bassmah-filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.open(admin_url('admin-ajax.php') + '?action=bassmah_export_reports&' + params.toString());
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bassmah-report-details-modal');
    if (event.target == modal) {
        hideReportDetails();
    }
}
</script>
