<?php
/**
 * AJAX Class for Advanced WooCommerce Wishlist
 *
 * @package Advanced_WC_Wishlist
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AWW_Ajax Class
 *
 * Handles all AJAX requests for wishlist functionality
 *
 * @since 1.0.0
 */
class AWW_Ajax {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX actions for logged-in users
        add_action( 'wp_ajax_aww_add_to_wishlist', array( $this, 'add_to_wishlist' ) );
        add_action( 'wp_ajax_aww_remove_from_wishlist', array( $this, 'remove_from_wishlist' ) );
        add_action( 'wp_ajax_aww_get_wishlist_count', array( $this, 'get_wishlist_count' ) );
        add_action( 'wp_ajax_aww_get_wishlist_items', array( $this, 'get_wishlist_items' ) );
        add_action( 'wp_ajax_aww_add_to_cart', array( $this, 'add_to_cart' ) );
        add_action( 'wp_ajax_aww_add_all_to_cart', array( $this, 'add_all_to_cart' ) );
        add_action( 'wp_ajax_aww_share_wishlist', array( $this, 'share_wishlist' ) );
        
        // Multiple wishlist management
        add_action( 'wp_ajax_aww_create_wishlist', array( $this, 'create_wishlist' ) );
        add_action( 'wp_ajax_aww_update_wishlist', array( $this, 'update_wishlist' ) );
        add_action( 'wp_ajax_aww_delete_wishlist', array( $this, 'delete_wishlist' ) );
        add_action( 'wp_ajax_aww_get_wishlists', array( $this, 'get_wishlists' ) );
        
        // Price drop notifications
        add_action( 'wp_ajax_aww_dismiss_price_drop', array( $this, 'dismiss_price_drop' ) );
        add_action( 'wp_ajax_aww_get_price_drops', array( $this, 'get_price_drops' ) );

        // AJAX actions for non-logged-in users
        add_action( 'wp_ajax_nopriv_aww_add_to_wishlist', array( $this, 'add_to_wishlist' ) );
        add_action( 'wp_ajax_nopriv_aww_remove_from_wishlist', array( $this, 'remove_from_wishlist' ) );
        add_action( 'wp_ajax_nopriv_aww_get_wishlist_count', array( $this, 'get_wishlist_count' ) );
        add_action( 'wp_ajax_nopriv_aww_get_wishlist_items', array( $this, 'get_wishlist_items' ) );
        add_action( 'wp_ajax_nopriv_aww_add_to_cart', array( $this, 'add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_aww_add_all_to_cart', array( $this, 'add_all_to_cart' ) );
        add_action( 'wp_ajax_nopriv_aww_share_wishlist', array( $this, 'share_wishlist' ) );
        
        // Guest wishlist management
        add_action( 'wp_ajax_nopriv_aww_create_wishlist', array( $this, 'create_wishlist' ) );
        add_action( 'wp_ajax_nopriv_aww_update_wishlist', array( $this, 'update_wishlist' ) );
        add_action( 'wp_ajax_nopriv_aww_delete_wishlist', array( $this, 'delete_wishlist' ) );
        add_action( 'wp_ajax_nopriv_aww_get_wishlists', array( $this, 'get_wishlists' ) );
    }

