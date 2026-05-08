<?php
/**
 * Working Days View
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get working days for selected month
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($month . '-01'));
$table_name = $wpdb->prefix . 'staff_working_days';

$working_days = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table_name} 
             WHERE work_date BETWEEN %s AND %s 
             ORDER BY work_date",
        $start_date,
        $end_date
    )
);

// Calculate statistics
$total_days = count($working_days);
$working_days_count = 0;
$holidays_count = 0;

foreach ($working_days as $day) {
    if ($day->is_holiday) {
        $holidays_count++;
    } else {
        $working_days_count++;
    }
}
?>

<div class="wrap bassmah-admin">
    <h1><?php _e('Working Days Management', 'bassmah-staff-reports'); ?></h1>
    
    <!-- Month/Year Filter -->
    <div class="bassmah-filters">
        <form method="get" action="">
            <table class="form-table">
                <tr>
                    <th>
                        <label for="month"><?php _e('Month:', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="month" name="month" id="month" value="<?php echo esc_attr($month); ?>" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="year"><?php _e('Year:', 'bassmah-staff-reports'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="year" id="year" value="<?php echo esc_attr($year); ?>" class="regular-text" min="2020" max="2030">
                    </td>
                </tr>
                
                <tr>
                    <th>&nbsp;</th>
                    <td>
                        <input type="submit" value="<?php _e('Filter', 'bassmah-staff-reports'); ?>" class="button">
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <!-- Actions -->
    <div class="bassmah-actions">
        <h2><?php _e('Quick Actions', 'bassmah-staff-reports'); ?></h2>
        
        <div class="action-buttons">
            <button class="button button-primary" onclick="generateWorkingDays()">
                <?php _e('Generate Working Days', 'bassmah-staff-reports'); ?>
            </button>
            
            <button class="button" onclick="addHolidays()">
                <?php _e('Add Holidays', 'bassmah-staff-reports'); ?>
            </button>
            
            <button class="button" onclick="importHolidays()">
                <?php _e('Import Holidays', 'bassmah-staff-reports'); ?>
            </button>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="bassmah-section">
        <h2><?php _e('Statistics for', 'bassmah-staff-reports'); ?> <?php echo date('F Y', strtotime($month . '-01')); ?></h2>
        
        <div class="stats-grid">
            <div class="stat-item">
                <h3><?php _e('Total Days', 'bassmah-staff-reports'); ?></h3>
                <div class="stat-value"><?php echo $total_days; ?></div>
            </div>
            
            <div class="stat-item">
                <h3><?php _e('Working Days', 'bassmah-staff-reports'); ?></h3>
                <div class="stat-value"><?php echo $working_days_count; ?></div>
            </div>
            
            <div class="stat-item">
                <h3><?php _e('Holidays', 'bassmah-staff-reports'); ?></h3>
                <div class="stat-value"><?php echo $holidays_count; ?></div>
            </div>
        </div>
    </div>
    
    <!-- Working Days Table -->
    <div class="bassmah-section">
        <h2><?php _e('Working Days Calendar', 'bassmah-staff-reports'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped bassmah-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Day', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Type', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Holiday Name', 'bassmah-staff-reports'); ?></th>
                    <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($working_days)): ?>
                    <?php foreach ($working_days as $day): ?>
                        <tr class="<?php echo $day->is_holiday ? 'holiday-row' : 'working-row'; ?>">
                            <td><?php echo esc_html($day->work_date); ?></td>
                            <td><?php echo date('l', strtotime($day->work_date)); ?></td>
                            <td>
                                <span class="day-type <?php echo $day->is_holiday ? 'holiday' : 'working'; ?>">
                                    <?php echo $day->is_holiday ? __('Holiday', 'bassmah-staff-reports') : __('Working', 'bassmah-staff-reports'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($day->holiday_name); ?></td>
                            <td>
                                <button class="button button-small" onclick="editWorkingDay(<?php echo $day->id; ?>)">
                                    <?php _e('Edit', 'bassmah-staff-reports'); ?>
                                </button>
                                <button class="button button-small" onclick="deleteWorkingDay(<?php echo $day->id; ?>)">
                                    <?php _e('Delete', 'bassmah-staff-reports'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?php _e('No working days found for this period.', 'bassmah-staff-reports'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Working Day Modal -->
<div id="working-day-modal" class="bassmah-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title"><?php _e('Add Working Day', 'bassmah-staff-reports'); ?></h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="working-day-form">
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="work_date"><?php _e('Date:', 'bassmah-staff-reports'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="work_date" id="work_date" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for="is_holiday"><?php _e('Is Holiday:', 'bassmah-staff-reports'); ?></label>
                        </th>
                        <td>
                            <select name="is_holiday" id="is_holiday" class="regular-text">
                                <option value="0"><?php _e('Working Day', 'bassmah-staff-reports'); ?></option>
                                <option value="1"><?php _e('Holiday', 'bassmah-staff-reports'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr id="holiday-name-row" style="display: none;">
                        <th>
                            <label for="holiday_name"><?php _e('Holiday Name:', 'bassmah-staff-reports'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="holiday_name" id="holiday_name" class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <div class="form-actions">
                    <input type="hidden" name="working_day_id" id="working_day_id">
                    <input type="hidden" name="action" id="action" value="add">
                    <button type="submit" class="button button-primary"><?php _e('Save', 'bassmah-staff-reports'); ?></button>
                    <button type="button" class="button" onclick="closeModal()"><?php _e('Cancel', 'bassmah-staff-reports'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateWorkingDays() {
    var month = document.getElementById('month').value;
    var year = document.getElementById('year').value;
    
    if (confirm('<?php _e('Generate working days for selected month?', 'bassmah-staff-reports'); ?>')) {
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=generate_working_days&month=' + month + '&year=' + year + '&_wpnonce=<?php echo wp_create_nonce('generate_working_days'); ?>';
    }
}

function addHolidays() {
    showModal('<?php _e('Add Holiday', 'bassmah-staff-reports'); ?>');
}

function importHolidays() {
    if (confirm('<?php _e('Import holidays from CSV file?', 'bassmah-staff-reports'); ?>')) {
        // Create file input and trigger import
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.csv';
        input.onchange = function(e) {
            var file = e.target.files[0];
            if (file) {
                var formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'import_holidays');
                formData.append('_wpnonce', '<?php echo wp_create_nonce('import_holidays'); ?>');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        alert('<?php _e('Holidays imported successfully!', 'bassmah-staff-reports'); ?>');
                        location.reload();
                    } else {
                        alert(data.message || '<?php _e('Import failed', 'bassmah-staff-reports'); ?>');
                    }
                }).catch(error => {
                    console.error('Error:', error);
                    alert('<?php _e('An error occurred during import', 'bassmah-staff-reports'); ?>');
                });
            }
        };
        input.click();
    }
}

function editWorkingDay(id) {
    // Load working day data and show modal
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_working_day&id=' + id + '&_wpnonce=<?php echo wp_create_nonce('get_working_day'); ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('working_day_id').value = data.data.id;
                document.getElementById('work_date').value = data.data.work_date;
                document.getElementById('is_holiday').value = data.data.is_holiday;
                document.getElementById('holiday_name').value = data.data.holiday_name || '';
                document.getElementById('action').value = 'edit';
                document.getElementById('modal-title').textContent = '<?php _e('Edit Working Day', 'bassmah-staff-reports'); ?>';
                
                toggleHolidayNameRow(data.data.is_holiday);
                showModal();
            }
        }).catch(error => {
            console.error('Error:', error);
        });
}

function deleteWorkingDay(id) {
    if (confirm('<?php _e('Are you sure you want to delete this working day?', 'bassmah-staff-reports'); ?>')) {
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=delete_working_day&id=' + id + '&_wpnonce=<?php echo wp_create_nonce('delete_working_day'); ?>';
    }
}

function showModal(title) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('working-day-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('working-day-modal').style.display = 'none';
    document.getElementById('working-day-form').reset();
}

function toggleHolidayNameRow(isHoliday) {
    var row = document.getElementById('holiday-name-row');
    row.style.display = isHoliday == '1' ? 'table-row' : 'none';
}

// Handle holiday type change
document.addEventListener('DOMContentLoaded', function() {
    var holidaySelect = document.getElementById('is_holiday');
    if (holidaySelect) {
        holidaySelect.addEventListener('change', function() {
            toggleHolidayNameRow(this.value);
        });
    }
});
</script>

<style>
.bassmah-actions {
    margin-bottom: 20px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-item {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-align: center;
}

.stat-item h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.holiday-row { display: table-row; }
.working-row { background: #f9f9f9; }
.holiday-row { background: #fff3cd; }

.day-type {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.day-type.holiday {
    background: #dc3545;
    color: white;
}

.day-type.working {
    background: #28a745;
    color: white;
}

.bassmah-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    margin: 50px auto;
    padding: 0;
    width: 500px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.modal-header {
    background: #0073aa;
    color: white;
    padding: 15px;
    border-radius: 5px 5px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close {
    font-size: 20px;
    cursor: pointer;
    background: none;
    border: none;
    color: white;
}

.modal-body {
    padding: 20px;
}

.form-actions {
    text-align: right;
    margin-top: 15px;
}
</style>
