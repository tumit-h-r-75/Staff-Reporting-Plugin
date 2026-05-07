<?php
/**
 * Validation Manager - Input Validation
 *
 * Provides comprehensive input validation with
 * custom rules, error messages, and sanitization.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Validation_Manager {

    /**
     * Validation rules
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $rules    Validation rules
     */
    private $rules = array();

    /**
     * Validation errors
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $errors    Validation errors
     */
    private $errors = array();

    /**
     * Custom error messages
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $messages    Error messages
     */
    private $messages = array();

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_default_rules();
        $this->load_default_messages();
    }

    /**
     * Validate data against rules
     *
     * @since    1.0.0
     * @param    array    $data    Data to validate
     * @param    array    $rules    Validation rules
     * @return   bool|WP_Error
     */
    public function validate($data, $rules = array()) {
        $this->errors = array();
        $rules = empty($rules) ? $this->rules : $rules;

        foreach ($rules as $field => $field_rules) {
            $value = isset($data[$field]) ? $data[$field] : null;

            foreach ($field_rules as $rule => $params) {
                if (!$this->validate_rule($field, $value, $rule, $params)) {
                    break; // Stop on first error for field
                }
            }
        }

        if (!empty($this->errors)) {
            return new WP_Error(
                'validation_failed',
                __('Validation failed', 'bassmah-staff-reports'),
                array('errors' => $this->errors)
            );
        }

        return true;
    }

    /**
     * Validate single rule
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    string    $rule     Rule name
     * @param    mixed     $params   Rule parameters
     * @return   bool
     */
    private function validate_rule($field, $value, $rule, $params) {
        $method = 'validate_' . $rule;

        if (method_exists($this, $method)) {
            return $this->$method($field, $value, $params);
        }

        // Allow custom validation functions
        if (is_callable($params)) {
            $result = call_user_func($params, $value, $field);
            if ($result !== true) {
                $this->add_error($field, $rule, $result);
                return false;
            }
            return true;
        }

        return true;
    }

    /**
     * Validate required field
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_required($field, $value, $params) {
        if (is_null($value) || $value === '' || $value === array()) {
            $this->add_error($field, 'required', $this->get_message('required', $field));
            return false;
        }
        return true;
    }

    /**
     * Validate email
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_email($field, $value, $params) {
        if (!empty($value) && !is_email($value)) {
            $this->add_error($field, 'email', $this->get_message('email', $field));
            return false;
        }
        return true;
    }

    /**
     * Validate numeric value
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_numeric($field, $value, $params) {
        if (!empty($value) && !is_numeric($value)) {
            $this->add_error($field, 'numeric', $this->get_message('numeric', $field));
            return false;
        }
        return true;
    }

    /**
     * Validate integer
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_integer($field, $value, $params) {
        if (!empty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->add_error($field, 'integer', $this->get_message('integer', $field));
            return false;
        }
        return true;
    }

    /**
     * Validate minimum length
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_min_length($field, $value, $params) {
        if (!empty($value) && strlen($value) < $params) {
            $this->add_error($field, 'min_length', 
                sprintf($this->get_message('min_length', $field), $params));
            return false;
        }
        return true;
    }

    /**
     * Validate maximum length
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_max_length($field, $value, $params) {
        if (!empty($value) && strlen($value) > $params) {
            $this->add_error($field, 'max_length', 
                sprintf($this->get_message('max_length', $field), $params));
            return false;
        }
        return true;
    }

    /**
     * Validate minimum value
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_min($field, $value, $params) {
        if (!empty($value) && is_numeric($value) && $value < $params) {
            $this->add_error($field, 'min', 
                sprintf($this->get_message('min', $field), $params));
            return false;
        }
        return true;
    }

    /**
     * Validate maximum value
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_max($field, $value, $params) {
        if (!empty($value) && is_numeric($value) && $value > $params) {
            $this->add_error($field, 'max', 
                sprintf($this->get_message('max', $field), $params));
            return false;
        }
        return true;
    }

    /**
     * Validate date
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_date($field, $value, $params) {
        if (!empty($value)) {
            $date = DateTime::createFromFormat($params, $value);
            if (!$date || $date->format($params) !== $value) {
                $this->add_error($field, 'date', 
                    sprintf($this->get_message('date', $field), $params));
                return false;
            }
        }
        return true;
    }

    /**
     * Validate date range
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_date_range($field, $value, $params) {
        if (!empty($value)) {
            $date = new DateTime($value);
            $min_date = new DateTime($params['min']);
            $max_date = new DateTime($params['max']);

            if ($date < $min_date || $date > $max_date) {
                $this->add_error($field, 'date_range', 
                    sprintf($this->get_message('date_range', $field), 
                        $min_date->format($params['format']), 
                        $max_date->format($params['format'])
                    ));
                return false;
            }
        }
        return true;
    }

    /**
     * Validate in array
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_in($field, $value, $params) {
        if (!empty($value) && !in_array($value, $params)) {
            $this->add_error($field, 'in', 
                sprintf($this->get_message('in', $field), implode(', ', $params)));
            return false;
        }
        return true;
    }

    /**
     * Validate regex pattern
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_regex($field, $value, $params) {
        if (!empty($value) && !preg_match($params, $value)) {
            $this->add_error($field, 'regex', $this->get_message('regex', $field));
            return false;
        }
        return true;
    }

    /**
     * Validate unique value
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    Field value
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_unique($field, $value, $params) {
        if (!empty($value)) {
            global $wpdb;
            
            $table = $params['table'];
            $column = $params['column'];
            $exclude_id = $params['exclude_id'] ?? null;
            
            $query = "SELECT COUNT(*) FROM {$table} WHERE {$column} = %s";
            $args = array($value);
            
            if ($exclude_id) {
                $query .= " AND id != %d";
                $args[] = $exclude_id;
            }
            
            $count = $wpdb->get_var($wpdb->prepare($query, $args));
            
            if ($count > 0) {
                $this->add_error($field, 'unique', $this->get_message('unique', $field));
                return false;
            }
        }
        return true;
    }

    /**
     * Validate file upload
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    mixed     $value    File array
     * @param    mixed     $params   Parameters
     * @return   bool
     */
    private function validate_file($field, $value, $params) {
        if (!empty($value) && isset($value['error']) && $value['error'] === UPLOAD_ERR_OK) {
            // Check file size
            if (isset($params['max_size']) && $value['size'] > $params['max_size']) {
                $this->add_error($field, 'file_size', 
                    sprintf($this->get_message('file_size', $field), 
                        size_format($params['max_size'])));
                return false;
            }

            // Check file type
            if (isset($params['allowed_types'])) {
                $file_type = wp_check_filetype_and_ext($value['name']);
                if (!in_array($file_type['ext'], $params['allowed_types'])) {
                    $this->add_error($field, 'file_type', 
                        sprintf($this->get_message('file_type', $field), 
                            implode(', ', $params['allowed_types'])));
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Add validation error
     *
     * @since    1.0.0
     * @param    string    $field    Field name
     * @param    string    $rule     Rule name
     * @param    string    $message  Error message
     */
    private function add_error($field, $rule, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = array();
        }
        
        $this->errors[$field][$rule] = $message;
    }

    /**
     * Get error message
     *
     * @since    1.0.0
     * @param    string    $rule     Rule name
     * @param    string    $field    Field name
     * @return   string
     */
    private function get_message($rule, $field) {
        $key = "{$rule}_{$field}";
        
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }
        
        if (isset($this->messages[$rule])) {
            return $this->messages[$rule];
        }
        
        return sprintf(__('Invalid %s', 'bassmah-staff-reports'), $field);
    }

    /**
     * Load default validation rules
     *
     * @since    1.0.0
     */
    private function load_default_rules() {
        $this->rules = array(
            'user_id' => array(
                'required' => true,
                'integer' => true,
                'min' => 1
            ),
            'report_date' => array(
                'required' => true,
                'date' => 'Y-m-d',
                'date_range' => array(
                    'min' => '2020-01-01',
                    'max' => date('Y-m-d', strtotime('+1 year')),
                    'format' => 'Y-m-d'
                )
            ),
            'monthly_salary' => array(
                'required' => true,
                'numeric' => true,
                'min' => 0,
                'max' => 999999.99
            ),
            'working_days_per_month' => array(
                'required' => true,
                'integer' => true,
                'min' => 1,
                'max' => 31
            ),
            'task_description' => array(
                'required' => true,
                'min_length' => 10,
                'max_length' => 1000
            ),
            'email' => array(
                'required' => true,
                'email' => true
            )
        );
    }

    /**
     * Load default error messages
     *
     * @since    1.0.0
     */
    private function load_default_messages() {
        $this->messages = array(
            'required' => __('This field is required.', 'bassmah-staff-reports'),
            'email' => __('Please enter a valid email address.', 'bassmah-staff-reports'),
            'numeric' => __('Please enter a valid number.', 'bassmah-staff-reports'),
            'integer' => __('Please enter a whole number.', 'bassmah-staff-reports'),
            'min_length' => __('This field must be at least %d characters long.', 'bassmah-staff-reports'),
            'max_length' => __('This field must not exceed %d characters.', 'bassmah-staff-reports'),
            'min' => __('This field must be at least %s.', 'bassmah-staff-reports'),
            'max' => __('This field must not exceed %s.', 'bassmah-staff-reports'),
            'date' => __('Please enter a valid date in %s format.', 'bassmah-staff-reports'),
            'date_range' => __('Date must be between %s and %s.', 'bassmah-staff-reports'),
            'in' => __('Please select from the available options: %s', 'bassmah-staff-reports'),
            'regex' => __('Please enter a valid value.', 'bassmah-staff-reports'),
            'unique' => __('This value is already in use.', 'bassmah-staff-reports'),
            'file_size' => __('File size must not exceed %s.', 'bassmah-staff-reports'),
            'file_type' => __('File type must be one of: %s', 'bassmah-staff-reports')
        );
    }

    /**
     * Get validation errors
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get first error message
     *
     * @since    1.0.0
     * @return   string|null
     */
    public function get_first_error() {
        if (empty($this->errors)) {
            return null;
        }

        $first_field = key($this->errors);
        $first_rule = key($this->errors[$first_field]);
        
        return $this->errors[$first_field][$first_rule];
    }

    /**
     * Check if validation passed
     *
     * @since    1.0.0
     * @return   bool
     */
    public function passed() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     *
     * @since    1.0.0
     * @return   bool
     */
    public function failed() {
        return !empty($this->errors);
    }

    /**
     * Reset validation state
     *
     * @since    1.0.0
     */
    public function reset() {
        $this->errors = array();
    }

    /**
     * Add custom validation rule
     *
     * @since    1.0.0
     * @param    string    $name     Rule name
     * @param    callable $callback Validation function
     */
    public function add_rule($name, $callback) {
        $this->rules[$name] = $callback;
    }

    /**
     * Add custom error message
     *
     * @since    1.0.0
     * @param    string    $key     Message key
     * @param    string    $message Error message
     */
    public function add_message($key, $message) {
        $this->messages[$key] = $message;
    }

    /**
     * Validate report data
     *
     * @since    1.0.0
     * @param    array    $data    Report data
     * @return   bool|WP_Error
     */
    public function validate_report($data) {
        $rules = array(
            'user_id' => $this->rules['user_id'],
            'report_date' => $this->rules['report_date'],
            'tasks' => array(
                'required' => true,
                'array' => true,
                'min_length' => 1
            )
        );

        // Validate tasks array
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            foreach ($data['tasks'] as $index => $task) {
                $rules["tasks.{$index}.task_description"] = $this->rules['task_description'];
                $rules["tasks.{$index}.completion_status"] = array(
                    'required' => true,
                    'in' => array('completed', 'in_progress', 'not_completed')
                );
                $rules["tasks.{$index}.next_action"] = array(
                    'required' => true,
                    'min_length' => 5
                );
            }
        }

        return $this->validate($data, $rules);
    }

    /**
     * Validate salary settings
     *
     * @since    1.0.0
     * @param    array    $data    Salary data
     * @return   bool|WP_Error
     */
    public function validate_salary_settings($data) {
        $rules = array(
            'user_id' => $this->rules['user_id'],
            'monthly_salary' => $this->rules['monthly_salary'],
            'working_days_per_month' => $this->rules['working_days_per_month'],
            'currency' => array(
                'required' => true,
                'in' => array('CAD', 'USD', 'EUR', 'GBP')
            ),
            'effective_from' => array(
                'required' => true,
                'date' => 'Y-m-d'
            )
        );

        return $this->validate($data, $rules);
    }
}
