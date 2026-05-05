// Staff Dashboard Component
class BSRDashboard {
    constructor(container) {
        this.container = container;
        this.userId = parseInt(container.dataset.userId);
        this.userName = container.dataset.userName;
        this.userRole = container.dataset.userRole;
        this.nonce = container.dataset.nonce;
        this.apiBaseUrl = window.bsrData.apiUrl;
        this.reportFormUrl = container.dataset.reportFormUrl;
        this.myReportsUrl = container.dataset.myReportsUrl;
        this.salaryUrl = container.dataset.salaryUrl;
        
        this.init();
    }
    
    init() {
        this.renderDashboard();
        this.loadDashboardData();
    }
    
    renderDashboard() {
        this.container.innerHTML = `
            <div class="bsr-dashboard">
                <header class="bsr-dashboard__header">
                    <div class="bsr-dashboard__welcome">
                        <h1 class="bsr-dashboard__title">Welcome back, ${this.userName}!</h1>
                        <p class="bsr-dashboard__subtitle">Here's your work summary for today</p>
                    </div>
                    <div class="bsr-dashboard__actions">
                        <button class="bsr-button bsr-button--primary" onclick="window.location.href='${this.reportFormUrl}'">
                            Submit Today's Report
                        </button>
                    </div>
                </header>
                
                <div class="bsr-dashboard__content">
                    <div class="bsr-dashboard__grid">
                        <!-- Today's Status Card -->
                        <div class="bsr-card bsr-card--today-status" id="todayStatusCard">
                            <div class="bsr-card__header">
                                <h3 class="bsr-card__title">Today's Report Status</h3>
                                <div class="bsr-loading-spinner"></div>
                            </div>
                            <div class="bsr-card__content">
                                <div class="bsr-status-placeholder">
                                    Loading today's status...
                                </div>
                            </div>
                        </div>
                        
                        <!-- Salary Summary Card -->
                        <div class="bsr-card bsr-card--salary" id="salaryCard">
                            <div class="bsr-card__header">
                                <h3 class="bsr-card__title">Monthly Salary Summary</h3>
                                <div class="bsr-loading-spinner"></div>
                            </div>
                            <div class="bsr-card__content">
                                <div class="bsr-salary-placeholder">
                                    Loading salary information...
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats Cards -->
                        <div class="bsr-card bsr-card--stats" id="statsCard">
                            <div class="bsr-card__header">
                                <h3 class="bsr-card__title">Quick Stats</h3>
                                <div class="bsr-loading-spinner"></div>
                            </div>
                            <div class="bsr-card__content">
                                <div class="bsr-stats-placeholder">
                                    Loading statistics...
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Reports Card -->
                        <div class="bsr-card bsr-card--recent" id="recentReportsCard">
                            <div class="bsr-card__header">
                                <h3 class="bsr-card__title">Recent Reports</h3>
                                <button class="bsr-button bsr-button--ghost bsr-button--sm" onclick="window.location.href='${this.myReportsUrl}'">
                                    View All
                                </button>
                            </div>
                            <div class="bsr-card__content">
                                <div class="bsr-recent-placeholder">
                                    Loading recent reports...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <footer class="bsr-dashboard__footer">
                    <div class="bsr-dashboard__nav">
                        <button class="bsr-button bsr-button--outline" onclick="window.location.href='${this.reportFormUrl}'">
                            📝 New Report
                        </button>
                        <button class="bsr-button bsr-button--outline" onclick="window.location.href='${this.myReportsUrl}'">
                            📊 My Reports
                        </button>
                        <button class="bsr-button bsr-button--outline" onclick="window.location.href='${this.salaryUrl}'">
                            💰 Salary Details
                        </button>
                    </div>
                </footer>
            </div>
        `;
    }
    
    async loadDashboardData() {
        try {
            // Load all data in parallel for better performance
            const [todayReport, salaryData, recentReports] = await Promise.all([
                this.fetchTodayReport(),
                this.fetchSalaryData(),
                this.fetchRecentReports()
            ]);
            
            this.renderTodayStatus(todayReport);
            this.renderSalarySummary(salaryData);
            this.renderQuickStats(salaryData, recentReports);
            this.renderRecentReports(recentReports);
            
        } catch (error) {
            console.error('Dashboard loading error:', error);
            this.showError('Failed to load dashboard data');
        }
    }
    
