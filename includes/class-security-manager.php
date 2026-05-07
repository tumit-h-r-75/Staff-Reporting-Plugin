<?php
/**
 * Security Manager - Security Operations
 *
 * Provides comprehensive security operations including
 * authentication, authorization, validation, and protection.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Security_Manager {

    /**
     * Database manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     */
    private $database;

    /**
     * Rate limiting storage
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $rate_limits    Rate limit storage
     */
    private $rate_limits = array();

    /**
     * Security log
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $security_log    Security events log
     */
    private $security_log = array();

    /**
     * Constructor
     *
     * @since    1.0.0
     * @param    Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     */
    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Verify nonce
     *
     * @since    1.0.0
     * @param    string    $nonce     Nonce value
     * @param    string    $action    Action name
     * @return   bool|WP_Error
     */
    public function verify_nonce($nonce, $action = '') {
        if (!wp_verify_nonce($nonce, $action)) {
            $this->log_security_event('nonce_verification_failed', array(
                'nonce' => $nonce,
                'action' => $action,
                'ip' => $this->get_client_ip(),
                'user_agent' => $this->get_user_agent()
            ));
            
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed. Please try again.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }
        
        return true;
    }

    /**
     * Check user capabilities
     *
     * @since    1.0.0
     * @param    string    $capability    Capability name
     * @param    int       $user_id      User ID (optional)
     * @return   bool|WP_Error
     */
    public function check_capability($capability, $user_id = null) {
        if (!is_user_logged_in()) {
            $this->log_security_event('unauthorized_access_attempt', array(
                'capability' => $capability,
                'ip' => $this->get_client_ip(),
                'user_agent' => $this->get_user_agent()
            ));
            
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to perform this action.', 'bassmah-staff-reports'),
                array('status' => 401)
            );
        }

        $user_id = $user_id ?: get_current_user_id();
        
        if (!user_can($user_id, $capability)) {
            $this->log_security_event('insufficient_permissions', array(
                'user_id' => $user_id,
                'capability' => $capability,
                'ip' => $this->get_client_ip()
            ));
            
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have sufficient permissions to perform this action.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Sanitize input data
     *
     * @since    1.0.0
     * @param    mixed     $data      Input data
     * @param    string    $type      Data type
     * @param    array     $options   Sanitization options
     * @return   mixed
     */
    public function sanitize_input($data, $type = 'text', $options = array()) {
        switch ($type) {
            case 'text':
                return sanitize_text_field($data);
            
            case 'email':
                return sanitize_email($data);
            
            case 'url':
                return esc_url_raw($data);
            
            case 'html':
                return wp_kses_post($data);
            
            case 'filename':
                return sanitize_file_name($data);
            
            case 'integer':
                return intval($data);
            
            case 'float':
                return floatval($data);
            
            case 'boolean':
                return (bool) $data;
            
            case 'array':
                if (!is_array($data)) {
                    return array();
                }
                return array_map(array($this, 'sanitize_input'), $data);
            
            case 'json':
                $decoded = json_decode($data, true);
                return is_array($decoded) ? $decoded : null;
            
            default:
                return apply_filters('bassmah_sanitize_input', $data, $type, $options);
        }
    }

    /**
     * Escape output
     *
     * @since    1.0.0
     * @param    string    $output    Output string
     * @param    string    $context   Output context
     * @return   string
     */
    public function escape_output($output, $context = 'display') {
        switch ($context) {
            case 'html':
                return esc_html($output);
            
            case 'attr':
                return esc_attr($output);
            
            case 'url':
                return esc_url($output);
            
            case 'js':
                return esc_js($output);
            
            default:
                return esc_html($output);
        }
    }

    /**
     * Validate user access to resource
     *
     * @since    1.0.0
     * @param    int       $resource_id    Resource ID
     * @param    string    $resource_type  Resource type
     * @param    int       $user_id        User ID (optional)
     * @return   bool|WP_Error
     */
    public function validate_resource_access($resource_id, $resource_type, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        
        switch ($resource_type) {
            case 'report':
                return $this->validate_report_access($resource_id, $user_id);
            
            case 'salary':
                return $this->validate_salary_access($resource_id, $user_id);
            
            default:
                return new WP_Error(
                    'invalid_resource_type',
                    __('Invalid resource type.', 'bassmah-staff-reports'),
                    array('status' => 400)
                );
        }
    }

    /**
     * Check rate limiting
     *
     * @since    1.0.0
     * @param    string    $action      Action name
     * @param    int       $limit       Request limit
     * @param    int       $window      Time window in seconds
     * @param    int       $user_id     User ID (optional)
     * @return   bool|WP_Error
     */
    public function check_rate_limit($action, $limit = 60, $window = 3600, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $ip = $this->get_client_ip();
        
        $key = "{$action}_{$user_id}_{$ip}";
        $current_time = time();
        
        if (!isset($this->rate_limits[$key])) {
            $this->rate_limits[$key] = array();
        }
        
        // Clean old entries
        $this->rate_limits[$key] = array_filter(
            $this->rate_limits[$key],
            function($timestamp) use ($current_time, $window) {
                return ($current_time - $timestamp) < $window;
            }
        );
        
        // Check limit
        if (count($this->rate_limits[$key]) >= $limit) {
            $this->log_security_event('rate_limit_exceeded', array(
                'action' => $action,
                'user_id' => $user_id,
                'ip' => $ip,
                'limit' => $limit,
                'window' => $window
            ));
            
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded. Please try again later.', 'bassmah-staff-reports'),
                array('status' => 429)
            );
        }
        
        // Add current request
        $this->rate_limits[$key][] = $current_time;
        
        return true;
    }

    /**
     * Generate secure token
     *
     * @since    1.0.0
     * @param    int       $length    Token length
     * @param    string    $type      Token type
     * @return   string
     */
    public function generate_token($length = 32, $type = 'alphanumeric') {
        switch ($type) {
            case 'alphanumeric':
                $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numeric':
                $chars = '0123456789';
                break;
            case 'hex':
                $chars = '0123456789abcdef';
                break;
            default:
                $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }
        
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $token;
    }

    /**
     * Hash password securely
     *
     * @since    1.0.0
     * @param    string    $password    Password to hash
     * @return   string
     */
    public function hash_password($password) {
        return wp_hash_password($password);
    }

    /**
     * Verify password
     *
     * @since    1.0.0
     * @param    string    $password    Plain password
     * @param    string    $hash        Password hash
     * @return   bool
     */
    public function verify_password($password, $hash) {
        return wp_check_password($password, $hash);
    }

    /**
     * Validate report access
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @param    int    $user_id      User ID
     * @return   bool|WP_Error
     */
    private function validate_report_access($report_id, $user_id) {
        $report = $this->database->get_row(
            "SELECT user_id FROM {$this->database->get_table_name('reports')} WHERE id = %d",
            array($report_id)
        );
        
        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }
        
        // Users can access their own reports
        if ($report->user_id == $user_id) {
            return $this->check_capability('bassmah_view_own_reports', $user_id);
        }
        
        // Managers can access all reports
        return $this->check_capability('bassmah_view_all_reports', $user_id);
    }

    /**
     * Validate salary access
     *
     * @since    1.0.0
     * @param    int    $target_user_id    Target user ID
     * @param    int    $user_id          Current user ID
     * @return   bool|WP_Error
     */
    private function validate_salary_access($target_user_id, $user_id) {
        // Users can access their own salary
        if ($target_user_id == $user_id) {
            return $this->check_capability('bassmah_view_own_salary', $user_id);
        }
        
        // Managers can access all salary
        return $this->check_capability('bassmah_view_all_salary', $user_id);
    }

    /**
     * Get client IP address
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Log security event
     *
     * @since    1.0.0
     * @param    string    $event_type    Event type
     * @param    array     $data         Event data
     */
    private function log_security_event($event_type, $data = array()) {
        $this->security_log[] = array(
            'event_type' => $event_type,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        );
        
        do_action('bassmah_security_event', $event_type, $data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Bassmah Security Event: {$event_type} - " . print_r($data, true));
        }
    }

    /**
     * Get security log
     *
     * @since    1.0.0
     * @param    int    $limit    Log limit
     * @return   array
     */
    public function get_security_log($limit = 100) {
        return array_slice($this->security_log, -$limit);
    }

    /**
     * Clear security log
     *
     * @since    1.0.0
     */
    public function clear_security_log() {
        $this->security_log = array();
    }

    /**
     * Validate CSRF token for AJAX requests
     *
     * @since    1.0.0
     * @param    string    $token    CSRF token
     * @return   bool
     */
    public function validate_csrf_token($token) {
        $session_token = wp_get_session_token();
        return hash_equals($session_token, $token);
    }

    /**
     * Generate CSRF token
     *
     * @since    1.0.0
     * @return   string
     */
    public function generate_csrf_token() {
        return wp_create_nonce('bassmah_csrf');
    }

    /**
     * Check if request is secure
     *
     * @since    1.0.0
     * @return   bool
     */
    public function is_secure_request() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443
        ) && $this->validate_csrf_token($_REQUEST['_wpnonce'] ?? '');
    }

    /**
     * Get security headers
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_security_headers() {
        return array(
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
        );
    }

    /**
     * Send security headers
     *
     * @since    1.0.0
     */
    public function send_security_headers() {
        if (!headers_sent()) {
            $headers = $this->get_security_headers();
            foreach ($headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
    }
}
