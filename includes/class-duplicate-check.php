<?php
/**
 * Duplicate Report Prevention
 *
 * Prevents users from submitting multiple reports for the same day.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

if (!class_exists('Bassmah_Staff_Reports_Duplicate_Check')) {
    class Bassmah_Staff_Reports_Duplicate_Check {

        /**
         * Check if user has already submitted a report for today
         *
         * @since    1.0.0
         * @param    int    $user_id    User ID
         * @return   bool    True if duplicate exists
         */
        public function has_today_report($user_id) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'staff_reports';
            $today = date('Y-m-d');
            
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                 WHERE user_id = %d AND report_date = %s",
                $user_id,
                $today
            ));
            
            return $count > 0;
        }

        /**
         * Get today's report if exists
         *
         * @since    1.0.0
         * @param    int    $user_id    User ID
         * @return   object|null    Report object or null
         */
        public function get_today_report($user_id) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'staff_reports';
            $today = date('Y-m-d');
            
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} 
                 WHERE user_id = %d AND report_date = %s",
                $user_id,
                $today
            ));
        }
    }
}
