<div class="bsr-card max-w-6xl mx-auto p-6">
    <div class="bsr-card-header">
        <h2 class="bsr-card-title text-2xl">📋 My Reports</h2>
        <p class="text-muted-foreground">View and manage your submitted reports</p>
    </div>
    
    <div class="mt-6">
        <?php
        $user_id = get_current_user_id();
        $reports = Basmah_Staff_Reports_Reports::get_reports(array('user_id' => $user_id));
        
        if (empty($reports)):
        ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 bg-muted rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">No Reports Found</h3>
                <p class="text-muted-foreground">You haven't submitted any reports yet.</p>
                <a href="/report-form" class="bsr-button bsr-button-primary mt-4">
                    Submit Your First Report
                </a>
            </div>
        <?php else: ?>
            <div class="bsr-table">
                <table class="w-full">
                    <thead class="bg-primary text-primary-foreground">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold">📅 Date</th>
                            <th class="px-6 py-4 text-left font-semibold">📊 Status</th>
                            <th class="px-6 py-4 text-left font-semibold">⏰ Submitted</th>
                            <th class="px-6 py-4 text-left font-semibold">💬 Manager Comment</th>
                            <th class="px-6 py-4 text-left font-semibold">🔧 Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr class="border-b border-border hover:bg-muted transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?php echo esc_html(date('M j, Y', strtotime($report['report_date']))); ?></div>
                                    <div class="text-sm text-muted-foreground"><?php echo esc_html(date('l', strtotime($report['report_date']))); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php
                                        if ($report['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                        elseif ($report['status'] == 'approved') echo 'bg-green-100 text-green-800';
                                        elseif ($report['status'] == 'rejected') echo 'bg-red-100 text-red-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                        <?php
                                        if ($report['status'] == 'pending') echo '⏳ Pending';
                                        elseif ($report['status'] == 'approved') echo '✅ Approved';
                                        elseif ($report['status'] == 'rejected') echo '❌ Rejected';
                                        else echo '📝 ' . ucfirst($report['status']);
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?php echo esc_html(date('g:i A', strtotime($report['created_at']))); ?></div>
                                    <div class="text-sm text-muted-foreground"><?php echo esc_html(date('M j, Y', strtotime($report['created_at']))); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <?php if ($report['manager_comment']): ?>
                                            <p class="text-sm"><?php echo esc_html($report['manager_comment']); ?></p>
                                        <?php else: ?>
                                            <span class="text-muted-foreground text-sm">No comment yet</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button onclick="viewReport(<?php echo $report['id']; ?>)" class="bsr-button bsr-button-secondary text-xs">
                                            👁️ View
                                        </button>
                                        <?php if ($report['status'] == 'pending'): ?>
                                            <button onclick="editReport(<?php echo $report['id']; ?>)" class="bsr-button bsr-button-primary text-xs">
                                            ✏️ Edit
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="flex items-center justify-between mt-6">
                <div class="text-sm text-muted-foreground">
                    Showing <?php echo count($reports); ?> reports
                </div>
                <div class="flex gap-2">
                    <button class="bsr-button bsr-button-secondary text-xs">Previous</button>
                    <button class="bsr-button bsr-button-secondary text-xs">Next</button>
                </div>
            </div>
        <?php endif; ?>
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

.bsr-table {
    position: relative;
    width: 100%;
    overflow: auto;
    border-collapse: collapse;
    font-size: 0.875rem;
    border-radius: calc(var(--radius) + 6px);
    border: 1px solid hsl(var(--border));
    background-color: hsl(var(--card));
}

.bsr-table th {
    border-bottom: 1px solid hsl(var(--border));
    font-weight: 600;
    text-align: left;
    padding: 1rem 1.5rem;
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.bsr-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
    vertical-align: top;
}

.bsr-table tr:last-child td {
    border-bottom: none;
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

.text-xs {
    font-size: 0.75rem;
    line-height: 1rem;
}

.max-w-xs {
    max-width: 20rem;
}

.bg-yellow-100 {
    background-color: #fef3c7;
}

.text-yellow-800 {
    color: #92400e;
}

.bg-green-100 {
    background-color: #dcfce7;
}

.text-green-800 {
    color: #166534;
}

.bg-red-100 {
    background-color: #fee2e2;
}

.text-red-800 {
    color: #991b1b;
}

.bg-gray-100 {
    background-color: #f3f4f6;
}

.text-gray-800 {
    color: #1f2937;
}

.w-16 {
    width: 4rem;
}

.h-16 {
    height: 4rem;
}

.w-8 {
    width: 2rem;
}

.h-8 {
    height: 2rem;
}

.py-12 {
    padding-top: 3rem;
    padding-bottom: 3rem;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

.mt-4 {
    margin-top: 1rem;
}

.px-6 {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}

.py-4 {
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.bg-muted {
    background-color: hsl(var(--muted));
}

.text-muted-foreground {
    color: hsl(var(--muted-foreground));
}

.bg-primary {
    background-color: hsl(var(--primary));
}

.text-primary-foreground {
    color: hsl(var(--primary-foreground));
}

.border-border {
    border-color: hsl(var(--border));
}

.hover\:bg-muted:hover {
    background-color: hsl(var(--muted));
}

.transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.font-medium {
    font-weight: 500;
}

.text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.inline-flex {
    display: inline-flex;
}

.items-center {
    align-items: center;
}

.px-3 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

.py-1 {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}

.rounded-full {
    border-radius: 9999px;
}

.text-lg {
    font-size: 1.125rem;
    line-height: 1.75rem;
}

.text-2xl {
    font-size: 1.5rem;
    line-height: 2rem;
}

.text-center {
    text-align: center;
}

.max-w-6xl {
    max-width: 72rem;
}

.mx-auto {
    margin-left: auto;
    margin-right: auto;
}

.p-6 {
    padding: 1.5rem;
}

.mt-6 {
    margin-top: 1.5rem;
}

.w-full {
    width: 100%;
}

.justify-between {
    justify-content: space-between;
}

.gap-2 {
    gap: 0.5rem;
}

.flex {
    display: flex;
}
</style>

<script>
function viewReport(reportId) {
    // Open modal or navigate to report details
    console.log('View report:', reportId);
    alert('Report details view coming soon!');
}

function editReport(reportId) {
    // Navigate to edit form
    console.log('Edit report:', reportId);
    alert('Report editing coming soon!');
}
</script>
