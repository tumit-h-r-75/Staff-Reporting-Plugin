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
            
            // Check if PhpSpreadsheet is available, fall back to CSV if not
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                // Fall back to CSV export
                return $this->export_reports_to_csv($filters);
            }
            
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
            
            // Create Excel file using PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array(
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
            );
            
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }
            
            // Add data rows
            $row = 2;
            foreach ($reports as $report) {
                $tasks = json_decode($report->tasks_json, true);
                $task_descriptions = array();
                
                if (!empty($tasks) && is_array($tasks)) {
                    foreach ($tasks as $task) {
                        $task_descriptions[] = $task['task_category'] . ': ' . $task['task_description'];
                    }
                }
                
                $task_list = implode('; ', $task_descriptions);
                
                $col = 1;
                $data = array(
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
                );
                
                foreach ($data as $value) {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                    $col++;
                }
                $row++;
            }
            
            // Auto-fit columns
            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            // Write to file
            $filename = 'staff_reports_' . date('Y-m-d_His') . '.xlsx';
            $filepath = tempnam(sys_get_temp_dir(), 'excel_');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);
            
            // Read file content
            $content = file_get_contents($filepath);
            
            // Clean up temp file
            unlink($filepath);
            
            return $content;
        }
    }
}
