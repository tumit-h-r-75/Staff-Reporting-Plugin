<?php
/**
 * Report Form View
 *
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$task_categories = get_option('bassmah_task_categories', array());
$task_statuses = get_option('bassmah_task_statuses', array());

// Enqueue public scripts to ensure bassmah_public is available
wp_enqueue_script('bassmah-public-scripts', plugin_dir_url(__FILE__) . '../js/public-scripts.js', array('jquery'), BASSMAH_STAFF_REPORTS_VERSION, true);
wp_localize_script('bassmah-public-scripts', 'bassmah_public', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'rest_url' => rest_url('bassmah/v1/'),
    'nonce' => wp_create_nonce('wp_rest'),
    'user_id' => $current_user->ID,
    'strings' => array(
        'loading' => __('Loading...', 'bassmah-staff-reports'),
        'error_occurred' => __('An error occurred. Please try again.', 'bassmah-staff-reports'),
        'success' => __('Success!', 'bassmah-staff-reports'),
        'report_submitted' => __('Report submitted successfully!', 'bassmah-staff-reports')
    )
));

// Check for duplicate submission
if (!class_exists('Bassmah_Staff_Reports_Duplicate_Check')) {
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-duplicate-check.php';
}
$duplicate_check = new Bassmah_Staff_Reports_Duplicate_Check();

if ($duplicate_check->has_today_report($current_user->ID)) {
    $has_duplicate = true;
    $duplicate_message = __('You have already submitted a report for today. Please contact your manager if you need to make changes.', 'bassmah-staff-reports');
} else {
    $has_duplicate = false;
    $duplicate_message = '';
}
?>

<div class="bassmah-container">
    <form class="bassmah-report-form" id="bassmah-report-form">
        <div class="bassmah-form-header">
            <h2><?php _e('Daily Work Report', 'bassmah-staff-reports'); ?></h2>
            <?php if ($has_duplicate): ?>
                <div class="bassmah-notice bassmah-notice-error">
                    <?php echo $duplicate_message; ?>
                </div>
            <?php else: ?>
                <p><?php _e('Please submit your daily work report below. All fields marked with * are required.', 'bassmah-staff-reports'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($has_duplicate): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var form = document.getElementById('bassmah-report-form');
                    var inputs = form.querySelectorAll('input, select, textarea');
                    inputs.forEach(function(input) {
                        input.disabled = true;
                    });
                    
                    var submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = '<?php _e('Form Disabled - Report Already Submitted', 'bassmah-staff-reports'); ?>';
                    }
                });
            </script>
        <?php endif; ?>

        <!-- Employee Information -->
        <div class="bassmah-form-row half">
            <div>
                <label class="bassmah-form-label" for="employee_name">
                    <?php _e('Employee Name', 'bassmah-staff-reports'); ?> *
                </label>
                <input type="text" 
                       id="employee_name" 
                       class="bassmah-form-input" 
                       value="<?php echo esc_attr($current_user->display_name); ?>" 
                       <?php echo $has_duplicate ? 'readonly disabled' : 'readonly'; ?>
                       aria-required="true">
            </div>
            <div>
                <label class="bassmah-form-label" for="employee_role">
                    <?php _e('Role/Department', 'bassmah-staff-reports'); ?> *
                </label>
                <input type="text" 
                       id="employee_role" 
                       class="bassmah-form-input" 
                       value="<?php echo esc_html(Bassmah_Staff_Reports_Roles::get_user_role_display($current_user->ID)); ?>" 
                       readonly 
                       disabled
                       aria-required="true">
            </div>
        </div>

        <!-- Report Date -->
        <div class="bassmah-form-row">
            <label class="bassmah-form-label" for="report_date">
                <?php _e('Report Date', 'bassmah-staff-reports'); ?> *
            </label>
            <input type="text" 
                   id="report_date" 
                   class="bassmah-form-input bassmah-datepicker" 
                   value="<?php echo current_time('Y-m-d'); ?>" 
                   readonly 
                   disabled
                   aria-required="true">
            <small><?php _e('Only today\'s date is allowed for report submission.', 'bassmah-staff-reports'); ?></small>
        </div>

        <!-- Tasks Section -->
        <div class="bassmah-form-row">
            <h3><?php _e('Tasks Completed Today', 'bassmah-staff-reports'); ?></h3>
            <p><?php _e('Please add all tasks you completed today. You can add multiple tasks.', 'bassmah-staff-reports'); ?></p>
        </div>

        <div class="bassmah-task-container">
            <!-- First task row (template) -->
            <div class="bassmah-task-row">
                <div class="bassmah-task-header">
                    <span class="bassmah-task-number"><?php _e('Task 1', 'bassmah-staff-reports'); ?></span>
                    <button type="button" class="bassmah-remove-task" style="display: none;" aria-label="<?php _e('Remove task', 'bassmah-staff-reports'); ?>">
                        <?php _e('Remove', 'bassmah-staff-reports'); ?>
                    </button>
                </div>

                <div class="bassmah-task-grid">
                    <div>
                        <label class="bassmah-form-label" for="task_category_1">
                            <?php _e('Task Category', 'bassmah-staff-reports'); ?>
                        </label>
                        <select id="task_category_1" class="bassmah-form-select bassmah-task-category" aria-required="true">
                            <option value=""><?php _e('Select Category', 'bassmah-staff-reports'); ?></option>
                            <?php foreach ($task_categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>">
                                    <?php echo esc_html($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="bassmah-form-label" for="completion_status_1">
                            <?php _e('Completion Status', 'bassmah-staff-reports'); ?> *
                        </label>
                        <div class="bassmah-radio-group">
                            <?php foreach ($task_statuses as $value => $label): ?>
                                <label class="bassmah-radio-label">
                                    <input type="radio" 
                                           name="completion_status_1" 
                                           value="<?php echo esc_attr($value); ?>"
                                           <?php echo ($value === 'completed') ? 'checked' : ''; ?>
                                           aria-required="true">
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="bassmah-task-full">
                    <label class="bassmah-form-label" for="task_description_1">
                        <?php _e('Task Description', 'bassmah-staff-reports'); ?> *
                    </label>
                    <textarea id="task_description_1" 
                              class="bassmah-form-textarea bassmah-task-description" 
                              placeholder="<?php _e('Describe what you worked on...', 'bassmah-staff-reports'); ?>"
                              required 
                              aria-required="true"></textarea>
                </div>

                <div class="bassmah-task-grid">
                    <div>
                        <label class="bassmah-form-label" for="next_action_1">
                            <?php _e('Next Action', 'bassmah-staff-reports'); ?> *
                        </label>
                        <textarea id="next_action_1" 
                                  class="bassmah-form-textarea bassmah-next-action" 
                                  placeholder="<?php _e('What are the next steps?', 'bassmah-staff-reports'); ?>"
                                  required 
                                  aria-required="true"></textarea>
                    </div>

                    <div>
                        <label class="bassmah-form-label" for="manager_task_1">
                            <?php _e('Manager Assigned Task', 'bassmah-staff-reports'); ?>
                        </label>
                        <textarea id="manager_task_1" 
                                  class="bassmah-form-textarea bassmah-manager-task" 
                                  placeholder="<?php _e('Any tasks assigned by manager...', 'bassmah-staff-reports'); ?>"></textarea>
                    </div>
                </div>

                <div class="bassmah-task-full">
                    <label class="bassmah-form-label" for="additional_notes_1">
                        <?php _e('Additional Notes', 'bassmah-staff-reports'); ?>
                    </label>
                    <textarea id="additional_notes_1" 
                              class="bassmah-form-textarea bassmah-additional-notes" 
                              placeholder="<?php _e('Any additional information...', 'bassmah-staff-reports'); ?>"></textarea>
                </div>
            </div>

            <!-- Hidden template for new tasks -->
            <div class="bassmah-task-row bassmah-task-template" style="display: none;">
                <div class="bassmah-task-header">
                    <span class="bassmah-task-number"><?php _e('Task X', 'bassmah-staff-reports'); ?></span>
                    <button type="button" class="bassmah-remove-task" aria-label="<?php _e('Remove task', 'bassmah-staff-reports'); ?>">
                        <?php _e('Remove', 'bassmah-staff-reports'); ?>
                    </button>
                </div>

                <div class="bassmah-task-grid">
                    <div>
                        <label class="bassmah-form-label" for="task_category_X">
                            <?php _e('Task Category', 'bassmah-staff-reports'); ?>
                        </label>
                        <select id="task_category_X" class="bassmah-form-select bassmah-task-category" disabled aria-required="true">
                            <option value=""><?php _e('Select Category', 'bassmah-staff-reports'); ?></option>
                            <?php foreach ($task_categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>">
                                    <?php echo esc_html($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="bassmah-form-label" for="completion_status_X">
                            <?php _e('Completion Status', 'bassmah-staff-reports'); ?> *
                        </label>
                        <div class="bassmah-radio-group">
                            <?php foreach ($task_statuses as $value => $label): ?>
                                <label class="bassmah-radio-label">
                                    <input type="radio" 
                                           name="completion_status_X" 
                                           value="<?php echo esc_attr($value); ?>"
                                           <?php echo ($value === 'completed') ? 'checked' : ''; ?>
                                           disabled 
                                           aria-required="true">
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="bassmah-task-full">
                    <label class="bassmah-form-label" for="task_description_X">
                        <?php _e('Task Description', 'bassmah-staff-reports'); ?> *
                    </label>
                    <textarea id="task_description_X" 
                              class="bassmah-form-textarea bassmah-task-description" 
                              placeholder="<?php _e('Describe what you worked on...', 'bassmah-staff-reports'); ?>"
                              disabled 
                              required 
                              aria-required="true"></textarea>
                </div>

                <div class="bassmah-task-grid">
                    <div>
                        <label class="bassmah-form-label" for="next_action_X">
                            <?php _e('Next Action', 'bassmah-staff-reports'); ?> *
                        </label>
                        <textarea id="next_action_X" 
                                  class="bassmah-form-textarea bassmah-next-action" 
                                  placeholder="<?php _e('What are the next steps?', 'bassmah-staff-reports'); ?>"
                                  disabled 
                                  required 
                                  aria-required="true"></textarea>
                    </div>

                    <div>
                        <label class="bassmah-form-label" for="manager_task_X">
                            <?php _e('Manager Assigned Task', 'bassmah-staff-reports'); ?>
                        </label>
                        <textarea id="manager_task_X" 
                                  class="bassmah-form-textarea bassmah-manager-task" 
                                  placeholder="<?php _e('Any tasks assigned by manager...', 'bassmah-staff-reports'); ?>"
                                  disabled></textarea>
                    </div>
                </div>

                <div class="bassmah-task-full">
                    <label class="bassmah-form-label" for="additional_notes_X">
                        <?php _e('Additional Notes', 'bassmah-staff-reports'); ?>
                    </label>
                    <textarea id="additional_notes_X" 
                              class="bassmah-form-textarea bassmah-additional-notes" 
                              placeholder="<?php _e('Any additional information...', 'bassmah-staff-reports'); ?>"
                              disabled></textarea>
                </div>
            </div>
        </div>

        <!-- Add Task Button -->
        <div class="bassmah-form-row">
            <button type="button" class="bassmah-button bassmah-button-secondary bassmah-add-task">
                <?php _e('+ Add Another Task', 'bassmah-staff-reports'); ?>
            </button>
        </div>

        <!-- Form Actions -->
        <div class="bassmah-form-actions">
            <button type="submit" class="bassmah-button bassmah-button-primary">
                <?php _e('Submit Report', 'bassmah-staff-reports'); ?>
            </button>
            <button type="button" class="bassmah-button bassmah-button-secondary" onclick="window.location.reload()">
                <?php _e('Clear Form', 'bassmah-staff-reports'); ?>
            </button>
        </div>
    </form>
</div>

<script>
// Add user ID to JavaScript for API calls
bassmah_public.user_id = <?php echo $current_user->ID; ?>;
</script>