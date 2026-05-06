<div class="bsr-report-form" id="bsr-report-form">
    <h2>Submit Daily Report</h2>
    
    <div id="bsr-message" style="display: none;"></div>
    
    <form id="bsr-report-form-submit" method="post">
        <div class="form-row">
            <label>Date:</label>
            <input type="date" id="report_date" name="report_date" required value="<?php echo current_time('Y-m-d'); ?>">
        </div>
        
        <div class="form-row">
            <label>Task Description:</label>
            <textarea id="task_description" name="task_description" rows="4" required placeholder="What did you work on today?"></textarea>
        </div>
        
        <div class="form-row">
            <label>Status:</label>
            <select id="task_status" name="task_status" required>
                <option value="">Select Status</option>
                <option value="completed">Completed</option>
                <option value="in-progress">In Progress</option>
                <option value="not-completed">Not Completed</option>
            </select>
        </div>
        
        <div class="form-row">
            <label>Next Action:</label>
            <textarea id="next_action" name="next_action" rows="2" required placeholder="What will you do next?"></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Submit Report</button>
    </form>
</div>

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
