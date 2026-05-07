<?php
/**
 * Staff Management View
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

// Get staff users
$staff_users = get_users(array(
    'role__in' => array('bassmah_staff', 'subscriber'),
    'fields' => array('ID', 'display_name', 'user_email', 'user_login'),
    'orderby' => 'display_name',
    'order' => 'ASC'
));

$manager_users = get_users(array(
    'role__in' => array('bassmah_manager'),
    'fields' => array('ID', 'display_name', 'user_email', 'user_login'),
    'orderby' => 'display_name',
    'order' => 'ASC'
));
?>

<div class="wrap bassmah-admin">
    <h1><?php _e('Staff Management', 'bassmah-staff-reports'); ?></h1>
    
    <div class="bassmah-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#staff" class="nav-tab nav-tab-active"><?php _e('Staff Members', 'bassmah-staff-reports'); ?></a>
            <a href="#managers"><?php _e('Managers', 'bassmah-staff-reports'); ?></a>
        </nav>
    </div>

    <div class="tab-content">
        <!-- Staff Members Tab -->
        <div id="staff" class="tab-pane active">
            <div class="bassmah-section">
                <h2><?php _e('Staff Members', 'bassmah-staff-reports'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped bassmah-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Email', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Username', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Role', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_users as $user): ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html(ucfirst($user->roles[0] ?? 'User')); ?></td>
                                <td>
                                    <button class="button button-small" onclick="editUser(<?php echo $user->ID; ?>)">
                                        <?php _e('Edit', 'bassmah-staff-reports'); ?>
                                    </button>
                                    <button class="button button-small" onclick="deleteUser(<?php echo $user->ID; ?>)">
                                        <?php _e('Delete', 'bassmah-staff-reports'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($staff_users)): ?>
                    <p><?php _e('No staff members found.', 'bassmah-staff-reports'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Managers Tab -->
        <div id="managers" class="tab-pane">
            <div class="bassmah-section">
                <h2><?php _e('Managers', 'bassmah-staff-reports'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped bassmah-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Email', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Username', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Role', 'bassmah-staff-reports'); ?></th>
                            <th><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manager_users as $user): ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html(ucfirst($user->roles[0] ?? 'User')); ?></td>
                                <td>
                                    <button class="button button-small" onclick="editUser(<?php echo $user->ID; ?>)">
                                        <?php _e('Edit', 'bassmah-staff-reports'); ?>
                                    </button>
                                    <button class="button button-small" onclick="deleteUser(<?php echo $user->ID; ?>)">
                                        <?php _e('Delete', 'bassmah-staff-reports'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($manager_users)): ?>
                    <p><?php _e('No managers found.', 'bassmah-staff-reports'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(userId) {
    if (confirm('<?php _e('Are you sure you want to edit this user?', 'bassmah-staff-reports'); ?>')) {
        window.location.href = '<?php echo admin_url('user-edit.php?user_id='); ?>' + userId;
    }
}

function deleteUser(userId) {
    if (confirm('<?php _e('Are you sure you want to delete this user?', 'bassmah-staff-reports'); ?>')) {
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=delete_user&user_id=' + userId + '&_wpnonce=<?php echo wp_create_nonce('delete_user_' . userId); ?>';
    }
}
</script>

<style>
.bassmah-tabs {
    margin-bottom: 20px;
}

.nav-tab-wrapper {
    margin-bottom: 0;
}

.nav-tab {
    display: inline-block;
    padding: 10px 15px;
    margin-right: 5px;
    background: #f1f1f1;
    border: 1px solid #ccc;
    text-decoration: none;
    color: #333;
    border-radius: 3px 3px 0 0;
}

.nav-tab-active {
    background: #0073aa;
    color: white;
}

.tab-content {
    margin-top: 20px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.bassmah-section {
    background: white;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
}

.bassmah-section h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
    margin-bottom: 15px;
}

.bassmah-table {
    margin-top: 10px;
}

.bassmah-table th {
    background: #f9f9f9;
    padding: 10px;
    text-align: left;
}

.bassmah-table td {
    padding: 10px;
    vertical-align: middle;
}

.button-small {
    padding: 5px 10px;
    font-size: 12px;
    margin-right: 5px;
}
</style>
