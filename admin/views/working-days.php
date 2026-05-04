<div class="wrap">
    <h1>Working Days & Holidays</h1>
    
    <?php if (isset($_GET['holiday_added']) && $_GET['holiday_added'] == 1): ?>
        <div class="notice notice-success is-dismissible">
            <p>Holiday added successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['holiday_deleted']) && $_GET['holiday_deleted'] == 1): ?>
        <div class="notice notice-success is-dismissible">
            <p>Holiday removed successfully!</p>
        </div>
    <?php endif; ?>
    
    <div class="bsr-admin-container">
        <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Select Month & Year</h2>
                
                <form method="get" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                    <input type="hidden" name="page" value="bsr-working-days">
                    
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Month</label>
                        <select name="month" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $current_month == $m ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Year</label>
                        <select name="year" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <?php 
                            $current_year = date('Y');
                            for ($y = $current_year - 2; $y <= $current_year + 2; $y++): 
                            ?>
                                <option value="<?php echo $y; ?>" <?php echo isset($_GET['year']) && $_GET['year'] == $y ? 'selected' : (empty($_GET['year']) && $y == $current_year ? 'selected' : ''); ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="button button-primary">View</button>
                </form>
            </div>
            
            <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Add Holiday</h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('bsr_add_holiday', 'bsr_holiday_nonce'); ?>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Holiday Date</label>
                        <input type="date" name="work_date" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Holiday Name</label>
                        <input type="text" name="holiday_name" placeholder="e.g., Christmas Day" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    
                    <button type="submit" name="bsr_add_holiday" class="button button-primary" style="width: 100%; padding: 12px;">Add Holiday</button>
                </form>
            </div>
        </div>
        
        <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 20px;">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">
                Holidays for <?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>
            </h2>
            
            <?php if (empty($days)): ?>
                <p style="color: #4a5568;">No holidays scheduled yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Holiday Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <?php if ($day['is_holiday']): ?>
                                <tr>
                                    <td><?php echo esc_html(date('F j, Y', strtotime($day['work_date']))); ?></td>
                                    <td><?php echo esc_html($day['holiday_name']); ?></td>
                                    <td>
                                        <a href="<?php echo add_query_arg(array('bsr_delete_holiday' => $day['id'])); ?>" class="button button-small" style="color: #c53030; border-color: #c53030;">Remove</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
