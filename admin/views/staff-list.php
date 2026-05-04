<div class="wrap">
    <h1>Staff List</h1>
    
    <?php
    $staff_users = get_users(array(
        'role__in' => array('basmah_staff', 'basmah_manager'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    ?>
    
    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
        <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">All Staff Members</h2>
        
        <?php if (empty($staff_users)): ?>
            <p style="color: #4a5568;">No staff members found.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Reports Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_users as $user): ?>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'staff_reports';
                        $report_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user->ID));
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($user->display_name); ?></strong></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 500; <?php echo in_array('basmah_manager', $user->roles) ? 'background: #ebf8ff; color: #2b6cb0;' : 'background: #c6f6d5; color: #276749;'; ?>">
                                    <?php echo in_array('basmah_manager', $user->roles) ? 'Manager' : 'Staff'; ?>
                                </span>
                            </td>
                            <td><strong><?php echo esc_html($report_count); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