    /**
     * Add product to wishlist
     */
    public function add_to_wishlist() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to add to wishlist.', 'advanced-wc-wishlist' ) ) );
        }
        check_ajax_referer( 'aww_nonce', 'nonce' );
        
        $product_id = intval( $_POST['product_id'] );
        $wishlist_id = isset( $_POST['wishlist_id'] ) ? intval( $_POST['wishlist_id'] ) : null;

        // Check if login is required
        if ( 'yes' === Advanced_WC_Wishlist::get_option( 'require_login', 'no' ) && ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Please log in to add items to your wishlist.', 'advanced-wc-wishlist' ),
                'redirect' => wp_login_url( get_permalink( $product_id ) )
            ) );
        }

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'advanced-wc-wishlist' ) ) );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'advanced-wc-wishlist' ) ) );
        }

        // Check if already in wishlist
        if ( AWW()->database->is_product_in_wishlist( $product_id, $wishlist_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Product is already in your wishlist.', 'advanced-wc-wishlist' ) ) );
        }

        $result = AWW()->database->add_to_wishlist( $product_id, $wishlist_id );
        
        if ( $result ) {
            $count = AWW()->database->get_wishlist_count( $wishlist_id );
            $wishlist_url = AWW()->core->get_wishlist_url( $wishlist_id );
            
            wp_send_json_success( array(
                'message' => __( 'Added to wishlist!', 'advanced-wc-wishlist' ),
                'count' => $count,
                'button_action' => 'add',
                'wishlist_id' => $wishlist_id,
                'wishlist_url' => $wishlist_url,
                'product_id' => $product_id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not add to wishlist.', 'advanced-wc-wishlist' ) ) );
        }
    }

    /**
     * Remove product from wishlist
     */
    public function remove_from_wishlist() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to remove from wishlist.', 'advanced-wc-wishlist' ) ) );
        }
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        $product_id = intval( $_POST['product_id'] );
        $wishlist_id = isset( $_POST['wishlist_id'] ) ? intval( $_POST['wishlist_id'] ) : null;
        
        if ( ! $product_id ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid product.', 'advanced-wc-wishlist' ),
            ) );
        }

        $result = AWW()->database->remove_from_wishlist( $product_id, $wishlist_id );

        if ( $result ) {
            $new_count = AWW()->database->get_wishlist_count( $wishlist_id );
            wp_send_json_success( array(
                'message' => __( 'Product removed from wishlist successfully!', 'advanced-wc-wishlist' ),
                'count' => $new_count,
                'product_id' => $product_id,
                'wishlist_id' => $wishlist_id,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Failed to remove product from wishlist. Please try again.', 'advanced-wc-wishlist' ),
            ) );
        }
    }

    /**
     * Get wishlist count
     */
    public function get_wishlist_count() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        $wishlist_id = isset( $_POST['wishlist_id'] ) ? intval( $_POST['wishlist_id'] ) : null;
        $count = AWW()->database->get_wishlist_count( $wishlist_id );

        wp_send_json_success( array(
            'count' => $count,
            'wishlist_id' => $wishlist_id,
        ) );
    }

    /**
     * Get wishlist items
     */
    public function get_wishlist_items() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        $wishlist_id = isset( $_POST['wishlist_id'] ) ? intval( $_POST['wishlist_id'] ) : null;
        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0;
        $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;

        $items = AWW()->database->get_wishlist_items( $wishlist_id, $limit, $offset );

        // Format items for response
        $formatted_items = array();
        foreach ( $items as $item ) {
            $product = wc_get_product( $item->product_id );
            if ( $product ) {
                $formatted_items[] = array(
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->get_name(),
                    'product_url' => $product->get_permalink(),
                    'product_image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                    'product_price' => $product->get_price_html(),
                    'product_stock' => $product->get_stock_status(),
                    'price_at_add' => $item->price_at_add,
                    'date_added' => $item->date_added,
                );
            }
        }

        wp_send_json_success( array(
            'items' => $formatted_items,
            'total' => count( $formatted_items ),
            'wishlist_id' => $wishlist_id,
        ) );
    }

    /**
     * Add all wishlist items to cart
     */
    public function add_all_to_cart() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to add all to cart.', 'advanced-wc-wishlist' ) ) );
        }
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Please log in to add items to cart.', 'advanced-wc-wishlist' ),
            ) );
        }

        $wishlist_id = isset( $_POST['wishlist_id'] ) ? intval( $_POST['wishlist_id'] ) : null;
        $items = AWW()->database->get_wishlist_items( $wishlist_id );
        $added_count = 0;
        $errors = array();
        $removed_items = array();

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->product_id );
            if ( $product && $product->is_purchasable() && $product->is_in_stock() ) {
                $result = WC()->cart->add_to_cart( $item->product_id );
                if ( $result ) {
                    $added_count++;
                    
                    // Remove from wishlist if setting is enabled
                    if ( 'yes' === Advanced_WC_Wishlist::get_option( 'remove_after_add_to_cart', 'no' ) ) {
                        AWW()->database->remove_from_wishlist( $item->product_id, $wishlist_id );
                        $removed_items[] = $item->product_id;
                    }
                } else {
                    $errors[] = sprintf( __( 'Failed to add %s to cart.', 'advanced-wc-wishlist' ), $product->get_name() );
                }
            } else {
                $errors[] = sprintf( __( '%s is not available for purchase.', 'advanced-wc-wishlist' ), $product ? $product->get_name() : 'Product' );
            }
        }

        if ( $added_count > 0 ) {
            $message = sprintf( _n( '%d item added to cart.', '%d items added to cart.', $added_count, 'advanced-wc-wishlist' ), $added_count );
            if ( ! empty( $errors ) ) {
                $message .= ' ' . __( 'Some items could not be added.', 'advanced-wc-wishlist' );
            }
            
            $response = array(
                'message' => $message,
                'added_count' => $added_count,
                'errors' => $errors,
            );
            
            // Add redirect URL if setting is enabled
            if ( 'yes' === Advanced_WC_Wishlist::get_option( 'redirect_to_cart', 'no' ) ) {
                $response['redirect'] = wc_get_cart_url();
            }
            
            // Update wishlist count if items were removed
            if ( ! empty( $removed_items ) ) {
                $new_count = AWW()->database->get_wishlist_count( $wishlist_id );
                $response['count'] = $new_count;
                $response['wishlist_id'] = $wishlist_id;
            }
            
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( array(
                'message' => __( 'No items could be added to cart.', 'advanced-wc-wishlist' ),
                'errors' => $errors,
            ) );
        }
    }

    /**
     * Share wishlist
     */
    public function share_wishlist() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Check if social sharing is enabled
        if ( ! AWW()->core->is_social_sharing_enabled() ) {
            wp_send_json_error( array(
                'message' => __( 'Social sharing is disabled.', 'advanced-wc-wishlist' ),
            ) );
        }

        $platform = sanitize_text_field( $_POST['platform'] );
        $wishlist_url = AWW()->core->get_wishlist_url();

        $share_urls = array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode( $wishlist_url ),
            'twitter' => 'https://twitter.com/intent/tweet?url=' . urlencode( $wishlist_url ) . '&text=' . urlencode( __( 'Check out my wishlist!', 'advanced-wc-wishlist' ) ),
            'whatsapp' => 'https://wa.me/?text=' . urlencode( __( 'Check out my wishlist!', 'advanced-wc-wishlist' ) . ' ' . $wishlist_url ),
            'email' => 'mailto:?subject=' . urlencode( __( 'My Wishlist', 'advanced-wc-wishlist' ) ) . '&body=' . urlencode( __( 'Check out my wishlist:', 'advanced-wc-wishlist' ) . ' ' . $wishlist_url ),
        );

        if ( isset( $share_urls[ $platform ] ) ) {
            wp_send_json_success( array(
                'share_url' => $share_urls[ $platform ],
                'platform' => $platform,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Invalid sharing platform.', 'advanced-wc-wishlist' ),
            ) );
        }
    }

    /**
     * Get wishlist data for guest users
     */
    public function get_guest_wishlist_data() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Check if guest wishlist is enabled
        if ( ! AWW()->core->is_guest_wishlist_enabled() ) {
            wp_send_json_error( array(
                'message' => __( 'Guest wishlist is disabled.', 'advanced-wc-wishlist' ),
            ) );
        }

        $items = AWW()->database->get_wishlist_items();
        $count = AWW()->database->get_wishlist_count();

        wp_send_json_success( array(
            'items' => $items,
            'count' => $count,
        ) );
    }

    /**
     * Save guest email for wishlist
     */
    public function save_guest_email() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Check if guest wishlist is enabled
        if ( ! AWW()->core->is_guest_wishlist_enabled() ) {
            wp_send_json_error( array(
                'message' => __( 'Guest wishlist is disabled.', 'advanced-wc-wishlist' ),
            ) );
        }

        $email = sanitize_email( $_POST['email'] );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter a valid email address.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Store guest email in session
        $_SESSION['aww_guest_email'] = $email;

        wp_send_json_success( array(
            'message' => __( 'Email saved successfully!', 'advanced-wc-wishlist' ),
        ) );
    }

    /**
     * Get wishlist analytics (admin only)
     */
    public function get_wishlist_analytics() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Check if user has admin capabilities
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to view analytics.', 'advanced-wc-wishlist' ),
            ) );
        }

        $analytics = AWW()->database->get_analytics();
        $popular_products = AWW()->database->get_popular_wishlisted_products( 10 );

        wp_send_json_success( array(
            'analytics' => $analytics,
            'popular_products' => $popular_products,
        ) );
    }

    /**
     * Export wishlist data (admin only)
     */
    public function export_wishlist_data() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ),
            ) );
        }

        // Check if user has admin capabilities
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to export data.', 'advanced-wc-wishlist' ),
            ) );
        }

        $format = sanitize_text_field( $_POST['format'] );
        if ( ! in_array( $format, array( 'csv', 'json' ), true ) ) {
            $format = 'csv';
        }

        $data = AWW()->database->export_wishlist_data( $format );

        wp_send_json_success( array(
            'data' => $data,
            'format' => $format,
        ) );
    }

    /**
     * Create new wishlist
     */
    public function create_wishlist() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to create wishlists.', 'advanced-wc-wishlist' ) ) );
        }
        if (!wp_verify_nonce($_POST['nonce'], 'aww_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-wc-wishlist')));
        }

        $name = sanitize_text_field($_POST['name']);
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Wishlist name is required.', 'advanced-wc-wishlist')));
        }

        $wishlist_id = AWW()->database->create_wishlist($name);
        if ($wishlist_id) {
            wp_send_json_success(array(
                'message' => __('Wishlist created successfully!', 'advanced-wc-wishlist'),
                'wishlist_id' => $wishlist_id,
                'name' => $name,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create wishlist. Please try again.', 'advanced-wc-wishlist')));
        }
    }

    /**
     * Update wishlist name
     */
    public function update_wishlist() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to update wishlists.', 'advanced-wc-wishlist' ) ) );
        }
        if (!wp_verify_nonce($_POST['nonce'], 'aww_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-wc-wishlist')));
        }

        $wishlist_id = intval($_POST['wishlist_id']);
        $name = sanitize_text_field($_POST['name']);
        
        if (!$wishlist_id || empty($name)) {
            wp_send_json_error(array('message' => __('Invalid wishlist or name.', 'advanced-wc-wishlist')));
        }

        $result = AWW()->database->update_wishlist($wishlist_id, $name);
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Wishlist updated successfully!', 'advanced-wc-wishlist'),
                'wishlist_id' => $wishlist_id,
                'name' => $name,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update wishlist. Please try again.', 'advanced-wc-wishlist')));
        }
    }

    /**
     * Delete wishlist
     */
    public function delete_wishlist() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to delete wishlists.', 'advanced-wc-wishlist' ) ) );
        }
        if (!wp_verify_nonce($_POST['nonce'], 'aww_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-wc-wishlist')));
        }

        $wishlist_id = intval($_POST['wishlist_id']);
        if (!$wishlist_id) {
            wp_send_json_error(array('message' => __('Invalid wishlist.', 'advanced-wc-wishlist')));
        }

        $result = AWW()->database->delete_wishlist($wishlist_id);
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Wishlist deleted successfully!', 'advanced-wc-wishlist'),
                'wishlist_id' => $wishlist_id,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete wishlist. Please try again.', 'advanced-wc-wishlist')));
        }
    }

    /**
     * Get user wishlists
     */
    public function get_wishlists() {
        if (!wp_verify_nonce($_POST['nonce'], 'aww_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-wc-wishlist')));
        }

        $wishlists = AWW()->database->get_wishlists();
        $formatted_wishlists = array();
        
        foreach ($wishlists as $wishlist) {
            $count = AWW()->database->get_wishlist_count($wishlist->id);
            $formatted_wishlists[] = array(
                'id' => $wishlist->id,
                'name' => $wishlist->name,
                'count' => $count,
                'date_created' => $wishlist->date_created,
                'date_updated' => $wishlist->date_updated,
            );
        }

        wp_send_json_success(array('wishlists' => $formatted_wishlists));
    }

    /**
     * Dismiss price drop notification
     */
    public function dismiss_price_drop() {
        if (!wp_verify_nonce($_POST['nonce'], 'aww_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-wc-wishlist')));
        }

        $product_id = intval($_POST['product_id']);
        $wishlist_id = isset($_POST['wishlist_id']) ? intval($_POST['wishlist_id']) : null;
        
        // Mark as dismissed (you can add a dismissed column to track this)
        wp_send_json_success(array('message' => __('Notification dismissed.', 'advanced-wc-wishlist')));
    }

    /**
     * Get price drops for current user
     */
    public function get_price_drops() {
        if (!wp_verify_nonce($_POST['nonce'], 'aww_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-wc-wishlist')));
        }

        $wishlist_id = isset($_POST['wishlist_id']) ? intval($_POST['wishlist_id']) : null;
        $items = AWW()->database->get_wishlist_items($wishlist_id);
        $price_drops = array();

        foreach ($items as $item) {
            if ($item->price_at_add) {
                $product = wc_get_product($item->product_id);
                if ($product) {
                    $current_price = $product->get_price();
                    if ($current_price && $current_price < $item->price_at_add) {
                        $price_drops[] = array(
                            'product_id' => $item->product_id,
                            'product_name' => $product->get_name(),
                            'old_price' => $item->price_at_add,
                            'new_price' => $current_price,
                            'discount' => round((($item->price_at_add - $current_price) / $item->price_at_add) * 100, 2),
                        );
                    }
                }
            }
        }

        wp_send_json_success(array('price_drops' => $price_drops));
    }

    /**
     * Add single item to cart from wishlist.
     */
    public function add_to_cart() {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to add to cart.', 'advanced-wc-wishlist' ) ) );
        }
        check_ajax_referer( 'aww_nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'advanced-wc-wishlist' ) ) );
        }

        try {
            $result = WC()->cart->add_to_cart( $product_id, 1 );
            if ( $result ) {
                wp_send_json_success( array(
                    'message' => __( 'Product added to cart successfully.', 'advanced-wc-wishlist' ),
                    'cart_url' => wc_get_cart_url(),
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Could not add product to cart.', 'advanced-wc-wishlist' ) ) );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }
} 