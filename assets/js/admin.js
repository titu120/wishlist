/**
 * Advanced WooCommerce Wishlist - Admin JavaScript
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Admin object
    var AWWAdmin = {
        // Initialize
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initColorPickers();
            this.initTabs();
        },

        // Bind events
        bindEvents: function() {
            $(document).on('click', '.aww-export-data', this.handleExportData);
            $(document).on('click', '.aww-clean-expired', this.handleCleanExpired);
            $(document).on('click', '.aww-refresh-analytics', this.handleRefreshAnalytics);
            $(document).on('change', '.aww-settings-toggle', this.handleSettingsToggle);
            $(document).on('click', '.aww-reset-settings', this.handleResetSettings);
            $(document).on('click', '.aww-test-email', this.handleTestEmail);
            $(document).on('click', '.aww-bulk-action', this.handleBulkAction);
            $(document).on('change', '.aww-date-range', this.handleDateRangeChange);
        },

        // Handle export data
        handleExportData: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var format = $btn.data('format') || 'csv';
            var dateRange = $('.aww-date-range').val();
            
            $btn.prop('disabled', true);
            $btn.text(aww_admin.texts.exporting);
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_export_data',
                    format: format,
                    date_range: dateRange,
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Download file
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        AWWAdmin.showMessage(response.data.message, 'success');
                    } else {
                        AWWAdmin.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.text(aww_admin.texts.export_data);
                }
            });
        },

        // Handle clean expired
        handleCleanExpired: function(e) {
            e.preventDefault();
            
            if (!confirm(aww_admin.texts.confirm_clean_expired)) {
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            $btn.text(aww_admin.texts.cleaning);
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_clean_expired',
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AWWAdmin.showMessage(response.data.message, 'success');
                        AWWAdmin.updateStats(response.data.stats);
                    } else {
                        AWWAdmin.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.text(aww_admin.texts.clean_expired);
                }
            });
        },

        // Handle refresh analytics
        handleRefreshAnalytics: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            $btn.text(aww_admin.texts.refreshing);
            
            AWWAdmin.loadAnalytics(function() {
                $btn.prop('disabled', false);
                $btn.text(aww_admin.texts.refresh_analytics);
            });
        },

        // Handle settings toggle
        handleSettingsToggle: function(e) {
            var $toggle = $(this);
            var setting = $toggle.data('setting');
            var value = $toggle.is(':checked') ? 'yes' : 'no';
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_update_setting',
                    setting: setting,
                    value: value,
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AWWAdmin.showMessage(response.data.message, 'success');
                    } else {
                        AWWAdmin.showMessage(response.data.message, 'error');
                        // Revert toggle
                        $toggle.prop('checked', !$toggle.is(':checked'));
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                    // Revert toggle
                    $toggle.prop('checked', !$toggle.is(':checked'));
                }
            });
        },

        // Handle reset settings
        handleResetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm(aww_admin.texts.confirm_reset_settings)) {
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_reset_settings',
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        AWWAdmin.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        // Handle test email
        handleTestEmail: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var email = $('.aww-test-email-input').val();
            
            if (!email || !AWWAdmin.isValidEmail(email)) {
                AWWAdmin.showMessage(aww_admin.texts.invalid_email, 'error');
                return;
            }
            
            $btn.prop('disabled', true);
            $btn.text(aww_admin.texts.sending);
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_test_email',
                    email: email,
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AWWAdmin.showMessage(response.data.message, 'success');
                    } else {
                        AWWAdmin.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.text(aww_admin.texts.send_test);
                }
            });
        },

        // Handle bulk action
        handleBulkAction: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var action = $btn.data('action');
            var selectedProducts = $('.aww-product-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedProducts.length === 0) {
                AWWAdmin.showMessage(aww_admin.texts.no_products_selected, 'warning');
                return;
            }
            
            if (!confirm(aww_admin.texts.confirm_bulk_action.replace('%s', action))) {
                return;
            }
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_bulk_action',
                    bulk_action: action,
                    product_ids: selectedProducts,
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AWWAdmin.showMessage(response.data.message, 'success');
                        location.reload();
                    } else {
                        AWWAdmin.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        // Handle date range change
        handleDateRangeChange: function(e) {
            var dateRange = $(this).val();
            AWWAdmin.loadAnalytics();
        },

        // Initialize charts
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }
            
            // Wishlist growth chart
            var growthCtx = document.getElementById('aww-growth-chart');
            if (growthCtx) {
                AWWAdmin.loadGrowthChart(growthCtx);
            }
            
            // Popular products chart
            var popularCtx = document.getElementById('aww-popular-chart');
            if (popularCtx) {
                AWWAdmin.loadPopularChart(popularCtx);
            }
            
            // Conversion rate chart
            var conversionCtx = document.getElementById('aww-conversion-chart');
            if (conversionCtx) {
                AWWAdmin.loadConversionChart(conversionCtx);
            }
        },

        // Load growth chart
        loadGrowthChart: function(ctx) {
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_growth_data',
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: aww_admin.texts.wishlist_growth,
                                    data: data.values,
                                    borderColor: '#e74c3c',
                                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        // Load popular chart
        loadPopularChart: function(ctx) {
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_popular_data',
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    data: data.values,
                                    backgroundColor: [
                                        '#e74c3c',
                                        '#3498db',
                                        '#2ecc71',
                                        '#f39c12',
                                        '#9b59b6',
                                        '#1abc9c',
                                        '#e67e22',
                                        '#34495e'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        // Load conversion chart
        loadConversionChart: function(ctx) {
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_conversion_data',
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: aww_admin.texts.conversion_rate,
                                    data: data.values,
                                    backgroundColor: '#2ecc71'
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: {
                                            callback: function(value) {
                                                return value + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        // Load analytics
        loadAnalytics: function(callback) {
            var dateRange = $('.aww-date-range').val();
            
            $.ajax({
                url: aww_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_analytics',
                    date_range: dateRange,
                    nonce: aww_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AWWAdmin.updateStats(response.data.stats);
                        AWWAdmin.updateCharts(response.data.charts);
                        
                        if (callback) {
                            callback();
                        }
                    }
                },
                error: function() {
                    AWWAdmin.showMessage(aww_admin.texts.error_message, 'error');
                    if (callback) {
                        callback();
                    }
                }
            });
        },

        // Update stats
        updateStats: function(stats) {
            $('.aww-total-wishlists').text(stats.total_wishlists);
            $('.aww-total-products').text(stats.total_products);
            $('.aww-active-users').text(stats.active_users);
            $('.aww-conversion-rate').text(stats.conversion_rate + '%');
        },

        // Update charts
        updateCharts: function(charts) {
            // Update chart data if needed
            if (charts.growth) {
                // Update growth chart
            }
            if (charts.popular) {
                // Update popular chart
            }
            if (charts.conversion) {
                // Update conversion chart
            }
        },

        // Initialize color pickers
        initColorPickers: function() {
            $('.aww-color-picker').wpColorPicker();
        },

        // Initialize tabs
        initTabs: function() {
            $('.aww-tab-nav a').click(function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Hide all tabs
                $('.aww-tab-content').hide();
                $('.aww-tab-nav a').removeClass('active');
                
                // Show target tab
                $(target).show();
                $(this).addClass('active');
            });
            
            // Show first tab by default
            $('.aww-tab-nav a:first').click();
        },

        // Show message
        showMessage: function(message, type) {
            var $message = $('<div class="aww-admin-message aww-admin-message-' + type + '">' + message + '</div>');
            $('.wrap h1').after($message);
            
            $message.fadeIn(300).delay(3000).fadeOut(300, function() {
                $message.remove();
            });
        },

        // Validate email
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AWWAdmin.init();
    });

    // Make AWWAdmin globally accessible
    window.AWWAdmin = AWWAdmin;

})(jQuery); 