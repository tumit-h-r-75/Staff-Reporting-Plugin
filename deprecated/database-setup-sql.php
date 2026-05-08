<?php
/**
 * Database Setup SQL Generator for proffg.pronizam.com
 * 
 * This script generates the exact SQL needed to create all database tables
 * Run this to get the SQL commands for phpMyAdmin
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Database Setup SQL for proffg.pronizam.com ===\n\n";
echo "Database: proffg_main\n";
echo "Prefix: " . $wpdb->prefix . "\n\n";

// Generate SQL for all tables
echo "COPY AND PASTE THESE SQL COMMANDS IN PHPMYADMIN:\n";
echo "===============================================\n\n";

// Table 1: staff_reports
echo "-- 1. Create staff_reports table\n";
echo "CREATE TABLE `" . $wpdb->prefix . "staff_reports` (\n";
echo "  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n";
echo "  `user_id` BIGINT(20) UNSIGNED NOT NULL,\n";
echo "  `report_date` DATE NOT NULL,\n";
echo "  `submission_time` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
echo "  `status` VARCHAR(20) NOT NULL DEFAULT 'submitted',\n";
echo "  `tasks_json` LONGTEXT NOT NULL,\n";
echo "  `manager_comment` TEXT NULL,\n";
echo "  `ip_address` VARCHAR(45) NULL,\n";
echo "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
echo "  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
echo "  PRIMARY KEY (`id`),\n";
echo "  UNIQUE KEY `unique_user_date` (`user_id`, `report_date`),\n";
echo "  KEY `idx_user_id` (`user_id`),\n";
echo "  KEY `idx_report_date` (`report_date`),\n";
echo "  KEY `idx_status` (`status`)\n";
echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

// Table 2: staff_salary_settings
echo "-- 2. Create staff_salary_settings table\n";
echo "CREATE TABLE `" . $wpdb->prefix . "staff_salary_settings` (\n";
echo "  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n";
echo "  `user_id` BIGINT(20) UNSIGNED NOT NULL,\n";
echo "  `monthly_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n";
echo "  `working_days_per_month` INT(11) NOT NULL DEFAULT 22,\n";
echo "  `daily_rate` DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary / working_days_per_month) STORED,\n";
echo "  `currency` VARCHAR(3) NOT NULL DEFAULT 'CAD',\n";
echo "  `effective_from` DATE NOT NULL,\n";
echo "  `created_by` BIGINT(20) UNSIGNED NOT NULL,\n";
echo "  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
echo "  PRIMARY KEY (`id`),\n";
echo "  UNIQUE KEY `unique_user_effective` (`user_id`, `effective_from`),\n";
echo "  KEY `idx_user_id` (`user_id`),\n";
echo "  KEY `idx_created_by` (`created_by`)\n";
echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

// Table 3: staff_working_days
echo "-- 3. Create staff_working_days table\n";
echo "CREATE TABLE `" . $wpdb->prefix . "staff_working_days` (\n";
echo "  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n";
echo "  `work_date` DATE NOT NULL,\n";
echo "  `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,\n";
echo "  `holiday_name` VARCHAR(100) NULL,\n";
echo "  `created_by` BIGINT(20) UNSIGNED NOT NULL,\n";
echo "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
echo "  PRIMARY KEY (`id`),\n";
echo "  UNIQUE KEY `unique_work_date` (`work_date`),\n";
echo "  KEY `idx_is_holiday` (`is_holiday`),\n";
echo "  KEY `idx_created_by` (`created_by`)\n";
echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

// Sample data for working days
echo "-- 4. Insert sample working days for current month\n";
$current_month = date('Y-m');
$days_in_month = date('t');
for ($day = 1; $day <= $days_in_month; $day++) {
    $work_date = date('Y-m-d', mktime(0, 0, 0, date('n'), $day, date('Y')));
    $is_holiday = (date('N', mktime(0, 0, 0, date('n'), $day, date('Y'))) >= 7) ? 1 : 0;
    $holiday_name = $is_holiday ? "'Weekend'" : "NULL";
    
    echo "INSERT INTO `" . $wpdb->prefix . "staff_working_days` ";
    echo "(`work_date`, `is_holiday`, `holiday_name`, `created_by`) VALUES ";
    echo "('$work_date', $is_holiday, $holiday_name, 1);\n";
}
echo "\n";

// Sample salary settings
echo "-- 5. Insert sample salary settings (update user_id as needed)\n";
echo "INSERT INTO `" . $wpdb->prefix . "staff_salary_settings` ";
echo "(`user_id`, `monthly_salary`, `working_days_per_month`, `currency`, `effective_from`, `created_by`) VALUES ";
echo "(1, 3500.00, 22, 'CAD', '" . date('Y-m-01') . "', 1);\n\n";

echo "===============================================\n";
echo "INSTRUCTIONS:\n";
echo "1. Go to phpMyAdmin\n";
echo "2. Select database: proffg_main\n";
echo "3. Click on SQL tab\n";
echo "4. Copy and paste all the SQL commands above\n";
echo "5. Click Go/Execute\n";
echo "6. Verify tables were created\n\n";

echo "After running SQL:\n";
echo "1. Clear WordPress cache: wp cache flush\n";
echo "2. Deactivate and reactivate plugin\n";
echo "3. Test dashboard and report submission\n";

// Also create a direct execution version
echo "\n\n=== OR RUN THIS DIRECT EXECUTION SCRIPT ===\n";
echo "URL: https://proffg.pronizam.com/wp-content/plugins/Staff%20Reporting%20Plugin/database-setup-sql.php?action=execute\n\n";

if (isset($_GET['action']) && $_GET['action'] === 'execute') {
    echo "Executing database setup...\n";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $charset = $wpdb->get_charset_collate();
    
    // Create tables using dbDelta
    $sql1 = "CREATE TABLE `" . $wpdb->prefix . "staff_reports` (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(20) UNSIGNED NOT NULL,
        `report_date` DATE NOT NULL,
        `submission_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `status` VARCHAR(20) NOT NULL DEFAULT 'submitted',
        `tasks_json` LONGTEXT NOT NULL,
        `manager_comment` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_date` (`user_id`, `report_date`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_report_date` (`report_date`),
        KEY `idx_status` (`status`)
    ) $charset;";
    
    $sql2 = "CREATE TABLE `" . $wpdb->prefix . "staff_salary_settings` (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(20) UNSIGNED NOT NULL,
        `monthly_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `working_days_per_month` INT(11) NOT NULL DEFAULT 22,
        `daily_rate` DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary / working_days_per_month) STORED,
        `currency` VARCHAR(3) NOT NULL DEFAULT 'CAD',
        `effective_from` DATE NOT NULL,
        `created_by` BIGINT(20) UNSIGNED NOT NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_effective` (`user_id`, `effective_from`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_created_by` (`created_by`)
    ) $charset;";
    
    $sql3 = "CREATE TABLE `" . $wpdb->prefix . "staff_working_days` (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `work_date` DATE NOT NULL,
        `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,
        `holiday_name` VARCHAR(100) NULL,
        `created_by` BIGINT(20) UNSIGNED NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_work_date` (`work_date`),
        KEY `idx_is_holiday` (`is_holiday`),
        KEY `idx_created_by` (`created_by`)
    ) $charset;";
    
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
    
    echo "Database tables created successfully!\n";
    
    // Add sample data
    for ($day = 1; $day <= $days_in_month; $day++) {
        $work_date = date('Y-m-d', mktime(0, 0, 0, date('n'), $day, date('Y')));
        $is_holiday = (date('N', mktime(0, 0, 0, date('n'), $day, date('Y'))) >= 7) ? 1 : 0;
        $holiday_name = $is_holiday ? 'Weekend' : null;
        
        $wpdb->insert(
            $wpdb->prefix . 'staff_working_days',
            array(
                'work_date' => $work_date,
                'is_holiday' => $is_holiday,
                'holiday_name' => $holiday_name,
                'created_by' => 1
            ),
            array('%s', '%d', '%s', '%d')
        );
    }
    
    echo "Sample working days added!\n";
    
    // Add sample salary
    $wpdb->insert(
        $wpdb->prefix . 'staff_salary_settings',
        array(
            'user_id' => 1,
            'monthly_salary' => 3500.00,
            'working_days_per_month' => 22,
            'currency' => 'CAD',
            'effective_from' => date('Y-m-01'),
            'created_by' => 1
        ),
        array('%d', '%f', '%d', '%s', '%s', '%d')
    );
    
    echo "Sample salary settings added!\n";
    echo "Setup complete! Now test your plugin.\n";
}
?>
