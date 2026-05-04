<?php
class Basmah_Staff_Reports_Roles {
    public static function add_roles() {
        $staff_capabilities = self::get_staff_capabilities();
        $manager_capabilities = self::get_manager_capabilities();

        add_role('basmah_staff', 'Basmah Staff', $staff_capabilities);
        add_role('basmah_manager', 'Basmah Manager', $manager_capabilities);

        self::sync_role_capabilities('basmah_staff', $staff_capabilities);
        self::sync_role_capabilities('basmah_manager', $manager_capabilities);
        self::sync_power_user_capabilities();
    }

    public static function sync_capabilities() {
        self::sync_role_capabilities('basmah_staff', self::get_staff_capabilities());
        self::sync_role_capabilities('basmah_manager', self::get_manager_capabilities());
        self::sync_power_user_capabilities();
        update_option('bsr_capabilities_synced_version', BASMAH_STAFF_REPORTS_VERSION);
    }

    public static function get_staff_capabilities() {
        return array(
            'read' => true,
            'basmah_submit_report' => true,
            'basmah_view_my_reports' => true,
            'basmah_view_my_salary' => true,
        );
    }

    public static function get_manager_capabilities() {
        return array(
            'read' => true,
            'edit_posts' => true,
            'basmah_submit_report' => true,
            'basmah_view_my_reports' => true,
            'basmah_view_my_salary' => true,
            'basmah_view_all_reports' => true,
            'basmah_edit_reports' => true,
            'basmah_view_all_salaries' => true,
            'basmah_manage_salaries' => true,
            'basmah_manage_staff' => true,
            'basmah_manage_working_days' => true,
            'basmah_export_reports' => true,
        );
    }

    private static function sync_role_capabilities($role_name, $capabilities) {
        $role = get_role($role_name);
        if (!$role) {
            return;
        }

        foreach ($capabilities as $capability => $grant) {
            if ($grant) {
                $role->add_cap($capability);
            }
        }
    }

    private static function sync_power_user_capabilities() {
        foreach (array('administrator', 'moderator') as $role_name) {
            self::sync_role_capabilities($role_name, self::get_manager_capabilities());
        }
    }
    
    public static function remove_roles() {
        remove_role('basmah_staff');
        remove_role('basmah_manager');
    }
}
