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
$table_name = $wpdb->prefix . 'bassmah_staff_reports';
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
                                <a href="<?php echo admin_url('admin.php?page=bassmah-report-details&report_id=' . $report->id); ?>" class="button button-small">
                                    <?php _e('View', 'bassmah-staff-reports'); ?>
                                </a>
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

<script>
function addComment(reportId) {
    var comment = prompt('<?php _e('Enter your comment:', 'bassmah-staff-reports'); ?>');
    if (comment) {
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=add_comment&report_id=' + reportId + '&comment=' + encodeURIComponent(comment) + '&_wpnonce=<?php echo wp_create_nonce('add_comment_' . reportId); ?>';
    }
}
</script>

<style>
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
