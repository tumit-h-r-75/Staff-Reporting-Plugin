<?php
/**
 * Salary Calculator Class
 *
 * Handles salary calculations, deductions, and salary history
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

class Bassmah_Staff_Reports_Salary_Calculator {

    /**
     * Calculate salary for a specific month
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    string $month      Month in Y-m format
     * @return   array
     */
    public static function calculate_monthly_salary($user_id, $month = null) {
        global $wpdb;
        
        if (!$month) {
            $month = date('Y-m');
        }
        
        $month_start = $month . '-01';
        $month_end = date('Y-m-t', strtotime($month_start));
        
        // Get salary settings for this user
        $salary_table = $wpdb->prefix . 'staff_salary_settings';
        $salary_setting = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $salary_table 
             WHERE user_id = %d AND effective_from <= %s 
             ORDER BY effective_from DESC LIMIT 1",
            $user_id, $month_start
        ));
        
        if (!$salary_setting) {
            return array(
                'monthly_salary' => 0,
                'daily_rate' => 0,
                'working_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'holidays' => 0,
                'total_deduction' => 0,
                'net_salary' => 0,
                'currency' => 'CAD'
            );
        }
        
        // Get working days for this month (excluding holidays)
        $working_days_table = $wpdb->prefix . 'staff_working_days';
        $working_days = $wpdb->get_results($wpdb->prepare(
            "SELECT work_date, is_holiday, holiday_name 
             FROM $working_days_table 
             WHERE work_date BETWEEN %s AND %s",
            $month_start, $month_end
        ));
        
        // Count working days (excluding holidays and weekends)
        $total_working_days = 0;
        $holidays = 0;
        $work_dates = array();
        
        foreach ($working_days as $day) {
            $work_dates[$day->work_date] = $day;
            if ($day->is_holiday) {
                $holidays++;
            } else {
                $day_of_week = date('N', strtotime($day->work_date));
                if ($day_of_week <= 5) { // Monday to Friday
                    $total_working_days++;
                }
            }
        }
        
        // If no working days are configured, use default
        if ($total_working_days === 0) {
            $total_working_days = $salary_setting->working_days_per_month;
        }
        
        // Get submitted reports for this month
        $reports_table = $wpdb->prefix . 'staff_reports';
        $submitted_reports = $wpdb->get_results($wpdb->prepare(
            "SELECT report_date, status 
             FROM $reports_table 
             WHERE user_id = %d AND report_date BETWEEN %s AND %s",
            $user_id, $month_start, $month_end
        ));
        
        $present_days = 0;
        $report_dates = array();
        foreach ($submitted_reports as $report) {
            $report_dates[$report->report_date] = $report;
            if (in_array($report->status, array('submitted', 'approved'))) {
                $present_days++;
            }
        }
        
        // Calculate absent days (working days - present days - holidays)
        $absent_days = $total_working_days - $present_days - $holidays;
        $absent_days = max(0, $absent_days);
        
        // Calculate deductions
        $daily_rate = $salary_setting->daily_rate;
        $total_deduction = $absent_days * $daily_rate;
        $net_salary = $salary_setting->monthly_salary - $total_deduction;
        
        return array(
            'monthly_salary' => $salary_setting->monthly_salary,
            'daily_rate' => $daily_rate,
            'working_days' => $total_working_days,
            'present_days' => $present_days,
            'absent_days' => $absent_days,
            'holidays' => $holidays,
            'total_deduction' => $total_deduction,
            'net_salary' => $net_salary,
            'currency' => $salary_setting->currency,
            'salary_setting' => $salary_setting,
            'work_dates' => $work_dates,
            'report_dates' => $report_dates
        );
    }
    
    /**
     * Get salary history for a user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    int    $limit      Number of months to get
     * @return   array
     */
    public static function get_salary_history($user_id, $limit = 6) {
        $history = array();
        
        for ($i = 0; $i < $limit; $i++) {
            $month = date('Y-m', strtotime("-$i months"));
            $calculation = self::calculate_monthly_salary($user_id, $month);
            
            $history[] = array(
                'month' => $month,
                'month_display' => date_i18n('F Y', strtotime($month)),
                'calculation' => $calculation
            );
        }
        
        return $history;
    }
    
    /**
     * Get dashboard stats for salary display
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array
     */
    public static function get_dashboard_stats($user_id) {
        $current_month = self::calculate_monthly_salary($user_id);
        
        // Get current month reports count
        global $wpdb;
        $reports_table = $wpdb->prefix . 'staff_reports';
        $monthly_reports = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $reports_table 
             WHERE user_id = %d AND report_date >= DATE_FORMAT(NOW(), '%%Y-%%m-01')",
            $user_id
        ));
        
        // Calculate attendance rate
        $attendance_rate = 0;
        if ($current_month['working_days'] > 0) {
            $attendance_rate = round(($current_month['present_days'] / $current_month['working_days']) * 100, 1);
        }
        
        return array(
            'monthly_salary' => $current_month['monthly_salary'],
            'daily_rate' => $current_month['daily_rate'],
            'working_days' => $current_month['working_days'],
            'present_days' => $current_month['present_days'],
            'absent_days' => $current_month['absent_days'],
            'total_deduction' => $current_month['total_deduction'],
            'net_salary' => $current_month['net_salary'],
            'currency' => $current_month['currency'],
            'attendance_rate' => $attendance_rate,
            'monthly_reports' => intval($monthly_reports),
            'expected_earnings' => $current_month['net_salary']
        );
    }
    
    /**
     * Generate salary summary report
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    string $from_date   Start date
     * @param    string $to_date     End date
     * @return   array
     */
    public static function generate_salary_summary($user_id, $from_date = null, $to_date = null) {
        if (!$from_date) {
            $from_date = date('Y-m-01');
        }
        if (!$to_date) {
            $to_date = date('Y-m-d');
        }
        
        $summary = array(
            'total_months' => 0,
            'total_gross_salary' => 0,
            'total_deductions' => 0,
            'total_net_salary' => 0,
            'total_working_days' => 0,
            'total_present_days' => 0,
            'total_absent_days' => 0,
            'monthly_breakdown' => array()
        );
        
        $start = new DateTime($from_date);
        $end = new DateTime($to_date);
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($start, $interval, $end);
        
        foreach ($period as $dt) {
            $month = $dt->format('Y-m');
            $calculation = self::calculate_monthly_salary($user_id, $month);
            
            $summary['total_months']++;
            $summary['total_gross_salary'] += $calculation['monthly_salary'];
            $summary['total_deductions'] += $calculation['total_deduction'];
            $summary['total_net_salary'] += $calculation['net_salary'];
            $summary['total_working_days'] += $calculation['working_days'];
            $summary['total_present_days'] += $calculation['present_days'];
            $summary['total_absent_days'] += $calculation['absent_days'];
            
            $summary['monthly_breakdown'][] = array(
                'month' => $month,
                'month_display' => date_i18n('F Y', strtotime($month)),
                'calculation' => $calculation
            );
        }
        
        return $summary;
    }
    
    /**
     * Export salary data to CSV
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    string $from_date   Start date
     * @param    string $to_date     End date
     * @return   void
     */
    public static function export_salary_csv($user_id, $from_date = null, $to_date = null) {
        $summary = self::generate_salary_summary($user_id, $from_date, $to_date);
        
        $filename = 'salary-report-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            __('Month', 'bassmah-staff-reports'),
            __('Gross Salary', 'bassmah-staff-reports'),
            __('Working Days', 'bassmah-staff-reports'),
            __('Present Days', 'bassmah-staff-reports'),
            __('Absent Days', 'bassmah-staff-reports'),
            __('Daily Rate', 'bassmah-staff-reports'),
            __('Total Deduction', 'bassmah-staff-reports'),
            __('Net Salary', 'bassmah-staff-reports'),
            __('Currency', 'bassmah-staff-reports')
        ));
        
        // CSV data
        foreach ($summary['monthly_breakdown'] as $month_data) {
            $calc = $month_data['calculation'];
            fputcsv($output, array(
                $month_data['month_display'],
                $calc['monthly_salary'],
                $calc['working_days'],
                $calc['present_days'],
                $calc['absent_days'],
                $calc['daily_rate'],
                $calc['total_deduction'],
                $calc['net_salary'],
                $calc['currency']
            ));
        }
        
        // Summary row
        fputcsv($output, array(
            __('TOTAL', 'bassmah-staff-reports'),
            $summary['total_gross_salary'],
            $summary['total_working_days'],
            $summary['total_present_days'],
            $summary['total_absent_days'],
            '',
            $summary['total_deductions'],
            $summary['total_net_salary'],
            ''
        ));
        
        fclose($output);
        exit;
    }
}
