<?php
class Basmah_Staff_Reports_Helper {
    public static function format_date($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }
    
    public static function get_staff_members() {
        return get_users(array('role' => 'basmah_staff'));
    }
}
