/**
 * Public JavaScript for Bassmah Staff Reports plugin
 */

jQuery(document).ready(function($) {
    
    // Initialize date pickers
    $('.bassmah-datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        minDate: 0, // Only allow today and future dates
        maxDate: 0  // Only allow today (not future dates)
    });

    // Handle report form submission
    $('.bassmah-report-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Validate form
        if (!validateReportForm($form)) {
            return;
        }
        
        // Show loading state with modern loading component
        $submitBtn.prop('disabled', true).html('<div class="bassmah-loading-container"><div class="modern-spinner loading-md"></div><span class="bassmah-loading-text">' + bassmah_public.strings.loading + '</span></div>');
        
        // Collect task data
        var tasks = collectTaskData();
        
        // Submit via REST API
        $.ajax({
            url: bassmah_public.rest_url + 'v1/reports',
            type: 'POST',
            data: {
                tasks: tasks,
                status: 'submitted',
                report_date: new Date().toISOString().split('T')[0]
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bassmah_public.nonce);
            },
            dataType: 'json',
            success: function(response) {
                if (response.data) {
                    showNotice(bassmah_public.strings.report_submitted, 'success');
                    $form[0].reset();
                    // Remove all task rows except the first one
                    $('.bassmah-task-row:not(:first)').remove();
                    // Reset first task row
                    resetTaskRow($('.bassmah-task-row:first'));
                } else {
                    showNotice(bassmah_public.strings.error_occurred, 'error');
                }
            },
            error: function(xhr) {
                var message = bassmah_public.strings.error_occurred;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    message = xhr.responseText;
                }
                showNotice(message, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Handle add task button
    $('.bassmah-add-task').on('click', function(e) {
        e.preventDefault();
        addTaskRow();
    });

    // Handle remove task button
    $(document).on('click', '.bassmah-remove-task', function(e) {
        e.preventDefault();
        var $taskRow = $(this).closest('.bassmah-task-row');
        if ($('.bassmah-task-row').length > 1) {
            $taskRow.fadeOut(300, function() {
                $(this).remove();
                updateTaskNumbers();
            });
        } else {
            showNotice('You must have at least one task.', 'error');
        }
    });

    // Handle dashboard refresh
    $('.bassmah-refresh-dashboard').on('click', function(e) {
        e.preventDefault();
        refreshDashboard();
    });

    // Handle reports pagination
    $('.bassmah-load-more').on('click', function(e) {
        e.preventDefault();
        loadMoreReports();
    });

    // Handle filter form
    $('.bassmah-filter-form').on('submit', function(e) {
        e.preventDefault();
        filterReports();
    });

    // Functions
    function validateReportForm($form) {
        var isValid = true;
        var $taskRows = $('.bassmah-task-row').not('.bassmah-task-template');
        
        if ($taskRows.length === 0) {
            showNotice('Please add at least one task.', 'error');
            return false;
        }
        
        $taskRows.each(function() {
            var $row = $(this);
            var $taskDesc = $row.find('.bassmah-task-description');
            var $nextAction = $row.find('.bassmah-next-action');
            
            if ($taskDesc.val().trim() === '') {
                showFieldError($taskDesc, 'Task description is required.');
                isValid = false;
            } else {
                clearFieldError($taskDesc);
            }
            
            if ($nextAction.val().trim() === '') {
                showFieldError($nextAction, 'Next action is required.');
                isValid = false;
            } else {
                clearFieldError($nextAction);
            }
        });
        
        return isValid;
    }

    function collectTaskData() {
        var tasks = [];
        
        $('.bassmah-task-row').not('.bassmah-task-template').each(function() {
            var $row = $(this);
            var task = {
                task_category: $row.find('.bassmah-task-category').val(),
                task_description: $row.find('.bassmah-task-description').val(),
                completion_status: $row.find('input[name^="completion_status_"]:checked').val(),
                next_action: $row.find('.bassmah-next-action').val(),
                manager_assigned_task: $row.find('.bassmah-manager-task').val(),
                additional_notes: $row.find('.bassmah-additional-notes').val()
            };
            tasks.push(task);
        });
        
        return tasks;
    }

    function addTaskRow() {
        var $template = $('.bassmah-task-template');
        var $newRow = $template.clone();
        
        $newRow.removeClass('bassmah-task-template').addClass('bassmah-task-row');
        $newRow.css('display', 'none');
        
        // Enable all form elements
        $newRow.find('input, select, textarea').prop('disabled', false);
        $newRow.find('.bassmah-remove-task').show();
        
        // Add to container
        $('.bassmah-task-container').append($newRow);
        
        // Show with animation
        $newRow.fadeIn(300);
        
        // Update task numbers
        updateTaskNumbers();
        
        // Initialize date picker for new row if needed
        $newRow.find('.bassmah-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }

    function resetTaskRow($row) {
        $row.find('select').prop('selectedIndex', 0);
        $row.find('input[type="radio"]').prop('checked', false);
        $row.find('input[type="text"], textarea').val('');
        $row.find('input[name="completion_status"][value="completed"]').prop('checked', true);
    }

    function updateTaskNumbers() {
        $('.bassmah-task-row').each(function(index) {
            $(this).find('.bassmah-task-number').text('Task ' + (index + 1));
        });
    }

    function showFieldError($field, message) {
        $field.addClass('error');
        if (!$field.next('.field-error').length) {
            $field.after('<span class="field-error">' + message + '</span>');
        }
    }

    function clearFieldError($field) {
        $field.removeClass('error');
        $field.next('.field-error').remove();
    }

    function showNotice(message, type) {
        var noticeClass = 'bassmah-notice-' + type;
        var notice = $('<div class="bassmah-notice ' + noticeClass + '">' + message + '</div>');
        
        // Insert at the top of the container
        $('.bassmah-container').prepend(notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to top to show notice
        $('html, body').animate({
            scrollTop: $('.bassmah-container').offset().top - 20
        }, 300);
    }

    function refreshDashboard() {
        var $refreshBtn = $('.bassmah-refresh-dashboard');
        var originalText = $refreshBtn.text();
        
        $refreshBtn.prop('disabled', true).html('<div class="bassmah-loading-container"><div class="modern-spinner loading-md"></div><span class="bassmah-loading-text">Loading...</span></div>');
        
        $.ajax({
            url: bassmah_public.rest_url + 'salary/dashboard/' + bassmah_public.user_id,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bassmah_public.nonce);
            },
            dataType: 'json',
            success: function(response) {
                if (response.data) {
                    updateDashboardUI(response.data);
                }
            },
            error: function() {
                showNotice('Failed to refresh dashboard.', 'error');
            },
            complete: function() {
                $refreshBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    function updateDashboardUI(data) {
        // Update salary statistics
        $('.bassmah-stat-monthly-salary').text(formatCurrency(data.monthly_salary, data.currency));
        $('.bassmah-stat-daily-rate').text(formatCurrency(data.daily_rate, data.currency));
        $('.bassmah-stat-working-days').text(data.total_working_days);
        $('.bassmah-stat-submitted-days').text(data.submitted_days);
        $('.bassmah-stat-missing-days').text(data.missing_days);
        $('.bassmah-stat-deduction').text(formatCurrency(data.total_deduction, data.currency));
        $('.bassmah-stat-net-salary').text(formatCurrency(data.net_salary, data.currency));
        $('.bassmah-stat-attendance').text(data.attendance_percentage + '%');
        
        // Update submission status
        if (data.has_submitted_today) {
            $('.bassmah-submission-status').addClass('submitted').removeClass('pending');
            $('.bassmah-submission-text').text('Report Submitted Today');
        } else {
            $('.bassmah-submission-status').addClass('pending').removeClass('submitted');
            $('.bassmah-submission-text').text('Report Not Submitted Today');
        }
    }

    function loadMoreReports() {
        var $loadBtn = $('.bassmah-load-more');
        var currentPage = parseInt($loadBtn.data('page')) || 1;
        var nextPage = currentPage + 1;
        
        $loadBtn.prop('disabled', true).html('<div class="bassmah-loading-container"><div class="modern-spinner loading-md"></div><span class="bassmah-loading-text">Loading...</span></div>');
        
        $.ajax({
            url: bassmah_public.rest_url + 'reports/mine',
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', bassmah_public.nonce);
            },
            data: {
                page: nextPage,
                per_page: 10
            },
            dataType: 'json',
            success: function(response) {
                if (response.data && response.data.reports) {
                    appendReports(response.data.reports);
                    $loadBtn.data('page', nextPage);
                    
                    if (response.data.reports.length < 10) {
                        $loadBtn.hide();
                    }
                }
            },
            error: function() {
                showNotice('Failed to load more reports.', 'error');
            },
            complete: function() {
                $loadBtn.prop('disabled', false).text('Load More');
            }
        });
    }

    function appendReports(reports) {
        var $container = $('.bassmah-reports-list');
        
        reports.forEach(function(report) {
            var $reportItem = createReportItem(report);
            $container.append($reportItem);
        });
    }

    function createReportItem(report) {
        var $item = $('<div class="bassmah-report-item"></div>');
        
        var statusClass = 'bassmah-status-' + report.status;
        var statusText = report.status.charAt(0).toUpperCase() + report.status.slice(1);
        
        $item.html(`
            <div class="bassmah-report-header">
                <div class="bassmah-report-date">${formatDate(report.report_date)}</div>
                <div class="bassmah-report-status ${statusClass}">${statusText}</div>
            </div>
            <div class="bassmah-report-meta">
                Submitted: ${formatDateTime(report.submission_time)} | Tasks: ${report.tasks.length}
            </div>
            ${report.manager_comment ? `
                <div class="bassmah-report-comment">
                    <strong>Manager Comment:</strong> ${report.manager_comment}
                </div>
            ` : ''}
        `);
        
        return $item;
    }

    function filterReports() {
        var dateFrom = $('.bassmah-filter-date-from').val();
        var dateTo = $('.bassmah-filter-date-to').val();
        
        var params = new URLSearchParams();
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        var url = window.location.pathname + '?' + params.toString();
        window.location.href = url;
    }

    function formatCurrency(amount, currency) {
        return new Intl.NumberFormat('en-CA', {
            style: 'currency',
            currency: currency || 'CAD'
        }).format(amount);
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-CA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('en-CA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Auto-save draft functionality
    var autoSaveTimer;
    $('.bassmah-report-form').on('input change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            autoSaveDraft();
        }, 30000); // Auto-save after 30 seconds of inactivity
    });

    function autoSaveDraft() {
        var tasks = collectTaskData();
        if (tasks.length === 0) return;
        
        localStorage.setItem('bassmah_report_draft', JSON.stringify({
            tasks: tasks,
            timestamp: new Date().toISOString()
        }));
    }

    // Restore draft on page load
    function restoreDraft() {
        var draft = localStorage.getItem('bassmah_report_draft');
        if (!draft) return;
        
        try {
            var draftData = JSON.parse(draft);
            var draftDate = new Date(draftData.timestamp);
            var now = new Date();
            
            // Only restore if draft is less than 24 hours old
            if (now - draftDate < 24 * 60 * 60 * 1000) {
                if (confirm('Found a draft report from ' + draftDate.toLocaleString() + '. Would you like to restore it?')) {
                    restoreDraftData(draftData.tasks);
                }
            }
        } catch (e) {
            console.error('Failed to restore draft:', e);
        }
    }

    function restoreDraftData(tasks) {
        // Clear existing task rows
        $('.bassmah-task-row:not(:first)').remove();
        
        // Add tasks
        tasks.forEach(function(task, index) {
            if (index > 0) {
                addTaskRow();
            }
            
            var $row = $('.bassmah-task-row').eq(index);
            $row.find('.bassmah-task-category').val(task.task_category || '');
            $row.find('.bassmah-task-description').val(task.task_description || '');
            $row.find('.bassmah-next-action').val(task.next_action || '');
            $row.find('.bassmah-manager-task').val(task.manager_assigned_task || '');
            $row.find('.bassmah-additional-notes').val(task.additional_notes || '');
            
            if (task.completion_status) {
                $row.find('input[name="completion_status"][value="' + task.completion_status + '"]').prop('checked', true);
            }
        });
        
        showNotice('Draft restored successfully.', 'success');
    }

    // Initialize
    restoreDraft();
    updateTaskNumbers();
});
