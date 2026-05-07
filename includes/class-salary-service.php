<?php
/**
 * Salary Service - Salary Calculation & Management
 *
 * Handles all salary-related operations including
 * calculations, settings management, and audit trail.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Salary_Service {

    /**
     * Database manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     */
    private $database;

    /**
     * Cache manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Cache_Manager    $cache    Cache manager
     */
    private $cache;

    /**
     * Validation manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Validation_Manager    $validation    Validation manager
     */
    private $validation;

    /**
     * Working days service instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Working_Days_Service    $working_days    Working days service
     */
    private $working_days;

    /**
     * Constructor
     *
     * @since    1.0.0
     * @param    Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     * @param    Bassmah_Staff_Reports_Cache_Manager       $cache       Cache manager
     * @param    Bassmah_Staff_Reports_Validation_Manager  $validation  Validation manager
     * @param    Bassmah_Staff_Reports_Working_Days_Service $working_days Working days service
     */
    public function __construct($database, $cache, $validation, $working_days) {
        $this->database = $database;
        $this->cache = $cache;
        $this->validation = $validation;
        $this->working_days = $working_days;
    }

    /**
     * Set salary settings for a user
     *
     * @since    1.0.0
     * @param    array    $data    Salary settings data
     * @return   int|WP_Error
     */
    public function set_salary_settings($data) {
        // Validate input data
        $validation_result = $this->validation->validate_salary_settings($data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        // Check permissions
        $permission_check = $this->check_salary_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Calculate daily rate
        $daily_rate = $data['monthly_salary'] / $data['working_days_per_month'];

        // Prepare salary data
        $salary_data = array(
            'user_id' => $data['user_id'],
            'monthly_salary' => $data['monthly_salary'],
            'working_days_per_month' => $data['working_days_per_month'],
            'currency' => $data['currency'] ?? 'CAD',
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $this->database->start_transaction();
        
        try {
            $salary_id = $this->database->insert('salary_settings', $salary_data);
            
            if (!$salary_id) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to save salary settings.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("salary_settings_{$data['user_id']}", 'salary');
            $this->cache->delete("user_salary_{$data['user_id']}", 'salary');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_salary_settings_created', $salary_id, $salary_data);

            return $salary_id;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get current salary settings for a user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   object|WP_Error
     */
    public function get_salary_settings($user_id) {
        // Check cache first
        $cache_key = "salary_settings_{$user_id}";
        $cached_settings = $this->cache->get($cache_key, 'salary');
        
        if ($cached_settings) {
            return $cached_settings;
        }

        // Get from database
        $settings = $this->database->get_row(
            "SELECT * FROM {$this->database->get_table_name('salary_settings')} 
             WHERE user_id = %d AND effective_from <= %s 
             ORDER BY effective_from DESC LIMIT 1",
            array($user_id, current_time('Y-m-d'))
        );

        if ($settings) {
            // Cache the result
            $this->cache->set($cache_key, $settings, 'salary', 3600);
        }

        return $settings;
    }

    /**
     * Get all salary settings for a user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array
     */
    public function get_all_salary_settings($user_id) {
        return $this->database->get_results(
            "SELECT * FROM {$this->database->get_table_name('salary_settings')} 
             WHERE user_id = %d ORDER BY effective_from DESC",
            array($user_id)
        );
    }

    /**
     * Calculate monthly salary summary
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    string $month      Month in Y-m format
     * @return   array|WP_Error
     */
    public function calculate_monthly_salary($user_id, $month = null) {
        if (!$month) {
            $month = date('Y-m');
        }

        // Get salary settings
        $salary_settings = $this->get_salary_settings($user_id);
        if (!$salary_settings) {
            return new WP_Error(
                'no_salary_settings',
                __('Salary settings not found for this user.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Get working days for the month
        $working_days = $this->working_days->get_working_days_in_month($month);
        $total_working_days = count($working_days);

        // Get submitted reports for the month
        $reports = $this->database->get_results(
            "SELECT COUNT(*) as submitted_days 
             FROM {$this->database->get_table_name('reports')} 
             WHERE user_id = %d AND report_date BETWEEN %s AND %s AND status = 'submitted'",
            array($user_id, $month . '-01', $month . '-31')
        );

        $submitted_days = intval($reports[0]->submitted_days ?? 0);
        $missing_days = $total_working_days - $submitted_days;

        // Calculate deductions
        $daily_rate = $salary_settings->monthly_salary / $salary_settings->working_days_per_month;
        $total_deduction = $missing_days * $daily_rate;
        $net_salary = $salary_settings->monthly_salary - $total_deduction;

        $salary_summary = array(
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
            'attendance_percentage' => $total_working_days > 0 ? round(($submitted_days / $total_working_days) * 100, 2) : 0,
            'settings_id' => $salary_settings->id
        );

        // Save to audit table
        $this->save_salary_audit($salary_summary);

        return $salary_summary;
    }

    /**
     * Get salary summary for multiple users
     *
     * @since    1.0.0
     * @param    array    $user_ids    User IDs
     * @param    string  $month      Month in Y-m format
     * @return   array
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
                $summary['user_role'] = $this->get_user_role_display($user_id);
                $summaries[] = $summary;
            }
        }

        return $summaries;
    }

    /**
     * Get salary history for a user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    int    $limit     Number of months
     * @return   array
     */
    public function get_salary_history($user_id, $limit = 12) {
        return $this->database->get_results(
            "SELECT * FROM {$this->database->get_table_name('salary_audit')} 
             WHERE user_id = %d ORDER BY month_year DESC LIMIT %d",
            array($user_id, $limit)
        );
    }

    /**
     * Get dashboard statistics for a user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array
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

        // Get today's report status
        $today = current_time('Y-m-d');
        $today_report = $this->database->get_row(
            "SELECT id, status FROM {$this->database->get_table_name('reports')} 
             WHERE user_id = %d AND report_date = %s",
            array($user_id, $today)
        );

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
            'can_submit_today' => !$has_submitted_today,
            'today_report_id' => $today_report ? $today_report->id : null,
            'today_report_status' => $today_report ? $today_report->status : null
        );
    }

    /**
     * Update salary settings
     *
     * @since    1.0.0
     * @param    int    $settings_id    Settings ID
     * @param    array  $data           Update data
     * @return   bool|WP_Error
     */
    public function update_salary_settings($settings_id, $data) {
        // Check permissions
        $permission_check = $this->check_salary_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Prepare update data
        $update_data = array('updated_at' => current_time('mysql'));

        if (isset($data['monthly_salary'])) {
            $update_data['monthly_salary'] = $data['monthly_salary'];
        }

        if (isset($data['working_days_per_month'])) {
            $update_data['working_days_per_month'] = $data['working_days_per_month'];
        }

        if (isset($data['currency'])) {
            $update_data['currency'] = $data['currency'];
        }

        if (isset($data['effective_to'])) {
            $update_data['effective_to'] = $data['effective_to'];
        }

        $this->database->start_transaction();
        
        try {
            $result = $this->database->update(
                'salary_settings',
                $update_data,
                array('id' => $settings_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to update salary settings.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $settings = $this->database->get_row(
                "SELECT user_id FROM {$this->database->get_table_name('salary_settings')} WHERE id = %d",
                array($settings_id)
            );
            
            if ($settings) {
                $this->cache->delete("salary_settings_{$settings->user_id}", 'salary');
                $this->cache->delete("user_salary_{$settings->user_id}", 'salary');
            }

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_salary_settings_updated', $settings_id, $update_data);

            return true;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Delete salary settings
     *
     * @since    1.0.0
     * @param    int    $settings_id    Settings ID
     * @return   bool|WP_Error
     */
    public function delete_salary_settings($settings_id) {
        // Check permissions
        $permission_check = $this->check_salary_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Get settings before deletion
        $settings = $this->database->get_row(
            "SELECT user_id FROM {$this->database->get_table_name('salary_settings')} WHERE id = %d",
            array($settings_id)
        );

        if (!$settings) {
            return new WP_Error(
                'settings_not_found',
                __('Salary settings not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        $this->database->start_transaction();
        
        try {
            $result = $this->database->delete(
                'salary_settings',
                array('id' => $settings_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to delete salary settings.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("salary_settings_{$settings->user_id}", 'salary');
            $this->cache->delete("user_salary_{$settings->user_id}", 'salary');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_salary_settings_deleted', $settings_id, $settings);

            return true;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Save salary audit record
     *
     * @since    1.0.0
     * @param    array    $salary_summary    Salary summary data
     * @return   bool
     */
    private function save_salary_audit($salary_summary) {
        $audit_data = array(
            'user_id' => $salary_summary['user_id'],
            'month_year' => $salary_summary['month'],
            'monthly_salary' => $salary_summary['monthly_salary'],
            'daily_rate' => $salary_summary['daily_rate'],
            'total_working_days' => $salary_summary['total_working_days'],
            'submitted_days' => $salary_summary['submitted_days'],
            'missing_days' => $salary_summary['missing_days'],
            'total_deduction' => $salary_summary['total_deduction'],
            'net_salary' => $salary_summary['net_salary'],
            'calculation_date' => current_time('mysql'),
            'calculated_by' => get_current_user_id()
        );

        return $this->database->insert('salary_audit', $audit_data);
    }

    /**
     * Check salary management permission
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    private function check_salary_permission() {
        return current_user_can('bassmah_manage_salary_settings') ?: 
            new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to manage salary settings.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
    }

    /**
     * Get user role display name
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   string
     */
    private function get_user_role_display($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return __('Unknown', 'bassmah-staff-reports');
        }

        if (in_array('bassmah_manager', $user->roles)) {
            return __('Manager', 'bassmah-staff-reports');
        } elseif (in_array('bassmah_staff', $user->roles)) {
            return __('Staff Member', 'bassmah-staff-reports');
        } elseif (in_array('administrator', $user->roles)) {
            return __('Administrator', 'bassmah-staff-reports');
        }

        return __('User', 'bassmah-staff-reports');
    }

    /**
     * Get salary statistics for all users
     *
     * @since    1.0.0
     * @param    string  $month    Month in Y-m format
     * @return   array|WP_Error
     */
    public function get_monthly_statistics($month = null) {
        // Check permissions
        if (!current_user_can('bassmah_view_all_salary')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view salary statistics.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        if (!$month) {
            $month = date('Y-m');
        }

        return $this->database->get_results(
            "SELECT 
                    user_id,
                    monthly_salary,
                    daily_rate,
                    total_working_days,
                    submitted_days,
                    missing_days,
                    total_deduction,
                    net_salary,
                    attendance_percentage,
                    calculation_date
                FROM {$this->database->get_table_name('salary_audit')} 
                WHERE month_year = %s ORDER BY calculation_date DESC",
            array($month)
        );
    }

    /**
     * Export salary data
     *
     * @since    1.0.0
     * @param    array    $user_ids    User IDs
     * @param    string  $month      Month in Y-m format
     * @param    string  $format     Export format
     * @return   string|WP_Error
     */
    public function export_salary_data($user_ids, $month = null, $format = 'csv') {
        if (!$month) {
            $month = date('Y-m');
        }

        $summaries = $this->get_batch_salary_summary($user_ids, $month);

        if ($format === 'csv') {
            return $this->generate_csv_export($summaries);
        } elseif ($format === 'excel') {
            return $this->generate_excel_export($summaries);
        }

        return new WP_Error(
            'unsupported_format',
            __('Export format not supported.', 'bassmah-staff-reports'),
            array('status' => 400)
        );
    }

    /**
     * Generate CSV export
     *
     * @since    1.0.0
     * @param    array    $summaries    Salary summaries
     * @return   string
     */
    private function generate_csv_export($summaries) {
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
     * Generate Excel export
     *
     * @since    1.0.0
     * @param    array    $summaries    Salary summaries
     * @return   string
     */
    private function generate_excel_export($summaries) {
        // This would require PhpSpreadsheet library
        // For now, return CSV format
        return $this->generate_csv_export($summaries);
    }

    /**
     * Recalculate salary for all users
     *
     * @since    1.0.0
     * @param    string  $month    Month in Y-m format
     * @return   bool|WP_Error
     */
    public function recalculate_monthly_salaries($month = null) {
        if (!$month) {
            $month = date('Y-m');
        }

        // Get all staff users
        $staff_users = get_users(array(
            'role__in' => array('bassmah_staff', 'subscriber'),
            'fields' => array('ID')
        ));

        $success_count = 0;
        $error_count = 0;

        foreach ($staff_users as $user) {
            $result = $this->calculate_monthly_salary($user->ID, $month);
            if (!is_wp_error($result)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        do_action('bassmah_salary_recalculated', $month, $success_count, $error_count);

        return array(
            'month' => $month,
            'total_users' => count($staff_users),
            'success_count' => $success_count,
            'error_count' => $error_count
        );
    }
}
