<?php
/**
 * Database Manager - Database Abstraction Layer
 *
 * Provides a clean interface for database operations
 * with proper error handling, logging, and performance monitoring.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Database_Manager {

    /**
     * WordPress database object
     *
     * @since    1.0.0
     * @access   private
     * @var      wpdb    $wpdb    Database instance
     */
    private $wpdb;

    /**
     * Table prefix
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $prefix    Table prefix
     */
    private $prefix;

    /**
     * Query log for debugging
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $query_log    Query log
     */
    private $query_log = array();

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'bassmah_';
    }

    /**
     * Get table name with prefix
     *
     * @since    1.0.0
     * @param    string    $table    Table name
     * @return   string
     */
    public function get_table_name($table) {
        return $this->prefix . $table;
    }

    /**
     * Execute a prepared query
     *
     * @since    1.0.0
     * @param    string    $query    SQL query
     * @param    array     $args     Query arguments
     * @return   mixed
     */
    public function query($query, $args = array()) {
        $start_time = microtime(true);
        
        if (!empty($args)) {
            $query = $this->wpdb->prepare($query, $args);
        }

        $result = $this->wpdb->query($query);
        
        $execution_time = microtime(true) - $start_time;
        $this->log_query($query, $args, $execution_time);

        if ($result === false) {
            $this->handle_error($query, $args);
        }

        return $result;
    }

    /**
     * Get a single row
     *
     * @since    1.0.0
     * @param    string    $query    SQL query
     * @param    array     $args     Query arguments
     * @return   object|null
     */
    public function get_row($query, $args = array()) {
        if (!empty($args)) {
            $query = $this->wpdb->prepare($query, $args);
        }

        return $this->wpdb->get_row($query);
    }

    /**
     * Get multiple rows
     *
     * @since    1.0.0
     * @param    string    $query    SQL query
     * @param    array     $args     Query arguments
     * @return   array
     */
    public function get_results($query, $args = array()) {
        if (!empty($args)) {
            $query = $this->wpdb->prepare($query, $args);
        }

        return $this->wpdb->get_results($query);
    }

    /**
     * Get a single variable
     *
     * @since    1.0.0
     * @param    string    $query    SQL query
     * @param    array     $args     Query arguments
     * @return   mixed
     */
    public function get_var($query, $args = array()) {
        if (!empty($args)) {
            $query = $this->wpdb->prepare($query, $args);
        }

        return $this->wpdb->get_var($query);
    }

    /**
     * Insert a row
     *
     * @since    1.0.0
     * @param    string    $table    Table name
     * @param    array     $data     Data to insert
     * @param    array     $format   Data format
     * @return   int|false
     */
    public function insert($table, $data, $format = null) {
        $table_name = $this->get_table_name($table);
        $result = $this->wpdb->insert($table_name, $data, $format);
        
        if ($result === false) {
            $this->handle_error("INSERT INTO {$table_name}", $data);
        }

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update rows
     *
     * @since    1.0.0
     * @param    string    $table       Table name
     * @param    array     $data        Data to update
     * @param    array     $where       Where conditions
     * @param    array     $format      Data format
     * @param    array     $where_format Where format
     * @return   int|false
     */
    public function update($table, $data, $where, $format = null, $where_format = null) {
        $table_name = $this->get_table_name($table);
        $result = $this->wpdb->update($table_name, $data, $where, $format, $where_format);
        
        if ($result === false) {
            $this->handle_error("UPDATE {$table_name}", array('data' => $data, 'where' => $where));
        }

        return $result;
    }

    /**
     * Delete rows
     *
     * @since    1.0.0
     * @param    string    $table       Table name
     * @param    array     $where       Where conditions
     * @param    array     $where_format Where format
     * @return   int|false
     */
    public function delete($table, $where, $where_format = null) {
        $table_name = $this->get_table_name($table);
        $result = $this->wpdb->delete($table_name, $where, $where_format);
        
        if ($result === false) {
            $this->handle_error("DELETE FROM {$table_name}", $where);
        }

        return $result;
    }

    /**
     * Get last insert ID
     *
     * @since    1.0.0
     * @return   int
     */
    public function get_insert_id() {
        return $this->wpdb->insert_id;
    }

    /**
     * Get number of affected rows
     *
     * @since    1.0.0
     * @return   int
     */
    public function get_affected_rows() {
        return $this->wpdb->rows_affected;
    }

    /**
     * Start a transaction
     *
     * @since    1.0.0
     */
    public function start_transaction() {
        $this->wpdb->query('START TRANSACTION');
    }

    /**
     * Commit a transaction
     *
     * @since    1.0.0
     */
    public function commit() {
        $this->wpdb->query('COMMIT');
    }

    /**
     * Rollback a transaction
     *
     * @since    1.0.0
     */
    public function rollback() {
        $this->wpdb->query('ROLLBACK');
    }

    /**
     * Check if table exists
     *
     * @since    1.0.0
     * @param    string    $table    Table name
     * @return   bool
     */
    public function table_exists($table) {
        $table_name = $this->get_table_name($table);
        $query = "SHOW TABLES LIKE %s";
        return $this->get_var($query, $table_name) === $table_name;
    }

    /**
     * Get table structure
     *
     * @since    1.0.0
     * @param    string    $table    Table name
     * @return   array
     */
    public function get_table_structure($table) {
        $table_name = $this->get_table_name($table);
        return $this->get_results("DESCRIBE {$table_name}");
    }

    /**
     * Create table from schema
     *
     * @since    1.0.0
     * @param    string    $table    Table name
     * @param    string    $schema   SQL schema
     * @return   bool
     */
    public function create_table($table, $schema) {
        $table_name = $this->get_table_name($table);
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} {$schema} {$charset_collate}";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        return $this->table_exists($table);
    }

    /**
     * Drop table
     *
     * @since    1.0.0
     * @param    string    $table    Table name
     * @return   bool
     */
    public function drop_table($table) {
        $table_name = $this->get_table_name($table);
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        return $this->query($sql);
    }

    /**
     * Handle database errors
     *
     * @since    1.0.0
     * @param    string    $query    SQL query
     * @param    array     $args     Query arguments
     */
    private function handle_error($query, $args = array()) {
        $error = $this->wpdb->last_error;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Bassmah DB Error: {$error}");
            error_log("Query: {$query}");
            error_log("Args: " . print_r($args, true));
        }
        
        do_action('bassmah_database_error', $error, $query, $args);
    }

    /**
     * Log queries for performance monitoring
     *
     * @since    1.0.0
     * @param    string    $query          SQL query
     * @param    array     $args           Query arguments
     * @param    float     $execution_time  Execution time
     */
    private function log_query($query, $args, $execution_time) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $this->query_log[] = array(
            'query' => $query,
            'args' => $args,
            'execution_time' => $execution_time,
            'timestamp' => current_time('mysql')
        );

        // Log slow queries (> 100ms)
        if ($execution_time > 0.1) {
            error_log("Bassmah Slow Query ({$execution_time}s): {$query}");
        }
    }

    /**
     * Get query log
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_query_log() {
        return $this->query_log;
    }

    /**
     * Clear query log
     *
     * @since    1.0.0
     */
    public function clear_query_log() {
        $this->query_log = array();
    }

    /**
     * Get database info
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_info() {
        return array(
            'mysql_version' => $this->wpdb->db_version(),
            'mysql_client' => $this->wpdb->db_client_info(),
            'charset' => $this->wpdb->charset,
            'collate' => $this->wpdb->collate,
            'prefix' => $this->wpdb->prefix
        );
    }
}
