<?php
/**
 * Report Approvals Page
 * Displays pending reports for manager approval
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!Bassmah_Staff_Reports_Roles::can_manage_approvals()) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
}

global $wpdb;

// Handle AJAX request for report details
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'bassmah_get_manager_report_details') {
    check_ajax_referer('bassmah_manager_nonce', 'nonce');
    
    $report_id = intval($_REQUEST['report_id']);
    
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
    $report_class = new Bassmah_Staff_Reports_Report();
    $report = $report_class->get_report($report_id);
    
    if (!$report) {
        wp_send_json_error(__('Report not found.', 'bassmah-staff-reports'));
    }
    
    ob_start();
    ?>
    <div class="bassmah-report-details">
        <h4><?php echo esc_html($report->user_display_name); ?> - <?php echo esc_html($report->report_date); ?></h4>
        
        <?php if (!empty($report->tasks)): ?>
            <h5><?php _e('Tasks:', 'bassmah-staff-reports'); ?></h5>
            <?php foreach ($report->tasks as $task): ?>
                <div class="bassmah-task-item">
                    <strong><?php echo esc_html($task['task_category']); ?></strong><br>
                    <?php echo esc_html($task['task_description']); ?><br>
                    <em><?php echo esc_html($task['next_action']); ?></em>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($report->manager_comment)): ?>
            <h5><?php _e('Manager Comment:', 'bassmah-staff-reports'); ?></h5>
            <p><?php echo esc_html($report->manager_comment); ?></p>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success($html);
}

// Get pending reports
$reports_table = $wpdb->prefix . 'staff_reports';
$pending_reports = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, u.display_name, u.user_email 
     FROM $reports_table r 
     LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
     WHERE r.status = %s 
     ORDER BY r.report_date DESC",
    'submitted'
));

?>
<div class="wrap">
    <h1><?php _e('Pending Report Approvals', 'bassmah-staff-reports'); ?></h1>
    
    <?php if (empty($pending_reports)): ?>
        <p><?php _e('No pending reports to approve.', 'bassmah-staff-reports'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Staff Member', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Report Date', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_reports as $report): ?>
                    <tr>
                        <td><?php echo esc_html($report->display_name); ?></td>
                        <td><?php echo esc_html($report->report_date); ?></td>
                        <td>
                            <?php 
                            $tasks = json_decode($report->tasks_json, true);
                            echo count($tasks); 
                            ?> <?php _e('tasks', 'bassmah-staff-reports'); ?>
                        </td>
                        <td><span class="status-pending"><?php _e('Pending', 'bassmah-staff-reports'); ?></span></td>
                        <td>
                            <button type="button" class="button button-small" onclick="viewReportDetails(<?php echo $report->id; ?>)">
                                <?php _e('View', 'bassmah-staff-reports'); ?>
                            </button>
                            <button type="button" class="button button-primary" onclick="approveReport(<?php echo $report->id; ?>)">
                                <?php _e('Approve', 'bassmah-staff-reports'); ?>
                            </button>
                            <button type="button" class="button" onclick="rejectReport(<?php echo $report->id; ?>)">
                                <?php _e('Reject', 'bassmah-staff-reports'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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
function viewReportDetails(reportId) {
    var modal = document.getElementById('bassmah-report-details-modal');
    var content = document.getElementById('bassmah-report-details-content');
    content.innerHTML = '<div class="bassmah-loading"><?php _e('Loading...', 'bassmah-staff-reports'); ?></div>';
    modal.style.display = 'block';

    var params = new URLSearchParams({
        action: 'bassmah_get_manager_report_details',
        nonce: '<?php echo wp_create_nonce('bassmah_manager_nonce'); ?>',
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
            content.innerHTML = data.data;
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
        action: 'bassmah_update_report_status',
        nonce: '<?php echo wp_create_nonce('bassmah_manager_nonce'); ?>',
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

.bassmah-report-details {
    padding: 20px;
}

.bassmah-task-item {
    background: #f9f9f9;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 4px solid #007cba;
    border-radius: 4px;
}

.status-pending {
    color: #d63638;
    font-weight: bold;
}
</style>
