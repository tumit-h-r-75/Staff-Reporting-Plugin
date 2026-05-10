<?php
/**
 * Handle salary calculations and management.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Salary {

    /**
     * Table name for salary settings
     *
     * @var string
     */
    private $salary_table;

    /**
     * Table name for working days
     *
     * @var string
     */
    private $working_days_table;

    /**
     * Report class instance
     *
     * @var Bassmah_Staff_Reports_Report
     */
    private $report_class;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->salary_table = $wpdb->prefix . 'staff_salary_settings';
        $this->working_days_table = $wpdb->prefix . 'staff_working_days';
        $this->report_class = new Bassmah_Staff_Reports_Report();
    }

    /**
     * Set salary settings for a user
     *
     * @param int $user_id
     * @param array $salary_data
     * @return bool|WP_Error
     */
    public function set_salary_settings($user_id, $salary_data) {
        global $wpdb;

        if (!Bassmah_Staff_Reports_Roles::can_manage_salary_settings()) {
            return new WP_Error(
                'permission_denied',
                __('You do not have permission to manage salary settings.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        // Validate required fields
        $required_fields = array('monthly_salary', 'working_days_per_month', 'effective_from');
        foreach ($required_fields as $field) {
            if (!isset($salary_data[$field]) || empty($salary_data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Missing required field: %s', 'bassmah-staff-reports'), $field),
                    array('status' => 400)
                );
            }
        }

        // Calculate daily_rate from monthly_salary and working_days_per_month
        $monthly_salary = floatval($salary_data['monthly_salary']);
        $working_days = intval($salary_data['working_days_per_month']);
        $daily_rate = $working_days > 0 ? round($monthly_salary / $working_days, 2) : 0.00;
        
        $settings = array(
            'user_id' => intval($user_id),
            'monthly_salary' => $monthly_salary,
            'working_days_per_month' => $working_days,
            'daily_rate' => $daily_rate,
            'currency' => isset($salary_data['currency']) ? $salary_data['currency'] : 'CAD',
            'effective_from' => $salary_data['effective_from'],
            'created_by' => get_current_user_id(),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->salary_table, $settings, array('%d', '%f', '%d', '%f', '%s', '%s', '%d', '%s'));

        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Failed to save salary settings.', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }

        return true;
    }

    /**
     * Get current salary settings for a user
     *
     * @param int $user_id
     * @return object|null
     */
    public function get_salary_settings($user_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->salary_table} 
             WHERE user_id = %d AND effective_from <= %s 
             ORDER BY effective_from DESC LIMIT 1",
            $user_id,
            current_time('Y-m-d')
        ));
    }

    /**
     * Get all salary settings for a user
     *
     * @param int $user_id
     * @return array
     */
    public function get_all_salary_settings($user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->salary_table} 
             WHERE user_id = %d 
             ORDER BY effective_from DESC",
            $user_id
        ));
    }

    /**
     * Calculate monthly salary summary
     *
     * @param int $user_id
     * @param string $month Year-month format (YYYY-MM)
     * @return array|WP_Error
     */
    public function calculate_monthly_salary($user_id, $month = null) {
        if (!$month) {
            $month = date('Y-m');
        }

        $salary_settings = $this->get_salary_settings($user_id);
        if (!$salary_settings) {
            return new WP_Error(
                'no_salary_settings',
                __('Salary settings not found for this user.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Get working days for the month
        $working_days = $this->get_working_days_in_month($month);
        $total_working_days = count($working_days);

        // Get submitted reports for the month
        $reports = $this->report_class->get_reports(array(
            'user_id' => $user_id,
            'date_from' => $month . '-01',
            'date_to' => $month . '-31'
        ));

        $submitted_days = count($reports);
        $missing_days = $total_working_days - $submitted_days;

        // Calculate deductions
        $daily_rate = $salary_settings->monthly_salary / $salary_settings->working_days_per_month;
        $total_deduction = $missing_days * $daily_rate;
        $net_salary = $salary_settings->monthly_salary - $total_deduction;

        return array(
            'user_id' => $user_id,
            'month' => $month,
            'monthly_salary' => $salary_settings->monthly_salary,
            'daily_rate' => $daily_rate,
            'currency' => $salary_settings->currency,
            'total_working_days' => $total_working_days,
            'submitted_days' => $submitted_days,
            'missing_days' => $missing_days,
            'total_deduction' => $total_deduction,
            'net_salary' => $net_salary,
            'attendance_percentage' => $total_working_days > 0 ? round(($submitted_days / $total_working_days) * 100, 2) : 0
        );
    }

    /**
     * Get salary summary for multiple users
     *
     * @param array $user_ids
     * @param string $month
     * @return array
     */
    public function get_batch_salary_summary($user_ids, $month = null) {
        if (!$month) {
            $month = date('Y-m');
        }

        $summaries = array();
        foreach ($user_ids as $user_id) {
            $summary = $this->calculate_monthly_salary($user_id, $month);
            if (!is_wp_error($summary)) {
                $user = get_userdata($user_id);
                $summary['user_name'] = $user ? $user->display_name : 'Unknown';
                $summary['user_email'] = $user ? $user->user_email : '';
                $summary['user_role'] = Bassmah_Staff_Reports_Roles::get_user_role_display($user_id);
                $summaries[] = $summary;
            }
        }

        return $summaries;
    }

    /**
     * Get working days in a month
     *
     * @param string $month Year-month format (YYYY-MM)
     * @return array Array of dates that are working days
     */
    public function get_working_days_in_month($month) {
        global $wpdb;

        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // Get all days in the month
        $working_days = $wpdb->get_col($wpdb->prepare(
            "SELECT work_date FROM {$this->working_days_table} 
             WHERE work_date BETWEEN %s AND %s AND is_holiday = 0 
             ORDER BY work_date",
            $start_date,
            $end_date
        ));

        // If no working days are configured, assume weekdays are working days
        if (empty($working_days)) {
            $working_days = $this->generate_default_working_days($month);
        }

        return $working_days;
    }

    /**
     * Generate default working days for a month when no configuration exists
     *
     * @param string $month
     * @return array
     */
    private function generate_default_working_days($month) {
        $working_days = array();
        $start_date = new DateTime($month . '-01');
        $end_date = new DateTime(date('Y-m-t', strtotime($month . '-01')));

        // Get holidays for the month
        $holidays = $this->get_holidays_in_month($month);

        while ($start_date <= $end_date) {
            $day_of_week = $start_date->format('N'); // 1 (Monday) to 7 (Sunday)
            $current_date = $start_date->format('Y-m-d');

            // Include Monday to Friday that are not holidays
            if ($day_of_week <= 5 && !in_array($current_date, $holidays)) {
                $working_days[] = $current_date;
            }
            $start_date->add(new DateInterval('P1D'));
        }

        return $working_days;
    }

    /**
     * Get holidays for a month
     *
     * @param string $month
     * @return array
     */
    private function get_holidays_in_month($month) {
        global $wpdb;
        
        $table_working_days = $wpdb->prefix . 'staff_working_days';
        $holidays = array();
        
        $query = $wpdb->prepare("
            SELECT work_date FROM $table_working_days 
            WHERE work_date LIKE %s 
            AND is_holiday = 1
        ", $month . '-%');
        
        $results = $wpdb->get_col($query);
        
        return $results ?: array();
    }

    /**
     * Add working day
     *
     * @param array $data
     * @return bool|WP_Error
     */
    public function add_working_day($data) {
        global $wpdb;

        if (!Bassmah_Staff_Reports_Roles::can_manage_working_days()) {
            return new WP_Error(
                'permission_denied',
                __('You do not have permission to manage working days.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $required_fields = array('work_date', 'is_holiday');
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Missing required field: %s', 'bassmah-staff-reports'), $field),
                    array('status' => 400)
                );
            }
        }

        $working_day = array(
            'work_date' => $data['work_date'],
            'is_holiday' => intval($data['is_holiday']),
            'holiday_name' => isset($data['holiday_name']) ? $data['holiday_name'] : null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->working_days_table, $working_day, array('%s', '%d', '%s', '%d', '%s'));

        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Failed to add working day.', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }

        return true;
    }

    /**
     * Get working days for a date range
     *
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public function get_working_days_range($date_from, $date_to) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->working_days_table} 
             WHERE work_date BETWEEN %s AND %s 
             ORDER BY work_date",
            $date_from,
            $date_to
        ));
    }

    /**
     * Get salary history for a user
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_salary_history($user_id, $limit = 12) {
        $history = array();
        
        // Get last N months
        for ($i = 0; $i < $limit; $i++) {
            $month = date('Y-m', strtotime("-$i months"));
            $summary = $this->calculate_monthly_salary($user_id, $month);
            
            if (!is_wp_error($summary)) {
                $history[] = $summary;
            }
        }

        return $history;
    }

    /**
     * Export salary data to CSV
     *
     * @param array $user_ids
     * @param string $month
     * @return string CSV content
     */
    public function export_salary_csv($user_ids, $month = null) {
        if (!$month) {
            $month = date('Y-m');
        }

        $summaries = $this->get_batch_salary_summary($user_ids, $month);

        $csv = "User Name,Email,Role,Monthly Salary,Daily Rate,Working Days,Submitted Days,Missing Days,Deduction,Net Salary,Attendance %\n";

        foreach ($summaries as $summary) {
            $csv .= sprintf(
                "%s,%s,%s,%.2f,%.2f,%d,%d,%d,%.2f,%.2f,%.2f%%\n",
                $summary['user_name'],
                $summary['user_email'],
                $summary['user_role'],
                $summary['monthly_salary'],
                $summary['daily_rate'],
                $summary['total_working_days'],
                $summary['submitted_days'],
                $summary['missing_days'],
                $summary['total_deduction'],
                $summary['net_salary'],
                $summary['attendance_percentage']
            );
        }

        return $csv;
    }

    /**
     * Get dashboard statistics for a user
     *
     * @param int $user_id
     * @return array
     */
    public function get_dashboard_stats($user_id) {
        $current_month = date('Y-m');
        $salary_summary = $this->calculate_monthly_salary($user_id, $current_month);
        
        if (is_wp_error($salary_summary)) {
            return array(
                'has_salary_settings' => false,
                'message' => $salary_summary->get_error_message()
            );
        }

        $today_report = $this->report_class->get_today_report();
        $has_submitted_today = !empty($today_report);

        return array(
            'has_salary_settings' => true,
            'current_month' => $current_month,
            'monthly_salary' => $salary_summary['monthly_salary'],
            'daily_rate' => $salary_summary['daily_rate'],
            'currency' => $salary_summary['currency'],
            'total_working_days' => $salary_summary['total_working_days'],
            'submitted_days' => $salary_summary['submitted_days'],
            'missing_days' => $salary_summary['missing_days'],
            'total_deduction' => $salary_summary['total_deduction'],
            'net_salary' => $salary_summary['net_salary'],
            'attendance_percentage' => $salary_summary['attendance_percentage'],
            'has_submitted_today' => $has_submitted_today,
            'can_submit_today' => !$has_submitted_today
        );
    }
}
