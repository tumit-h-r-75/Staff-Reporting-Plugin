<?php
/**
 * User Service - User Management & Roles
 *
 * Handles user-related operations including role management,
 * user information retrieval, and permission checks.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_User_Service {

    /**
     * Database manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     */
    private $database;

    /**
     * Security manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Security_Manager    $security    Security manager
     */
    private $security;

    /**
     * Constructor
     *
     * @since    1.0.0
     * @param    Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     * @param    Bassmah_Staff_Reports_Security_Manager    $security    Security manager
     */
    public function __construct($database, $security) {
        $this->database = $database;
        $this->security = $security;
    }

    /**
     * Get users by role
     *
     * @since    1.0.0
     * @param    array    $roles    User roles
     * @param    array    $args     Additional arguments
     * @return   array
     */
    public function get_users_by_roles($roles = array('bassmah_staff', 'bassmah_manager'), $args = array()) {
        $defaults = array(
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email', 'user_login', 'user_registered', 'user_status')
        );

        $args = wp_parse_args($args, $defaults);
        $args['role__in'] = $roles;

        return get_users($args);
    }

    /**
     * Get staff users
     *
     * @since    1.0.0
     * @param    array    $args    Additional arguments
     * @return   array
     */
    public function get_staff_users($args = array()) {
        return $this->get_users_by_roles(array('bassmah_staff', 'subscriber'), $args);
    }

    /**
     * Get manager users
     *
     * @since    1.0.0
     * @param    array    $args    Additional arguments
     * @return   array
     */
    public function get_manager_users($args = array()) {
        return $this->get_users_by_roles(array('bassmah_manager'), $args);
    }

    /**
     * Get all plugin users
     *
     * @since    1.0.0
     * @param    array    $args    Additional arguments
     * @return   array
     */
    public function get_all_users($args = array()) {
        $defaults = array(
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email', 'user_login', 'user_registered', 'user_status')
        );

        $args = wp_parse_args($args, $defaults);
        $args['role__in'] = array('bassmah_staff', 'bassmah_manager', 'administrator');

        return get_users($args);
    }

    /**
     * Get user details
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   object|WP_Error
     */
    public function get_user_details($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        return (object) array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'user_login' => $user->user_login,
            'user_registered' => $user->user_registered,
            'user_status' => $this->get_user_status($user),
            'roles' => $user->roles,
            'role_display' => $this->get_role_display($user),
            'last_login' => $this->get_last_login($user_id),
            'report_count' => $this->get_user_report_count($user_id),
            'has_salary_settings' => $this->user_has_salary_settings($user_id)
        );
    }

    /**
     * Assign role to user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    string  $role       Role to assign
     * @return   bool|WP_Error
     */
    public function assign_role($user_id, $role) {
        // Check permissions
        $permission_check = $this->security->check_capability('promote_users');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Validate role
        $valid_roles = array('bassmah_staff', 'bassmah_manager');
        if (!in_array($role, $valid_roles)) {
            return new WP_Error(
                'invalid_role',
                __('Invalid role specified.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Remove existing plugin roles
        $user->remove_role('bassmah_staff');
        $user->remove_role('bassmah_manager');

        // Add new role
        $result = $user->add_role($role);

        if ($result) {
            // Trigger actions
            do_action('bassmah_user_role_assigned', $user_id, $role);
            do_action('bassmah_user_updated', $user_id);
        }

        return $result;
    }

    /**
     * Remove role from user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    string  $role       Role to remove
     * @return   bool|WP_Error
     */
    public function remove_role($user_id, $role) {
        // Check permissions
        $permission_check = $this->security->check_capability('promote_users');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        $result = $user->remove_role($role);

        if ($result) {
            // Trigger actions
            do_action('bassmah_user_role_removed', $user_id, $role);
            do_action('bassmah_user_updated', $user_id);
        }

        return $result;
    }

    /**
     * Create new user
     *
     * @since    1.0.0
     * @param    array    $user_data    User data
     * @return   int|WP_Error
     */
    public function create_user($user_data) {
        // Check permissions
        $permission_check = $this->security->check_capability('create_users');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Validate required fields
        $required_fields = array('user_login', 'user_email', 'display_name', 'user_pass');
        foreach ($required_fields as $field) {
            if (empty($user_data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Missing required field: %s', 'bassmah-staff-reports'), $field),
                    array('status' => 400)
                );
            }
        }

        // Validate email
        if (!is_email($user_data['user_email'])) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Prepare user data
        $new_user_data = array(
            'user_login' => $user_data['user_login'],
            'user_email' => $user_data['user_email'],
            'display_name' => $user_data['display_name'],
            'user_pass' => $user_data['user_pass'],
            'role' => $user_data['role'] ?? 'bassmah_staff'
        );

        $user_id = wp_insert_user($new_user_data);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Trigger actions
        do_action('bassmah_user_created', $user_id, $new_user_data);

        return $user_id;
    }

    /**
     * Update user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    array  $user_data  User data to update
     * @return   bool|WP_Error
     */
    public function update_user($user_id, $user_data) {
        // Check permissions
        $permission_check = $this->security->check_capability('edit_users');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Prepare update data
        $update_data = array();
        $allowed_fields = array('display_name', 'user_email', 'user_pass');

        foreach ($allowed_fields as $field) {
            if (isset($user_data[$field])) {
                $update_data[$field] = $user_data[$field];
            }
        }

        if (empty($update_data)) {
            return new WP_Error(
                'no_valid_fields',
                __('No valid fields to update.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Validate email if being updated
        if (isset($update_data['user_email']) && !is_email($update_data['user_email'])) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        $result = wp_update_user(array(
            'ID' => $user_id,
            'data' => $update_data
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        // Trigger actions
        do_action('bassmah_user_updated', $user_id, $update_data);

        return true;
    }

    /**
     * Deactivate user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   bool|WP_Error
     */
    public function deactivate_user($user_id) {
        // Check permissions
        $permission_check = $this->security->check_capability('remove_users');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        $result = wp_update_user(array(
            'ID' => $user_id,
            'user_status' => 'inactive'
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        // Trigger actions
        do_action('bassmah_user_deactivated', $user_id);

        return true;
    }

    /**
     * Get user statistics
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array
     */
    public function get_user_statistics($user_id) {
        $cache_key = "user_stats_{$user_id}";
        $cached_stats = $this->cache->get($cache_key, 'users');
        
        if ($cached_stats) {
            return $cached_stats;
        }

        // Get report count
        $report_count = $this->get_user_report_count($user_id);

        // Get salary info
        $salary_settings = $this->database->get_row(
            "SELECT monthly_salary, working_days_per_month, effective_from 
                 FROM {$this->database->get_table_name('salary_settings')} 
                 WHERE user_id = %d ORDER BY effective_from DESC LIMIT 1",
            array($user_id)
        );

        // Get last login
        $last_login = get_user_meta($user_id, 'bassmah_last_login', true);

        $statistics = array(
            'user_id' => $user_id,
            'report_count' => $report_count,
            'has_salary_settings' => !empty($salary_settings),
            'current_salary' => $salary_settings ? array(
                'monthly_salary' => $salary_settings->monthly_salary,
                'working_days_per_month' => $salary_settings->working_days_per_month,
                'daily_rate' => $salary_settings->monthly_salary / $salary_settings->working_days_per_month,
                'effective_from' => $salary_settings->effective_from
            ) : null,
            'last_login' => $last_login,
            'account_status' => $this->get_user_status(get_userdata($user_id))
        );

        // Cache the result
        $this->cache->set($cache_key, $statistics, 'users', 1800);

        return $statistics;
    }

    /**
     * Get user report count
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   int
     */
    private function get_user_report_count($user_id) {
        return $this->database->get_var(
            "SELECT COUNT(*) FROM {$this->database->get_table_name('reports')} WHERE user_id = %d",
            array($user_id)
        );
    }

    /**
     * Check if user has salary settings
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   bool
     */
    private function user_has_salary_settings($user_id) {
        $settings = $this->database->get_var(
            "SELECT COUNT(*) FROM {$this->database->get_table_name('salary_settings')} WHERE user_id = %d",
            array($user_id)
        );

        return !empty($settings);
    }

    /**
     * Get user status
     *
     * @since    1.0.0
     * @param    object    $user    User object
     * @return   string
     */
    private function get_user_status($user) {
        if (!$user) {
            return 'unknown';
        }

        // Check if user is active
        if ($user->user_status === 'active') {
            return 'active';
        }

        // Check last activity
        $last_login = get_user_meta($user->ID, 'bassmah_last_login', true);
        $thirty_days_ago = strtotime('-30 days');

        if ($last_login && $last_login > $thirty_days_ago) {
            return 'active';
        }

        return 'inactive';
    }

    /**
     * Get role display name
     *
     * @since    1.0.0
     * @param    object    $user    User object
     * @return   string
     */
    private function get_role_display($user) {
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
     * Get last login timestamp
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   string|null
     */
    private function get_last_login($user_id) {
        global $wpdb;
        
        $last_login = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} 
                 WHERE user_id = %d AND meta_key = 'last_login' 
                 ORDER BY umeta_id DESC LIMIT 1",
            $user_id
        ));

        return $last_login;
    }

    /**
     * Update last login
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   bool
     */
    public function update_last_login($user_id) {
        return update_user_meta($user_id, 'bassmah_last_login', current_time('mysql'));
    }

    /**
     * Get active users count
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_active_users_count() {
        $cache_key = 'active_users_count';
        $cached_count = $this->cache->get($cache_key, 'users');
        
        if ($cached_count !== null) {
            return $cached_count;
        }

        $staff_count = count_users(array('role' => 'bassmah_staff'));
        $manager_count = count_users(array('role' => 'bassmah_manager'));
        $admin_count = count_users(array('role' => 'administrator'));

        $counts = array(
            'staff' => $staff_count,
            'managers' => $manager_count,
            'administrators' => $admin_count,
            'total' => $staff_count + $manager_count + $admin_count
        );

        $this->cache->set($cache_key, $counts, 'users', 300); // Cache for 5 minutes

        return $counts;
    }

    /**
     * Search users
     *
     * @since    1.0.0
     * @param    string    $search    Search term
     * @param    array    $args     Additional arguments
     * @return   array
     */
    public function search_users($search, $args = array()) {
        $defaults = array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email', 'user_login')
        );

        $args = wp_parse_args($args, $defaults);
        $args['role__in'] = array('bassmah_staff', 'bassmah_manager', 'administrator');

        return get_users($args);
    }

    /**
     * Bulk assign roles
     *
     * @since    1.0.0
     * @param    array    $user_ids   User IDs
     * @param    string  $role       Role to assign
     * @return   array
     */
    public function bulk_assign_roles($user_ids, $role) {
        $permission_check = $this->security->check_capability('promote_users');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $results = array();
        foreach ($user_ids as $user_id) {
            $result = $this->assign_role($user_id, $role);
            $results[] = array(
                'user_id' => $user_id,
                'success' => !is_wp_error($result),
                'error' => is_wp_error($result) ? $result->get_error_message() : null
            );
        }

        // Trigger bulk action
        do_action('bassmah_users_bulk_role_assigned', $user_ids, $role, $results);

        return $results;
    }

    /**
     * Get user permissions summary
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array
     */
    public function get_user_permissions_summary($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return array();
        }

        $capabilities = array(
            'can_submit_reports' => user_can($user_id, 'bassmah_submit_reports'),
            'can_view_own_reports' => user_can($user_id, 'bassmah_view_own_reports'),
            'can_view_all_reports' => user_can($user_id, 'bassmah_view_all_reports'),
            'can_comment_reports' => user_can($user_id, 'bassmah_comment_reports'),
            'can_export_reports' => user_can($user_id, 'bassmah_export_reports'),
            'can_view_own_salary' => user_can($user_id, 'bassmah_view_own_salary'),
            'can_view_all_salary' => user_can($user_id, 'bassmah_view_all_salary'),
            'can_manage_salary_settings' => user_can($user_id, 'bassmah_manage_salary_settings'),
            'can_manage_working_days' => user_can($user_id, 'bassmah_manage_working_days'),
            'can_manage_staff' => user_can($user_id, 'bassmah_manage_staff')
        );

        return array(
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'display_name' => $user->display_name,
            'roles' => $user->roles,
            'role_display' => $this->get_role_display($user),
            'capabilities' => $capabilities,
            'is_staff' => in_array('bassmah_staff', $user->roles),
            'is_manager' => in_array('bassmah_manager', $user->roles),
            'is_admin' => in_array('administrator', $user->roles)
        );
    }

    /**
     * Clear user cache
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID (optional)
     */
    public function clear_user_cache($user_id = null) {
        if ($user_id) {
            $this->cache->delete("user_stats_{$user_id}", 'users');
            $this->cache->delete("user_settings_{$user_id}", 'users');
        } else {
            $this->cache->clear_group('users');
        }
    }
}
