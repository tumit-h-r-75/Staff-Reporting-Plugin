<?php
/**
 * Handle user roles and capabilities for the plugin.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Roles {

    /**
     * Check if current user has specific capability
     *
     * @param string $capability
     * @return bool
     */
    public static function current_user_can($capability) {
        return current_user_can($capability);
    }

    /**
     * Check if current user is a staff member
     *
     * @return bool
     */
    public static function is_staff() {
        $user = wp_get_current_user();
        return in_array('bassmah_staff', $user->roles) || 
               current_user_can('bassmah_submit_reports');
    }

    /**
     * Check if current user is a manager
     *
     * @return bool
     */
    public static function is_manager() {
        $user = wp_get_current_user();
        return in_array('bassmah_manager', $user->roles) || 
               current_user_can('bassmah_view_all_reports');
    }

    /**
     * Check if current user can view specific user's reports
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_view_user_reports($user_id) {
        $current_user_id = get_current_user_id();
        
        // Can view own reports
        if ($current_user_id == $user_id) {
            return current_user_can('bassmah_view_own_reports');
        }
        
        // Can view all reports if manager
        return current_user_can('bassmah_view_all_reports');
    }

    /**
     * Check if current user can view specific user's salary
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_view_user_salary($user_id) {
        $current_user_id = get_current_user_id();
        
        // Can view own salary
        if ($current_user_id == $user_id) {
            return current_user_can('bassmah_view_own_salary');
        }
        
        // Can view all salary if manager
        return current_user_can('bassmah_view_all_salary');
    }

    /**
     * Check if current user can comment on reports
     *
     * @return bool
     */
    public static function can_comment_reports() {
        return current_user_can('bassmah_comment_reports');
    }

    /**
     * Check if current user can export reports
     *
     * @return bool
     */
    public static function can_export_reports() {
        return current_user_can('bassmah_export_reports');
    }

    /**
     * Check if current user can manage salary settings
     *
     * @return bool
     */
    public static function can_manage_salary_settings() {
        return current_user_can('bassmah_manage_salary_settings');
    }

    /**
     * Check if current user can manage working days
     *
     * @return bool
     */
    public static function can_manage_working_days() {
        return current_user_can('bassmah_manage_working_days');
    }

    /**
     * Check if current user can manage staff
     *
     * @return bool
     */
    public static function can_manage_staff() {
        return current_user_can('bassmah_manage_staff');
    }

    /**
     * Get all users with staff or manager roles
     *
     * @param array $roles
     * @return array
     */
    public static function get_users_by_roles($roles = array('bassmah_staff', 'bassmah_manager')) {
        $args = array(
            'role__in' => $roles,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email', 'user_login')
        );

        return get_users($args);
    }

    /**
     * Get staff users only
     *
     * @return array
     */
    public static function get_staff_users() {
        return self::get_users_by_roles(array('bassmah_staff'));
    }

    /**
     * Get manager users only
     *
     * @return array
     */
    public static function get_manager_users() {
        return self::get_users_by_roles(array('bassmah_manager'));
    }

    /**
     * Get user role display name
     *
     * @param int $user_id
     * @return string
     */
    public static function get_user_role_display($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return __('Unknown', 'bassmah-staff-reports');
        }

        if (in_array('bassmah_manager', $user->roles)) {
            return __('Manager', 'bassmah-staff-reports');
        } elseif (in_array('bassmah_staff', $user->roles)) {
            return __('Staff Member', 'bassmah-staff-reports');
        } elseif (in_array('administrator', $user->roles)) {
            return __('Administrator', 'bassmah-staff-reports');
        }

        return __('User', 'bassmah-staff-reports');
    }

    /**
     * Verify nonce for security
     *
     * @param string $nonce
     * @param string $action
     * @return bool|WP_Error
     */
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed. Please try again.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }
        return true;
    }

    /**
     * Check if user is logged in
     *
     * @return bool|WP_Error
     */
    public static function require_login() {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to perform this action.', 'bassmah-staff-reports'),
                array('status' => 401)
            );
        }
        return true;
    }
}
