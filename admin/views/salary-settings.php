<div class="wrap">
    <h1>Salary Settings</h1>
    
    <?php if (isset($_GET['salary_saved']) && $_GET['salary_saved'] == 1): ?>
        <div class="notice notice-success is-dismissible">
            <p>Salary settings saved successfully!</p>
        </div>
    <?php endif; ?>
    
    <div class="bsr-admin-container">
        <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Select Staff Member</h2>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Staff Member</label>
                    <select id="staff_select" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                        <option value="">Select a staff member</option>
                        <?php foreach ($staff_users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>">
                                <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div id="salary_form_container" style="display: none; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Salary Configuration</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('bsr_save_salary', 'bsr_salary_nonce'); ?>
                <input type="hidden" name="user_id" id="salary_user_id" value="">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Monthly Salary</label>
                        <input type="number" step="0.01" id="monthly_salary" name="monthly_salary" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Working Days Per Month</label>
                        <input type="number" id="working_days_per_month" name="working_days_per_month" required min="1" max="31" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Currency</label>
                        <select id="currency" name="currency" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="CAD">CAD</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Daily Rate (Calculated)</label>
                        <input type="text" id="daily_rate" readonly style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; background: #f7fafc;">
                    </div>
                </div>
                
                <div style="margin-top: 25px;">
                    <button type="submit" name="bsr_save_salary" class="button button-primary" style="padding: 12px 30px;">
                        Save Salary Settings
                    </button>
                </div>
            </form>
        </div>
        
        <div style="margin-top: 30px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">How It Works</h2>
            
            <div style="background: #ebf8ff; padding: 20px; border-radius: 10px; border-left: 4px solid #4299e1;">
                <h3 style="margin-top: 0; color: #2b6cb0; font-size: 1.1rem;">Salary Calculation</h3>
                <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #2d3748;">
                    <li><strong>Daily Rate</strong> = Monthly Salary ÷ Working Days</li>
                    <li><strong>Missing Days</strong> = Working Days - Reports Submitted</li>
                    <li><strong>Deduction</strong> = Missing Days × Daily Rate</li>
                    <li><strong>Net Salary</strong> = Monthly Salary - Deduction</li>
                </ul>
            </div>
            
            <div style="margin-top: 20px; background: #c6f6d5; padding: 20px; border-radius: 10px; border-left: 4px solid #48bb78;">
                <h3 style="margin-top: 0; color: #276749; font-size: 1.1rem;">Example</h3>
                <p style="margin: 0; color: #2d3748; line-height: 1.8;">
                    <strong>Monthly:</strong> CAD 3,500<br>
                    <strong>Days:</strong> 22<br>
                    <strong>Daily:</strong> CAD 159.09<br>
                    <strong>Submitted:</strong> 18 days<br>
                    <strong>Missing:</strong> 4 days<br>
                    <strong>Deduction:</strong> CAD 636.36<br>
                    <strong>Net:</strong> CAD 2,863.64
                </p>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var staffSettings = <?php 
            $settings = array();
            foreach ($staff_users as $user) {
                $user_settings = Basmah_Staff_Reports_Salary::get_settings($user->ID);
                $settings[$user->ID] = $user_settings;
            }
            echo json_encode($settings);
        ?>;
        
        $('#staff_select').change(function() {
            var userId = $(this).val();
            if (userId) {
                var settings = staffSettings[userId];
                if (settings) {
                    $('#salary_user_id').val(userId);
                    $('#monthly_salary').val(settings.monthly_salary);
                    $('#working_days_per_month').val(settings.working_days_per_month);
                    $('#currency').val(settings.currency);
                    calculateDailyRate();
                    $('#salary_form_container').show();
                }
            } else {
                $('#salary_form_container').hide();
            }
        });
        
        function calculateDailyRate() {
            var salary = parseFloat($('#monthly_salary').val()) || 0;
            var days = parseInt($('#working_days_per_month').val()) || 1;
            var daily = (salary / days).toFixed(2);
            var currency = $('#currency').val();
            $('#daily_rate').val(currency + ' ' + daily);
        }
        
        $('#monthly_salary, #working_days_per_month, #currency').on('input change', calculateDailyRate);
    });
    </script>
</div>
