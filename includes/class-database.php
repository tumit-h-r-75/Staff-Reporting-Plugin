<?php
/**
 * Database Setup Class
 * Creates and manages plugin database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Database {
    
    /**
     * Create all required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Staff Reports Table
        $table_reports = $wpdb->prefix . 'bsr_staff_reports';
        $sql_reports = "CREATE TABLE $table_reports (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            report_date date NOT NULL,
            submission_time datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('draft', 'submitted', 'approved', 'rejected') DEFAULT 'submitted',
            tasks_json longtext DEFAULT NULL,
            manager_comment text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_user_date (user_id, report_date),
            KEY idx_status (status),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Salary Settings Table
        $table_salary = $wpdb->prefix . 'bsr_salary_settings';
        $sql_salary = "CREATE TABLE $table_salary (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            monthly_salary decimal(10,2) DEFAULT 0.00,
            working_days_per_month int DEFAULT 22,
            daily_rate decimal(10,2) DEFAULT 0.00,
            currency varchar(3) DEFAULT 'CAD',
            effective_from date NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_user_effective (user_id, effective_from),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Working Days Table
        $table_working = $wpdb->prefix . 'bsr_working_days';
        $sql_working = "CREATE TABLE $table_working (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            work_date date NOT NULL,
            is_holiday tinyint(1) DEFAULT 0,
            holiday_name varchar(100) DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_date (work_date),
            FOREIGN KEY (created_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_reports);
        dbDelta($sql_salary);
        dbDelta($sql_working);
        
        // Add default working days for current month
        self::add_default_working_days();
    }
    
    /**
     * Drop all plugin tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'bsr_staff_reports',
            $wpdb->prefix . 'bsr_salary_settings',
            $wpdb->prefix . 'bsr_working_days'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Add default working days for current month
     */
    private static function add_default_working_days() {
        global $wpdb;
        
        $table_working = $wpdb->prefix . 'bsr_working_days';
        $current_month = date('Y-m-01');
        
        // Check if working days already exist for current month
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_working WHERE work_date >= %s",
            $current_month
        ));
        
        if ($existing > 0) {
            return;
        }
        
        // Add working days for current month (excluding weekends)
        $year = date('Y');
        $month = date('m');
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_of_week = date('N', strtotime($date));
            
            // Skip weekends (Saturday=6, Sunday=7)
            if ($day_of_week >= 6) {
                continue;
            }
            
            $wpdb->insert(
                $table_working,
                [
                    'work_date' => $date,
                    'is_holiday' => 0,
                    'created_by' => get_current_user_id()
                ],
                ['%s', '%d', '%d']
            );
        }
    }
    
    /**
     * Get working days for a month
     */
    public static function get_working_days($year, $month) {
        global $wpdb;
        
        $table_working = $wpdb->prefix . 'bsr_working_days';
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_working 
             WHERE work_date BETWEEN %s AND %s 
             ORDER BY work_date",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Check if a date is a working day
     */
    public static function is_working_day($date) {
        global $wpdb;
        
        $table_working = $wpdb->prefix . 'bsr_working_days';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT is_holiday FROM $table_working WHERE work_date = %s",
            $date
        ));
        
        return $result !== null ? (int)$result === 0 : false;
    }
}
