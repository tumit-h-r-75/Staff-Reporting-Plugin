<?php
/**
 * Export Reports Class
 *
 * Handles CSV and Excel export functionality for reports.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

if (!class_exists('Bassmah_Staff_Reports_Exporter')) {
    class Bassmah_Staff_Reports_Exporter {

        /**
         * Export reports to CSV
         *
         * @since    1.0.0
         * @param    array    $filters    Filter criteria
         * @return    string    CSV content
         */
        public function export_reports_to_csv($filters = array()) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'staff_reports';
            $where_clause = "WHERE 1=1";
            $params = array();
            
            if (!empty($filters['date_from'])) {
                $where_clause .= " AND report_date >= %s";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_clause .= " AND report_date <= %s";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['user_id'])) {
                $where_clause .= " AND user_id = %d";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['status'])) {
                $where_clause .= " AND status = %s";
                $params[] = $filters['status'];
            }
            
            $reports = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, u.display_name, u.user_email 
                 FROM {$table_name} r 
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
                 {$where_clause} 
                 ORDER BY r.report_date DESC, r.submission_time DESC",
                $params
            ));
            
            // Generate CSV
            $csv = fopen('php://temp', 'w');
            
            // CSV Headers
            fputcsv($csv, array(
                __('Report ID', 'bassmah-staff-reports'),
                __('Employee Name', 'bassmah-staff-reports'),
                __('Email', 'bassmah-staff-reports'),
                __('Role', 'bassmah-staff-reports'),
                __('Report Date', 'bassmah-staff-reports'),
                __('Submission Time', 'bassmah-staff-reports'),
                __('Status', 'bassmah-staff-reports'),
                __('Tasks', 'bassmah-staff-reports'),
                __('Manager Comment', 'bassmah-staff-reports'),
                __('IP Address', 'bassmah-staff-reports')
            ));
            
            // CSV Data
            foreach ($reports as $report) {
                $tasks = json_decode($report->tasks_json, true);
                $task_descriptions = array();
                
                if (!empty($tasks) && is_array($tasks)) {
                    foreach ($tasks as $task) {
                        $task_descriptions[] = $task['task_category'] . ': ' . $task['task_description'];
                    }
                }
                
                $task_list = implode('; ', $task_descriptions);
                
                fputcsv($csv, array(
                    $report->id,
                    $report->display_name,
                    $report->user_email,
                    Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id),
                    $report->report_date,
                    $report->submission_time,
                    $report->status,
                    $task_list,
                    $report->manager_comment,
                    $report->ip_address
                ));
            }
            
            rewind($csv);
            return stream_get_contents($csv);
        }
        
        /**
         * Export reports to Excel
         *
         * @since    1.0.0
         * @param    array    $filters    Filter criteria
         * @return    string    Excel file content
         */
        public function export_reports_to_excel($filters = array()) {
            global $wpdb;
            
            $table_reports = $wpdb->prefix . 'staff_reports';
            $table_users = $wpdb->users;
            
            // Build query with proper prepared statements
            $where_clause = "1=1";
            $params = array();
            
            if (!empty($filters['date_from'])) {
                $where_clause .= " AND r.report_date >= %s";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where_clause .= " AND r.report_date <= %s";
                $params[] = $filters['date_to'];
            }
            if (!empty($filters['user_id'])) {
                $where_clause .= " AND r.user_id = %d";
                $params[] = intval($filters['user_id']);
            }
            if (!empty($filters['status'])) {
                $where_clause .= " AND r.status = %s";
                $params[] = $filters['status'];
            }
            
            $query = "SELECT r.*, u.display_name, u.user_email 
                     FROM {$table_reports} r 
                     LEFT JOIN {$table_users} u ON r.user_id = u.ID 
                     WHERE {$where_clause}
                     ORDER BY r.report_date DESC, r.submission_time DESC";
            
            $reports = $wpdb->get_results(
                $params ? $wpdb->prepare($query, $params) : $query
            );
            
            // Create Excel file using PHP's built-in functions
            $filename = 'staff_reports_' . date('Y-m-d') . '.xlsx';
            $filepath = tempnam(sys_get_temp_dir(), 'excel_');
            
            // Create a simple Excel file using HTML table format (Excel can open HTML tables)
            $html = '<html><head><meta charset="UTF-8"><title>Staff Reports</title></head><body>';
            $html .= '<table border="1">';
            
            // Headers
            $html .= '<tr>
                <th>ID</th>
                <th>Employee Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Report Date</th>
                <th>Submission Time</th>
                <th>Status</th>
                <th>Tasks</th>
                <th>Manager Comment</th>
                <th>IP Address</th>
            </tr>';
            
            // Data rows
            foreach ($reports as $report) {
                $tasks = json_decode($report->tasks_json, true);
                $task_descriptions = array();
                
                if (!empty($tasks) && is_array($tasks)) {
                    foreach ($tasks as $task) {
                        $task_descriptions[] = $task['task_category'] . ': ' . $task['task_description'];
                    }
                }
                
                $task_list = implode('; ', $task_descriptions);
                
                $html .= '<tr>
                    <td>' . esc_html($report->id) . '</td>
                    <td>' . esc_html($report->display_name) . '</td>
                    <td>' . esc_html($report->user_email) . '</td>
                    <td>' . esc_html(Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id)) . '</td>
                    <td>' . esc_html($report->report_date) . '</td>
                    <td>' . esc_html($report->submission_time) . '</td>
                    <td>' . esc_html($report->status) . '</td>
                    <td>' . esc_html($task_list) . '</td>
                    <td>' . esc_html($report->manager_comment) . '</td>
                    <td>' . esc_html($report->ip_address) . '</td>
                </tr>';
            }
            
            $html .= '</table></body></html>';
            
            // Write to file
            file_put_contents($filepath, $html);
            
            // Read file content
            $content = file_get_contents($filepath);
            
            // Clean up temp file
            unlink($filepath);
            
            return $content;
        }
    }
}
