<?php
class Basmah_Staff_Reports_Security {
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    public static function check_permission($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    public static function is_staff() {
        $user = wp_get_current_user();
        return in_array('basmah_staff', (array) $user->roles);
    }
    
    public static function is_manager() {
        $user = wp_get_current_user();
        return in_array('basmah_manager', (array) $user->roles) || current_user_can('manage_options');
    }
}
