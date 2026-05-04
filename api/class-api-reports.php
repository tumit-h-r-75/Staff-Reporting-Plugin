<?php
class Basmah_Staff_Reports_API_Reports {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('bsr/v1', '/reports', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reports'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        register_rest_route('bsr/v1', '/reports', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_report'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }
    
    public function check_permission() {
        return is_user_logged_in();
    }
    
    public function get_reports($request) {
        $reports = Basmah_Staff_Reports_Reports::get_reports();
        return new WP_REST_Response($reports, 200);
    }
    
    public function create_report($request) {
        $params = $request->get_json_params();
        $result = Basmah_Staff_Reports_Reports::create_report($params);
        return new WP_REST_Response(array('success' => $result), 200);
    }
}

new Basmah_Staff_Reports_API_Reports();
