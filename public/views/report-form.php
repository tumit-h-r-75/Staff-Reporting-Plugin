<div class="bsr-card max-w-2xl mx-auto p-6" id="bsr-report-form">
    <div class="bsr-card-header">
        <h2 class="bsr-card-title text-2xl">Submit Daily Report</h2>
        <p class="text-muted-foreground">Fill in your daily work report below</p>
    </div>
    
    <div class="mt-6">
        <div id="bsr-message" class="p-4 rounded-lg mb-4" style="display: none;"></div>
        
        <form id="bsr-report-form-submit" class="space-y-6">
            <div class="bsr-form-row">
                <label for="report_date" class="bsr-label">Report Date</label>
                <input 
                    type="date" 
                    id="report_date" 
                    name="report_date" 
                    required 
                    value="<?php echo current_time('Y-m-d'); ?>"
                    class="bsr-input"
                    max="<?php echo current_time('Y-m-d'); ?>"
                >
            </div>
            
            <div id="tasks-container">
                <div class="task-item" data-task-index="0">
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
                                &#10004; Completed
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="completion_status[]" value="in-progress" required>
                                &#10226; In Progress
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="completion_status[]" value="not-completed" required>
                                &#10060; Not Completed
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
                </div>
            </div>
            
            <button type="button" onclick="addTask()" class="bsr-button bsr-button-secondary">
                + Add Another Task
            </button>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" class="bsr-button bsr-button-primary bsr-button-lg">
                    Submit Report
                </button>
                <button type="button" onclick="window.location.reload()" class="bsr-button bsr-button-secondary bsr-button-default">
                    Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="bsr-success-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-card text-card-foreground p-6 rounded-xl shadow-lg max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-lg">Report Submitted Successfully!</h3>
                <p class="text-muted-foreground">Your daily report has been submitted</p>
            </div>
        </div>
        
        <div class="bg-muted p-4 rounded-lg mb-4">
            <p class="text-sm">
                <strong>Date:</strong> <span id="submitted-date"></span>
            </p>
            <p class="text-sm mt-1 text-muted-foreground">
                You cannot submit another report for this date today.
            </p>
        </div>
        
        <div class="flex gap-3">
            <button onclick="closeModal()" class="bsr-button bsr-button-primary bsr-button-default">
                Close
            </button>
            <button onclick="window.location.href='/dashboard'" class="bsr-button bsr-button-secondary bsr-button-default">
                Go to Dashboard
            </button>
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
    font-size: 1.5rem;
    line-height: 2rem;
    font-weight: 600;
    letter-spacing: -0.025em;
}

.bsr-form-row {
    margin-bottom: 1rem;
}

.bsr-label {
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.25rem;
    margin-bottom: 0.25rem;
    display: block;
}

.bsr-input {
    display: flex;
    height: 2.25rem;
    width: 100%;
    border-radius: calc(var(--radius) + 2px);
    border: 1px solid hsl(var(--input));
    background-color: hsl(var(--background));
    font-size: 1rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.25rem 0.75rem;
}

.bsr-input:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px hsl(var(--ring));
}

.bsr-textarea {
    display: flex;
    min-height: 60px;
    width: 100%;
    border-radius: calc(var(--radius) + 2px);
    border: 1px solid hsl(var(--input));
    background-color: hsl(var(--background));
    font-size: 1rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.5rem 0.75rem;
    resize: vertical;
}

.bsr-textarea:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px hsl(var(--ring));
}

.bsr-select {
    display: flex;
    height: 2.25rem;
    width: 100%;
    border-radius: calc(var(--radius) + 2px);
    border: 1px solid hsl(var(--input));
    background-color: hsl(var(--background));
    font-size: 0.875rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.25rem 0.75rem;
}

.bsr-select:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px hsl(var(--ring));
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

.bsr-button-default {
    height: 2.25rem;
    padding: 0.5rem 1rem;
}

.bsr-button-lg {
    height: 2.5rem;
    padding: 0.5rem 2rem;
}

/* Message styling */
#bsr-message.success {
    background-color: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border: 1px solid hsl(var(--primary) / 0.2);
}

#bsr-message.error {
    background-color: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border: 1px solid hsl(var(--destructive) / 0.2);
}

/* Alert styling */
.alert {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: calc(var(--radius) + 2px);
    border: 1px solid;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.alert-success {
    background-color: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border-color: hsl(var(--primary) / 0.2);
}

.alert-danger {
    background-color: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border-color: hsl(var(--destructive) / 0.2);
}

.alert-warning {
    background-color: #fef3c7;
    color: #92400e;
    border-color: #f59e0b;
}

/* Task management styles */
.task-item {
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) + 4px);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background-color: hsl(var(--card));
}

.task-divider {
    border: none;
    border-top: 1px solid hsl(var(--border));
    margin: 1.5rem 0 0 0;
}

.radio-group {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: calc(var(--radius) + 2px);
    border: 1px solid hsl(var(--border));
    background-color: hsl(var(--background));
    transition: all 150ms ease;
}

.radio-label:hover {
    background-color: hsl(var(--muted));
}

.radio-label input[type="radio"] {
    margin: 0;
}

