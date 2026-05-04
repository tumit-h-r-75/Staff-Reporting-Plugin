<?php
class Basmah_Staff_Reports_Tables {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Staff Reports Table
        $staff_reports_table = $wpdb->prefix . 'staff_reports';
        $sql1 = "CREATE TABLE $staff_reports_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            report_date date NOT NULL,
            tasks_json longtext,
            status varchar(50) DEFAULT 'pending',
            manager_comment text,
            submission_time datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_user_date (user_id, report_date),
            KEY user_id (user_id),
            KEY report_date (report_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql1);
        
        // Staff Salary Settings Table
        $salary_settings_table = $wpdb->prefix . 'staff_salary_settings';
        $sql2 = "CREATE TABLE $salary_settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            monthly_salary decimal(10,2) NOT NULL DEFAULT 0.00,
            working_days_per_month int NOT NULL DEFAULT 22,
            daily_rate decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) DEFAULT 'CAD',
            effective_from date,
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_user (user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql2);
        
        // Working Days & Holidays Table
        $working_days_table = $wpdb->prefix . 'staff_working_days';
        $sql3 = "CREATE TABLE $working_days_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            work_date date NOT NULL,
            is_holiday tinyint(1) DEFAULT 0,
            holiday_name varchar(100),
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_date (work_date),
            KEY work_date (work_date),
            KEY is_holiday (is_holiday)
        ) $charset_collate;";
        dbDelta($sql3);
        
        update_option('bsr_db_version', '2.0.0');
    }
}
