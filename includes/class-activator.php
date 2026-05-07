<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        
        // Define table names with WordPress prefix
        $table_reports = $wpdb->prefix . 'staff_reports';
        $table_salary_settings = $wpdb->prefix . 'staff_salary_settings';
        $table_working_days = $wpdb->prefix . 'staff_working_days';
        
        // Set charset
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create staff_reports table
        $sql_reports = "CREATE TABLE {$wpdb->prefix}bassmah_staff_reports (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            report_date DATE NOT NULL,
            submission_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) NOT NULL DEFAULT 'submitted',
            tasks_json LONGTEXT NOT NULL,
            manager_comment TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_date (user_id, report_date),
            KEY idx_user_id (user_id),
            KEY idx_report_date (report_date),
            KEY idx_status (status)
        ) $charset_collate;";
        
        // Create staff_salary_settings table
        $sql_salary = "CREATE TABLE $table_salary_settings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            monthly_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            working_days_per_month INT(11) NOT NULL DEFAULT 22,
            daily_rate DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary / working_days_per_month) STORED,
            currency VARCHAR(3) NOT NULL DEFAULT 'CAD',
            effective_from DATE NOT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_effective (user_id, effective_from),
            KEY idx_user_id (user_id),
            KEY idx_created_by (created_by)
        ) $charset_collate;";
        
        // Create staff_working_days table
        $sql_working_days = "CREATE TABLE $table_working_days (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            work_date DATE NOT NULL,
            is_holiday TINYINT(1) NOT NULL DEFAULT 0,
            holiday_name VARCHAR(100) NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_work_date (work_date),
            KEY idx_is_holiday (is_holiday),
            KEY idx_created_by (created_by)
        ) $charset_collate;";
        
        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create tables
        dbDelta($sql_reports);
        dbDelta($sql_salary);
        dbDelta($sql_working_days);
        
        // Create custom user roles
        self::create_user_roles();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create custom user roles for the plugin
     */
    private static function create_user_roles() {
        // Remove roles first to avoid conflicts if they exist
        remove_role('bassmah_staff');
        remove_role('bassmah_manager');
        
        // Create Staff role
        add_role('bassmah_staff', __('Staff Member', 'bassmah-staff-reports'), array(
            'read' => true,
            'bassmah_submit_reports' => true,
            'bassmah_view_own_reports' => true,
            'bassmah_view_own_salary' => true,
        ));
        
        // Create Manager role
        add_role('bassmah_manager', __('Report Manager', 'bassmah-staff-reports'), array(
            'read' => true,
            'bassmah_submit_reports' => true,
            'bassmah_view_all_reports' => true,
            'bassmah_view_own_reports' => true,
            'bassmah_comment_reports' => true,
            'bassmah_export_reports' => true,
            'bassmah_view_all_salary' => true,
            'bassmah_view_own_salary' => true,
            'bassmah_manage_salary_settings' => true,
            'bassmah_manage_working_days' => true,
            'bassmah_manage_staff' => true,
        ));
        
        // Add capabilities to Administrator role
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('bassmah_submit_reports');
            $admin->add_cap('bassmah_view_all_reports');
            $admin->add_cap('bassmah_view_own_reports');
            $admin->add_cap('bassmah_comment_reports');
            $admin->add_cap('bassmah_export_reports');
            $admin->add_cap('bassmah_view_all_salary');
            $admin->add_cap('bassmah_view_own_salary');
            $admin->add_cap('bassmah_manage_salary_settings');
            $admin->add_cap('bassmah_manage_working_days');
            $admin->add_cap('bassmah_manage_staff');
        }
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = array(
            'bassmah_task_categories' => array(
                'Client Follow-up',
                'Documentation',
                'Meeting',
                'Development',
                'Testing',
                'Research',
                'Training',
                'Administrative',
                'Other'
            ),
            'bassmah_task_statuses' => array(
                'completed' => __('Completed', 'bassmah-staff-reports'),
                'in_progress' => __('In Progress', 'bassmah-staff-reports'),
                'not_completed' => __('Not Completed', 'bassmah-staff-reports')
            ),
            'bassmah_email_notifications' => array(
                'report_submitted' => true,
                'daily_reminder' => true,
                'manager_comment' => true
            ),
            'bassmah_default_currency' => 'CAD',
            'bassmah_default_working_days' => 22,
            'bassmah_reminder_time' => '17:00',
            'bassmah_version' => BASSMAH_STAFF_REPORTS_VERSION
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}
