<?php
/**
 * Service Container for Dependency Injection
 *
 * Implements a simple dependency injection container for
 * managing service instances and their dependencies.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Service_Container {

    /**
     * Registered services
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $services    Service registry
     */
    private $services = array();

    /**
     * Service instances
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $instances    Service instances
     */
    private $instances = array();

    /**
     * Singleton instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Service_Container    $instance    Container instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since    1.0.0
     * @return   Bassmah_Staff_Reports_Service_Container
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a service
     *
     * @since    1.0.0
     * @param    string    $name          Service name
     * @param    callable  $resolver      Service resolver
     * @param    bool      $singleton     Whether service is singleton
     */
    public function register($name, $resolver, $singleton = true) {
        $this->services[$name] = array(
            'resolver' => $resolver,
            'singleton' => $singleton
        );
    }

    /**
     * Get a service instance
     *
     * @since    1.0.0
     * @param    string    $name    Service name
     * @return   mixed
     */
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new Exception("Service '{$name}' not found");
        }

        $service = $this->services[$name];

        if ($service['singleton'] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $instance = call_user_func($service['resolver'], $this);

        if ($service['singleton']) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Check if service is registered
     *
     * @since    1.0.0
     * @param    string    $name    Service name
     * @return   bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }

    /**
     * Register core services
     *
     * @since    1.0.0
     */
    public function register_core_services() {
        // Database Manager
        $this->register('database', function($container) {
            return new Bassmah_Staff_Reports_Database_Manager();
        });

        // Cache Manager
        $this->register('cache', function($container) {
            return new Bassmah_Staff_Reports_Cache_Manager();
        });

        // Security Manager
        $this->register('security', function($container) {
            return new Bassmah_Staff_Reports_Security_Manager();
        });

        // Validation Manager
        $this->register('validation', function($container) {
            return new Bassmah_Staff_Reports_Validation_Manager();
        });

        // File Manager
        // @TODO: Implement Bassmah_Staff_Reports_File_Manager
        // $this->register('file', function($container) {
        //     return new Bassmah_Staff_Reports_File_Manager();
        // });

        // Config Manager
        // @TODO: Implement Bassmah_Staff_Reports_Config_Manager
        // $this->register('config', function($container) {
        //     return new Bassmah_Staff_Reports_Config_Manager();
        // });

        // Report Service
        $this->register('report', function($container) {
            return new Bassmah_Staff_Reports_Report_Service(
                $container->get('database'),
                $container->get('cache'),
                $container->get('security'),
                $container->get('validation')
            );
        });

        // Salary Service
        $this->register('salary', function($container) {
            return new Bassmah_Staff_Reports_Salary_Service(
                $container->get('database'),
                $container->get('cache'),
                $container->get('validation'),
                $container->get('working_days')
            );
        });

        // Working Days Service
        $this->register('working_days', function($container) {
            return new Bassmah_Staff_Reports_Working_Days_Service(
                $container->get('database'),
                $container->get('cache'),
                $container->get('validation')
            );
        });

        // User Service
        $this->register('user', function($container) {
            return new Bassmah_Staff_Reports_User_Service(
                $container->get('database'),
                $container->get('security')
            );
        });

        // Notification Service
        // @TODO: Implement Bassmah_Staff_Reports_Notification_Service
        // $this->register('notification', function($container) {
        //     return new Bassmah_Staff_Reports_Notification_Service(
        //         $container->get('config'),
        //         $container->get('file')
        //     );
        // });

        // Export Service
        // @TODO: Implement Bassmah_Staff_Reports_Export_Service
        // $this->register('export', function($container) {
        //     return new Bassmah_Staff_Reports_Export_Service(
        //         $container->get('file'),
        //         $container->get('validation')
        //     );
        // });

        // Audit Service
        // @TODO: Implement Bassmah_Staff_Reports_Audit_Service
        // $this->register('audit', function($container) {
        //     return new Bassmah_Staff_Reports_Audit_Service(
        //         $container->get('database'),
        //         $container->get('security')
        //     );
        // });
    }
}
