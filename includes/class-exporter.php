<?php
class Basmah_Staff_Reports_Exporter {
    public static function export_reports_to_csv($args = array()) {
        global $wpdb;
        
        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        
        if (empty($reports)) {
            return false;
        }
        
        $filename = 'staff-reports-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, array(
            'Report ID',
            'Employee Name',
            'Email',
            'Report Date',
            'Status',
            'Submission Time',
            'Tasks',
            'Manager Comment',
            'IP Address'
        ));
        
        // CSV Data
        foreach ($reports as $report) {
            $tasks = json_decode($report['tasks_json'], true);
            $task_summary = '';
            
            if (is_array($tasks)) {
                $task_descriptions = array();
                foreach ($tasks as $task) {
                    $task_descriptions[] = isset($task['task_description']) ? $task['task_description'] : '';
                }
                $task_summary = implode('; ', $task_descriptions);
            }
            
            fputcsv($output, array(
                $report['id'],
                $report['display_name'],
                $report['user_email'],
                $report['report_date'],
                $report['status'],
                $report['submission_time'],
                $task_summary,
                $report['manager_comment'],
                $report['ip_address']
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public static function export_salary_summary($month, $year) {
        global $wpdb;
        
        $users_table = $wpdb->prefix . 'users';
        $usermeta_table = $wpdb->prefix . 'usermeta';
        $capabilities_key = $wpdb->prefix . 'capabilities';
        
        $staff_users = $wpdb->get_results($wpdb->prepare("
            SELECT u.ID, u.display_name, u.user_email
            FROM $users_table u
            JOIN $usermeta_table um ON u.ID = um.user_id
            WHERE um.meta_key = %s
            AND um.meta_value LIKE %s
        ", $capabilities_key, '%basmah_staff%'), ARRAY_A);
        
        if (empty($staff_users)) {
            return false;
        }
        
        $filename = 'salary-summary-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, array(
            'Employee Name',
            'Email',
            'Monthly Salary',
            'Daily Rate',
            'Working Days',
            'Submitted Days',
            'Approved Days',
            'Missing Days',
            'Total Deduction',
            'Net Salary',
            'Currency'
        ));
        
        // CSV Data
        foreach ($staff_users as $user) {
            $salary_data = Basmah_Staff_Reports_Salary::calculate_salary($user['ID'], $month, $year);
            
            fputcsv($output, array(
                $user['display_name'],
                $user['user_email'],
                $salary_data['monthly_salary'],
                $salary_data['daily_rate'],
                $salary_data['working_days'],
                $salary_data['submitted_days'],
                $salary_data['approved_days'],
                $salary_data['missing_days'],
                $salary_data['total_deduction'],
                $salary_data['net_salary'],
                $salary_data['currency']
            ));
        }
        
        fclose($output);
        exit;
    }
}
