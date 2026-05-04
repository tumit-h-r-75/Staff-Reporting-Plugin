<?php
class Basmah_Staff_Reports_Roles {
    public static function add_roles() {
        add_role('basmah_staff', 'Basmah Staff', array(
            'read' => true,
            'basmah_submit_report' => true,
            'basmah_view_my_reports' => true,
            'basmah_view_my_salary' => true,
        ));
        
        add_role('basmah_manager', 'Basmah Manager', array(
            'read' => true,
            'edit_posts' => true,
            'basmah_view_all_reports' => true,
            'basmah_edit_reports' => true,
            'basmah_view_all_salaries' => true,
            'basmah_manage_salaries' => true,
            'basmah_manage_staff' => true,
            'basmah_manage_working_days' => true,
            'basmah_export_reports' => true,
        ));
    }
    
    public static function remove_roles() {
        remove_role('basmah_staff');
        remove_role('basmah_manager');
    }
}
