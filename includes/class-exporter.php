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
            
            fclose($csv);
            return file_get_contents('php://temp');
        }
        
        /**
         * Export reports to Excel
         *
         * @since    1.0.0
         * @param    array    $filters    Filter criteria
         * @return    string    Excel file content
         */
        public function export_reports_to_excel($filters = array()) {
            // For now, return CSV format (Excel can be added later)
            return $this->export_reports_to_csv($filters);
        }
    }
}
