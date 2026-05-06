jQuery(document).ready(function($) {
    console.log('Basmah Staff Reports Public JS loaded');
    console.log('bsrData:', bsrData);
    
    // Debug: Check if user has proper capabilities
    console.log('Current user ID:', bsrData ? bsrData.userId : 'Not available');
    console.log('User role:', bsrData ? bsrData.userRole : 'Not available');
    
    // Check if already submitted for today
    function checkTodaySubmission() {
        const today = new Date().toISOString().split('T')[0];
        
        $.ajax({
            url: '/wp-json/bassmah/v1/reports/mine',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bsrData ? bsrData.nonce : '');
            },
            success: function(reports) {
                if (reports.some(r => r.report_date === today)) {
                    $('#bsr-report-form').hide();
                    $('#bsr-message').html('<div class="alert alert-warning">You have already submitted a report for today.</div>').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking today\'s submission:', error);
            }
        });
    }
    
    // Form submission
    $('#bsr-report-form-submit').on('submit', function(e) {
        e.preventDefault();
        
        // Collect all tasks
        const tasks = [];
        $('.task-item').each(function(index) {
            const taskData = {
                task_category: $(this).find('[name="task_category[]"]').val(),
                task_description: $(this).find('[name="task_description[]"]').val(),
                completion_status: $(this).find('[name="completion_status[]"]:checked').val(),
                next_action: $(this).find('[name="next_action[]"]').val(),
                manager_assigned_task: $(this).find('[name="manager_assigned_task[]"]').val(),
                additional_notes: $(this).find('[name="additional_notes[]"]').val()
            };
            
            // Validate task
            if (taskData.task_category && taskData.task_description && taskData.completion_status && taskData.next_action) {
                tasks.push(taskData);
            }
        });
        
        const formData = {
            report_date: $('#report_date').val(),
            tasks: tasks
        };
        
        // Validate form
        if (!formData.report_date || tasks.length === 0) {
            $('#bsr-message').html('<div class="alert alert-danger">Please fill in all required fields and at least one task.</div>').show();
            return;
        }
        
        // Show loading
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Submitting...');
        
        // Submit via AJAX
        $.ajax({
            url: '/wp-json/bassmah/v1/reports',
            method: 'POST',
            data: JSON.stringify({
                user_id: bsrData ? bsrData.userId : 0,
                report_date: formData.report_date,
                tasks: [{
                    task_category: 'daily_report',
                    task_description: formData.task_description,
                    status: formData.task_status,
                    next_action: formData.next_action
                }]
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bsrData ? bsrData.nonce : '');
            },
            success: function(response) {
                if (response.success) {
                    // Show success modal
                    $('#submitted-date').text(formData.report_date);
                    $('#bsr-success-modal').show();
                    
                    // Hide form
                    $('#bsr-report-form').hide();
                    
                    // Show success message
                    $('#bsr-message').html('<div class="alert alert-success">Report submitted successfully!</div>').show();
                } else {
                    $('#bsr-message').html('<div class="alert alert-danger">Error: ' + (response.message || 'Unknown error') + '</div>').show();
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Submission failed. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 401) {
                    errorMessage = 'Authentication failed. Please refresh and try again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Permission denied. You may not have rights to submit reports.';
                }
                
                $('#bsr-message').html('<div class="alert alert-danger">' + errorMessage + '</div>').show();
            },
            complete: function() {
                // Restore button
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Add new task function
    window.addTask = function() {
        const taskCount = $('.task-item').length;
        const newTaskIndex = taskCount;
        
        const newTaskHtml = `
            <div class="task-item" data-task-index="${newTaskIndex}">
                <div class="bsr-form-row">
                    <label class="bsr-label">Task Category</label>
                    <select name="task_category[]" required class="bsr-select">
                        <option value="">Select Category</option>
                        <option value="Client Follow-up">Client Follow-up</option>
                        <option value="Development">Development</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Documentation">Documentation</option>
                        <option value="Training">Training</option>
                        <option value="Administration">Administration</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="bsr-form-row">
                    <label class="bsr-label">Task Description</label>
                    <textarea 
                        name="task_description[]" 
                        rows="3" 
                        required 
                        placeholder="Describe what you worked on..."
                        class="bsr-textarea"
                    ></textarea>
                </div>
                
                <div class="bsr-form-row">
                    <label class="bsr-label">Completion Status</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="completion_status[]" value="completed" required>
                            ✅ Completed
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="completion_status[]" value="in-progress" required>
                            🔄 In Progress
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="completion_status[]" value="not-completed" required>
                            ❌ Not Completed
                        </label>
                    </div>
                </div>
                
                <div class="bsr-form-row">
                    <label class="bsr-label">Next Action</label>
                    <textarea 
                        name="next_action[]" 
                        rows="2" 
                        required 
                        placeholder="What will you do next?"
                        class="bsr-textarea"
                    ></textarea>
                </div>
                
                <div class="bsr-form-row">
                    <label class="bsr-label">Manager Assigned Task</label>
                    <textarea 
                        name="manager_assigned_task[]" 
                        rows="2" 
                        placeholder="Any task assigned by manager..."
                        class="bsr-textarea"
                    ></textarea>
                </div>
                
                <div class="bsr-form-row">
                    <label class="bsr-label">Additional Notes</label>
                    <textarea 
                        name="additional_notes[]" 
                        rows="2" 
                        placeholder="Any additional notes..."
                        class="bsr-textarea"
                    ></textarea>
                </div>
                
                <hr class="task-divider">
                ${taskCount > 0 ? '<button type="button" onclick="removeTask(' + newTaskIndex + ')" class="bsr-button bsr-button-destructive" style="margin-top: 10px;">Remove Task</button>' : ''}
            </div>
        `;
        
        $('#tasks-container').append(newTaskHtml);
    };
    
    // Remove task function
    window.removeTask = function(taskIndex) {
        $(`.task-item[data-task-index="${taskIndex}"]`).remove();
    };
    
    // Close modal function
    window.closeModal = function() {
        $('#bsr-success-modal').hide();
    };
    
    // Check today's submission on page load
    checkTodaySubmission();
});
