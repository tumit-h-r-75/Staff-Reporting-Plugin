<?php
/**
 * Report Approvals View
 *
 * Displays pending reports awaiting manager approval/rejection
 * with full filtering and action capabilities.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!Bassmah_Staff_Reports_Roles::can_manage_approvals()) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
}

global $wpdb;
?>

<div class="wrap bassmah-admin">
    <h1><?php _e('Report Approvals', 'bassmah-staff-reports'); ?></h1>

    <div class="bassmah-approvals-container">
        <!-- Filter Section -->
        <div class="bassmah-filters">
            <form id="bassmah-approval-filter-form" method="get" action="">
                <input type="hidden" name="page" value="bassmah-pending-reports">
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filter-staff"><?php _e('Staff Member:', 'bassmah-staff-reports'); ?></label>
                        <select id="filter-staff" name="staff_user_id">
                            <option value=""><?php _e('All Staff', 'bassmah-staff-reports'); ?></option>
                            <?php
                            $staff_users = Bassmah_Staff_Reports_Roles::get_staff_users();
                            foreach ($staff_users as $user) {
                                echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-date-from"><?php _e('Date From:', 'bassmah-staff-reports'); ?></label>
                        <input type="date" id="filter-date-from" name="date_from">
                    </div>

                    <div class="filter-group">
                        <label for="filter-date-to"><?php _e('Date To:', 'bassmah-staff-reports'); ?></label>
                        <input type="date" id="filter-date-to" name="date_to">
                    </div>

                    <button type="submit" class="button button-primary"><?php _e('Filter', 'bassmah-staff-reports'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bassmah-pending-reports')); ?>" class="button"><?php _e('Reset', 'bassmah-staff-reports'); ?></a>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="bassmah-stats-row">
            <div class="stat-card">
                <div class="stat-value" id="stat-pending">0</div>
                <div class="stat-label"><?php _e('Pending Approvals', 'bassmah-staff-reports'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-approved">0</div>
                <div class="stat-label"><?php _e('Approved Today', 'bassmah-staff-reports'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-rejected">0</div>
                <div class="stat-label"><?php _e('Rejected Today', 'bassmah-staff-reports'); ?></div>
            </div>
        </div>

        <!-- Pending Reports Table -->
        <table class="wp-list-table widefat fixed striped bassmah-approvals-table">
            <thead>
                <tr>
                    <th class="column-date"><?php _e('Report Date', 'bassmah-staff-reports'); ?></th>
                    <th class="column-staff"><?php _e('Staff Member', 'bassmah-staff-reports'); ?></th>
                    <th class="column-email"><?php _e('Email', 'bassmah-staff-reports'); ?></th>
                    <th class="column-submitted"><?php _e('Submitted', 'bassmah-staff-reports'); ?></th>
                    <th class="column-tasks"><?php _e('Tasks', 'bassmah-staff-reports'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'bassmah-staff-reports'); ?></th>
                </tr>
            </thead>
            <tbody id="bassmah-approvals-tbody">
                <tr class="no-items">
                    <td colspan="6">
                    <div class="bassmah-loading-container">
                        <span class="loading loading-infinity loading-xl"></span>
                        <span class="bassmah-loading-text"><?php _e('Loading pending reports...', 'bassmah-staff-reports'); ?></span>
                    </div>
                </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num" id="approval-count"></span>
                <span class="pagination-links" id="approval-pagination"></span>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="approval-modal" class="bassmah-modal" style="display: none;">
    <div class="bassmah-modal-content">
        <span class="close" data-modal="approval-modal">&times;</span>
        <h2><?php _e('Report Details', 'bassmah-staff-reports'); ?></h2>
        
        <div id="modal-report-details"></div>

        <div class="modal-actions">
            <button type="button" class="button button-primary" id="approve-btn"><?php _e('Approve', 'bassmah-staff-reports'); ?></button>
            <button type="button" class="button button-secondary" id="reject-btn"><?php _e('Reject', 'bassmah-staff-reports'); ?></button>
            <button type="button" class="button" data-modal-close="approval-modal"><?php _e('Close', 'bassmah-staff-reports'); ?></button>
        </div>

        <div id="modal-message"></div>
    </div>
</div>

<!-- Approval Comment Modal -->
<div id="comment-modal" class="bassmah-modal" style="display: none;">
    <div class="bassmah-modal-content">
        <span class="close" data-modal="comment-modal">&times;</span>
        <h2 id="comment-modal-title"></h2>
        
        <textarea id="comment-textarea" placeholder="<?php _e('Enter your comment or reason...', 'bassmah-staff-reports'); ?>" rows="5" style="width: 100%;"></textarea>

        <div class="modal-actions" style="margin-top: 15px;">
            <button type="button" class="button button-primary" id="submit-comment-btn"><?php _e('Submit', 'bassmah-staff-reports'); ?></button>
            <button type="button" class="button" data-modal-close="comment-modal"><?php _e('Cancel', 'bassmah-staff-reports'); ?></button>
        </div>

        <div id="comment-message"></div>
    </div>
</div>

<!-- Inline Styles -->
<style>
.bassmah-approvals-container {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    margin-top: 20px;
}

.bassmah-filters {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 600;
    font-size: 13px;
}

.filter-group input,
.filter-group select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.bassmah-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 5px;
    text-align: center;
}

.stat-card.approved {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.rejected {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
}

.stat-label {
    font-size: 12px;
    margin-top: 5px;
    opacity: 0.9;
}

.bassmah-approvals-table {
    width: 100%;
    margin-bottom: 20px;
}

.bassmah-approvals-table th {
    background: #f5f5f5;
    font-weight: 600;
    padding: 12px;
    border-bottom: 2px solid #ddd;
}

.bassmah-approvals-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.bassmah-approvals-table tr:hover {
    background: #fafafa;
}

.column-date {
    width: 15%;
}

.column-staff {
    width: 20%;
}

.column-email {
    width: 20%;
}

.column-submitted {
    width: 15%;
}

.column-tasks {
    width: 15%;
}

.column-actions {
    width: 15%;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.action-buttons .button {
    padding: 5px 10px;
    font-size: 11px;
}

.bassmah-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
}

.bassmah-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 5px;
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #000;
}

#modal-report-details {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border-left: 4px solid #667eea;
}

.report-detail-item {
    margin-bottom: 12px;
}

.report-detail-item strong {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.report-tasks {
    margin: 15px 0;
    padding: 10px;
    background: white;
    border-radius: 3px;
}

.task-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.task-item:last-child {
    border-bottom: none;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.modal-actions .button {
    padding: 8px 15px;
}

#modal-message,
#comment-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 3px;
    display: none;
}

#modal-message.success,
#comment-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    display: block;
}

#modal-message.error,
#comment-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    display: block;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.submitted {
    background: #fff3cd;
    color: #856404;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.rejected {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }

    .bassmah-stats-row {
        grid-template-columns: 1fr;
    }

    .bassmah-modal-content {
        width: 95%;
        margin: 20% auto;
    }

    .column-date,
    .column-staff,
    .column-email,
    .column-submitted,
    .column-tasks,
    .column-actions {
        width: auto !important;
    }
}
</style>

<script>
(function($) {
    'use strict';

    let currentReportId = null;
    let currentAction = null; // 'approve' or 'reject'
    let currentPage = 1;
    let itemsPerPage = 20;

    $(document).ready(function() {
        loadPendingReports();
        setupEventListeners();
        updateStats();
    });

    function loadPendingReports(page = 1) {
        page = page || 1;
        currentPage = page;
        
        const staffUserId = $('#filter-staff').val() || '';
        const dateFrom = $('#filter-date-from').val() || '';
        const dateTo = $('#filter-date-to').val() || '';

        $.ajax({
            url: '/wp-json/bassmah/v1/reports/pending',
            method: 'GET',
            data: {
                limit: itemsPerPage,
                offset: (page - 1) * itemsPerPage,
                user_id: staffUserId
            },
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayReports(response.data);
                    updatePagination(response.total, page);
                    $('#stat-pending').text(response.total);
                } else {
                    showError('<?php _e('Failed to load reports', 'bassmah-staff-reports'); ?>');
                }
            },
            error: function(xhr) {
                showError('<?php _e('Error loading reports', 'bassmah-staff-reports'); ?>');
                console.log(xhr.responseText);
            }
        });
    }

    function displayReports(reports) {
        const tbody = $('#bassmah-approvals-tbody');
        tbody.empty();

        if (reports.length === 0) {
            tbody.html('<tr class="no-items"><td colspan="6"><?php _e('No pending reports found.', 'bassmah-staff-reports'); ?></td></tr>');
            return;
        }

        reports.forEach(function(report) {
            const row = $('<tr>');
            
            const submitDate = new Date(report.report_date);
            const submitTime = new Date(report.submitted_at);
            
            const formattedDate = submitDate.toLocaleDateString('en-US');
            const formattedTime = submitTime.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'});

            const taskCount = report.tasks ? report.tasks.length : 0;

            row.html(`
                <td class="column-date"><strong>${formattedDate}</strong></td>
                <td class="column-staff">${escapeHtml(report.user_name)}</td>
                <td class="column-email"><a href="mailto:${escapeHtml(report.user_email)}">${escapeHtml(report.user_email)}</a></td>
                <td class="column-submitted"><small>${formattedTime}</small></td>
                <td class="column-tasks"><span class="badge">${taskCount} <?php _e('tasks', 'bassmah-staff-reports'); ?></span></td>
                <td class="column-actions">
                    <div class="action-buttons">
                        <button class="button button-small view-report" data-report-id="${report.id}"><?php _e('View', 'bassmah-staff-reports'); ?></button>
                        <button class="button button-small button-primary approve-action" data-report-id="${report.id}"><?php _e('Approve', 'bassmah-staff-reports'); ?></button>
                        <button class="button button-small button-secondary reject-action" data-report-id="${report.id}"><?php _e('Reject', 'bassmah-staff-reports'); ?></button>
                    </div>
                </td>
            `);

            tbody.append(row);
        });
    }

    function setupEventListeners() {
        $(document).on('click', '.view-report', function() {
            const reportId = $(this).data('report-id');
            showReportDetails(reportId, 'view');
        });

        $(document).on('click', '.approve-action', function() {
            const reportId = $(this).data('report-id');
            currentReportId = reportId;
            currentAction = 'approve';
            $('#comment-modal-title').text('<?php _e('Approve Report', 'bassmah-staff-reports'); ?> - <?php _e('Add Optional Comment', 'bassmah-staff-reports'); ?>');
            $('#comment-textarea').val('');
            $('#comment-message').removeClass('success error').hide();
            $('#comment-modal').fadeIn();
        });

        $(document).on('click', '.reject-action', function() {
            const reportId = $(this).data('report-id');
            currentReportId = reportId;
            currentAction = 'reject';
            $('#comment-modal-title').text('<?php _e('Reject Report', 'bassmah-staff-reports'); ?> - <?php _e('Provide Rejection Reason', 'bassmah-staff-reports'); ?>');
            $('#comment-textarea').val('');
            $('#comment-textarea').attr('required', 'required');
            $('#comment-message').removeClass('success error').hide();
            $('#comment-modal').fadeIn();
        });

        $(document).on('click', '#submit-comment-btn', function() {
            const comment = $('#comment-textarea').val().trim();
            
            if (currentAction === 'reject' && !comment) {
                showMessage('comment-message', '<?php _e('Rejection reason is required.', 'bassmah-staff-reports'); ?>', 'error');
                return;
            }

            if (currentAction === 'approve') {
                approveReport(currentReportId, comment);
            } else if (currentAction === 'reject') {
                rejectReport(currentReportId, comment);
            }
        });

        $(document).on('click', '.close, [data-modal-close]', function() {
            const modalId = $(this).data('modal') || $(this).attr('data-modal-close');
            $('#' + modalId).fadeOut();
        });

        $(document).on('click', '#bassmah-approval-filter-form button[type="submit"]', function(e) {
            e.preventDefault();
            loadPendingReports(1);
        });

        // Pagination
        $(document).on('click', '.pagination-links a', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                loadPendingReports(page);
                $('html, body').animate({ scrollTop: 0 }, 'smooth');
            }
        });
    }

    function showReportDetails(reportId, action) {
        $.ajax({
            url: `/wp-json/bassmah/v1/reports/${reportId}`,
            method: 'GET',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            success: function(response) {
                if (response.success || response.data) {
                    const report = response.data || response;
                    let html = `
                        <div class="report-detail-item">
                            <strong><?php _e('Staff Member:', 'bassmah-staff-reports'); ?></strong>
                            ${escapeHtml(report.user_name || '')}
                        </div>
                        <div class="report-detail-item">
                            <strong><?php _e('Report Date:', 'bassmah-staff-reports'); ?></strong>
                            ${new Date(report.report_date).toLocaleDateString()}
                        </div>
                        <div class="report-detail-item">
                            <strong><?php _e('Submitted:', 'bassmah-staff-reports'); ?></strong>
                            ${new Date(report.submitted_at || report.submission_time).toLocaleString()}
                        </div>
                    `;

                    if (report.tasks && report.tasks.length > 0) {
                        html += '<div class="report-tasks"><strong><?php _e('Tasks:', 'bassmah-staff-reports'); ?></strong>';
                        report.tasks.forEach(function(task, index) {
                            html += `
                                <div class="task-item">
                                    <strong>${index + 1}. ${escapeHtml(task.title || 'Task ' + (index + 1))}</strong><br>
                                    <small>${escapeHtml(task.description || '')}</small>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }

                    $('#modal-report-details').html(html);
                    $('#approval-modal').fadeIn();
                } else {
                    showError('<?php _e('Failed to load report details', 'bassmah-staff-reports'); ?>');
                }
            },
            error: function() {
                showError('<?php _e('Error loading report', 'bassmah-staff-reports'); ?>');
            }
        });
    }

    function approveReport(reportId, comment) {
        $.ajax({
            url: `/wp-json/bassmah/v1/reports/${reportId}/approve`,
            method: 'POST',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                comment: comment
            }),
            success: function(response) {
                if (response.success) {
                    showMessage('comment-message', '<?php _e('Report approved successfully!', 'bassmah-staff-reports'); ?>', 'success');
                    setTimeout(function() {
                        $('#comment-modal').fadeOut();
                        loadPendingReports(currentPage);
                        updateStats();
                    }, 1000);
                } else {
                    showMessage('comment-message', response.message || '<?php _e('Failed to approve report', 'bassmah-staff-reports'); ?>', 'error');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || '<?php _e('Error approving report', 'bassmah-staff-reports'); ?>';
                showMessage('comment-message', errorMsg, 'error');
            }
        });
    }

    function rejectReport(reportId, reason) {
        $.ajax({
            url: `/wp-json/bassmah/v1/reports/${reportId}/reject`,
            method: 'POST',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                reason: reason
            }),
            success: function(response) {
                if (response.success) {
                    showMessage('comment-message', '<?php _e('Report rejected successfully!', 'bassmah-staff-reports'); ?>', 'success');
                    setTimeout(function() {
                        $('#comment-modal').fadeOut();
                        loadPendingReports(currentPage);
                        updateStats();
                    }, 1000);
                } else {
                    showMessage('comment-message', response.message || '<?php _e('Failed to reject report', 'bassmah-staff-reports'); ?>', 'error');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || '<?php _e('Error rejecting report', 'bassmah-staff-reports'); ?>';
                showMessage('comment-message', errorMsg, 'error');
            }
        });
    }

    function updatePagination(total, currentPage) {
        const totalPages = Math.ceil(total / itemsPerPage);
        let paginationHtml = '';

        if (currentPage > 1) {
            paginationHtml += `<a href="#" data-page="1" class="button">«</a>`;
            paginationHtml += `<a href="#" data-page="${currentPage - 1}" class="button">‹</a>`;
        }

        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            if (i === currentPage) {
                paginationHtml += `<span class="button button-disabled">${i}</span>`;
            } else {
                paginationHtml += `<a href="#" data-page="${i}" class="button">${i}</a>`;
            }
        }

        if (currentPage < totalPages) {
            paginationHtml += `<a href="#" data-page="${currentPage + 1}" class="button">›</a>`;
            paginationHtml += `<a href="#" data-page="${totalPages}" class="button">»</a>`;
        }

        $('#approval-pagination').html(paginationHtml);
        $('#approval-count').text(`<?php _e('Showing', 'bassmah-staff-reports'); ?> ${total} <?php _e('reports', 'bassmah-staff-reports'); ?>`);
    }

    function updateStats() {
        // In a real implementation, you might fetch these from the API
        // For now, we'll just use the pending count
    }

    function showMessage(elementId, message, type) {
        const $element = $('#' + elementId);
        $element.removeClass('success error').addClass(type).text(message).show();
    }

    function showError(message) {
        alert(message);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
})(jQuery);
</script>
