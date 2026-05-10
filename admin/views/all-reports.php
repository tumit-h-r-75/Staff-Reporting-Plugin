<?php
/**
 * All Reports View
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

// Get filter parameters
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Build WHERE clause
$where_conditions = array();
$where_values = array();

if ($user_id > 0) {
    $where_conditions[] = "r.user_id = %d";
    $where_values[] = $user_id;
}

if (!empty($status)) {
    $where_conditions[] = "r.status = %s";
    $where_values[] = $status;
}

if (!empty($date_from)) {
    $where_conditions[] = "r.report_date >= %s";
    $where_values[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "r.report_date <= %s";
    $where_values[] = $date_to;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Query reports
$table_name = $wpdb->prefix . 'staff_reports';
$query = "
    SELECT r.*, u.display_name, u.user_email, u.user_login 
    FROM {$table_name} r 
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
    {$where_clause} 
    ORDER BY r.submission_time DESC 
    LIMIT {$limit} OFFSET {$offset}
";

if (!empty($where_values)) {
    $query = $wpdb->prepare($query, $where_values);
}

$reports = $wpdb->get_results($query);

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) 
    FROM {$table_name} r 
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
    {$where_clause}
";

if (!empty($where_values)) {
    $count_query = $wpdb->prepare($count_query, $where_values);
}

$total_reports = $wpdb->get_var($count_query);
$total_pages = ceil($total_reports / $limit);

// Get users for filter dropdown
$users = get_users(array(
    'role__in' => array('bassmah_staff', 'bassmah_manager', 'subscriber'),
    'fields' => array('ID', 'display_name'),
    'orderby' => 'display_name',
    'order' => 'ASC'
));
?>

<div class="wrap bassmah-admin">
    <h1><?php _e('All Reports', 'bassmah-staff-reports'); ?></h1>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <button type="button" class="button" onclick="exportReports()">
                <?php _e('Export Reports', 'bassmah-staff-reports'); ?>
            </button>
            <select id="export-format" class="button">
                <option value="csv"><?php _e('CSV', 'bassmah-staff-reports'); ?></option>
                <option value="excel"><?php _e('Excel', 'bassmah-staff-reports'); ?></option>
            </select>
        </div>
        <div class="alignright">
            <label class="screen-reader-text" for="post-search-input"><?php _e('Search Reports:', 'bassmah-staff-reports'); ?></label>
            <input type="search" id="post-search-input" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" />
            <?php submit_button(__('Search Reports', 'bassmah-staff-reports'), 'button', 'button-primary'); ?>
        </div>
    </div>
    
    <div class="bassmah-filters">
        <form method="get" action="">
            <table class="form-table">
                <tr>
                    <th>
                        <label for="user_id"><?php _e('User:', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <select name="user_id" id="user_id">
                            <option value=""><?php _e('All Users', 'bassmah-staff-reports'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected($user_id, $user->ID); ?>>
                                    <?php echo esc_html($user->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="status"><?php _e('Status:', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value=""><?php _e('All Statuses', 'bassmah-staff-reports'); ?></option>
                            <option value="draft" <?php selected($status, 'draft'); ?>><?php _e('Draft', 'bassmah-staff-reports'); ?></option>
                            <option value="submitted" <?php selected($status, 'submitted'); ?>><?php _e('Submitted', 'bassmah-staff-reports'); ?></option>
                            <option value="approved" <?php selected($status, 'approved'); ?>><?php _e('Approved', 'bassmah-staff-reports'); ?></option>
                            <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Rejected', 'bassmah-staff-reports'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="date_from"><?php _e('From:', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="date_to"><?php _e('To:', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th>&nbsp;</th>
                    <td>
                        <input type="submit" value="<?php _e('Filter', 'bassmah-staff-reports'); ?>" class="button">
                        <input type="hidden" name="page" value="bassmah-all-reports">
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <!-- Reports Table -->
    <div class="bassmah-section">
        <div class="tablenav top">
            <div class="alignleft actions">
                <?php
                if ($offset > 0) {
                    $prev_offset = max(0, $offset - $limit);
                    echo '<a href="' . esc_url(add_query_arg(array('offset' => $prev_offset))) . '" class="prev button">' . __('&laquo; Previous', 'bassmah-staff-reports') . '</a>';
                }
                
                if ($offset + $limit < $total_reports) {
                    $next_offset = $offset + $limit;
                    echo '<a href="' . esc_url(add_query_arg(array('offset' => $next_offset))) . '" class="next button">' . __('Next &raquo;', 'bassmah-staff-reports') . '</a>';
                }
                ?>
            </div>
            <div class="alignright">
                <span class="displaying-num">
                    <?php
                    printf(
                        __('Displaying %d–%d of %d', 'bassmah-staff-reports'),
                        $offset + 1,
                        min($offset + $limit, $total_reports),
                        $total_reports
                    );
                    ?>
                </span>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped bassmah-table">
            <thead>
                <tr>
                    <th><?php _e('ID', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('User', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Date', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reports)): ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo $report->id; ?></td>
                            <td><?php echo esc_html($report->display_name); ?></td>
                            <td><?php echo esc_html($report->report_date); ?></td>
                            <td>
                                <span class="status-<?php echo $report->status; ?>">
                                    <?php echo ucfirst($report->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $tasks = json_decode($report->tasks_json, true);
                                if ($tasks && is_array($tasks)) {
                                    foreach ($tasks as $index => $task) {
                                        echo '<div class="task-item">';
                                        echo '<strong>' . ($index + 1) . '.</strong> ' . esc_html($task['task_description']);
                                        echo '</div>';
                                    }
                                } else {
                                    _e('No tasks', 'bassmah-staff-reports');
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small" onclick="viewReportDetails(<?php echo $report->id; ?>)">
                                    <?php _e('View', 'bassmah-staff-reports'); ?>
                                </button>
                                <?php if (current_user_can('bassmah_comment_reports')): ?>
                                    <button class="button button-small" onclick="addComment(<?php echo $report->id; ?>)">
                                        <?php _e('Comment', 'bassmah-staff-reports'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php _e('No reports found.', 'bassmah-staff-reports'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
            <div class="bassmah-loading-container">
                <span class="loading loading-infinity loading-xl"></span>
                <span class="bassmah-loading-text"><?php _e('Loading...', 'bassmah-staff-reports'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Comment Modal -->
<div id="bassmah-comment-modal" class="bassmah-modal" style="display:none;">
    <div class="bassmah-modal-content">
        <div class="bassmah-modal-header">
            <h3 id="bassmah-comment-title"><?php _e('Add Comment', 'bassmah-staff-reports'); ?></h3>
            <button class="bassmah-modal-close" type="button" onclick="hideCommentModal()">&times;</button>
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

<script>
function exportReports() {
    var format = document.getElementById('export-format').value;
    var url = bassmah_admin.rest_url + 'reports/export?format=' + format;
    
    // Add current filters to URL
    var userId = document.getElementById('user_id') ? document.getElementById('user_id').value : '';
    var dateFrom = document.getElementById('date_from') ? document.getElementById('date_from').value : '';
    var dateTo = document.getElementById('date_to') ? document.getElementById('date_to').value : '';
    var status = document.getElementById('status') ? document.getElementById('status').value : '';
    
    if (userId) url += '&user_id=' + encodeURIComponent(userId);
    if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
    if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
    if (status) url += '&status=' + encodeURIComponent(status);
    
    window.location.href = url;
}

function viewReportDetails(reportId) {
    var modal = document.getElementById('bassmah-report-details-modal');
    var content = document.getElementById('bassmah-report-details-content');
    content.innerHTML = '<div class="bassmah-loading-container"><div class="modern-spinner loading-xl"></div><span class="bassmah-loading-text"><?php _e('Loading...', 'bassmah-staff-reports'); ?></span></div>';
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

document.getElementById('bassmah-comment-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var reportId = document.getElementById('bassmah-comment-report-id').value;
    var action = document.getElementById('bassmah-comment-action').value;
    var comment = document.getElementById('bassmah-comment-text').value;

    var params = new URLSearchParams({
        action: 'bassmah_admin_ajax',
        nonce: '<?php echo wp_create_nonce('bassmah_admin_nonce'); ?>',
        action_type: 'update_report_status',
        report_id: reportId,
        status: action,
        comment: comment
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
            hideCommentModal();
            window.location.reload();
        } else {
            alert(data.data || '<?php _e('Unable to update report.', 'bassmah-staff-reports'); ?>');
        }
    })
    .catch(function() {
        alert('<?php _e('Error updating report.', 'bassmah-staff-reports'); ?>');
    });
});

window.onclick = function(event) {
    var detailsModal = document.getElementById('bassmah-report-details-modal');
    var commentModal = document.getElementById('bassmah-comment-modal');

    if (event.target === detailsModal) {
        hideReportDetails();
    }
    if (event.target === commentModal) {
        hideCommentModal();
    }
};
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

.bassmah-form-group {
    margin-bottom: 15px;
}

.bassmah-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.bassmah-error {
    color: #dc3545;
    padding: 15px;
}

.tab-content {
    margin-top: 20px;
}

.bassmah-filters {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
}

.bassmah-filters table {
    margin: 0;
}

.bassmah-filters th {
    text-align: right;
    padding: 5px;
    font-weight: bold;
}

.bassmah-filters td {
    padding: 5px;
}

.status-draft { color: #666; }
.status-submitted { color: #0073aa; }
.status-approved { color: #46b450; }
.status-rejected { color: #dc3545; }

.task-item {
    margin-bottom: 5px;
    font-size: 12px;
}

.button-small {
    padding: 3px 8px;
    font-size: 12px;
    margin-right: 5px;
}
</style>
