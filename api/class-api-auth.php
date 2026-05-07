<?php
class Basmah_Staff_Reports_API_Auth {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        foreach (array('bsr/v1', 'bassmah/v1') as $namespace) {
            register_rest_route($namespace, '/auth/me', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_current_user'),
                'permission_callback' => array($this, 'check_permission'),
            ));
        }
    }
    
    public function check_permission($request) {
        return is_user_logged_in();
    }
    
    public function get_current_user($request) {
        $user = wp_get_current_user();
        return new WP_REST_Response(array(
            'id' => $user->ID,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'roles' => $user->roles
        ), 200);
    }
}