    async fetchTodayReport() {
        const today = new Date().toISOString().split('T')[0];
        const response = await fetch(`${this.apiBaseUrl}reports/mine?date_from=${today}&date_to=${today}`, {
            headers: {
                'X-WP-Nonce': this.nonce
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch today report');
        const reports = await response.json();
        return reports.length > 0 ? reports[0] : null;
    }
    
    async fetchSalaryData() {
        const response = await fetch(`${this.apiBaseUrl}salary/me`, {
            headers: {
                'X-WP-Nonce': this.nonce
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch salary data');
        return await response.json();
    }
    
    async fetchRecentReports() {
        const response = await fetch(`${this.apiBaseUrl}reports/mine?limit=5`, {
            headers: {
                'X-WP-Nonce': this.nonce
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch recent reports');
        return await response.json();
    }
    
    renderTodayStatus(report) {
        const todayStatusCard = document.getElementById('todayStatusCard');
        if (!todayStatusCard) return;
        
        const today = new Date().toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        if (report) {
            const statusIcon = report.status === 'approved' ? '✅' : (report.status === 'rejected' ? '❌' : '⏳');
            const statusClass = `bsr-status-indicator bsr-status-indicator--${report.status}`;
            
            todayStatusCard.innerHTML = `
                <div class="bsr-card__header">
                    <h3 class="bsr-card__title">Today's Report Status</h3>
                    <span class="bsr-status-indicator ${statusClass}">
                        ${statusIcon}
                    </span>
                </div>
                <div class="bsr-card__content">
                    <div class="bsr-today-status">
                        <h4 class="bsr-today-status__title">Report Submitted</h4>
                        <p class="bsr-today-status__date">${today}</p>
                        <div class="bsr-today-status__details">
                            <span class="bsr-today-status__status">${report.status.charAt(0).toUpperCase() + report.status.slice(1)}</span>
                            ${report.manager_comment ? `<p class="bsr-today-status__comment">${report.manager_comment}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
        } else {
            todayStatusCard.innerHTML = `
                <div class="bsr-card__header">
                    <h3 class="bsr-card__title">Today's Report Status</h3>
                    <span class="bsr-status-indicator bsr-status-indicator--pending">
                        ⏰
                    </span>
                </div>
                <div class="bsr-card__content">
                    <div class="bsr-today-status bsr-today-status--pending">
                        <h4 class="bsr-today-status__title">No Report Submitted</h4>
                        <p class="bsr-today-status__date">${today}</p>
                        <div class="bsr-today-status__actions">
                            <button class="bsr-button bsr-button--primary" onclick="window.location.href='${this.reportFormUrl}'">
                                Submit Today's Report
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    renderSalarySummary(data) {
        const salaryCard = document.getElementById('salaryCard');
        if (!salaryCard) return;
        
        const {
            monthly_salary = 0,
            working_days = 0,
            submitted_days = 0,
            approved_days = 0,
            missing_days = 0,
            total_deduction = 0,
            net_salary = 0,
            currency = 'CAD'
        } = data;
        
        salaryCard.innerHTML = `
            <div class="bsr-card__header">
                <h3 class="bsr-card__title">Monthly Salary Summary</h3>
                <span class="bsr-badge bsr-badge--info">${new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}</span>
            </div>
            <div class="bsr-card__content">
                <div class="bsr-salary-overview">
                    <div class="bsr-salary-overview__main">
                        <div class="bsr-salary-amount">
                            <span class="bsr-salary-amount__currency">${currency}</span>
                            <span class="bsr-salary-amount__value">${net_salary.toFixed(2)}</span>
                            <span class="bsr-salary-amount__label">Net Salary</span>
                        </div>
                        <div class="bsr-salary-breakdown">
                            <div class="bsr-salary-breakdown__item">
                                <span class="bsr-salary-breakdown__label">Monthly</span>
                                <span class="bsr-salary-breakdown__value">${monthly_salary.toFixed(2)} ${currency}</span>
                            </div>
                            <div class="bsr-salary-breakdown__item bsr-salary-breakdown__item--deduction">
                                <span class="bsr-salary-breakdown__label">Deduction</span>
                                <span class="bsr-salary-breakdown__value">-${total_deduction.toFixed(2)} ${currency}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bsr-salary-stats">
                        <div class="bsr-salary-stat">
                            <span class="bsr-salary-stat__value">${working_days}</span>
                            <span class="bsr-salary-stat__label">Working Days</span>
                        </div>
                        <div class="bsr-salary-stat">
                            <span class="bsr-salary-stat__value">${submitted_days}</span>
                            <span class="bsr-salary-stat__label">Submitted</span>
                        </div>
                        <div class="bsr-salary-stat">
                            <span class="bsr-salary-stat__value">${missing_days}</span>
                            <span class="bsr-salary-stat__label">Missing</span>
                        </div>
                        <div class="bsr-salary-stat">
                            <span class="bsr-salary-stat__value">${approved_days}</span>
                            <span class="bsr-salary-stat__label">Approved</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderQuickStats(salaryData, recentReports) {
        const statsCard = document.getElementById('statsCard');
        if (!statsCard) return;
        
        const stats = [
            {
                label: 'Reports This Month',
                value: salaryData.submitted_days || 0,
                hint: 'Total reports submitted this month',
                icon: '📊',
                tone: 'primary'
            },
            {
                label: 'Tasks Completed',
                value: salaryData.approved_days || 0,
                hint: 'Approved reports this month',
                icon: '✅',
                tone: 'success'
            },
            {
                label: 'Missing Days',
                value: salaryData.missing_days || 0,
                hint: 'Days without reports',
                icon: '⚠️',
                tone: 'warning'
            },
            {
                label: 'Completion Rate',
                value: salaryData.working_days > 0 ? 
                    Math.round((salaryData.submitted_days / salaryData.working_days) * 100) : 0,
                hint: 'Report completion percentage',
                icon: '📈',
                tone: 'info'
            }
        ];

        statsCard.innerHTML = `
            <div class="bsr-card__header">
                <h3 class="bsr-card__title">Quick Stats</h3>
            </div>
            <div class="bsr-card__content">
                <div class="bsr-stats-grid">
                    ${stats.map(stat => `
                        <div class="bsr-stat-card bsr-stat-card--${stat.tone}">
                            <div class="bsr-stat-card__icon">
                                ${stat.icon}
                            </div>
                            <div class="bsr-stat-card__content">
                                <span class="bsr-stat-card__value">${stat.value}${stat.tone === 'info' ? '%' : ''}</span>
                                <span class="bsr-stat-card__label">${stat.label}</span>
                            </div>
                            <div class="bsr-stat-card__hint">${stat.hint}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    renderRecentReports(recentReports) {
        const recentReportsCard = document.getElementById('recentReportsCard');
        if (!recentReportsCard) return;

        if (!recentReports || recentReports.length === 0) {
            recentReportsCard.innerHTML = `
                <div class="bsr-card__header">
                    <h3 class="bsr-card__title">Recent Reports</h3>
                    <button class="bsr-button bsr-button--ghost bsr-button--sm" onclick="window.location.href='${this.myReportsUrl}'">
                        View All
                    </button>
                </div>
                <div class="bsr-card__content">
                    <div class="bsr-empty-state">
                        <div class="bsr-empty-state__icon">📝</div>
                        <h4 class="bsr-empty-state__title">No recent reports</h4>
                        <p class="bsr-empty-state__description">Start by submitting your first daily report!</p>
                        <button class="bsr-button bsr-button--primary" onclick="window.location.href='${this.reportFormUrl}'">
                            Submit Your First Report
                        </button>
                    </div>
                </div>
            `;
            return;
        }

        recentReportsCard.innerHTML = `
            <div class="bsr-card__header">
                <h3 class="bsr-card__title">Recent Reports</h3>
                <button class="bsr-button bsr-button--ghost bsr-button--sm" onclick="window.location.href='${this.myReportsUrl}'">
                    View All
                </button>
            </div>
            <div class="bsr-card__content">
                <div class="bsr-recent-list">
                    ${recentReports.map(report => {
                        const date = new Date(report.report_date);
                        const formattedDate = date.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });

                        const statusClass = `bsr-status-badge bsr-status-badge--${report.status}`;
                        const statusIcon = report.status === 'approved' ? '✅' : (report.status === 'rejected' ? '❌' : '⏳');
                        
                        const tasks = JSON.parse(report.tasks_json || '[]');
                        
                        return `
                            <div class="bsr-recent-item">
                                <div class="bsr-recent-item__header">
                                    <span class="bsr-recent-item__date">${formattedDate}</span>
                                    <span class="${statusClass}">
                                        ${statusIcon} ${report.status.charAt(0).toUpperCase() + report.status.slice(1)}
                                    </span>
                                </div>
                                <div class="bsr-recent-item__content">
                                    <h5 class="bsr-recent-item__title">${tasks.length} task${tasks.length !== 1 ? 's' : ''} reported</h5>
                                    <p class="bsr-recent-item__description">
                                        ${tasks.slice(0, 2).map(task => `${task.task_category}: ${task.task_description}`).join('; ')}
                                        ${tasks.length > 2 ? '...' : ''}
                                    </p>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    showError(message) {
        const errorHtml = `
            <div class="bsr-error-toast">
                <span class="bsr-error-toast__icon">⚠️</span>
                <span class="bsr-error-toast__message">${message}</span>
                <button class="bsr-error-toast__close" onclick="this.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', errorHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const error = document.querySelector('.bsr-error-toast');
            if (error) error.remove();
        }, 5000);
    }
}

// Auto-initialize dashboard components
document.addEventListener('DOMContentLoaded', function() {
    const dashboardContainers = document.querySelectorAll('[data-bsr-dashboard]');
    dashboardContainers.forEach(container => {
        new BSRDashboard(container);
    });
});
