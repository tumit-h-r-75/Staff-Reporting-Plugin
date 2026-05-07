<?php
/**
 * Working Days Service - Working Days & Holidays Management
 *
 * Handles working days configuration, holidays management,
 * and business day calculations.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Working_Days_Service {

    /**
     * Database manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     */
    private $database;

    /**
     * Cache manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Cache_Manager    $cache    Cache manager
     */
    private $cache;

    /**
     * Validation manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Validation_Manager    $validation    Validation manager
     */
    private $validation;

    /**
     * Constructor
     *
     * @since    1.0.0
     * @param    Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     * @param    Bassmah_Staff_Reports_Cache_Manager       $cache       Cache manager
     * @param    Bassmah_Staff_Reports_Validation_Manager  $validation  Validation manager
     */
    public function __construct($database, $cache, $validation) {
        $this->database = $database;
        $this->cache = $cache;
        $this->validation = $validation;
    }

    /**
     * Add working day
     *
     * @since    1.0.0
     * @param    array    $data    Working day data
     * @return   int|WP_Error
     */
    public function add_working_day($data) {
        // Validate input data
        $rules = array(
            'work_date' => array(
                'required' => true,
                'date' => 'Y-m-d',
                'unique' => array(
                    'table' => $this->database->get_table_name('working_days'),
                    'column' => 'work_date'
                )
            ),
            'is_holiday' => array(
                'required' => true,
                'boolean' => true
            ),
            'holiday_name' => array(
                'required' => false,
                'max_length' => 100
            ),
            'holiday_type' => array(
                'required' => false,
                'in' => array('public', 'company', 'custom')
            )
        );

        $validation_result = $this->validation->validate($data, $rules);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        // Check permissions
        $permission_check = $this->check_working_days_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Prepare working day data
        $working_day_data = array(
            'work_date' => $data['work_date'],
            'is_holiday' => $data['is_holiday'] ? 1 : 0,
            'holiday_name' => !empty($data['holiday_name']) ? $data['holiday_name'] : null,
            'holiday_type' => $data['holiday_type'] ?? 'public',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $this->database->start_transaction();
        
        try {
            $working_day_id = $this->database->insert('working_days', $working_day_data);
            
            if (!$working_day_id) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to add working day.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete('working_days', 'working_days');
            $this->cache->delete("working_days_{$data['work_date']}", 'working_days');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_working_day_added', $working_day_id, $working_day_data);

            return $working_day_id;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get working days for a date range
     *
     * @since    1.0.0
     * @param    string    $date_from    Start date
     * @param    string    $date_to      End date
     * @return   array
     */
    public function get_working_days_range($date_from, $date_to) {
        $cache_key = 'working_days_range_' . md5($date_from . '_' . $date_to);
        $cached_days = $this->cache->get($cache_key, 'working_days');
        
        if ($cached_days) {
            return $cached_days;
        }

        $working_days = $this->database->get_results(
            "SELECT * FROM {$this->database->get_table_name('working_days')} 
                 WHERE work_date BETWEEN %s AND %s ORDER BY work_date",
            array($date_from, $date_to)
        );

        // Cache the results
        $this->cache->set($cache_key, $working_days, 'working_days', 3600);

        return $working_days;
    }

    /**
     * Get working days in a month
     *
     * @since    1.0.0
     * @param    string    $month    Month in Y-m format
     * @return   array
     */
    public function get_working_days_in_month($month) {
        $cache_key = 'working_days_month_' . $month;
        $cached_days = $this->cache->get($cache_key, 'working_days');
        
        if ($cached_days) {
            return $cached_days;
        }

        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($month . '-01'));

        $working_days = $this->database->get_results(
            "SELECT work_date, is_holiday, holiday_name, holiday_type 
                 FROM {$this->database->get_table_name('working_days')} 
                 WHERE work_date BETWEEN %s AND %s ORDER BY work_date",
            array($start_date, $end_date)
        );

        // Extract just the dates that are working days (not holidays)
        $work_dates = array();
        foreach ($working_days as $day) {
            if (!$day->is_holiday) {
                $work_dates[] = $day->work_date;
            }
        }

        // Cache the results
        $this->cache->set($cache_key, $work_dates, 'working_days', 3600);

        return $work_dates;
    }

    /**
     * Get holidays in a month
     *
     * @since    1.0.0
     * @param    string    $month    Month in Y-m format
     * @return   array
     */
    public function get_holidays_in_month($month) {
        $cache_key = 'holidays_month_' . $month;
        $cached_holidays = $this->cache->get($cache_key, 'working_days');
        
        if ($cached_holidays) {
            return $cached_holidays;
        }

        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($month . '-01'));

        $holidays = $this->database->get_results(
            "SELECT * FROM {$this->database->get_table_name('working_days')} 
                 WHERE work_date BETWEEN %s AND %s AND is_holiday = 1 ORDER BY work_date",
            array($start_date, $end_date)
        );

        // Cache the results
        $this->cache->set($cache_key, $holidays, 'working_days', 3600);

        return $holidays;
    }

    /**
     * Check if a date is a working day
     *
     * @since    1.0.0
     * @param    string    $date    Date in Y-m-d format
     * @return   bool
     */
    public function is_working_day($date) {
        $cache_key = 'is_working_day_' . $date;
        $cached_result = $this->cache->get($cache_key, 'working_days');
        
        if ($cached_result !== null) {
            return $cached_result;
        }

        $working_day = $this->database->get_row(
            "SELECT is_holiday FROM {$this->database->get_table_name('working_days')} 
                 WHERE work_date = %s",
            array($date)
        );

        $is_working = !$working_day || !$working_day->is_holiday;

        // Cache the result
        $this->cache->set($cache_key, $is_working, 'working_days', 86400); // Cache for 1 day

        return $is_working;
    }

    /**
     * Update working day
     *
     * @since    1.0.0
     * @param    int    $working_day_id    Working day ID
     * @param    array  $data              Update data
     * @return   bool|WP_Error
     */
    public function update_working_day($working_day_id, $data) {
        // Check permissions
        $permission_check = $this->check_working_days_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Prepare update data
        $update_data = array('updated_at' => current_time('mysql'));

        if (isset($data['is_holiday'])) {
            $update_data['is_holiday'] = $data['is_holiday'] ? 1 : 0;
        }

        if (isset($data['holiday_name'])) {
            $update_data['holiday_name'] = !empty($data['holiday_name']) ? $data['holiday_name'] : null;
        }

        if (isset($data['holiday_type'])) {
            $update_data['holiday_type'] = $data['holiday_type'];
        }

        $this->database->start_transaction();
        
        try {
            $result = $this->database->update(
                'working_days',
                $update_data,
                array('id' => $working_day_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to update working day.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete('working_days', 'working_days');
            $this->cache->delete("working_days_{$working_day_id}", 'working_days');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_working_day_updated', $working_day_id, $update_data);

            return true;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Delete working day
     *
     * @since    1.0.0
     * @param    int    $working_day_id    Working day ID
     * @return   bool|WP_Error
     */
    public function delete_working_day($working_day_id) {
        // Check permissions
        $permission_check = $this->check_working_days_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Get working day before deletion
        $working_day = $this->database->get_row(
            "SELECT * FROM {$this->database->get_table_name('working_days')} WHERE id = %d",
            array($working_day_id)
        );

        if (!$working_day) {
            return new WP_Error(
                'working_day_not_found',
                __('Working day not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        $this->database->start_transaction();
        
        try {
            $result = $this->database->delete(
                'working_days',
                array('id' => $working_day_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to delete working day.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete('working_days', 'working_days');
            $this->cache->delete("working_days_{$working_day_id}", 'working_days');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_working_day_deleted', $working_day_id, $working_day);

            return true;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Generate working days for a period
     *
     * @since    1.0.0
     * @param    string    $date_from    Start date
     * @param    string    $date_to      End date
     * @return   array
     */
    public function generate_working_days($date_from, $date_to) {
        $working_days = array();
        $current = new DateTime($date_from);
        $end = new DateTime($date_to);

        while ($current <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 7)
            if ($current->format('N') < 6) {
                $working_days[] = $current->format('Y-m-d');
            }
            $current->add(new DateInterval('P1D'));
        }

        return $working_days;
    }

    /**
     * Add standard holidays for a year
     *
     * @since    1.0.0
     * @param    int    $year    Year
     * @return   array
     */
    public function add_standard_holidays($year) {
        $standard_holidays = array(
            // New Year's Day
            array(
                'work_date' => $year . '-01-01',
                'is_holiday' => 1,
                'holiday_name' => 'New Year\'s Day',
                'holiday_type' => 'public'
            ),
            // Canada Day
            array(
                'work_date' => date('Y-m-d', strtotime("{$year}-07-01")),
                'is_holiday' => 1,
                'holiday_name' => 'Canada Day',
                'holiday_type' => 'public'
            ),
            // Christmas Day
            array(
                'work_date' => $year . '-12-25',
                'is_holiday' => 1,
                'holiday_name' => 'Christmas Day',
                'holiday_type' => 'public'
            ),
            // Boxing Day
            array(
                'work_date' => $year . '-12-26',
                'is_holiday' => 1,
                'holiday_name' => 'Boxing Day',
                'holiday_type' => 'public'
            )
        );

        $added_count = 0;
        foreach ($standard_holidays as $holiday) {
            $result = $this->add_working_day($holiday);
            if (!is_wp_error($result)) {
                $added_count++;
            }
        }

        return array(
            'year' => $year,
            'total_holidays' => count($standard_holidays),
            'added_count' => $added_count
        );
    }

    /**
     * Get working days statistics
     *
     * @since    1.0.0
     * @param    string    $month    Month in Y-m format
     * @return   array
     */
    public function get_working_days_stats($month = null) {
        if (!$month) {
            $month = date('Y-m');
        }

        $working_days = $this->get_working_days_in_month($month);
        $holidays = $this->get_holidays_in_month($month);

        // Generate default working days if none exist
        if (empty($working_days) && empty($holidays)) {
            $generated_days = $this->generate_working_days($month . '-01', date('Y-m-t', strtotime($month . '-01')));
            $working_days = $generated_days;
        }

        $total_days = count($working_days);
        $holiday_count = count($holidays);

        return array(
            'month' => $month,
            'total_working_days' => $total_days,
            'holidays' => $holiday_count,
            'actual_working_days' => $total_days - $holiday_count,
            'working_days_list' => $working_days,
            'holidays_list' => $holidays
        );
    }

    /**
     * Check working days management permission
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    private function check_working_days_permission() {
        return current_user_can('bassmah_manage_working_days') ?: 
            new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to manage working days.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
    }

    /**
     * Clear working days cache
     *
     * @since    1.0.0
     */
    public function clear_cache() {
        $this->cache->clear_group('working_days');
    }

    /**
     * Get upcoming holidays
     *
     * @since    1.0.0
     * @param    int    $days    Number of days to look ahead
     * @return   array
     */
    public function get_upcoming_holidays($days = 30) {
        $today = current_time('Y-m-d');
        $future_date = date('Y-m-d', strtotime("+{$days} days"));

        return $this->database->get_results(
            "SELECT * FROM {$this->database->get_table_name('working_days')} 
                 WHERE work_date BETWEEN %s AND %s AND is_holiday = 1 
                 ORDER BY work_date LIMIT 10",
            array($today, $future_date)
        );
    }

    /**
     * Bulk import working days
     *
     * @since    1.0.0
     * @param    array    $working_days    Working days data
     * @return   array
     */
    public function bulk_import_working_days($working_days) {
        $permission_check = $this->check_working_days_permission();
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $imported = array();
        $errors = array();

        $this->database->start_transaction();

        try {
            foreach ($working_days as $index => $day_data) {
                $result = $this->add_working_day($day_data);
                if (is_wp_error($result)) {
                    $errors[] = array(
                        'index' => $index,
                        'data' => $day_data,
                        'error' => $result->get_error_message()
                    );
                } else {
                    $imported[] = $result;
                }
            }

            $this->database->commit();

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'import_failed',
                __('Bulk import failed.', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }

        // Clear cache
        $this->clear_cache();

        return array(
            'imported_count' => count($imported),
            'error_count' => count($errors),
            'errors' => $errors
        );
    }
}
