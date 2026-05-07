<?php
/**
 * Cache Manager - Caching Layer
 *
 * Provides a unified interface for caching operations
 * with WordPress object cache and custom caching strategies.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Cache_Manager {

    /**
     * Cache group prefix
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $prefix    Cache group prefix
     */
    private $prefix = 'bassmah_';

    /**
     * Default cache TTL in seconds
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $default_ttl    Default TTL
     */
    private $default_ttl = 3600; // 1 hour

    /**
     * Cache statistics
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $stats    Cache statistics
     */
    private $stats = array(
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    );

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->default_ttl = apply_filters('bassmah_cache_default_ttl', $this->default_ttl);
    }

    /**
     * Get cached value
     *
     * @since    1.0.0
     * @param    string    $key        Cache key
     * @param    string    $group      Cache group
     * @param    bool      $found      Whether value was found
     * @return   mixed
     */
    public function get($key, $group = 'default', &$found = null) {
        $cache_key = $this->get_cache_key($key);
        $cache_group = $this->get_cache_group($group);
        
        $value = wp_cache_get($cache_key, $cache_group, $found);
        
        if ($found) {
            $this->stats['hits']++;
            do_action('bassmah_cache_hit', $key, $group, $value);
        } else {
            $this->stats['misses']++;
            do_action('bassmah_cache_miss', $key, $group);
        }
        
        return $value;
    }

    /**
     * Set cached value
     *
     * @since    1.0.0
     * @param    string    $key        Cache key
     * @param    mixed     $value      Value to cache
     * @param    string    $group      Cache group
     * @param    int       $ttl        Time to live in seconds
     * @return   bool
     */
    public function set($key, $value, $group = 'default', $ttl = null) {
        $cache_key = $this->get_cache_key($key);
        $cache_group = $this->get_cache_group($group);
        $cache_ttl = $ttl !== null ? $ttl : $this->default_ttl;
        
        $result = wp_cache_set($cache_key, $value, $cache_group, $cache_ttl);
        
        if ($result) {
            $this->stats['sets']++;
            do_action('bassmah_cache_set', $key, $group, $value, $cache_ttl);
        }
        
        return $result;
    }

    /**
     * Delete cached value
     *
     * @since    1.0.0
     * @param    string    $key        Cache key
     * @param    string    $group      Cache group
     * @return   bool
     */
    public function delete($key, $group = 'default') {
        $cache_key = $this->get_cache_key($key);
        $cache_group = $this->get_cache_group($group);
        
        $result = wp_cache_delete($cache_key, $cache_group);
        
        if ($result) {
            $this->stats['deletes']++;
            do_action('bassmah_cache_delete', $key, $group);
        }
        
        return $result;
    }

    /**
     * Clear cache group
     *
     * @since    1.0.0
     * @param    string    $group    Cache group
     * @return   bool
     */
    public function clear_group($group) {
        $cache_group = $this->get_cache_group($group);
        $result = wp_cache_delete_group($cache_group);
        
        do_action('bassmah_cache_group_cleared', $group);
        
        return $result;
    }

    /**
     * Clear all plugin cache
     *
     * @since    1.0.0
     * @return   bool
     */
    public function clear_all() {
        $groups = array('reports', 'salary', 'users', 'working_days', 'settings');
        $success = true;
        
        foreach ($groups as $group) {
            if (!$this->clear_group($group)) {
                $success = false;
            }
        }
        
        do_action('bassmah_cache_cleared');
        
        return $success;
    }

    /**
     * Remember function result
     *
     * @since    1.0.0
     * @param    string    $key        Cache key
     * @param    callable  $callback   Callback function
     * @param    string    $group      Cache group
     * @param    int       $ttl        Time to live
     * @return   mixed
     */
    public function remember($key, $callback, $group = 'default', $ttl = null) {
        $value = $this->get($key, $group, $found);
        
        if ($found) {
            return $value;
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $group, $ttl);
        
        return $value;
    }

    /**
     * Get multiple values
     *
     * @since    1.0.0
     * @param    array     $keys    Cache keys
     * @param    string    $group   Cache group
     * @return   array
     */
    public function get_multiple($keys, $group = 'default') {
        $results = array();
        
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $group);
        }
        
        return $results;
    }

    /**
     * Set multiple values
     *
     * @since    1.0.0
     * @param    array     $data    Key-value pairs
     * @param    string    $group   Cache group
     * @param    int       $ttl     Time to live
     * @return   bool
     */
    public function set_multiple($data, $group = 'default', $ttl = null) {
        $success = true;
        
        foreach ($data as $key => $value) {
            if (!$this->set($key, $value, $group, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Increment cached value
     *
     * @since    1.0.0
     * @param    string    $key        Cache key
     * @param    int       $offset     Increment offset
     * @param    string    $group      Cache group
     * @return   int|false
     */
    public function increment($key, $offset = 1, $group = 'default') {
        $value = $this->get($key, $group);
        
        if ($value === false) {
            $value = 0;
        }
        
        $new_value = $value + $offset;
        $this->set($key, $new_value, $group);
        
        return $new_value;
    }

    /**
     * Decrement cached value
     *
     * @since    1.0.0
     * @param    string    $key        Cache key
     * @param    int       $offset     Decrement offset
     * @param    string    $group      Cache group
     * @return   int|false
     */
    public function decrement($key, $offset = 1, $group = 'default') {
        return $this->increment($key, -$offset, $group);
    }

    /**
     * Check if key exists in cache
     *
     * @since    1.0.0
     * @param    string    $key    Cache key
     * @param    string    $group  Cache group
     * @return   bool
     */
    public function exists($key, $group = 'default') {
        $found = false;
        $this->get($key, $group, $found);
        return $found;
    }

    /**
     * Get cache statistics
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_stats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hit_rate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
        
        return array_merge($this->stats, array(
            'total_requests' => $total,
            'hit_rate' => round($hit_rate, 2)
        ));
    }

    /**
     * Reset cache statistics
     *
     * @since    1.0.0
     */
    public function reset_stats() {
        $this->stats = array(
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0
        );
    }

    /**
     * Get cache key with prefix
     *
     * @since    1.0.0
     * @param    string    $key    Original key
     * @return   string
     */
    private function get_cache_key($key) {
        return $this->prefix . $key;
    }

    /**
     * Get cache group with prefix
     *
     * @since    1.0.0
     * @param    string    $group    Original group
     * @return   string
     */
    private function get_cache_group($group) {
        return $this->prefix . $group;
    }

    /**
     * Warm up cache with common data
     *
     * @since    1.0.0
     */
    public function warm_up() {
        do_action('bassmah_cache_warm_up');
        
        // Warm up common queries
        $this->set('plugin_version', BASSMAH_STAFF_REPORTS_VERSION, 'settings', 86400);
        $this->set('last_cache_warm_up', current_time('timestamp'), 'settings', 3600);
    }

    /**
     * Get cache size estimate
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_size_info() {
        // This is an approximation since WordPress cache doesn't provide size info
        return array(
            'estimated_size' => 'unknown',
            'note' => 'WordPress object cache does not provide size information',
            'stats' => $this->get_stats()
        );
    }
}
