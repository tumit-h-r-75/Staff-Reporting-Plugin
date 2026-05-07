/**
 * Admin JavaScript for Bassmah Staff Reports plugin
 */

jQuery(document).ready(function($) {
    
    // Initialize date pickers
    $('.bassmah-datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    // Handle form submissions
    $('.bassmah-ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Show loading state
        $submitBtn.prop('disabled', true).html('<span class="bassmah-loading"></span> ' + bassmah_admin.strings.loading);
        
        // Submit form via AJAX
        $.ajax({
            url: bassmah_admin.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&action=bassmah_admin_ajax&nonce=' + bassmah_admin.nonce,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message || 'Success!', 'success');
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        $form[0].reset();
                    }
                } else {
                    showNotice(response.data.message || 'An error occurred.', 'error');
                }
            },
            error: function() {
                showNotice(bassmah_admin.strings.error_occurred, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Handle export buttons
    $('.bassmah-export-btn').on('click', function(e) {
        e.preventDefault();
        
        var exportType = $(this).data('export-type');
        var filters = getFilterValues();
        
        if (exportType === 'reports') {
            exportReports(filters);
        } else if (exportType === 'salary') {
            exportSalary(filters);
        }
    });

    // Handle filter form
    $('.bassmah-filter-form').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });

    // Handle clear filters
    $('.bassmah-clear-filters').on('click', function(e) {
        e.preventDefault();
        $('.bassmah-filter-form')[0].reset();
        applyFilters();
    });

    // Handle delete confirmations
    $('.bassmah-delete-btn').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(bassmah_admin.strings.confirm_delete)) {
            return;
        }
        
        var $btn = $(this);
        var itemId = $btn.data('item-id');
        var itemType = $btn.data('item-type');
        
        deleteItem(itemId, itemType);
    });

    // Handle task row management in report forms
    $('.bassmah-add-task').on('click', function(e) {
        e.preventDefault();
        addTaskRow();
    });

    $(document).on('click', '.bassmah-remove-task', function(e) {
        e.preventDefault();
        $(this).closest('.bassmah-task-row').remove();
    });

    // Functions
    function showNotice(message, type) {
        var noticeClass = 'bassmah-notice-' + type;
        var notice = $('<div class="bassmah-notice ' + noticeClass + '">' + message + '</div>');
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
    }

    function getFilterValues() {
        var filters = {};
        $('.bassmah-filter-form').find('input, select').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            if (name && value) {
                filters[name] = value;
            }
        });
        return filters;
    }

    function applyFilters() {
        var filters = getFilterValues();
        var queryParams = $.param(filters);
        var currentUrl = window.location.href.split('?')[0];
        window.location.href = currentUrl + '?' + queryParams;
    }

    function exportReports(filters) {
        $.ajax({
            url: bassmah_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bassmah_admin_ajax',
                action_type: 'export_reports',
                filters: filters,
                nonce: bassmah_admin.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    downloadCSV(response.data.csv, 'reports-export.csv');
                } else {
                    showNotice(response.data.message || 'Export failed.', 'error');
                }
            },
            error: function() {
                showNotice('Export failed. Please try again.', 'error');
            }
        });
    }

    function exportSalary(filters) {
        $.ajax({
            url: bassmah_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bassmah_admin_ajax',
                action_type: 'export_salary',
                filters: filters,
                nonce: bassmah_admin.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    downloadCSV(response.data.csv, response.data.filename);
                } else {
                    showNotice(response.data.message || 'Export failed.', 'error');
                }
            },
            error: function() {
                showNotice('Export failed. Please try again.', 'error');
            }
        });
    }

    function downloadCSV(csv, filename) {
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function deleteItem(itemId, itemType) {
        $.ajax({
            url: bassmah_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bassmah_admin_ajax',
                action_type: 'delete_item',
                item_id: itemId,
                item_type: itemType,
                nonce: bassmah_admin.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message || 'Item deleted successfully.', 'success');
                    $('.bassmah-item-' + itemId).fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotice(response.data.message || 'Delete failed.', 'error');
                }
            },
            error: function() {
                showNotice('Delete failed. Please try again.', 'error');
            }
        });
    }

    function addTaskRow() {
        var taskRow = $('.bassmah-task-template').clone();
        taskRow.removeClass('bassmah-task-template').addClass('bassmah-task-row');
        taskRow.find('input, select, textarea').prop('disabled', false);
        taskRow.find('.bassmah-remove-task').show();
        taskRow.insertBefore('.bassmah-task-template');
    }

    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('.bassmah-tooltip').tooltip();
    }

    // Auto-refresh dashboard stats
    if ($('.bassmah-dashboard').length) {
        setInterval(function() {
            refreshDashboardStats();
        }, 30000); // Refresh every 30 seconds
    }

    function refreshDashboardStats() {
        $.ajax({
            url: bassmah_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bassmah_admin_ajax',
                action_type: 'refresh_stats',
                nonce: bassmah_admin.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data.stats);
                }
            }
        });
    }

    function updateDashboardStats(stats) {
        $.each(stats, function(key, value) {
            $('.bassmah-stat-' + key).text(value);
        });
    }
});
