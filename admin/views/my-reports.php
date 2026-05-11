<?php
/**
 * My Reports Page
 * Displays user's own submitted reports
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!Bassmah_Staff_Reports_Roles::can_view_own_reports()) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
}

global $wpdb;

// Get user's reports
$reports_table = $wpdb->prefix . 'staff_reports';
$user_id = get_current_user_id();
$reports = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $reports_table WHERE user_id = %d ORDER BY report_date DESC",
    $user_id
));

?>
<div class="wrap">
    <h1><?php _e('My Reports', 'bassmah-staff-reports'); ?></h1>
    
    <?php if (empty($reports)): ?>
        <p><?php _e('No reports found. Submit your first report!', 'bassmah-staff-reports'); ?></p>
        <p>
            <a href="<?php echo home_url('/report/'); ?>" class="button button-primary">
                <?php _e('Submit Report', 'bassmah-staff-reports'); ?>
            </a>
        </p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo esc_html($report->report_date); ?></td>
                        <td>
                            <?php 
                            $tasks = json_decode($report->tasks_json, true);
                            echo count($tasks); 
                            ?> <?php _e('tasks', 'bassmah-staff-reports'); ?>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($report->status); ?>">
                                <?php 
                                $status_labels = array(
                                    'submitted' => __('Submitted', 'bassmah-staff-reports'),
                                    'approved' => __('Approved', 'bassmah-staff-reports'),
                                    'rejected' => __('Rejected', 'bassmah-staff-reports')
                                );
                                echo $status_labels[$report->status] ?? $report->status;
                                ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="viewReportDetails(<?php echo $report->id; ?>)">
                                <?php _e('View', 'bassmah-staff-reports'); ?>
                            </button>
                            <?php if ($report->status === 'submitted'): ?>
                                <button type="button" class="button" onclick="editReport(<?php echo $report->id; ?>)">
                                    <?php _e('Edit', 'bassmah-staff-reports'); ?>
                                </button>
                            <?php endif; ?>
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

<script>
function viewReportDetails(reportId) {
    var modal = document.getElementById('bassmah-report-details-modal');
    var content = document.getElementById('bassmah-report-details-content');
    content.innerHTML = '<div class="bassmah-loading"><?php _e('Loading...', 'bassmah-staff-reports'); ?></div>';
    modal.style.display = 'block';

    var params = new URLSearchParams({
        action: 'bassmah_get_my_report_details',
        nonce: '<?php echo wp_create_nonce('bassmah_my_reports_nonce'); ?>',
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

function editReport(reportId) {
    // Redirect to edit page with report ID
    window.location.href = '<?php echo home_url('/report/'); ?>?edit=' + reportId;
}

window.onclick = function(event) {
    var modal = document.getElementById('bassmah-report-details-modal');
    if (event.target === modal) {
        hideReportDetails();
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

    .bassmah-large-modal {
        max-width: 900px;
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

.status-submitted {
    color: #17a2b8;
    font-weight: bold;
}

.status-approved {
    color: #28a745;
    font-weight: bold;
}

.status-rejected {
    color: #dc3545;
    font-weight: bold;
}
</style>
