<?php
class Basmah_Staff_Reports_API_Salary {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('bsr/v1', '/salary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_salary'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }
    
    public function check_permission() {
        return is_user_logged_in();
    }
    
    public function get_salary($request) {
        $user_id = get_current_user_id();
        $month = $request->get_param('month');
        $year = $request->get_param('year');
        
        $salary = Basmah_Staff_Reports_Salary::calculate_salary($user_id, $month, $year);
        return new WP_REST_Response(array('salary' => $salary), 200);
    }
}

new Basmah_Staff_Reports_API_Salary();
