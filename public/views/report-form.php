<div class="bsr-report-form" id="bsr-report-form">
    <h2>Submit Daily Report</h2>
    
    <div id="bsr-form-message" style="display: none;"></div>
    
    <form id="bsr-daily-report-form">
        <div class="form-row">
            <label for="report_date">Date:</label>
            <input type="date" id="report_date" name="report_date" required>
        </div>
        
        <div id="tasks-container">
            <div class="task-row">
                <h3>Task 1</h3>
                <div class="form-row">
                    <label for="task_category_1">Task Category:</label>
                    <select id="task_category_1" name="task_category_1" required>
                        <option value="">Select Category</option>
                        <option value="Client Follow-up">Client Follow-up</option>
                        <option value="Documentation">Documentation</option>
                        <option value="Development">Development</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Research">Research</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="task_description_1">Task Description:</label>
                    <textarea id="task_description_1" name="task_description_1" rows="3" required placeholder="Describe what you worked on..."></textarea>
                </div>
                
                <div class="form-row">
                    <label for="task_status_1">Status:</label>
                    <select id="task_status_1" name="task_status_1" required>
                        <option value="">Select Status</option>
                        <option value="completed">Completed</option>
                        <option value="in-progress">In Progress</option>
                        <option value="not-completed">Not Completed</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="next_action_1">Next Action:</label>
                    <textarea id="next_action_1" name="next_action_1" rows="2" required placeholder="What will you do next?"></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" id="add-task-btn" class="button-secondary">+ Add Another Task</button>
            <button type="submit" id="submit-report-btn" class="button-primary">Submit Report</button>
        </div>
    </form>
</div>

<style>
.bsr-report-form {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-row {
    margin-bottom: 15px;
}

.form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-row input,
.form-row select,
.form-row textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.task-row {
    border: 1px solid #e0e0e0;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 5px;
    background: #f9f9f9;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.button-primary {
    background: #0073aa;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.button-secondary {
    background: #f0f0f1;
    color: #333;
    padding: 10px 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

#bsr-form-message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-weight: bold;
}

#bsr-form-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#bsr-form-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
jQuery(document).ready(function($) {
    let taskCount = 1;
    
    // Set today's date as default and prevent past dates
    $('#report_date').val(new Date().toISOString().split('T')[0]);
    $('#report_date').attr('max', new Date().toISOString().split('T')[0]);
    
    // Add task functionality
    $('#add-task-btn').click(function() {
        taskCount++;
        const taskHtml = `
            <div class="task-row">
                <h3>Task ${taskCount}</h3>
                <div class="form-row">
                    <label for="task_category_${taskCount}">Task Category:</label>
                    <select id="task_category_${taskCount}" name="task_category_${taskCount}" required>
                        <option value="">Select Category</option>
                        <option value="Client Follow-up">Client Follow-up</option>
                        <option value="Documentation">Documentation</option>
                        <option value="Development">Development</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Research">Research</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="task_description_${taskCount}">Task Description:</label>
                    <textarea id="task_description_${taskCount}" name="task_description_${taskCount}" rows="3" required placeholder="Describe what you worked on..."></textarea>
                </div>
                
                <div class="form-row">
                    <label for="task_status_${taskCount}">Status:</label>
                    <select id="task_status_${taskCount}" name="task_status_${taskCount}" required>
                        <option value="">Select Status</option>
                        <option value="completed">Completed</option>
                        <option value="in-progress">In Progress</option>
                        <option value="not-completed">Not Completed</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="next_action_${taskCount}">Next Action:</label>
                    <textarea id="next_action_${taskCount}" name="next_action_${taskCount}" rows="2" required placeholder="What will you do next?"></textarea>
                </div>
            </div>
        `;
        $('#tasks-container').append(taskHtml);
    });
    
    // Form submission
    $('#bsr-daily-report-form').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            report_date: $('#report_date').val(),
            tasks: []
        };
        
        // Collect all tasks
        $('.task-row').each(function(index) {
            const taskCategory = $(this).find(`[name^="task_category_"]`).val();
            const taskDescription = $(this).find(`[name^="task_description_"]`).val();
            const taskStatus = $(this).find(`[name^="task_status_"]`).val();
            const nextAction = $(this).find(`[name^="next_action_"]`).val();
            
            if (taskCategory && taskDescription && taskStatus && nextAction) {
                formData.tasks.push({
                    task_category: taskCategory,
                    task_description: taskDescription,
                    status: taskStatus,
                    next_action: nextAction
                });
            }
        });
        
        if (formData.tasks.length === 0) {
            showMessage('Please add at least one task with all required fields.', 'error');
            return;
        }
        
        // Submit via AJAX
        $.ajax({
            url: '/wp-json/bassmah/v1/reports',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bsrData.nonce);
            },
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    showMessage('Report submitted successfully! The form will now be hidden.', 'success');
                    
                    // Hide form after 2 seconds
                    setTimeout(function() {
                        $('#bsr-report-form').hide();
                    }, 2000);
                } else {
                    showMessage(response.message || 'Failed to submit report. Please try again.', 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to submit report. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage(errorMessage, 'error');
            }
        });
    });
    
    function showMessage(message, type) {
        const messageDiv = $('#bsr-form-message');
        messageDiv.text(message)
                   .removeClass('success error')
                   .addClass(type)
                   .show();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 5000);
    }
});
</script>
