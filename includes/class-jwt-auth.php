<?php
/**
 * JWT Authentication Class
 * 
 * Implements JWT token-based authentication for WordPress
 * to avoid cookie/session issues
 */

if (!class_exists('Bassmah_Staff_Reports_JWT_Auth')) {
    class Bassmah_Staff_Reports_JWT_Auth {
        
        private $secret_key;
        private $token_expire_time;
        
        public function __construct() {
            $this->secret_key = $this->get_secret_key();
            $this->token_expire_time = 28800; // 8 hours (full working day)
        }
        
        /**
         * Define JWT hooks
         */
        public function define_jwt_hooks() {
            add_action('wp_login', array($this, 'generate_jwt_token_on_login'), 10, 2);
            add_action('wp_logout', array($this, 'revoke_jwt_token'));
            add_action('wp_ajax_bassmah_get_jwt_token', array($this, 'get_jwt_token'));
            add_action('wp_ajax_nopriv_bassmah_get_jwt_token', array($this, 'get_jwt_token'));
        }
        
        /**
         * Get or generate secret key
         */
        private function get_secret_key() {
            $secret_key = get_option('bassmah_jwt_secret_key');
            
            if (!$secret_key) {
                $secret_key = wp_generate_password(64, true, true);
                update_option('bassmah_jwt_secret_key', $secret_key);
            }
            
            return $secret_key;
        }
        
        /**
         * Generate JWT token on user login
         */
        public function generate_jwt_token_on_login($user_login, $user) {
            $token = $this->generate_token($user);
            
            // Store token in user meta for validation
            update_user_meta($user->ID, 'bassmah_jwt_token', $token);
            update_user_meta($user->ID, 'bassmah_jwt_token_issued', time());
            
            return $token;
        }
        
        /**
         * Generate JWT token for user
         */
        public function generate_token($user) {
            $payload = array(
                'iss' => get_bloginfo('url'),
                'iat' => time(),
                'exp' => time() + $this->token_expire_time,
                'data' => array(
                    'user_id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'roles' => $user->roles
                )
            );
            
            return $this->encode_jwt($payload);
        }
        
        /**
         * Encode JWT token (simple implementation)
         */
        private function encode_jwt($payload) {
            // Header
            $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
            $header_encoded = $this->base64url_encode($header);
            
            // Payload
            $payload_encoded = $this->base64url_encode(json_encode($payload));
            
            // Signature
            $signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, $this->secret_key, true);
            $signature_encoded = $this->base64url_encode($signature);
            
            return $header_encoded . "." . $payload_encoded . "." . $signature_encoded;
        }
        
        /**
         * Decode and validate JWT token
         */
        public function validate_token($token) {
            if (empty($token)) {
                return new WP_Error('invalid_token', 'Token is required', array('status' => 401));
            }
            
            $parts = explode('.', $token);
            if (count($parts) != 3) {
                return new WP_Error('invalid_token', 'Invalid token format', array('status' => 401));
            }
            
            // Decode header and payload
            $header = json_decode($this->base64url_decode($parts[0]), true);
            $payload = json_decode($this->base64url_decode($parts[1]), true);
            
            if (!$header || !$payload) {
                return new WP_Error('invalid_token', 'Invalid token structure', array('status' => 401));
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return new WP_Error('expired_token', 'Token has expired', array('status' => 401));
            }
            
            // Verify signature
            $signature_expected = hash_hmac('sha256', $parts[0] . "." . $parts[1], $this->secret_key, true);
            $signature_expected_encoded = $this->base64url_encode($signature_expected);
            
            if (!hash_equals($signature_expected_encoded, $parts[2])) {
                return new WP_Error('invalid_token', 'Invalid token signature', array('status' => 401));
            }
            
            // Check if token matches stored token (for revocation)
            if (isset($payload['data']['user_id'])) {
                $stored_token = get_user_meta($payload['data']['user_id'], 'bassmah_jwt_token', true);
                if ($stored_token !== $token) {
                    return new WP_Error('revoked_token', 'Token has been revoked', array('status' => 401));
                }
            }
            
            // Return object with user_id property for compatibility
            return (object) array(
                'user_id' => $payload['data']['user_id'],
                'exp' => $payload['exp'],
                'iat' => $payload['iat']
            );
        }
        
        /**
         * Base64 URL safe encoding
         */
        private function base64url_encode($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        }
        
        /**
         * Base64 URL safe decoding
         */
        private function base64url_decode($data) {
            return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
        }
        
        /**
         * Get JWT token via AJAX
         */
        public function get_jwt_token() {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error('User not logged in', 401);
            }
            
            $user = wp_get_current_user();
            $token = $this->generate_token($user);
            
            // Update stored token
            update_user_meta($user->ID, 'bassmah_jwt_token', $token);
            update_user_meta($user->ID, 'bassmah_jwt_token_issued', time());
            
            wp_send_json_success(array(
                'token' => $token,
                'expires_in' => $this->token_expire_time,
                'user' => array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'roles' => $user->roles
                )
            ));
        }
        
        /**
         * Revoke JWT token on logout
         */
        public function revoke_jwt_token() {
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                delete_user_meta($user_id, 'bassmah_jwt_token');
                delete_user_meta($user_id, 'bassmah_jwt_token_issued');
            }
        }
        
        /**
         * Validate token from request headers
         */
        public function get_token_from_request() {
            $headers = getallheaders();
            $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
            
            // Check for Bearer token
            if (strpos($auth_header, 'Bearer ') === false) {
                // Check for token in POST data as fallback
                return isset($_POST['jwt_token']) ? sanitize_text_field($_POST['jwt_token']) : '';
            }
            
            return str_replace('Bearer ', '', $auth_header);
        }
        
        /**
         * Middleware to validate JWT for AJAX requests
         */
        public function validate_jwt_middleware() {
            $token = $this->get_token_from_request();
            $validation = $this->validate_token($token);
            
            if (is_wp_error($validation)) {
                wp_send_json_error($validation->get_error_message(), $validation->get_error_data()['status']);
            }
            
            return $validation;
        }
    }
}
