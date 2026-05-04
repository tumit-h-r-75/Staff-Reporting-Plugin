<?php
class Basmah_Staff_Reports_Activator {
    public static function activate() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'database/tables.php';
        Basmah_Staff_Reports_Tables::create_tables();
        
        Basmah_Staff_Reports_Roles::add_roles();
        
        flush_rewrite_rules();
    }
}
