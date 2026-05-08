<?php
/**
 * Working Admin Class with Fixed CSV Export
 * This is a replacement for the problematic admin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bassmah_Staff_Reports_Admin_Working {

    private $plugin_name;
    private $version;

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_name = BASSMAH_STAFF_REPORTS_PLUGIN_NAME;
        $this->version = BASSMAH_STAFF_REPORTS_VERSION;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'admin/css/admin-style.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'admin/js/admin-scripts.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * Add plugin admin menu
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('Staff Reports', 'bassmah-staff-reports'),
            __('Manage Staff Reports', 'bassmah-staff-reports'),
            'bassmah_manage_reports',
            array($this, 'display_all_reports_page'),
            'dashicons-admin-users',
            6
        );

        // Reports submenu
        add_submenu_page(
            'bassmah_manage_reports',
            __('All Reports', 'bassmah-staff-reports'),
            'bassmah-all-reports',
            array($this, 'display_all_reports_page'),
            'dashicons-list-view'
        );

        // Salary Settings submenu
        add_submenu_page(
            'bassmah_manage_reports',
            __('Salary Settings', 'bassmah-staff-reports'),
            'bassmah-salary-settings',
            array($this, 'display_salary_settings_page'),
            'dashicons-admin-generic'
        );

        // Working Days submenu
        add_submenu_page(
            'bassmah_manage_reports',
            __('Working Days', 'bassmah-staff-reports'),
            'bassmah-working-days',
            array($this, 'display_working_days_page'),
            'dashicons-calendar'
        );
    }

    /**
     * Display all reports page
     */
    public function display_all_reports_page() {
        global $wpdb;
        
        $reports_table = $wpdb->prefix . 'staff_reports';
        $reports = $wpdb->get_results("SELECT * FROM $reports_table ORDER BY report_date DESC");
        
        ?>
        <div class="wrap">
            <div class="bassmah-admin-header">
                <h1><?php _e('All Staff Reports', 'bassmah-staff-reports'); ?></h1>
                <div class="bassmah-admin-actions">
                    <button type="button" class="button button-primary" onclick="exportReports()">
                        <i class="dashicons-download"></i>
                        <?php _e('Export CSV', 'bassmah-staff-reports'); ?>
                    </button>
                    <button type="button" class="button" onclick="refreshReports()">
                        <i class="dashicons-update"></i>
                        <?php _e('Refresh', 'bassmah-staff-reports'); ?>
                    </button>
                </div>
            </div>
            
            <div class="bassmah-admin-filters">
                <h2><?php _e('Filters', 'bassmah-staff-reports'); ?></h2>
                <form method="get" class="bassmah-filter-form">
                    <div class="bassmah-filter-row">
                        <label for="filter_status"><?php _e('Status:', 'bassmah-staff-reports'); ?></label>
                        <select name="filter_status" id="filter_status">
                            <option value=""><?php _e('All', 'bassmah-staff-reports'); ?></option>
                            <option value="submitted"><?php _e('Submitted', 'bassmah-staff-reports'); ?></option>
                            <option value="approved"><?php _e('Approved', 'bassmah-staff-reports'); ?></option>
                            <option value="rejected"><?php _e('Rejected', 'bassmah-staff-reports'); ?></option>
                        </select>
                    </div>
                    <div class="bassmah-filter-row">
                        <label for="filter_user"><?php _e('Staff:', 'bassmah-staff-reports'); ?></label>
                        <select name="filter_user" id="filter_user">
                            <option value=""><?php _e('All', 'bassmah-staff-reports'); ?></option>
                            <?php 
                            $users = get_users(array('fields' => array('ID', 'display_name')));
                            foreach ($users as $user) {
                                echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="bassmah-filter-row">
                        <label for="filter_date_from"><?php _e('From:', 'bassmah-staff-reports'); ?></label>
                        <input type="date" name="filter_date_from" id="filter_date_from" class="regular-text">
                    </div>
                    <div class="bassmah-filter-row">
                        <label for="filter_date_to"><?php _e('To:', 'bassmah-staff-reports'); ?></label>
                        <input type="date" name="filter_date_to" id="filter_date_to" class="regular-text">
                    </div>
                    <div class="bassmah-filter-actions">
                        <button type="submit" class="button button-secondary">
                            <?php _e('Apply Filters', 'bassmah-staff-reports'); ?>
                        </button>
                        <button type="button" class="button" onclick="clearFilters()">
                            <?php _e('Clear', 'bassmah-staff-reports'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="bassmah-admin-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Date', 'bassmah-staff-reports'); ?></th>
                            <th scope="col"><?php _e('Staff', 'bassmah-staff-reports'); ?></th>
                            <th scope="col"><?php _e('Status', 'bassmah-staff-reports'); ?></th>
                            <th scope="col"><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                            <th scope="col"><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr class="bassmah-report-row" data-status="<?php echo esc_attr($report->status); ?>">
                                <td data-label="<?php _e('Date', 'bassmah-staff-reports'); ?>"><?php echo esc_html($report->report_date); ?></td>
                                <td data-label="<?php _e('Staff', 'bassmah-staff-reports'); ?>"><?php echo esc_html($report->user_display_name); ?></td>
                                <td data-label="<?php _e('Status', 'bassmah-staff-reports'); ?>">
                                    <span class="bassmah-status bassmah-status-<?php echo esc_attr($report->status); ?>">
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
                                <td data-label="<?php _e('Tasks', 'bassmah-staff-reports'); ?>">
                                    <div class="bassmah-task-summary">
                                        <?php 
                                            $tasks = json_decode($report->tasks_json, true);
                                            if (!empty($tasks)) {
                                                echo '<strong>' . count($tasks) . ' tasks</strong><br>';
                                                foreach ($tasks as $task) {
                                                    echo '<span class="bassmah-task-item">' . esc_html($task['task_category']) . ': ' . esc_html($task['task_description']) . '</span><br>';
                                                }
                                            } else {
                                                echo '<em>' . __('No tasks', 'bassmah-staff-reports') . '</em>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td data-label="<?php _e('Actions', 'bassmah-staff-reports'); ?>">
                                    <div class="bassmah-action-buttons">
                                        <button type="button" class="button button-small bassmah-view-btn" onclick="viewReport(<?php echo $report->id; ?>)">
                                            <i class="dashicons-visibility"></i>
                                            <?php _e('View', 'bassmah-staff-reports'); ?>
                                        </button>
                                        <?php if ($report->status === 'submitted'): ?>
                                            <button type="button" class="button button-small bassmah-approve-btn" onclick="approveReport(<?php echo $report->id; ?>)">
                                                <i class="dashicons-yes-alt"></i>
                                                <?php _e('Approve', 'bassmah-staff-reports'); ?>
                                            </button>
                                            <button type="button" class="button button-small bassmah-reject-btn" onclick="rejectReport(<?php echo $report->id; ?>)">
                                                <i class="dashicons-no-alt"></i>
                                                <i class="dashicons-dismiss"></i>
                                                <?php _e('Reject', 'bassmah-staff-reports'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Export manager reports to CSV with working implementation
     */
    public function export_manager_reports() {
        if (!current_user_can('bassmah_export_reports')) {
            wp_send_json_error(__('You do not have permission to export reports.', 'bassmah-staff-reports'));
        }
        
        $filename = 'manager-reports-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        $csv_headers = array(
            'Report Date',
            'Employee Name', 
            'Email',
            'Role',
            'Status',
            'Submission Time',
            'Tasks',
            'Manager Comment'
        );
        
        // Write headers
        fputcsv($output, $csv_headers);
        
        global $wpdb;
        $reports_table = $wpdb->prefix . 'staff_reports';
        $reports = $wpdb->get_results("SELECT r.*, u.display_name, u.user_email FROM $reports_table r LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID WHERE 1=1 ORDER BY r.report_date DESC");
        
        foreach ($reports as $report) {
            $user = get_userdata($report->user_id);
            $tasks = json_decode($report->tasks_json, true);
            $task_descriptions = array();
            
            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $task_descriptions[] = $task['task_category'] . ': ' . $task['task_description'];
                }
            }
            
            $task_list = implode('; ', $task_descriptions);
            
            // CSV data row
            $csv_row = array(
                $report->report_date,
                $user ? $user->display_name : 'Unknown',
                $user ? $user->user_email : 'Unknown',
                'Staff Member',
                $report->status,
                $report->submission_time,
                $task_list,
                $report->manager_comment ?? ''
            );
            
            // Write CSV row
            fputcsv($output, $csv_row);
        }
        
        fclose($output);
        exit;
    }
}
?>
