<?php
class Basmah_Staff_Reports_API_Salary {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        foreach (array('bsr/v1', 'bassmah/v1') as $namespace) {
            register_rest_route($namespace, '/salary', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_salary'),
                'permission_callback' => array($this, 'check_permission'),
            ));

            register_rest_route($namespace, '/salary/me', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_salary'),
                'permission_callback' => array($this, 'check_permission'),
            ));

            register_rest_route($namespace, '/salary/(?P<user_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_staff_salary'),
                'permission_callback' => array($this, 'check_manager_permission'),
            ));
        }
    }
    
    public function check_permission() {
        return is_user_logged_in();
    }

    public function check_manager_permission() {
        return is_user_logged_in() && (current_user_can('basmah_view_all_salaries') || current_user_can('manage_options'));
    }
    
    public function get_salary($request) {
        $user_id = get_current_user_id();
        $month = $request->get_param('month') ? intval($request->get_param('month')) : intval(current_time('m'));
        $year = $request->get_param('year') ? intval($request->get_param('year')) : intval(current_time('Y'));
        
        $salary = Basmah_Staff_Reports_Salary::calculate_salary($user_id, $month, $year);
        return new WP_REST_Response(array('salary' => $salary), 200);
    }

    public function get_staff_salary($request) {
        $user_id = intval($request->get_param('user_id'));
        $month = $request->get_param('month') ? intval($request->get_param('month')) : intval(current_time('m'));
        $year = $request->get_param('year') ? intval($request->get_param('year')) : intval(current_time('Y'));

        $salary = Basmah_Staff_Reports_Salary::calculate_salary($user_id, $month, $year);
        return new WP_REST_Response(array('salary' => $salary), 200);
    }
}
