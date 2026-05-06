<div class="bsr-report-form">
    <h2>Submit Daily Report</h2>
    
    <form id="bsr-report-form" method="post">
        <div class="form-row">
            <label>Date:</label>
            <input type="date" name="report_date" required value="<?php echo current_time('Y-m-d'); ?>">
        </div>
        
        <div class="form-row">
            <label>Task Description:</label>
            <textarea name="task_description" rows="4" required placeholder="What did you work on today?"></textarea>
        </div>
        
        <div class="form-row">
            <label>Status:</label>
            <select name="task_status" required>
                <option value="">Select Status</option>
                <option value="completed">Completed</option>
                <option value="in-progress">In Progress</option>
                <option value="not-completed">Not Completed</option>
            </select>
        </div>
        
        <div class="form-row">
            <label>Next Action:</label>
            <textarea name="next_action" rows="2" required placeholder="What will you do next?"></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Submit Report</button>
    </form>
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
</style>
