/**
 * Advanced WooCommerce Wishlist - Frontend JavaScript
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Wishlist object
    var AWW = {
        // Initialize
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initCounters();
            this.initModals();
            this.initWishlistSelector();
            this.checkPriceDrops();
        },

        // Bind events
        bindEvents: function() {
            $(document).on('click', '.aww-wishlist-btn', this.handleWishlistClick);
            $(document).on('click', '.aww-remove-wishlist-btn', this.handleRemoveWishlist);
            $(document).on('click', '.aww-add-to-cart-btn', this.handleAddToCart);
            $(document).on('click', '.aww-add-all-to-cart', this.handleAddAllToCart);
            $(document).on('click', '.aww-share-btn', this.handleShareClick);
            $(document).on('click', '.aww-share-options a', this.handleShareOption);
            
            // Multiple wishlist management
            $(document).on('click', '.aww-create-wishlist-btn', this.handleCreateWishlist);
            $(document).on('click', '.aww-rename-wishlist-btn', this.handleRenameWishlist);
            $(document).on('click', '.aww-delete-wishlist-btn', this.handleDeleteWishlist);
            $(document).on('change', '#aww-wishlist-select', this.handleWishlistSelect);
            $(document).on('change', '#aww-wishlist-selector', this.handleWishlistSelector);
            
            // Modal events
            $(document).on('click', '.aww-modal-close, .aww-modal-cancel', this.closeModal);
            $(document).on('click', '.aww-modal', this.handleModalClick);
            $(document).on('submit', '#aww-create-wishlist-form', this.handleCreateWishlistSubmit);
            $(document).on('submit', '#aww-rename-wishlist-form', this.handleRenameWishlistSubmit);
            $(document).on('submit', '#aww-delete-wishlist-form', this.handleDeleteWishlistSubmit);
            
            // Price drop notifications
            $(document).on('click', '.aww-dismiss-price-drop', this.handleDismissPriceDrop);
        },

        // Handle wishlist button click
        handleWishlistClick: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = $btn.data('product-id');
            var wishlistId = $btn.data('wishlist-id');
            var nonce = $btn.data('nonce');
            if ($btn.hasClass('loading')) {
                return;
            }
            var isInWishlist = $btn.hasClass('added');
            // If already in wishlist, open wishlist page
            if (isInWishlist) {
                var wishlistUrl = $btn.data('wishlist-url') || aww_ajax.wishlist_url || '/wishlist/';
                window.location.href = wishlistUrl;
                return;
            }
            $btn.addClass('loading');
            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_add_to_wishlist',
                    product_id: productId,
                    wishlist_id: wishlistId,
                    nonce: nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        // Handle remove from wishlist
        handleRemoveWishlist: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.aww-wishlist-item, .aww-wishlist-row');
            var productId = $btn.data('product-id');
            var wishlistId = $btn.data('wishlist-id');
            var nonce = $btn.data('nonce');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');
            $row.addClass('loading');

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_remove_from_wishlist',
                    product_id: productId,
                    wishlist_id: wishlistId,
                    nonce: nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                    $row.removeClass('loading');
                }
            });
        },

        // Handle add to cart
        handleAddToCart: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = $btn.data('product-id');
            var wishlistId = $btn.data('wishlist-id');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_add_to_cart',
                    product_id: productId,
                    wishlist_id: wishlistId,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        // Handle add all to cart
        handleAddAllToCart: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var wishlistId = $btn.data('wishlist-id');
            var nonce = $btn.data('nonce');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_add_all_to_cart',
                    wishlist_id: wishlistId,
                    nonce: nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        // Handle share click
        handleShareClick: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.data('url');
            var platform = '';
            if ($btn.hasClass('aww-share-facebook')) platform = 'facebook';
            if ($btn.hasClass('aww-share-twitter')) platform = 'twitter';
            if ($btn.hasClass('aww-share-whatsapp')) platform = 'whatsapp';
            if ($btn.hasClass('aww-share-email')) platform = 'email';
            var title = document.title;

            switch (platform) {
                case 'facebook':
                    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank');
                    break;
                case 'twitter':
                    window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
                    break;
                case 'whatsapp':
                    window.open('https://wa.me/?text=' + encodeURIComponent(title + ' ' + url), '_blank');
                    break;
                case 'email':
                    window.open('mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(url), '_blank');
                    break;
            }
        },

        // Handle share option
        handleShareOption: function(e) {
            e.preventDefault();
            var $link = $(this);
            var platform = $link.hasClass('aww-share-facebook') ? 'facebook' :
                          $link.hasClass('aww-share-twitter') ? 'twitter' :
                          $link.hasClass('aww-share-whatsapp') ? 'whatsapp' : 'email';
            var url = $link.data('url');
            var title = document.title;

            switch (platform) {
                case 'facebook':
                    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank');
                    break;
                case 'twitter':
                    window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
                    break;
                case 'whatsapp':
                    window.open('https://wa.me/?text=' + encodeURIComponent(title + ' ' + url), '_blank');
                    break;
                case 'email':
                    window.open('mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(url), '_blank');
                    break;
            }

            $('.aww-share-options').hide();
        },

        // Handle create wishlist
        handleCreateWishlist: function(e) {
            e.preventDefault();
            $('#aww-create-wishlist-modal').show();
        },

        // Handle rename wishlist
        handleRenameWishlist: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var wishlistId = $btn.data('wishlist-id');
            var currentName = $btn.closest('.aww-wishlist-manager').find('#aww-wishlist-select option:selected').text().split(' (')[0];
            
            $('#aww-rename-wishlist-id').val(wishlistId);
            $('#aww-rename-wishlist-name').val(currentName);
            $('#aww-rename-wishlist-modal').show();
        },

        // Handle delete wishlist
        handleDeleteWishlist: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var wishlistId = $btn.data('wishlist-id');
            
            $('#aww-delete-wishlist-id').val(wishlistId);
            $('#aww-delete-wishlist-modal').show();
        },

        // Handle wishlist select
        handleWishlistSelect: function(e) {
            var wishlistId = $(this).val();
            var currentUrl = new URL(window.location);
            currentUrl.searchParams.set('wishlist_id', wishlistId);
            window.location.href = currentUrl.toString();
        },

        // Handle wishlist selector
        handleWishlistSelector: function(e) {
            var wishlistId = $(this).val();
            var nonce = $(this).data('nonce');

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_wishlist_items',
                    wishlist_id: wishlistId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update wishlist content
                        AWW.updateWishlistContent(response.data.items, wishlistId);
                    }
                }
            });
        },

        // Handle create wishlist submit
        handleCreateWishlistSubmit: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var name = $form.find('#aww-wishlist-name').val();

            if (!name.trim()) {
                AWW.showMessage('Please enter a wishlist name.', 'error');
                return;
            }

            $submitBtn.prop('disabled', true);

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_create_wishlist',
                    name: name,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $submitBtn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        },

        // Handle rename wishlist submit
        handleRenameWishlistSubmit: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var wishlistId = $form.find('#aww-rename-wishlist-id').val();
            var name = $form.find('#aww-rename-wishlist-name').val();

            if (!name.trim()) {
                AWW.showMessage('Please enter a wishlist name.', 'error');
                return;
            }

            $submitBtn.prop('disabled', true);

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_update_wishlist',
                    wishlist_id: wishlistId,
                    name: name,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $submitBtn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        },

        // Handle delete wishlist submit
        handleDeleteWishlistSubmit: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var wishlistId = $form.find('#aww-delete-wishlist-id').val();

            $submitBtn.prop('disabled', true);

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_delete_wishlist',
                    wishlist_id: wishlistId,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    AWW.handleAjaxResponse(response, $submitBtn);
                },
                error: function() {
                    AWW.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        },

        // Handle dismiss price drop
        handleDismissPriceDrop: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = $btn.data('product-id');
            var wishlistId = $btn.data('wishlist-id');

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_dismiss_price_drop',
                    product_id: productId,
                    wishlist_id: wishlistId,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('.aww-price-drop-item').fadeOut();
                    }
                }
            });
        },

        // Initialize tooltips
        initTooltips: function() {
            $('[title]').tooltip();
        },

        // Initialize counters
        initCounters: function() {
            $('.aww-wishlist-count').each(function() {
                var $counter = $(this);
                var wishlistId = $counter.data('wishlist-id');
                AWW.updateCounter($counter, wishlistId);
            });
        },

        // Initialize modals
        initModals: function() {
            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) {
                    AWW.closeModal();
                }
            });
        },

        // Initialize wishlist selector
        initWishlistSelector: function() {
            // Auto-hide share options when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.aww-share-wishlist').length) {
                    $('.aww-share-options').hide();
                }
            });
        },

        // Check price drops
        checkPriceDrops: function() {
            var currentWishlistId = $('.aww-wishlist-page').data('wishlist-id') || 
                                   $('#aww-wishlist-select').val() || 
                                   $('#aww-wishlist-selector').val();

            if (!currentWishlistId) {
                return;
            }

            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_price_drops',
                    wishlist_id: currentWishlistId,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.price_drops.length > 0) {
                        AWW.showPriceDrops(response.data.price_drops);
                    }
                }
            });
        },

        // Show price drops
        showPriceDrops: function(priceDrops) {
            var $section = $('.aww-price-drops-section');
            var $list = $('#aww-price-drops-list');
            
            $list.empty();
            
            priceDrops.forEach(function(drop) {
                var html = '<div class="aww-price-drop-item">' +
                    '<div class="aww-product-info">' +
                    '<h4>' + drop.product_name + '</h4>' +
                    '</div>' +
                    '<div class="aww-price-info">' +
                    '<span class="aww-old-price">$' + drop.old_price + '</span>' +
                    '<span class="aww-new-price">$' + drop.new_price + '</span>' +
                    '<span class="aww-discount">-' + drop.discount + '%</span>' +
                    '</div>' +
                    '<button class="aww-dismiss-price-drop" data-product-id="' + drop.product_id + '">×</button>' +
                    '</div>';
                
                $list.append(html);
            });
            
            $section.show();
        },

        // Update counters
        updateCounters: function(wishlistId, count) {
            // Update all counters with this wishlist ID
            $('.aww-wishlist-count[data-wishlist-id="' + wishlistId + '"] .aww-count').text(count);
            $('.aww-wishlist-count-shortcode[data-wishlist-id="' + wishlistId + '"] .aww-count').text(count);
            
            // Update floating icon count
            $('.aww-floating-icon[data-wishlist-id="' + wishlistId + '"] .aww-floating-icon__count').text(count);
            
            // Hide count if zero
            if (count == 0) {
                $('.aww-floating-icon[data-wishlist-id="' + wishlistId + '"] .aww-floating-icon__count').hide();
            } else {
                $('.aww-floating-icon[data-wishlist-id="' + wishlistId + '"] .aww-floating-icon__count').show();
            }
        },

        // Update counter
        updateCounter: function($counter, wishlistId) {
            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_get_wishlist_count',
                    wishlist_id: wishlistId,
                    nonce: aww_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $counter.find('.aww-count').text(response.data.count);
                    }
                }
            });
        },

        // Update wishlist content
        updateWishlistContent: function(items, wishlistId) {
            // This would update the wishlist table content
            // Implementation depends on your specific needs
        },

        // Show message (toast/notification)
        showMessage: function(message, type, showViewLink, wishlistUrl) {
            var $toast = $('#aww-toast');
            if ($toast.length === 0) {
                $toast = $('<div id="aww-toast" class="aww-toast"></div>').appendTo('body');
            }
            
            $toast.removeClass('success error').addClass(type);
            
            var toastContent = message;
            if (showViewLink && wishlistUrl) {
                toastContent += ' <a href="' + wishlistUrl + '" class="aww-view-wishlist-link">' + 
                               (aww_ajax.strings && aww_ajax.strings.view_wishlist ? aww_ajax.strings.view_wishlist : 'View Wishlist') + 
                               '</a>';
            }
            
            $toast.html(toastContent);
            $toast.fadeIn(200);
            
            // Auto-hide after 4 seconds
            setTimeout(function() { 
                $toast.fadeOut(400); 
            }, 4000);
        },

        // Close modal
        closeModal: function() {
            $('.aww-modal').hide();
            $('.aww-modal input').val('');
        },

        // Handle modal click
        handleModalClick: function(e) {
            if (e.target === this) {
                AWW.closeModal();
            }
        },

        // Handle AJAX response
        handleAjaxResponse: function(response, button) {
            if (response.success) {
                // Update counters
                if (response.data.count !== undefined && response.data.wishlist_id) {
                    this.updateCounters(response.data.wishlist_id, response.data.count);
                }
                
                // Show success message with View Wishlist link
                if (response.data.wishlist_url) {
                    this.showMessage(response.data.message, 'success', true, response.data.wishlist_url);
                } else {
                    this.showMessage(response.data.message, 'success');
                }
                
                // Handle redirects
                if (response.data.redirect) {
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
                }
                
                // Update button state
                if (button) {
                    this.updateButtonState(button, response.data);
                }
                
                // Handle wishlist item removal
                if (response.data.product_id && button && button.closest('.aww-wishlist-item, .aww-wishlist-row').length) {
                    button.closest('.aww-wishlist-item, .aww-wishlist-row').fadeOut(300, function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($('.aww-wishlist-item, .aww-wishlist-row').length === 0) {
                            location.reload();
                        }
                    });
                }
                
                // Handle modal actions
                if (response.data.message && (response.data.message.includes('created') || response.data.message.includes('updated') || response.data.message.includes('deleted'))) {
                    this.closeModal();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
                
                // Trigger cart update for add to cart actions
                if (response.data.added_count || response.data.message.includes('cart')) {
                    $(document.body).trigger('added_to_cart');
                }
            } else {
                // Handle login requirement
                if (response.data && response.data.redirect) {
                    this.showMessage(response.data.message, 'error');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    this.showMessage(response.data.message, 'error');
                }
            }
        },

        // Update button state
        updateButtonState: function(button, data) {
            if (button.hasClass('aww-wishlist-btn')) {
                var isInWishlist = button.hasClass('added');
                if (isInWishlist) {
                    // Remove from wishlist (not used here)
                    button.removeClass('added');
                    button.find('.aww-text').text('Add to wishlist');
                } else {
                    // Add to wishlist
                    button.addClass('added');
                    button.find('.aww-text').text('Browse wishlist');
                    // Add wishlist URL to button data for future clicks
                    if (data.wishlist_url) {
                        button.data('wishlist-url', data.wishlist_url);
                    }
                }
            }
            // Re-enable button
            button.prop('disabled', false);
        },

        // Show browse wishlist toast
        showBrowseWishlistToast: function($btn) {
            // Remove any existing toast
            $('.aww-browse-wishlist-toast').remove();
            var toast = $('<div class="aww-browse-wishlist-toast" style="position:absolute;z-index:9999;background:#222;color:#fff;padding:12px 20px;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,0.18);font-size:15px;display:flex;align-items:center;gap:12px;">'+
                '<span>' + (aww_ajax.strings.added_to_wishlist || 'Added to wishlist!') + '</span>' +
                '<a href="'+ aww_ajax.wishlist_url +'" class="aww-browse-link" style="color:#fff;text-decoration:underline;font-weight:600;">Browse Wishlist</a>' +
                '</div>');
            $('body').append(toast);
            // Position near button
            var offset = $btn.offset();
            toast.css({
                top: offset.top - toast.outerHeight() - 10,
                left: offset.left
            });
            // Remove on click or after 4s
            toast.find('.aww-browse-link').on('click', function() { toast.remove(); });
            setTimeout(function(){ toast.fadeOut(300, function(){ $(this).remove(); }); }, 4000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AWW.init();
    });

    // Add CSS for toast
    $(function() {
        if ($('#aww-toast-style').length === 0) {
            $('head').append('<style id="aww-toast-style">' +
                '.aww-toast{' +
                    'position: fixed;' +
                    'bottom: 30px;' +
                    'left: 50%;' +
                    'transform: translateX(-50%);' +
                    'background: #222;' +
                    'color: #fff;' +
                    'padding: 14px 28px;' +
                    'border-radius: 6px;' +
                    'z-index: 9999;' +
                    'font-size: 16px;' +
                    'box-shadow: 0 4px 16px rgba(0,0,0,0.15);' +
                    'display: none;' +
                    'max-width: 90%;' +
                    'text-align: center;' +
                '}' +
                '.aww-toast.success{' +
                    'background: #27ae60;' +
                '}' +
                '.aww-toast.error{' +
                    'background: #e74c3c;' +
                '}' +
                '.aww-toast .aww-view-wishlist-link{' +
                    'color: #fff;' +
                    'text-decoration: underline;' +
                    'margin-left: 10px;' +
                    'font-weight: bold;' +
                '}' +
                '.aww-toast .aww-view-wishlist-link:hover{' +
                    'color: #f0f0f0;' +
                '}' +
                '</style>');
        }
    });

})(jQuery); 