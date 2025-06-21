/**
 * Advanced WooCommerce Wishlist - Frontend JavaScript
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.4
 */

(function($) {
    'use strict';

    // Wishlist object
    var AWW = {
        // Initialize
        init: function() {
            this.bindEvents();
            this.initButtons();
            this.initTooltips();
            this.initCounters();
            this.initModals();
            this.initWishlistSelector();
            this.checkPriceDrops();
        },

        // Bind events
        bindEvents: function() {
            $(document).on('click', '.aww-wishlist-btn', this.handleWishlistClick.bind(this));
            $(document).on('click', '.aww-remove-wishlist-btn', this.handleRemoveWishlist.bind(this));
            $(document).on('click', '.aww-add-to-cart-btn', this.handleAddToCart.bind(this));
            $(document).on('click', '.aww-add-all-to-cart', this.handleAddAllToCart.bind(this));
            $(document).on('click', '.aww-share-btn', this.handleShareClick.bind(this));
            $(document).on('click', '.aww-share-options a', this.handleShareOption.bind(this));
            
            // Multiple wishlist management
            $(document).on('click', '.aww-create-wishlist-btn', this.handleCreateWishlist.bind(this));
            $(document).on('click', '.aww-rename-wishlist-btn', this.handleRenameWishlist.bind(this));
            $(document).on('click', '.aww-delete-wishlist-btn', this.handleDeleteWishlist.bind(this));
            $(document).on('change', '#aww-wishlist-select', this.handleWishlistSelect.bind(this));
            $(document).on('change', '#aww-wishlist-selector', this.handleWishlistSelector.bind(this));
            
            // Modal events
            $(document).on('click', '.aww-modal-close, .aww-modal-cancel', this.closeModal.bind(this));
            $(document).on('click', '.aww-modal', this.handleModalClick.bind(this));
            $(document).on('submit', '#aww-create-wishlist-form', this.handleCreateWishlistSubmit.bind(this));
            $(document).on('submit', '#aww-rename-wishlist-form', this.handleRenameWishlistSubmit.bind(this));
            $(document).on('submit', '#aww-delete-wishlist-form', this.handleDeleteWishlistSubmit.bind(this));
            
            // Price drop notifications
            $(document).on('click', '.aww-dismiss-price-drop', this.handleDismissPriceDrop.bind(this));
        },

        // Initialize buttons on page load
        initButtons: function() {
            var self = this;
            $('.aww-wishlist-btn').each(function() {
                var $btn = $(this);
                var isInWishlist = $btn.hasClass('added');
                self.updateButton($btn, isInWishlist);
            });
        },
        
        // Get icon HTML
        getIconHtml: function(icon) {
            switch (icon) {
                case 'heart':
                    return '<span class="aww-icon">&hearts;</span>';
                case 'star':
                    return '<span class="aww-icon">&#9733;</span>';
                case 'plus':
                    return '<span class="aww-icon">&#43;</span>';
                case 'custom':
                    // Use the custom SVG provided in settings
                    return aww_ajax.button_custom_svg ? '<span class="aww-icon custom-svg-icon">' + aww_ajax.button_custom_svg + '</span>' : '';
                case 'none':
                default:
                    return '';
            }
        },

        // Update button appearance
        updateButton: function($btn, isInWishlist) {
            var iconHtml = this.getIconHtml(aww_ajax.button_icon);
            var text = isInWishlist ? aww_ajax.strings.button_text_added : aww_ajax.strings.button_text;
            
            $btn.removeClass('aww-style-default aww-style-outline aww-style-minimal');
            $btn.addClass('aww-style-' + aww_ajax.button_style);

            $btn.html(iconHtml + '<span class="aww-text">' + text + '</span>');
            if (isInWishlist) {
                $btn.addClass('added');
            } else {
                $btn.removeClass('added');
            }
        },

        // Handle wishlist button click
        handleWishlistClick: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
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
            var self = this;
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
                    self.handleAjaxResponse(response, $btn);
                    if (response.success) {
                        self.updateButton($btn, true);
                    }
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        // Handle remove from wishlist
        handleRemoveWishlist: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var $row = $btn.closest('.aww-wishlist-item, .aww-wishlist-row');
            var productId = $btn.data('product-id');
            var wishlistId = $btn.data('wishlist-id');
            var nonce = $btn.data('nonce');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');
            $row.addClass('loading');
            var self = this;
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
                    self.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
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
            var $btn = $(e.currentTarget);
            var productId = $btn.data('product-id');
            var wishlistId = $btn.data('wishlist-id');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');
            var self = this;
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
                    self.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        // Handle add all to cart
        handleAddAllToCart: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var wishlistId = $btn.data('wishlist-id');
            var nonce = $btn.data('nonce');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');
            var self = this;
            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aww_add_all_to_cart',
                    wishlist_id: wishlistId,
                    nonce: nonce
                },
                success: function(response) {
                    self.handleAjaxResponse(response, $btn);
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error || 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        // Handle share click
        handleShareClick: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
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
            var $link = $(e.currentTarget);
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
            this.openModal('create-wishlist-modal');
        },

        // Handle rename wishlist
        handleRenameWishlist: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var wishlistId = $btn.data('wishlist-id');
            var currentName = $btn.data('wishlist-name');
            $('#aww-rename-wishlist-id').val(wishlistId);
            $('#aww-rename-wishlist-name').val(currentName);
            this.openModal('rename-wishlist-modal');
        },

        // Handle delete wishlist
        handleDeleteWishlist: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var wishlistId = $btn.data('wishlist-id');
            $('#aww-delete-wishlist-id').val(wishlistId);
            this.openModal('delete-wishlist-modal');
        },

        // Handle create wishlist submit
        handleCreateWishlistSubmit: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var self = this;
            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    self.handleAjaxResponse(response);
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error, 'error');
                }
            });
        },

        // Handle rename wishlist submit
        handleRenameWishlistSubmit: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var self = this;
            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    self.handleAjaxResponse(response);
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error, 'error');
                }
            });
        },

        // Handle delete wishlist submit
        handleDeleteWishlistSubmit: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var self = this;
            $.ajax({
                url: aww_ajax.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    self.handleAjaxResponse(response);
                },
                error: function() {
                    self.showMessage(aww_ajax.strings.error, 'error');
                }
            });
        },

        // Handle main wishlist select change
        handleWishlistSelect: function(e) {
            var wishlistId = $(e.currentTarget).val();
            var baseUrl = window.location.href.split('?')[0];
            window.location.href = baseUrl + '?wishlist_id=' + wishlistId;
        },

        // Handle wishlist selector (for adding items)
        handleWishlistSelector: function(e) {
            var $selector = $(e.currentTarget);
            var wishlistId = $selector.val();
            var productId = $selector.data('product-id');
            $('.aww-wishlist-btn[data-product-id="' + productId + '"]').data('wishlist-id', wishlistId);
        },

        // Open a modal
        openModal: function(modalId) {
            $('#' + modalId).fadeIn(200);
        },

        // Close any open modals
        closeModal: function() {
            $('.aww-modal').fadeOut(200);
        },

        // Handle modal click (close if outside content)
        handleModalClick: function(e) {
            if ($(e.target).is('.aww-modal')) {
                this.closeModal();
            }
        },

        // Initialize tooltips
        initTooltips: function() {
            // Simple CSS tooltips are assumed, no JS needed unless you want a library
        },

        // Initialize counters
        initCounters: function() {
            var $counter = $('#aww-wishlist-count, .aww-wishlist-counter');
            if ($counter.length) {
                $.ajax({
                    url: aww_ajax.ajax_url,
                    type: 'POST',
                    data: { action: 'aww_get_wishlist_count' },
                    success: function(response) {
                        if (response.success) {
                            $counter.text(response.data.count);
                        }
                    }
                });
            }
        },

        // Initialize the wishlist selector dropdowns
        initWishlistSelector: function() {
            // Logic to populate wishlist selectors if they are empty
        },

        // Check for price drop notifications
        checkPriceDrops: function() {
            // Logic to check and display price drop notifications
        },
        
        // Handle dismiss price drop notification
        handleDismissPriceDrop: function(e) {
            e.preventDefault();
            // Logic to dismiss notification
        },

        // Handle AJAX response
        handleAjaxResponse: function(response, $btn) {
            if (response.success) {
                this.showMessage(response.data.message, 'success');
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
                if (response.data.count) {
                    $('#aww-wishlist-count, .aww-wishlist-counter').text(response.data.count);
                }
                if (response.data.wishlist_html) {
                    $('.aww-wishlist-table-container').html(response.data.wishlist_html);
                }
            } else {
                this.showMessage(response.data.message, 'error');
            }
        },

        // Show message/toast
        showMessage: function(message, type) {
            $('.aww-toast').remove();
            var toast = $('<div class="aww-toast ' + type + '">' + message + '</div>').appendTo('body');
            toast.fadeIn(300);
            toast.on('click', function() { $(this).remove(); });
            setTimeout(function(){ toast.fadeOut(300, function(){ $(this).remove(); }); }, 4000);
        }
    };

    // Initialize on document ready
    $(function() {
        AWW.init();
    });

})(jQuery); 