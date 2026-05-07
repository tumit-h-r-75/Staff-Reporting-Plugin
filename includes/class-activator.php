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
        $sql_reports = "CREATE TABLE {$wpdb->prefix}staff_reports (
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
        
        // Debug: Log table creation attempts
        error_log('Bassmah Plugin: Creating tables with prefix: ' . $wpdb->prefix);
        error_log('Bassmah Plugin: Reports table name: ' . $wpdb->prefix . 'staff_reports');
        error_log('Bassmah Plugin: Salary table name: ' . $table_salary_settings);
        error_log('Bassmah Plugin: Working days table name: ' . $table_working_days);
        
        // Create tables using direct query for better reliability
        error_log('Bassmah Plugin: Attempting to create staff_reports table');
        $result_reports = $wpdb->query($sql_reports);
        error_log('Bassmah Plugin: staff_reports creation result: ' . ($result_reports ? 'SUCCESS' : 'FAILED'));
        
        error_log('Bassmah Plugin: Attempting to create staff_salary_settings table');
        $result_salary = $wpdb->query($sql_salary);
        error_log('Bassmah Plugin: staff_salary_settings creation result: ' . ($result_salary ? 'SUCCESS' : 'FAILED'));
        
        error_log('Bassmah Plugin: Attempting to create staff_working_days table');
        $result_working_days = $wpdb->query($sql_working_days);
        error_log('Bassmah Plugin: staff_working_days creation result: ' . ($result_working_days ? 'SUCCESS' : 'FAILED'));
        
        // Alternative: Try dbDelta if direct query fails
        if (!$result_reports || !$result_salary || !$result_working_days) {
            error_log('Bassmah Plugin: Direct query failed, trying dbDelta');
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            $result_reports = dbDelta($sql_reports);
            $result_salary = dbDelta($sql_salary);
            $result_working_days = dbDelta($sql_working_days);
        }
        
        // Verify tables exist after creation
        $staff_reports_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "staff_reports'");
        error_log('Bassmah Plugin: staff_reports table exists: ' . ($staff_reports_exists ? 'YES' : 'NO'));
        
        $staff_salary_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "staff_salary_settings'");
        error_log('Bassmah Plugin: staff_salary_settings table exists: ' . ($staff_salary_exists ? 'YES' : 'NO'));
        
        $staff_working_days_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "staff_working_days'");
        error_log('Bassmah Plugin: staff_working_days table exists: ' . ($staff_working_days_exists ? 'YES' : 'NO'));
        
        // Check if all tables were created successfully
        $all_tables_created = $staff_reports_exists && $staff_salary_exists && $staff_working_days_exists;
        
        if (!$all_tables_created) {
            error_log('Bassmah Plugin: ERROR - Some tables failed to create');
            wp_die(__('Database tables could not be created. Please check database permissions and try again.', 'bassmah-staff-reports'));
        }
        
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