.bsr-button-destructive {
    background-color: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.bsr-button-destructive:hover {
    background-color: hsl(var(--destructive) / 0.9);
}

/* Modal backdrop */
.fixed.inset-0 {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.bg-black-50 {
    background-color: rgba(0, 0, 0, 0.5);
}

.z-50 {
    z-index: 50;
}

/* Responsive */
@media (min-width: 768px) {
    .max-w-2xl {
        max-width: 42rem;
    }
    
    .max-w-md {
        max-width: 28rem;
    }
}

.mx-auto {
    margin-left: auto;
    margin-right: auto;
}

.mx-4 {
    margin-left: 1rem;
    margin-right: 1rem;
}

.space-y-6 > * + * {
    margin-top: 1.5rem;
}

.mt-6 {
    margin-top: 1.5rem;
}

.mb-4 {
    margin-bottom: 1rem;
}

.pt-4 {
    padding-top: 1rem;
}

.p-4 {
    padding: 1rem;
}

.p-6 {
    padding: 1.5rem;
}

.text-muted-foreground {
    color: hsl(var(--muted-foreground));
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

.font-semibold {
    font-weight: 600;
}

.w-12 {
    width: 3rem;
}

.h-12 {
    height: 3rem;
}

.w-6 {
    width: 1.5rem;
}

.h-6 {
    height: 1.5rem;
}

.bg-green-100 {
    background-color: #dcfce7;
}

.text-green-600 {
    color: #16a34a;
}

.bg-muted {
    background-color: hsl(var(--muted));
}

.mt-1 {
    margin-top: 0.25rem;
}

.gap-3 {
    gap: 0.75rem;
}

.gap-4 {
    gap: 1rem;
}

.flex {
    display: flex;
}

.items-center {
    align-items: center;
}

.justify-center {
    justify-content: center;
}

.rounded-xl {
    border-radius: calc(var(--radius) + 6px);
}

.rounded-lg {
    border-radius: calc(var(--radius) + 4px);
}

.rounded-full {
    border-radius: 9999px;
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}
</style>

<!-- Success Modal -->
<div id="bsr-success-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✅ Report Submitted Successfully!</h3>
        </div>
        <div class="modal-body">
            <p>Your daily report has been submitted for <strong id="submitted-date"></strong>.</p>
            <p>You cannot submit another report for this date today.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="close-btn">Close</button>
        </div>
    </div>
</div>

<style>
.bsr-report-form {
    max-width: 500px;
    margin: 20px auto;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
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
    border: 1px solid #ccc;
    border-radius: 3px;
}

.submit-btn {
    background: #0073aa;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.submit-btn:hover {
    background: #005a87;
}

#bsr-message {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    font-weight: bold;
}

#bsr-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#bsr-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Modal Styles */
#bsr-success-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header h3 {
    margin-top: 0;
    color: #28a745;
}

.modal-body {
    margin: 20px 0;
}

.modal-footer {
    margin-top: 20px;
}

.close-btn {
    background: #6c757d;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.close-btn:hover {
    background: #5a6268;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Check if already submitted for today
    var today = new Date().toISOString().split('T')[0];
    
    $.ajax({
        url: '/wp-json/bassmah/v1/reports/mine',
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', bsrData ? bsrData.nonce : '');
        },
        success: function(reports) {
            var hasSubmittedToday = reports.some(function(report) {
                return report.report_date === today;
            });
            
            if (hasSubmittedToday) {
                $('#bsr-report-form').hide();
                $('#bsr-message').html('You have already submitted a report for today. You cannot submit another report for the same date.')
                               .addClass('error')
                               .show();
            }
        }
    });
    
    // Form submission
    $('#bsr-report-form-submit').submit(function(e) {
        e.preventDefault();
        
        var formData = {
            report_date: $('#report_date').val(),
            task_description: $('#task_description').val(),
            task_status: $('#task_status').val(),
            next_action: $('#next_action').val()
        };
        
        // Basic validation
        if (!formData.task_description || !formData.task_status || !formData.next_action) {
            $('#bsr-message').html('Please fill in all required fields.')
                           .addClass('error')
                           .show();
            return;
        }
        
        // Submit via AJAX
        $.ajax({
            url: '/wp-json/bassmah/v1/reports',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bsrData ? bsrData.nonce : '');
            },
            data: JSON.stringify({
                report_date: formData.report_date,
                tasks: [{
                    task_category: 'Daily Work',
                    task_description: formData.task_description,
                    status: formData.task_status,
                    next_action: formData.next_action
                }]
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Show success modal
                    $('#submitted-date').text(formData.report_date);
                    $('#bsr-success-modal').show();
                    
                    // Hide form after showing modal
                    setTimeout(function() {
                        $('#bsr-report-form').hide();
                        $('#bsr-message').hide();
                    }, 1000);
                } else {
                    $('#bsr-message').html(response.message || 'Failed to submit report. Please try again.')
                                   .addClass('error')
                                   .show();
                }
            },
            error: function(xhr) {
                var errorMessage = 'Failed to submit report. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $('#bsr-message').html(errorMessage)
                               .addClass('error')
                               .show();
            }
        });
    });
});

function closeModal() {
    document.getElementById('bsr-success-modal').style.display = 'none';
}
</script>

<style>
.bsr-report-form {
    max-width: 500px;
    margin: 20px auto;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
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
    border: 1px solid #ccc;
    border-radius: 3px;
}

.submit-btn {
    background: #0073aa;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.submit-btn:hover {
    background: #005a87;
}
</style>
