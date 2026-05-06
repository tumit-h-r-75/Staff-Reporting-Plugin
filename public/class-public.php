<?php
class Basmah_Staff_Reports_Public {
    public function __construct() {
        add_shortcode('bsr_staff_dashboard', array($this, 'render_staff_dashboard'));
        add_shortcode('bassmah_staff_dashboard', array($this, 'render_bassmah_staff_dashboard'));
        add_shortcode('bsr_report_form', array($this, 'render_report_form'));
        add_shortcode('bassmah_report_form', array($this, 'render_bassmah_report_form'));
        add_shortcode('bsr_my_reports', array($this, 'render_my_reports'));
        add_shortcode('bassmah_my_reports', array($this, 'render_bassmah_my_reports'));
        add_shortcode('bsr_salary_view', array($this, 'render_salary_view'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('init', array($this, 'handle_report_submission'));
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style('bsr-public-css', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/style.css');
        wp_enqueue_style('bsr-tailwind-utilities', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/css/tailwind-utilities.css', array(), '1.0.0');
        wp_enqueue_script('bsr-public-js', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/script.js', array('jquery'), null, true);
        
        // Localize script with nonce and other data
        wp_localize_script('bsr-public-js', 'bsrData', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rest_url('bassmah/v1/'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ));
        
        // Enqueue React-style components
        wp_enqueue_style('bsr-react-components-css', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/react-components/index.css', array(), '1.0.0');
        wp_enqueue_script('bsr-react-components-js', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/react-components/index.js', array('jquery'), '1.0.0', true);
        
        // Pass WordPress data to JavaScript
        wp_localize_script('bsr-react-components-js', 'bsrData', array(
            'apiUrl' => rest_url('bsr/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'userName' => wp_get_current_user()->display_name,
            'userRole' => $this->get_user_role(),
            'pluginUrl' => BASMAH_STAFF_REPORTS_PLUGIN_URL
        ));
    }
    
    public function render_staff_dashboard() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/dashboard.php';
        return ob_get_clean();
    }

    public function render_bassmah_staff_dashboard() {
        if (!is_user_logged_in()) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">Please log in to view your dashboard.</p>';
        }
        
        $is_admin = current_user_can('manage_options');
        $can_view = current_user_can('basmah_view_my_salary') || $is_admin;
        
        if (!$can_view) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">You do not have permission to view the dashboard.</p>';
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        
        // Include modern dashboard
        ob_start();
        ?>
        <div class="bsr-card max-w-4xl mx-auto p-6">
            <div class="bsr-card-header">
                <h2 class="bsr-card-title text-2xl">Staff Dashboard</h2>
                <p class="text-muted-foreground">Welcome back, <strong><?php echo esc_html($user->display_name); ?></strong>!</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <!-- Quick Actions Card -->
                <div class="bsr-card">
                    <div class="bsr-card-header">
                        <h3 class="bsr-card-title text-lg">🚀 Quick Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="/report-form" class="bsr-button bsr-button-primary w-full">
                            📝 Submit Report
                        </a>
                        <a href="/my-reports" class="bsr-button bsr-button-secondary w-full">
                            📋 My Reports
                        </a>
                        <a href="/salary" class="bsr-button bsr-button-secondary w-full">
                            💰 Salary Info
                        </a>
                    </div>
                </div>
                
                <!-- Today's Status Card -->
                <div class="bsr-card">
                    <div class="bsr-card-header">
                        <h3 class="bsr-card-title text-lg">📊 Today's Status</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Date:</span>
                                <span class="font-semibold"><?php echo current_time('Y-m-d'); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Status:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✅ Ready
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Report Submitted:</span>
                                <span class="font-semibold" id="today-report-status">Not yet</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Overview Card -->
                <div class="bsr-card">
                    <div class="bsr-card-header">
                        <h3 class="bsr-card-title text-lg">📈 This Month</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Reports:</span>
                                <span class="font-semibold" id="monthly-reports">0</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Working Days:</span>
                                <span class="font-semibold">22</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Completion:</span>
                                <span class="font-semibold text-green-600" id="completion-rate">0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="mt-8">
                <div class="bsr-card">
                    <div class="bsr-card-header">
                        <h3 class="bsr-card-title text-lg">📋 Recent Activity</h3>
                    </div>
                    <div class="p-6">
                        <div id="recent-activity" class="space-y-3">
                            <p class="text-muted-foreground text-sm">Loading recent activity...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .bsr-card {
            border-radius: calc(var(--radius) + 6px);
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--card));
            color: hsl(var(--card-foreground));
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }
        
        .bsr-card-header {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            padding: 1.5rem;
            padding-bottom: 0;
        }
        
        .bsr-card-title {
            font-size: 1.125rem;
            line-height: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.025em;
        }
        
        .bsr-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
            border-radius: calc(var(--radius) + 2px);
            font-size: 0.875rem;
            font-weight: 500;
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
            transition-duration: 150ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            outline: 2px solid transparent;
            outline-offset: 2px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        
        .bsr-button-primary {
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }
        
        .bsr-button-primary:hover {
            background-color: hsl(var(--primary) / 0.9);
        }
        
        .bsr-button-secondary {
            background-color: hsl(var(--secondary));
            color: hsl(var(--secondary-foreground));
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        
        .bsr-button-secondary:hover {
            background-color: hsl(var(--secondary) / 0.8);
        }
        
        .w-full {
            width: 100%;
        }
        
        .space-y-3 > * + * {
            margin-top: 0.75rem;
        }
        
        .space-y-4 > * + * {
            margin-top: 1rem;
        }
        
        .grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        .gap-6 {
            gap: 1.5rem;
        }
        
        .mt-6 {
            margin-top: 1.5rem;
        }
        
        .mt-8 {
            margin-top: 2rem;
        }
        
        .p-6 {
            padding: 1.5rem;
        }
        
        .text-2xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .text-lg {
            font-size: 1.125rem;
            line-height: 1.75rem;
        }
        
        .text-sm {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        
        .text-muted-foreground {
            color: hsl(var(--muted-foreground));
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        .text-green-600 {
            color: #16a34a;
        }
        
        .bg-green-100 {
            background-color: #dcfce7;
        }
        
        .text-green-800 {
            color: #166534;
        }
        
        .text-xs {
            font-size: 0.75rem;
            line-height: 1rem;
        }
        
        .inline-flex {
            display: inline-flex;
        }
        
        .items-center {
            align-items: center;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .px-2 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .py-1 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
        
        .rounded-full {
            border-radius: 9999px;
        }
        
        .max-w-4xl {
            max-width: 56rem;
        }
        
        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }
        
        @media (min-width: 768px) {
            .md\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Load today's report status
            $.ajax({
                url: '/wp-json/bassmah/v1/reports/mine',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', bsrData ? bsrData.nonce : '');
                },
                success: function(reports) {
                    const today = new Date().toISOString().split('T')[0];
                    const todayReport = reports.find(r => r.report_date === today);
                    
                    if (todayReport) {
                        $('#today-report-status').text('Submitted ✅').addClass('text-green-600');
                    }
                    
                    // Update monthly stats
                    const currentMonth = new Date().getMonth();
                    const currentYear = new Date().getFullYear();
                    const monthReports = reports.filter(r => {
                        const reportDate = new Date(r.report_date);
                        return reportDate.getMonth() === currentMonth && reportDate.getFullYear() === currentYear;
                    });
                    
                    $('#monthly-reports').text(monthReports.length);
                    const completionRate = Math.round((monthReports.length / 22) * 100);
                    $('#completion-rate').text(completionRate + '%');
                    
                    // Load recent activity
                    if (monthReports.length > 0) {
                        const recentHtml = monthReports.slice(0, 5).map(report => `
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div>
                                    <div class="font-medium">${report.report_date}</div>
                                    <div class="text-sm text-muted-foreground">${report.status}</div>
                                </div>
                                <div class="text-sm">
                                    ${report.status === 'approved' ? '✅' : report.status === 'pending' ? '⏳' : '❌'}
                                </div>
                            </div>
                        `).join('');
                        
                        $('#recent-activity').html(recentHtml);
                    } else {
                        $('#recent-activity').html('<p class="text-muted-foreground text-sm">No reports this month yet.</p>');
                    }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function render_report_form() {
        return $this->render_bassmah_report_form();
    }
    
    public function render_my_reports() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/my-reports.php';
        return ob_get_clean();
    }

    public function render_bassmah_my_reports() {
        if (!is_user_logged_in()) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">Please log in to view your reports.</p>';
        }
        
        $is_admin = current_user_can('manage_options');
        $can_view = current_user_can('basmah_view_my_reports') || $is_admin;
        
        if (!$can_view) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">You do not have permission to view reports.</p>';
        }

        $user_id = get_current_user_id();
        $reports = Basmah_Staff_Reports_Reports::get_reports(array('user_id' => $user_id));

        ob_start();
        ?>
        <div class="bsr-my-reports">
            <h2>My Reports</h2>
            <?php if (empty($reports)): ?>
                <p>No reports found.</p>
            <?php else: ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                                <td>
                                    <span class="status-badge <?php echo sanitize_title($report['status']); ?>">
                                        <?php echo esc_html(ucfirst($report['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_salary_view() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/salary-view.php';
        return ob_get_clean();
    }

    public function render_bassmah_report_form() {
        if (!is_user_logged_in()) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">Please log in to submit a report.</p>';
        }

        $current_user = wp_get_current_user();
        $user_id = get_current_user_id();
        
        // Allow admin (manage_options) to see all reports/forms, but restrict staff to submission only
        $is_admin = current_user_can('manage_options');
        $is_staff = current_user_can('basmah_submit_report');
        
        if (!$is_admin && !$is_staff) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">You do not have permission to submit reports.</p>';
        }

        // Include the actual form file
        ob_start();
        include BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/report-form.php';
        return ob_get_clean();
    }

    // Helper method to get user role
    private function get_user_role() {
        $user = wp_get_current_user();
        if (in_array('administrator', $user->roles)) {
            return 'Administrator';
        } elseif (in_array('basmah_manager', $user->roles)) {
            return 'Manager';
        } elseif (in_array('basmah_staff', $user->roles)) {
            return 'Staff Member';
        } else {
            return 'User';
        }
    }

    public function handle_report_submission() {
        if (!isset($_POST['bassmah_submit_report'])) {
            return;
        }

        if (!is_user_logged_in() || !current_user_can('basmah_submit_report')) {
            return;
        }

        if (!isset($_POST['bassmah_report_nonce']) || !wp_verify_nonce($_POST['bassmah_report_nonce'], 'bassmah_report_submit')) {
            return;
        }

        $user_id = get_current_user_id();
        $report_date = current_time('Y-m-d');
        $tasks = isset($_POST['tasks']) && is_array($_POST['tasks']) ? $_POST['tasks'] : array();
        
        $sanitized_tasks = array();
        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $task_category = isset($task['task_category']) ? sanitize_text_field($task['task_category']) : '';
            $task_description = isset($task['task_description']) ? sanitize_textarea_field($task['task_description']) : '';
            $status = isset($task['status']) ? sanitize_text_field($task['status']) : '';
            $next_action = isset($task['next_action']) ? sanitize_textarea_field($task['next_action']) : '';

            if ($task_category === '' || $task_description === '' || $status === '' || $next_action === '') {
                continue;
            }

            $task_data = array(
                'task_category' => $task_category,
                'task_description' => $task_description,
                'status' => $status,
                'next_action' => $next_action
            );
            
            if (!empty($task['manager_assigned_task'])) {
                $task_data['manager_assigned_task'] = sanitize_textarea_field($task['manager_assigned_task']);
            }
            if (!empty($task['additional_notes'])) {
                $task_data['additional_notes'] = sanitize_textarea_field($task['additional_notes']);
            }
            
            $sanitized_tasks[] = $task_data;
        }

        $existing_report = Basmah_Staff_Reports_Reports::report_exists($user_id, $report_date);

        if ($existing_report) {
            wp_redirect(add_query_arg('duplicate_report', '1'));
            exit;
        }

        if (empty($sanitized_tasks)) {
            wp_redirect(add_query_arg('report_error', '1'));
            exit;
        }

        $inserted = Basmah_Staff_Reports_Reports::create_report(array(
            'user_id' => $user_id,
            'report_date' => $report_date,
            'tasks' => $sanitized_tasks
        ));

        if ($inserted) {
            Basmah_Staff_Reports_Emails::notify_manager_on_report_submission($user_id, $report_date);
            wp_redirect(add_query_arg('report_submitted', '1'));
            exit;
        }

        if (Basmah_Staff_Reports_Reports::report_exists($user_id, $report_date)) {
            wp_redirect(add_query_arg('duplicate_report', '1'));
            exit;
        }

        wp_redirect(add_query_arg('report_error', '1'));
        exit;
    }
}
