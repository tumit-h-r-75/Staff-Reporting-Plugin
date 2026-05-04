<?php
class Basmah_Staff_Reports_Salary {
    public static function get_settings($user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_salary_settings';
        
        if ($user_id) {
            $settings = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user_id
            ), ARRAY_A);
            
            if (!$settings) {
                return array(
                    'id' => 0,
                    'user_id' => $user_id,
                    'monthly_salary' => 3500.00,
                    'working_days_per_month' => 22,
                    'daily_rate' => 159.09,
                    'currency' => 'CAD',
                    'effective_from' => null,
                    'created_by' => 0,
                    'created_at' => null,
                    'updated_at' => null
                );
            }
            return $settings;
        }
        
        $defaults = array(
            'monthly_salary' => 3500.00,
            'working_days_per_month' => 22,
            'daily_rate' => 159.09,
            'currency' => 'CAD'
        );
        
        return wp_parse_args(get_option('bsr_salary_settings', array()), $defaults);
    }
    
    public static function save_settings($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_salary_settings';
        
        $data['daily_rate'] = round($data['monthly_salary'] / $data['working_days_per_month'], 2);
        $data['updated_at'] = current_time('mysql');
        
        $existing = self::get_settings($data['user_id']);
        
        if ($existing['id'] > 0) {
            return $wpdb->update($table_name, $data, array('user_id' => $data['user_id']));
        } else {
            $data['created_at'] = current_time('mysql');
            $data['created_by'] = get_current_user_id();
            return $wpdb->insert($table_name, $data);
        }
    }
    
    public static function calculate_salary($user_id, $month, $year) {
        $settings = self::get_settings($user_id);
        $working_days = Basmah_Staff_Reports_Working_Days::get_working_days_count($month, $year);
        $submitted_days = Basmah_Staff_Reports_Reports::get_report_count(array(
            'user_id' => $user_id,
            'month' => $month,
            'year' => $year
        ));
        
        $daily_rate = $settings['daily_rate'];
        $monthly_salary = $settings['monthly_salary'];
        
        $missing_days = max(0, $working_days - $submitted_days);
        $total_deduction = $missing_days * $daily_rate;
        $net_salary = max(0, $monthly_salary - $total_deduction);
        
        return array(
            'monthly_salary' => $monthly_salary,
            'working_days' => $working_days,
            'submitted_days' => $submitted_days,
            'missing_days' => $missing_days,
            'daily_rate' => $daily_rate,
            'total_deduction' => $total_deduction,
            'net_salary' => $net_salary,
            'currency' => $settings['currency']
        );
    }
}
