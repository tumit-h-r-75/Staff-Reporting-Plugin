<?php
class Basmah_Staff_Reports_Tables {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $staff_reports_table = $wpdb->prefix . 'staff_reports';
        $sql0 = "CREATE TABLE $staff_reports_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            report_date date NOT NULL,
            tasks_json longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY report_date (report_date)
        ) $charset_collate;";
        dbDelta($sql0);
        
        $reports_table = $wpdb->prefix . 'bsr_reports';
        $sql1 = "CREATE TABLE $reports_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            report_date date NOT NULL,
            content text NOT NULL,
            status varchar(50) DEFAULT 'pending',
            manager_comment text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY report_date (report_date)
        ) $charset_collate;";
        dbDelta($sql1);
        
        $salary_settings_table = $wpdb->prefix . 'bsr_salary_settings';
        $sql2 = "CREATE TABLE $salary_settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        dbDelta($sql2);
        
        $working_days_table = $wpdb->prefix . 'bsr_working_days';
        $sql3 = "CREATE TABLE $working_days_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            day_of_week tinyint(1) NOT NULL,
            is_working_day tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql3);
        
        $holidays_table = $wpdb->prefix . 'bsr_holidays';
        $sql4 = "CREATE TABLE $holidays_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            holiday_date date NOT NULL,
            name varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY holiday_date (holiday_date)
        ) $charset_collate;";
        dbDelta($sql4);
        
        update_option('bsr_db_version', '1.0.0');
    }
}
