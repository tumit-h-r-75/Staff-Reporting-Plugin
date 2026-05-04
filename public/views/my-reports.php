<div class="bsr-my-reports">
    <h2>My Reports</h2>
    
    <?php
    $user_id = get_current_user_id();
    $reports = Basmah_Staff_Reports_Reports::get_reports(array('user_id' => $user_id));
    
    if (empty($reports)):
    ?>
        <p>No reports found.</p>
    <?php else: ?>
        <div style="margin-top: 20px;">
            <table class="reports-table" style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                <thead style="background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);">
                    <tr>
                        <th style="padding: 18px 20px; color: white; font-weight: 700; text-align: left;">Date</th>
                        <th style="padding: 18px 20px; color: white; font-weight: 700; text-align: left;">Status</th>
                        <th style="padding: 18px 20px; color: white; font-weight: 700; text-align: left;">Submitted At</th>
                        <th style="padding: 18px 20px; color: white; font-weight: 700; text-align: left;">Manager Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 16px 20px; color: #4a5568; font-size: 1rem;"><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                            <td style="padding: 16px 20px;">
                                <span class="status-badge <?php echo sanitize_title($report['status']); ?>" style="display: inline-block; padding: 6px 18px; border-radius: 24px; font-size: 0.875rem; font-weight: 600; text-transform: capitalize; <?php
                                    if ($report['status'] == 'pending') echo 'background: #fed7aa; color: #c05621;';
                                    elseif ($report['status'] == 'approved') echo 'background: #c6f6d5; color: #276749;';
                                    elseif ($report['status'] == 'rejected') echo 'background: #fed7d7; color: #c53030;';
                                    ?>">
                                    <?php echo esc_html(ucfirst($report['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 16px 20px; color: #4a5568; font-size: 1rem;"><?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></td>
                            <td style="padding: 16px 20px; color: #4a5568; font-size: 1rem;">
                                <?php echo $report['manager_comment'] ? esc_html($report['manager_comment']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
