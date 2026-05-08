/**
 * JWT Authentication JavaScript
 * 
 * Handles JWT token management and form submission
 * without relying on cookies
 */

(function($) {
    'use strict';
    
    // JWT Authentication Manager
    window.BassmahJWTAuth = {
        
        token: null,
        tokenExpiry: null,
        
        /**
         * Initialize JWT authentication
         */
        init: function() {
            this.token = localStorage.getItem('bassmah_jwt_token');
            this.tokenExpiry = localStorage.getItem('bassmah_jwt_token_expiry');
            
            // Check if token is expired
            if (this.token && this.tokenExpiry && Date.now() > parseInt(this.tokenExpiry)) {
                this.clearToken();
            }
            
            // Get new token if user is logged in but no token
            if (!this.token && bassmahAuth.is_logged_in) {
                this.getNewToken();
            }
        },
        
        /**
         * Get new JWT token from server
         */
        getNewToken: function() {
            var self = this;
            
            $.ajax({
                url: bassmah_public.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bassmah_get_jwt_token'
                },
                success: function(response) {
                    if (response.success) {
                        self.setToken(response.data.token, response.data.expires_in);
                        console.log('JWT token obtained successfully');
                    } else {
                        console.error('Failed to get JWT token:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('JWT token request failed:', error);
                }
            });
        },
        
        /**
         * Set JWT token and expiry
         */
        setToken: function(token, expiresIn) {
            this.token = token;
            this.tokenExpiry = Date.now() + (expiresIn * 1000);
            
            localStorage.setItem('bassmah_jwt_token', token);
            localStorage.setItem('bassmah_jwt_token_expiry', this.tokenExpiry.toString());
        },
        
        /**
         * Clear JWT token
         */
        clearToken: function() {
            this.token = null;
            this.tokenExpiry = null;
            
            localStorage.removeItem('bassmah_jwt_token');
            localStorage.removeItem('bassmah_jwt_token_expiry');
        },
        
        /**
         * Get authorization header
         */
        getAuthHeader: function() {
            return this.token ? 'Bearer ' + this.token : '';
        },
        
        /**
         * Make authenticated AJAX request
         */
        authenticatedAjax: function(options) {
            var self = this;
            
            // Set authorization header
            if (!options.headers) {
                options.headers = {};
            }
            options.headers['Authorization'] = this.getAuthHeader();
            
            // Add error handling for token expiry
            var originalError = options.error || function() {};
            options.error = function(xhr, status, error) {
                if (xhr.status === 401) {
                    // Token expired or invalid, get new token and retry
                    self.clearToken();
                    self.getNewToken();
                    
                    // Retry once after getting new token
                    setTimeout(function() {
                        if (self.token) {
                            options.headers['Authorization'] = self.getAuthHeader();
                            $.ajax(options);
                        }
                    }, 1000);
                } else {
                    originalError(xhr, status, error);
                }
            };
            
            return $.ajax(options);
        }
    };
    
    // Enhanced Form Submission with JWT
    window.BassmahFormSubmit = {
        
        /**
         * Submit report with JWT authentication
         */
        submitReport: function(formData) {
            var self = this;
            
            return BassmahJWTAuth.authenticatedAjax({
                url: bassmah_public.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bassmah_frontend_ajax',
                    action_type: 'submit_report',
                    tasks: formData.tasks
                },
                beforeSend: function() {
                    self.showLoading();
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showSuccess(response.data.message);
                        self.resetForm();
                        
                        // Refresh dashboard if needed
                        if (typeof bassmahRefreshDashboard === 'function') {
                            bassmahRefreshDashboard();
                        }
                    } else {
                        self.showError(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    
                    if (xhr.status === 401) {
                        self.showError('Authentication failed. Please refresh the page and try again.');
                    } else {
                        self.showError('An error occurred. Please try again.');
                    }
                }
            });
        },
        
        /**
         * Get my reports with JWT authentication
         */
        getMyReports: function(page, filters) {
            return BassmahJWTAuth.authenticatedAjax({
                url: bassmah_public.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bassmah_frontend_ajax',
                    action_type: 'get_my_reports',
                    page: page || 1,
                    filters: filters || {}
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof bassmahRenderReports === 'function') {
                            bassmahRenderReports(response.data);
                        }
                    } else {
                        console.error('Failed to get reports:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reports request failed:', error);
                }
            });
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            $('.bassmah-submit-btn').prop('disabled', true).text('Submitting...');
            $('.bassmah-loading').show();
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('.bassmah-submit-btn').prop('disabled', false).text('Submit Report');
            $('.bassmah-loading').hide();
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            $('.bassmah-message').removeClass('error').addClass('success').text(message).show();
            setTimeout(function() {
                $('.bassmah-message').fadeOut();
            }, 5000);
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            $('.bassmah-message').removeClass('success').addClass('error').text(message).show();
            setTimeout(function() {
                $('.bassmah-message').fadeOut();
            }, 5000);
        },
        
        /**
         * Reset form
         */
        resetForm: function() {
            $('.bassmah-task-item:not(:first)').remove();
            $('.bassmah-task-item input, .bassmah-task-item textarea, .bassmah-task-item select').val('');
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Initialize JWT authentication
        BassmahJWTAuth.init();
        
        // Form submission is handled by public-scripts.js using REST API
        
        // Handle pagination
        $(document).on('click', '.bassmah-pagination a', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            BassmahFormSubmit.getMyReports(page);
        });
        
        // Handle filters
        $('.bassmah-filter-form').on('change', function() {
            var filters = {
                date_from: $(this).find('.date-from').val(),
                date_to: $(this).find('.date-to').val(),
                status: $(this).find('.status-filter').val()
            };
            BassmahFormSubmit.getMyReports(1, filters);
        });
    });
    
})(jQuery);
