<?php
/**
 * Modern Loading Component
 *
 * Provides a beautiful, modern loading interface with multiple styles
 * and animations for better user experience.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Loading_Component {

    /**
     * Available loading styles
     *
     * @since    1.0.0
     * @var      array
     */
    private $styles = array(
        'spinner' => 'spinner-dots',
        'pulse' => 'pulse-ring',
        'skeleton' => 'skeleton-wave',
        'progress' => 'progress-bar'
    );

    /**
     * Available loading sizes
     *
     * @since    1.0.0
     * @var      array
     */
    private $sizes = array(
        'small' => 'loading-sm',
        'medium' => 'loading-md',
        'large' => 'loading-lg',
        'xlarge' => 'loading-xl'
    );

    /**
     * Get loading HTML
     *
     * @since    1.0.0
     * @param    string    $style    Loading style
     * @param    string    $size    Loading size
     * @param    string    $text    Loading text
     * @param    array     $options    Additional options
     * @return   string    Loading HTML
     */
    public function get_loading_html($style = 'spinner', $size = 'medium', $text = '', $options = array()) {
        $defaults = array(
            'overlay' => false,
            'centered' => true,
            'background' => 'rgba(255, 255, 255, 0.9)',
            'color' => '#0073aa'
        );
        
        $options = wp_parse_args($options, $defaults);
        $size_class = isset($this->sizes[$size]) ? $this->sizes[$size] : $this->sizes['medium'];
        
        $html = '<div class="bassmah-loading-wrapper ' . ($options['overlay'] ? 'loading-overlay' : '') . '">';
        
        if ($options['overlay']) {
            $html .= '<div class="loading-backdrop" style="background-color: ' . $options['background'] . ';"></div>';
        }
        
        $html .= '<div class="bassmah-loading-container ' . ($options['centered'] ? 'loading-centered' : '') . '">';
        
        // Add different loading styles
        switch ($style) {
            case 'spinner':
                $html .= $this->get_spinner_html($size_class, $options['color']);
                break;
            case 'pulse':
                $html .= $this->get_pulse_html($size_class, $options['color']);
                break;
            case 'skeleton':
                $html .= $this->get_skeleton_html();
                break;
            case 'progress':
                $html .= $this->get_progress_html($options['color']);
                break;
            default:
                $html .= $this->get_spinner_html($size_class, $options['color']);
        }
        
        if ($text) {
            $html .= '<div class="loading-text">' . esc_html($text) . '</div>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }

    /**
     * Get spinner HTML
     *
     * @since    1.0.0
     * @param    string    $size_class    Size class
     * @param    string    $color        Color
     * @return   string    Spinner HTML
     */
    private function get_spinner_html($size_class, $color) {
        return '<div class="modern-spinner ' . $size_class . '" style="--spinner-color: ' . $color . ';">
                    <div class="spinner-circle"></div>
                    <div class="spinner-circle"></div>
                    <div class="spinner-circle"></div>
                </div>';
    }

    /**
     * Get pulse HTML
     *
     * @since    1.0.0
     * @param    string    $size_class    Size class
     * @param    string    $color        Color
     * @return   string    Pulse HTML
     */
    private function get_pulse_html($size_class, $color) {
        return '<div class="pulse-loader ' . $size_class . '" style="--pulse-color: ' . $color . ';">
                    <div class="pulse-ring"></div>
                    <div class="pulse-ring"></div>
                </div>';
    }

    /**
     * Get skeleton HTML
     *
     * @since    1.0.0
     * @return   string    Skeleton HTML
     */
    private function get_skeleton_html() {
        return '<div class="skeleton-loader">
                    <div class="skeleton-line skeleton-title"></div>
                    <div class="skeleton-line skeleton-text"></div>
                    <div class="skeleton-line skeleton-text"></div>
                    <div class="skeleton-line skeleton-text short"></div>
                </div>';
    }

    /**
     * Get progress HTML
     *
     * @since    1.0.0
     * @param    string    $color    Color
     * @return   string    Progress HTML
     */
    private function get_progress_html($color) {
        return '<div class="progress-loader">
                    <div class="progress-bar" style="--progress-color: ' . $color . ';">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">Loading...</div>
                </div>';
    }

    /**
     * Enqueue loading styles
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'bassmah-loading-component',
            plugin_dir_url(__FILE__) . 'assets/css/loading-component.css',
            array(),
            BASSMAH_STAFF_REPORTS_VERSION
        );
    }

    /**
     * Get loading JavaScript
     *
     * @since    1.0.0
     * @return   string    JavaScript code
     */
    public function get_loading_script() {
        return '
        // Bassmah Loading Component JavaScript
        window.BassmahLoading = {
            show: function(element, options) {
                const defaults = {
                    style: "spinner",
                    size: "medium", 
                    text: "",
                    overlay: false
                };
                
                const settings = Object.assign(defaults, options || {});
                const component = new Bassmah_Staff_Reports_Loading_Component();
                
                element.innerHTML = component.get_loading_html(
                    settings.style,
                    settings.size, 
                    settings.text,
                    {overlay: settings.overlay}
                );
                
                if (settings.overlay) {
                    element.style.position = "relative";
                }
                
                return element;
            },
            
            hide: function(element) {
                const wrapper = element.querySelector(".bassmah-loading-wrapper");
                if (wrapper) {
                    wrapper.remove();
                }
            }
        };';
    }
}
