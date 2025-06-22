<?php
/**
 * Admin Class for Advanced WooCommerce Wishlist
 *
 * @package Advanced_WC_Wishlist
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AWW_Admin Class
 *
 * Handles admin functionality, settings, and analytics
 *
 * @since 1.0.0
 */
class AWW_Admin {

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
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Product meta box
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('save_post', array($this, 'save_product_meta'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-product', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Plugin links
        add_filter('plugin_action_links_' . AWW_PLUGIN_BASENAME, array($this, 'add_plugin_links'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Price drop notifications
        add_action('admin_init', array($this, 'schedule_price_drop_check'));
        add_action('aww_check_price_drops', array($this, 'check_price_drops'));
        
        // Export functionality
        add_action('admin_post_aww_export_wishlist', array($this, 'handle_export_wishlist'));

        // Add admin AJAX actions
        add_action( 'wp_ajax_aww_get_analytics', array( $this, 'get_analytics' ) );
        add_action( 'wp_ajax_aww_export_data', array( $this, 'export_data' ) );
        add_action( 'wp_ajax_aww_clean_expired', array( $this, 'clean_expired' ) );

        // Add admin scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add product column
        add_filter( 'manage_product_posts_columns', array( $this, 'add_wishlist_column' ) );
        add_action( 'manage_product_posts_custom_column', array( $this, 'wishlist_column_content' ), 10, 2 );
        add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_wishlist_column_sortable' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Wishlist Settings', 'advanced-wc-wishlist'),
            __('Wishlist', 'advanced-wc-wishlist'),
            'manage_woocommerce',
            'aww-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'woocommerce',
            __('Wishlist Analytics', 'advanced-wc-wishlist'),
            __('Wishlist Analytics', 'advanced-wc-wishlist'),
            'manage_woocommerce',
            'aww-analytics',
            array($this, 'analytics_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('aww_settings', 'aww_settings', array($this, 'sanitize_settings'));

        // General Settings
        add_settings_section(
            'aww_general_settings',
            __('General Settings', 'advanced-wc-wishlist'),
            array($this, 'general_settings_section_callback'),
            'aww_settings'
        );

        add_settings_field(
            'enable_guest_wishlist',
            __('Enable Guest Wishlist', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_general_settings',
            array('field' => 'enable_guest_wishlist')
        );

        add_settings_field(
            'enable_social_sharing',
            __('Enable Social Sharing', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_general_settings',
            array('field' => 'enable_social_sharing')
        );

        // Multiple Wishlist Settings
        add_settings_section(
            'aww_multiple_wishlist_settings',
            __('Multiple Wishlist Settings', 'advanced-wc-wishlist'),
            array($this, 'multiple_wishlist_settings_section_callback'),
            'aww_settings'
        );

        add_settings_field(
            'enable_multiple_wishlists',
            __('Enable Multiple Wishlists', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_multiple_wishlist_settings',
            array('field' => 'enable_multiple_wishlists')
        );

        add_settings_field(
            'max_wishlists_per_user',
            __('Maximum Wishlists per User', 'advanced-wc-wishlist'),
            array($this, 'number_field_callback'),
            'aww_settings',
            'aww_multiple_wishlist_settings',
            array('field' => 'max_wishlists_per_user', 'min' => 1, 'max' => 50)
        );

        add_settings_field(
            'enable_guest_multiple_wishlists',
            __('Enable Multiple Wishlists for Guests', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_multiple_wishlist_settings',
            array('field' => 'enable_guest_multiple_wishlists')
        );

        // Price Drop Notification Settings
        add_settings_section(
            'aww_price_drop_settings',
            __('Price Drop Notification Settings', 'advanced-wc-wishlist'),
            array($this, 'price_drop_settings_section_callback'),
            'aww_settings'
        );

        add_settings_field(
            'enable_price_drop_notifications',
            __('Enable Price Drop Notifications', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_price_drop_settings',
            array('field' => 'enable_price_drop_notifications')
        );

        add_settings_field(
            'price_drop_threshold',
            __('Price Drop Threshold (%)', 'advanced-wc-wishlist'),
            array($this, 'number_field_callback'),
            'aww_settings',
            'aww_price_drop_settings',
            array('field' => 'price_drop_threshold', 'min' => 1, 'max' => 100)
        );

        add_settings_field(
            'price_drop_notification_frequency',
            __('Notification Frequency', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_price_drop_settings',
            array(
                'field' => 'price_drop_notification_frequency',
                'options' => array(
                    'daily' => __('Daily', 'advanced-wc-wishlist'),
                    'weekly' => __('Weekly', 'advanced-wc-wishlist'),
                    'monthly' => __('Monthly', 'advanced-wc-wishlist')
                )
            )
        );

        add_settings_field(
            'enable_email_notifications',
            __('Enable Email Notifications', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_price_drop_settings',
            array('field' => 'enable_email_notifications')
        );

        add_settings_field(
            'enable_dashboard_notifications',
            __('Enable Dashboard Notifications', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_price_drop_settings',
            array('field' => 'enable_dashboard_notifications')
        );

        // Button Settings
        add_settings_section(
            'aww_button_settings',
            __('Button Settings', 'advanced-wc-wishlist'),
            array($this, 'button_settings_section_callback'),
            'aww_settings'
        );

        add_settings_field(
            'button_position',
            __('Button Position', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_position', 'options' => array(
                'after_add_to_cart' => __('After "Add to Cart" button', 'advanced-wc-wishlist'),
                'before_add_to_cart' => __('Before "Add to Cart" button', 'advanced-wc-wishlist'),
                'after_title' => __('After product title', 'advanced-wc-wishlist'),
                'after_price' => __('After price', 'advanced-wc-wishlist'),
                'after_meta' => __('After product meta', 'advanced-wc-wishlist'),
                'custom' => __('Custom (use shortcode)', 'advanced-wc-wishlist'),
            ))
        );

        add_settings_field(
            'button_font_size',
            __('Button Font Size (px)', 'advanced-wc-wishlist'),
            array($this, 'number_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_font_size')
        );

        add_settings_field(
            'button_icon_size',
            __('Button Icon Size (px)', 'advanced-wc-wishlist'),
            array($this, 'number_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_icon_size')
        );

        add_settings_field(
            'button_text',
            __('Button Text', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_text')
        );

        add_settings_field(
            'button_text_added',
            __('Button Text (Added)', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_text_added')
        );

        add_settings_field(
            'button_text_color',
            __('Button Text Color', 'advanced-wc-wishlist'),
            array($this, 'color_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_text_color')
        );

        add_settings_field(
            'button_icon_color',
            __('Button Icon Color', 'advanced-wc-wishlist'),
            array($this, 'color_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_icon_color')
        );

        add_settings_field(
            'enable_hover_border',
            __('Enable Hover Border', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'enable_hover_border')
        );

        add_settings_field(
            'button_hover_border_color',
            __('Button Hover Border Color', 'advanced-wc-wishlist'),
            array($this, 'color_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_hover_border_color')
        );

        add_settings_field(
            'button_tooltip',
            __('Button Tooltip', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_tooltip')
        );

        add_settings_field(
            'button_custom_css',
            __('Custom Button CSS', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_custom_css')
        );

        add_settings_field(
            'button_icon',
            __('Button Icon', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_button_settings',
            array('field' => 'button_icon')
        );

        // Loop Settings
        add_settings_section(
            'aww_loop_settings',
            __('Loop Settings', 'advanced-wc-wishlist'),
            function() { echo '<p>' . __('Configure wishlist button position in product loops (shop, category, etc).', 'advanced-wc-wishlist') . '</p>'; },
            'aww_settings'
        );
        add_settings_field(
            'loop_button_position',
            __('Position of "Add to wishlist" in loop', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_loop_settings',
            array(
                'field' => 'loop_button_position',
                'options' => array(
                    'on_image' => __('On top of the image', 'advanced-wc-wishlist'),
                    'before_add_to_cart' => __('Before "Add to cart" button', 'advanced-wc-wishlist'),
                    'after_add_to_cart' => __('After "Add to cart" button', 'advanced-wc-wishlist'),
                    'shortcode' => __('Use shortcode', 'advanced-wc-wishlist'),
                )
            )
        );

        // Floating Icon/Counter
        add_settings_section(
            'aww_floating_icon_settings',
            __('Floating Wishlist Icon', 'advanced-wc-wishlist'),
            array($this, 'floating_icon_settings_section_callback'),
            'aww_settings'
        );
        add_settings_field(
            'enable_floating_icon',
            __('Enable Floating Icon', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_floating_icon_settings',
            array('field' => 'enable_floating_icon')
        );
        add_settings_field(
            'floating_icon_position',
            __('Floating Icon Position', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_floating_icon_settings',
            array('field' => 'floating_icon_position', 'options' => array(
                'top_right' => __('Top Right', 'advanced-wc-wishlist'),
                'top_left' => __('Top Left', 'advanced-wc-wishlist'),
                'bottom_right' => __('Bottom Right', 'advanced-wc-wishlist'),
                'bottom_left' => __('Bottom Left', 'advanced-wc-wishlist'),
                'header' => __('Header', 'advanced-wc-wishlist'),
            ))
        );
        add_settings_field(
            'floating_icon_style',
            __('Floating Icon Style', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_floating_icon_settings',
            array('field' => 'floating_icon_style', 'options' => array(
                'circle' => __('Circle', 'advanced-wc-wishlist'),
                'square' => __('Square', 'advanced-wc-wishlist'),
                'minimal' => __('Minimal', 'advanced-wc-wishlist'),
            ))
        );
        add_settings_field(
            'floating_icon_custom_css',
            __('Custom Floating Icon CSS', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_floating_icon_settings',
            array('field' => 'floating_icon_custom_css')
        );

        // Sharing Options
        add_settings_section(
            'aww_sharing_settings',
            __('Sharing Options', 'advanced-wc-wishlist'),
            array($this, 'sharing_settings_section_callback'),
            'aww_settings'
        );
        add_settings_field(
            'enable_sharing',
            __('Enable Sharing', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_sharing_settings',
            array('field' => 'enable_sharing')
        );
        add_settings_field(
            'sharing_networks',
            __('Sharing Networks', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_sharing_settings',
            array('field' => 'sharing_networks')
        );
        add_settings_field(
            'sharing_message',
            __('Custom Sharing Message', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_sharing_settings',
            array('field' => 'sharing_message')
        );

        // Guest/User Behavior
        add_settings_section(
            'aww_guest_user_settings',
            __('Guest/User Wishlist Behavior', 'advanced-wc-wishlist'),
            array($this, 'guest_user_settings_section_callback'),
            'aww_settings'
        );
        add_settings_field(
            'require_login',
            __('Require Login for Wishlist', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_guest_user_settings',
            array('field' => 'require_login')
        );
        add_settings_field(
            'merge_guest_on_login',
            __('Merge Guest Wishlist on Login', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_guest_user_settings',
            array('field' => 'merge_guest_on_login')
        );

        // Add to Cart Behavior
        add_settings_section(
            'aww_add_to_cart_settings',
            __('Add to Cart Behavior', 'advanced-wc-wishlist'),
            array($this, 'add_to_cart_settings_section_callback'),
            'aww_settings'
        );
        add_settings_field(
            'remove_after_add_to_cart',
            __('Remove from Wishlist after Add to Cart', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_add_to_cart_settings',
            array('field' => 'remove_after_add_to_cart')
        );
        add_settings_field(
            'redirect_to_cart',
            __('Redirect to Cart after Add to Cart', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_add_to_cart_settings',
            array('field' => 'redirect_to_cart')
        );

        // Wishlist Page & Shortcode
        add_settings_section(
            'aww_page_settings',
            __('Wishlist Page & Shortcode', 'advanced-wc-wishlist'),
            array($this, 'page_settings_section_callback'),
            'aww_settings'
        );
        add_settings_field(
            'wishlist_page',
            __('Wishlist Page', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_page_settings',
            array('field' => 'wishlist_page', 'options' => $this->get_pages_list())
        );
        add_settings_field(
            'wishlist_shortcode',
            __('Wishlist Shortcode', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_page_settings',
            array('field' => 'wishlist_shortcode')
        );
        add_settings_field(
            'wishlist_endpoint',
            __('Wishlist Endpoint', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_page_settings',
            array('field' => 'wishlist_endpoint')
        );

        // Display/UX
        add_settings_section(
            'aww_display_ux_settings',
            __('Display & UX', 'advanced-wc-wishlist'),
            array($this, 'display_ux_settings_section_callback'),
            'aww_settings'
        );
        add_settings_field(
            'enable_modal',
            __('Enable Modal Popups', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'enable_modal')
        );
        add_settings_field(
            'enable_tooltips',
            __('Enable Tooltips', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'enable_tooltips')
        );
        add_settings_field(
            'enable_ajax_feedback',
            __('Enable AJAX Feedback', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'enable_ajax_feedback')
        );
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'advanced-wc-wishlist'),
            array($this, 'text_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'custom_css')
        );
        add_settings_field(
            'enable_responsive',
            __('Enable Responsive Styles', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'enable_responsive')
        );
        add_settings_field(
            'enable_accessibility',
            __('Enable Accessibility Features', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'enable_accessibility')
        );
        add_settings_field(
            'enable_rtl',
            __('Enable RTL Support', 'advanced-wc-wishlist'),
            array($this, 'checkbox_field_callback'),
            'aww_settings',
            'aww_display_ux_settings',
            array('field' => 'enable_rtl')
        );

        // Loop Settings
        add_settings_section(
            'aww_loop_settings',
            __('Loop Settings', 'advanced-wc-wishlist'),
            function() { echo '<p>' . __('Configure wishlist button position in product loops (shop, category, etc).', 'advanced-wc-wishlist') . '</p>'; },
            'aww_settings'
        );
        add_settings_field(
            'loop_button_position',
            __('Position of "Add to wishlist" in loop', 'advanced-wc-wishlist'),
            array($this, 'select_field_callback'),
            'aww_settings',
            'aww_loop_settings',
            array(
                'field' => 'loop_button_position',
                'options' => array(
                    'on_image' => __('On top of the image', 'advanced-wc-wishlist'),
                    'before_add_to_cart' => __('Before "Add to cart" button', 'advanced-wc-wishlist'),
                    'after_add_to_cart' => __('After "Add to cart" button', 'advanced-wc-wishlist'),
                    'shortcode' => __('Use shortcode', 'advanced-wc-wishlist'),
                )
            )
        );
    }

    /**
     * Multiple wishlist settings section callback
     */
    public function multiple_wishlist_settings_section_callback() {
        echo '<p>' . __('Configure multiple wishlist functionality for users and guests.', 'advanced-wc-wishlist') . '</p>';
    }

    /**
     * Price drop settings section callback
     */
    public function price_drop_settings_section_callback() {
        echo '<p>' . __('Configure price drop notification settings for wishlist items.', 'advanced-wc-wishlist') . '</p>';
    }

    /**
     * Schedule price drop check
     */
    public function schedule_price_drop_check() {
        if (!wp_next_scheduled('aww_check_price_drops')) {
            $frequency = Advanced_WC_Wishlist::get_option('price_drop_notification_frequency', 'daily');
            $schedule = $frequency === 'weekly' ? 'weekly' : ($frequency === 'monthly' ? 'monthly' : 'daily');
            wp_schedule_event(time(), $schedule, 'aww_check_price_drops');
        }
    }

    /**
     * Check price drops
     */
    public function check_price_drops() {
        if (!Advanced_WC_Wishlist::get_option('enable_price_drop_notifications', 'yes')) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aww_wishlists';
        $threshold = Advanced_WC_Wishlist::get_option('price_drop_threshold', 5);

        // Get all wishlist items with price tracking
        $items = $wpdb->get_results("
            SELECT w.*, p.post_title as product_name, u.user_email 
            FROM {$table_name} w 
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
            LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID 
            WHERE w.price_at_add IS NOT NULL AND w.price_at_add > 0
        ");

        $notifications = array();

        foreach ($items as $item) {
            $product = wc_get_product($item->product_id);
            if (!$product) {
                continue;
            }

            $current_price = $product->get_price();
            if (!$current_price || $current_price >= $item->price_at_add) {
                continue;
            }

            $price_drop_percentage = (($item->price_at_add - $current_price) / $item->price_at_add) * 100;
            
            if ($price_drop_percentage >= $threshold) {
                $notifications[] = array(
                    'user_id' => $item->user_id,
                    'user_email' => $item->user_email,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'old_price' => $item->price_at_add,
                    'new_price' => $current_price,
                    'discount' => round($price_drop_percentage, 2)
                );
            }
        }

        // Send notifications
        $this->send_price_drop_notifications($notifications);
    }

    /**
     * Send price drop notifications
     */
    private function send_price_drop_notifications($notifications) {
        if (empty($notifications)) {
            return;
        }

        $enable_email = Advanced_WC_Wishlist::get_option('enable_email_notifications', 'yes');
        $enable_dashboard = Advanced_WC_Wishlist::get_option('enable_dashboard_notifications', 'yes');

        // Group notifications by user
        $user_notifications = array();
        foreach ($notifications as $notification) {
            if ($notification['user_id']) {
                $user_notifications[$notification['user_id']][] = $notification;
            }
        }

        foreach ($user_notifications as $user_id => $user_notifs) {
            if ($enable_email && !empty($user_notifs[0]['user_email'])) {
                $this->send_price_drop_email($user_notifs[0]['user_email'], $user_notifs);
            }

            if ($enable_dashboard) {
                $this->add_dashboard_notification($user_id, $user_notifs);
            }
        }
    }

    /**
     * Send price drop email
     */
    private function send_price_drop_email($email, $notifications) {
        $subject = sprintf(__('Price Drop Alert - %s items in your wishlist', 'advanced-wc-wishlist'), count($notifications));
        
        $message = '<h2>' . __('Price Drop Alert!', 'advanced-wc-wishlist') . '</h2>';
        $message .= '<p>' . __('The following items in your wishlist have dropped in price:', 'advanced-wc-wishlist') . '</p>';
        
        $message .= '<ul>';
        foreach ($notifications as $notification) {
            $product_url = get_permalink($notification['product_id']);
            $message .= '<li>';
            $message .= '<strong>' . esc_html($notification['product_name']) . '</strong><br>';
            $message .= '<del>$' . number_format($notification['old_price'], 2) . '</del> ';
            $message .= '<strong>$' . number_format($notification['new_price'], 2) . '</strong> ';
            $message .= '<span style="color: #e74c3c;">(-' . $notification['discount'] . '%)</span><br>';
            $message .= '<a href="' . esc_url($product_url) . '">' . __('View Product', 'advanced-wc-wishlist') . '</a>';
            $message .= '</li>';
        }
        $message .= '</ul>';
        
        $message .= '<p><a href="' . esc_url(wc_get_account_endpoint_url('wishlist')) . '">' . __('View Your Wishlist', 'advanced-wc-wishlist') . '</a></p>';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Add dashboard notification
     */
    private function add_dashboard_notification($user_id, $notifications) {
        $notification_data = array(
            'type' => 'price_drop',
            'notifications' => $notifications,
            'date' => current_time('mysql')
        );

        update_user_meta($user_id, 'aww_price_drop_notifications', $notification_data);
    }

    /**
     * Get analytics data for multiple wishlists
     */
    public function get_analytics_data() {
        global $wpdb;
        $analytics = array();

        // Basic analytics
        $analytics['total_items'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aww_wishlists");
        $analytics['total_wishlists'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aww_wishlist_lists");
        $analytics['unique_users'] = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}aww_wishlist_lists WHERE user_id IS NOT NULL");
        $analytics['guest_sessions'] = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}aww_wishlist_lists WHERE session_id IS NOT NULL");

        // Items added today
        $analytics['items_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aww_wishlists WHERE DATE(date_added) = %s",
            current_time('Y-m-d')
        ));

        // Items added this week
        $analytics['items_this_week'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aww_wishlists WHERE YEARWEEK(date_added) = YEARWEEK(%s)",
            current_time('Y-m-d')
        ));

        // Items added this month
        $analytics['items_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aww_wishlists WHERE YEAR(date_added) = YEAR(%s) AND MONTH(date_added) = MONTH(%s)",
            current_time('Y-m-d'),
            current_time('Y-m-d')
        ));

        // Multiple wishlist analytics
        $analytics['users_with_multiple_wishlists'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}aww_wishlist_lists 
            WHERE user_id IS NOT NULL 
            AND user_id IN (
                SELECT user_id 
                FROM {$wpdb->prefix}aww_wishlist_lists 
                WHERE user_id IS NOT NULL 
                GROUP BY user_id 
                HAVING COUNT(*) > 1
            )
        ");

        $analytics['average_wishlists_per_user'] = $wpdb->get_var("
            SELECT AVG(wishlist_count) 
            FROM (
                SELECT user_id, COUNT(*) as wishlist_count 
                FROM {$wpdb->prefix}aww_wishlist_lists 
                WHERE user_id IS NOT NULL 
                GROUP BY user_id
            ) as user_wishlists
        ");

        // Price drop analytics
        $analytics['items_with_price_drops'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}aww_wishlists w 
            JOIN {$wpdb->posts} p ON w.product_id = p.ID 
            WHERE w.price_at_add IS NOT NULL 
            AND w.price_at_add > 0
        ");

        $analytics['total_price_drops'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}aww_wishlists w 
            JOIN {$wpdb->posts} p ON w.product_id = p.ID 
            WHERE w.price_at_add IS NOT NULL 
            AND w.price_at_add > 0 
            AND w.price_at_add > p.post_content
        ");

        return $analytics;
    }

    /**
     * Export wishlist data with multiple wishlist support
     */
    public function handle_export_wishlist() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to export wishlist data.', 'advanced-wc-wishlist'));
        }

        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';
        $wishlist_id = isset($_GET['wishlist_id']) ? intval($_GET['wishlist_id']) : null;

        global $wpdb;
        
        if ($wishlist_id) {
            // Export specific wishlist
            $sql = $wpdb->prepare("
                SELECT w.*, p.post_title as product_name, u.user_login, u.user_email, l.name as wishlist_name
                FROM {$wpdb->prefix}aww_wishlists w 
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
                LEFT JOIN {$wpdb->prefix}aww_wishlist_lists l ON w.wishlist_id = l.id
                WHERE w.wishlist_id = %d
                ORDER BY w.date_added DESC
            ", $wishlist_id);
        } else {
            // Export all wishlists
            $sql = "
                SELECT w.*, p.post_title as product_name, u.user_login, u.user_email, l.name as wishlist_name
                FROM {$wpdb->prefix}aww_wishlists w 
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
                LEFT JOIN {$wpdb->prefix}aww_wishlist_lists l ON w.wishlist_id = l.id
                ORDER BY w.date_added DESC
            ";
        }

        $data = $wpdb->get_results($sql, ARRAY_A);

        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="wishlist-export-' . date('Y-m-d') . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            // CSV format
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="wishlist-export-' . date('Y-m-d') . '.csv"');

            if (!empty($data)) {
                $output = fopen('php://output', 'w');
                fputcsv($output, array_keys($data[0])); // Headers
                
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
                
                fclose($output);
            }
        }
        exit;
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Save settings
        if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['aww_settings_nonce'], 'aww_settings' ) ) {
            $this->save_settings();
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Advanced WooCommerce Wishlist Settings', 'advanced-wc-wishlist' ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General', 'advanced-wc-wishlist' ); ?></a>
                <a href="#button" class="nav-tab"><?php esc_html_e( 'Product Details Page', 'advanced-wc-wishlist' ); ?></a>
                <a href="#loop" class="nav-tab"><?php esc_html_e( 'Shop Page', 'advanced-wc-wishlist' ); ?></a>
                <a href="#floating" class="nav-tab"><?php esc_html_e( 'Floating Icon', 'advanced-wc-wishlist' ); ?></a>
                <a href="#sharing" class="nav-tab"><?php esc_html_e( 'Sharing', 'advanced-wc-wishlist' ); ?></a>
                <a href="#behavior" class="nav-tab"><?php esc_html_e( 'Behavior', 'advanced-wc-wishlist' ); ?></a>
                <a href="#page" class="nav-tab"><?php esc_html_e( 'Page & Shortcode', 'advanced-wc-wishlist' ); ?></a>
                <a href="#display" class="nav-tab"><?php esc_html_e( 'Display & UX', 'advanced-wc-wishlist' ); ?></a>
                <a href="#shortcodes" class="nav-tab"><?php esc_html_e( 'Shortcodes', 'advanced-wc-wishlist' ); ?></a>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field( 'aww_settings', 'aww_settings_nonce' ); ?>

                <!-- General Settings Tab -->
                <div id="general" class="tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Guest Wishlist', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_guest_wishlist" value="yes" <?php checked( $settings['enable_guest_wishlist'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable wishlist for guest users', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Social Sharing', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_social_sharing" value="yes" <?php checked( $settings['enable_social_sharing'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable social sharing buttons', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_wishlist_expiry_days"><?php esc_html_e( 'Guest Wishlist Expiry (Days)', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="number" name="aww_wishlist_expiry_days" id="aww_wishlist_expiry_days" value="<?php echo esc_attr( $settings['wishlist_expiry_days'] ); ?>" min="1" max="365" />
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Button Settings Tab -->
                <div id="button" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="aww_button_position"><?php esc_html_e( 'Button Position', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <select name="aww_button_position" id="aww_button_position">
                                    <option value="after_add_to_cart" <?php selected( $settings['button_position'], 'after_add_to_cart' ); ?>>
                                        <?php esc_html_e( 'After Add to Cart Button', 'advanced-wc-wishlist' ); ?>
                                    </option>
                                    <option value="before_add_to_cart" <?php selected( $settings['button_position'], 'before_add_to_cart' ); ?>>
                                        <?php esc_html_e( 'Before Add to Cart Button', 'advanced-wc-wishlist' ); ?>
                                    </option>
                                    <option value="after_title" <?php selected( $settings['button_position'], 'after_title' ); ?>>
                                        <?php esc_html_e( 'After Product Title', 'advanced-wc-wishlist' ); ?>
                                    </option>
                                    <option value="after_price" <?php selected( $settings['button_position'], 'after_price' ); ?>>
                                        <?php esc_html_e( 'After Product Price', 'advanced-wc-wishlist' ); ?>
                                    </option>
									<option value="after_meta" <?php selected( $settings['button_position'], 'after_meta' ); ?>>
                                        <?php esc_html_e( 'After Product Meta', 'advanced-wc-wishlist' ); ?>
                                    </option>
                                    <option value="custom" <?php selected( $settings['button_position'], 'custom' ); ?>><?php esc_html_e( 'Custom (use shortcode)', 'advanced-wc-wishlist' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_font_size"><?php esc_html_e( 'Button Font Size (px)', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="number" name="aww_button_font_size" id="aww_button_font_size" value="<?php echo esc_attr( $settings['button_font_size'] ); ?>" min="10" max="50" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_icon_size"><?php esc_html_e( 'Button Icon Size (px)', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="number" name="aww_button_icon_size" id="aww_button_icon_size" value="<?php echo esc_attr( $settings['button_icon_size'] ); ?>" min="16" max="100" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_text"><?php esc_html_e( 'Button Text', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_button_text" id="aww_button_text" value="<?php echo esc_attr( $settings['button_text'] ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_text_added"><?php esc_html_e( 'Button Text (Added)', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_button_text_added" id="aww_button_text_added" value="<?php echo esc_attr( $settings['button_text_added'] ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_text_color"><?php esc_html_e( 'Button Text Color', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="color" name="aww_button_text_color" id="aww_button_text_color" value="<?php echo esc_attr( $settings['button_text_color'] ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_icon_color"><?php esc_html_e( 'Button Icon Color', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="color" name="aww_button_icon_color" id="aww_button_icon_color" value="<?php echo esc_attr( $settings['button_icon_color'] ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_tooltip"><?php esc_html_e( 'Button Tooltip', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_button_tooltip" id="aww_button_tooltip" value="<?php echo esc_attr( $settings['button_tooltip'] ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_custom_css"><?php esc_html_e( 'Custom Button CSS', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <textarea name="aww_button_custom_css" id="aww_button_custom_css" rows="5" cols="50"><?php echo esc_textarea( $settings['button_custom_css'] ); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_button_icon"><?php esc_html_e( 'Button Icon', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_button_icon" id="aww_button_icon" value="<?php echo esc_attr( $settings['button_icon'] ); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Loop Settings Tab -->
                <div id="loop" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Position of "Add to wishlist" in loop', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <select name="aww_loop_button_position" id="aww_loop_button_position">
                                    <option value="on_image" <?php selected( $settings['loop_button_position'], 'on_image' ); ?>><?php esc_html_e( 'On top of the image', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="before_add_to_cart" <?php selected( $settings['loop_button_position'], 'before_add_to_cart' ); ?>><?php esc_html_e( 'Before "Add to cart" button', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="after_add_to_cart" <?php selected( $settings['loop_button_position'], 'after_add_to_cart' ); ?>><?php esc_html_e( 'After "Add to cart" button', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="shortcode" <?php selected( $settings['loop_button_position'], 'shortcode' ); ?>><?php esc_html_e( 'Use shortcode', 'advanced-wc-wishlist' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'Choose where to show the wishlist button in product loops (shop, category, etc).', 'advanced-wc-wishlist' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Floating Icon Tab -->
                <div id="floating" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Floating Icon', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_floating_icon" value="yes" <?php checked( $settings['enable_floating_icon'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Show floating wishlist icon/counter', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_floating_icon_position"><?php esc_html_e( 'Floating Icon Position', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <select name="aww_floating_icon_position" id="aww_floating_icon_position">
                                    <option value="top_right" <?php selected( $settings['floating_icon_position'], 'top_right' ); ?>><?php esc_html_e( 'Top Right', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="top_left" <?php selected( $settings['floating_icon_position'], 'top_left' ); ?>><?php esc_html_e( 'Top Left', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="bottom_right" <?php selected( $settings['floating_icon_position'], 'bottom_right' ); ?>><?php esc_html_e( 'Bottom Right', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="bottom_left" <?php selected( $settings['floating_icon_position'], 'bottom_left' ); ?>><?php esc_html_e( 'Bottom Left', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="header" <?php selected( $settings['floating_icon_position'], 'header' ); ?>><?php esc_html_e( 'Header', 'advanced-wc-wishlist' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_floating_icon_style"><?php esc_html_e( 'Floating Icon Style', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <select name="aww_floating_icon_style" id="aww_floating_icon_style">
                                    <option value="circle" <?php selected( $settings['floating_icon_style'], 'circle' ); ?>><?php esc_html_e( 'Circle', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="square" <?php selected( $settings['floating_icon_style'], 'square' ); ?>><?php esc_html_e( 'Square', 'advanced-wc-wishlist' ); ?></option>
                                    <option value="minimal" <?php selected( $settings['floating_icon_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'advanced-wc-wishlist' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_floating_icon_custom_css"><?php esc_html_e( 'Custom Floating Icon CSS', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <textarea name="aww_floating_icon_custom_css" id="aww_floating_icon_custom_css" rows="5" cols="50"><?php echo esc_textarea( $settings['floating_icon_custom_css'] ); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sharing Tab -->
                <div id="sharing" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Sharing', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_sharing" value="yes" <?php checked( $settings['enable_sharing'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable social sharing functionality', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_sharing_networks"><?php esc_html_e( 'Sharing Networks', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_sharing_networks" id="aww_sharing_networks" value="<?php echo esc_attr( $settings['sharing_networks'] ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Comma-separated list: facebook, twitter, whatsapp, email', 'advanced-wc-wishlist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_sharing_message"><?php esc_html_e( 'Custom Sharing Message', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_sharing_message" id="aww_sharing_message" value="<?php echo esc_attr( $settings['sharing_message'] ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Use {product_name} and {site_name} as placeholders', 'advanced-wc-wishlist' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Behavior Tab -->
                <div id="behavior" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Require Login', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_require_login" value="yes" <?php checked( $settings['require_login'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Require users to login before adding to wishlist', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Merge Guest Wishlist', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_merge_guest_on_login" value="yes" <?php checked( $settings['merge_guest_on_login'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Merge guest wishlist items when user logs in', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Remove After Add to Cart', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_remove_after_add_to_cart" value="yes" <?php checked( $settings['remove_after_add_to_cart'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Remove item from wishlist after adding to cart', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Redirect to Cart', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_redirect_to_cart" value="yes" <?php checked( $settings['redirect_to_cart'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Redirect to cart page after adding to cart from wishlist', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Page & Shortcode Tab -->
                <div id="page" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="aww_wishlist_page"><?php esc_html_e( 'Wishlist Page', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <select name="aww_wishlist_page" id="aww_wishlist_page">
                                    <?php foreach ( $this->get_pages_list() as $page_id => $page_title ) : ?>
                                        <option value="<?php echo esc_attr( $page_id ); ?>" <?php selected( $settings['wishlist_page'], $page_id ); ?>>
                                            <?php echo esc_html( $page_title ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_wishlist_shortcode"><?php esc_html_e( 'Wishlist Shortcode', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_wishlist_shortcode" id="aww_wishlist_shortcode" value="<?php echo esc_attr( $settings['wishlist_shortcode'] ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Default: [aww_wishlist]', 'advanced-wc-wishlist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_wishlist_endpoint"><?php esc_html_e( 'Wishlist Endpoint', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name="aww_wishlist_endpoint" id="aww_wishlist_endpoint" value="<?php echo esc_attr( $settings['wishlist_endpoint'] ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Default: wishlist', 'advanced-wc-wishlist' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Display & UX Tab -->
                <div id="display" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Modal Popups', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_modal" value="yes" <?php checked( $settings['enable_modal'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Show modal popups for wishlist actions', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Tooltips', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_tooltips" value="yes" <?php checked( $settings['enable_tooltips'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Show tooltips on wishlist elements', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable AJAX Feedback', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_ajax_feedback" value="yes" <?php checked( $settings['enable_ajax_feedback'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Show AJAX feedback messages', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Responsive Styles', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_responsive" value="yes" <?php checked( $settings['enable_responsive'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable responsive design for mobile devices', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Accessibility Features', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_accessibility" value="yes" <?php checked( $settings['enable_accessibility'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable accessibility features (ARIA labels, keyboard navigation)', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable RTL Support', 'advanced-wc-wishlist' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aww_enable_rtl" value="yes" <?php checked( $settings['enable_rtl'], 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable right-to-left language support', 'advanced-wc-wishlist' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="aww_custom_css"><?php esc_html_e( 'Custom CSS', 'advanced-wc-wishlist' ); ?></label>
                            </th>
                            <td>
                                <textarea name="aww_custom_css" id="aww_custom_css" rows="10" cols="50"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Add custom CSS to style wishlist elements', 'advanced-wc-wishlist' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Shortcodes Tab -->
                <div id="shortcodes" class="tab-content">
                    <h3><?php esc_html_e( 'Available Shortcodes', 'advanced-wc-wishlist' ); ?></h3>
                    <p><?php esc_html_e( 'Use these shortcodes to display wishlist functionality anywhere on your site:', 'advanced-wc-wishlist' ); ?></p>
                    
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Shortcode', 'advanced-wc-wishlist' ); ?></th>
                                <th><?php esc_html_e( 'Description', 'advanced-wc-wishlist' ); ?></th>
                                <th><?php esc_html_e( 'Attributes', 'advanced-wc-wishlist' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[aww_wishlist]</code></td>
                                <td><?php esc_html_e( 'Display the full wishlist page', 'advanced-wc-wishlist' ); ?></td>
                                <td><code>wishlist_id="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>[aww_wishlist_count]</code></td>
                                <td><?php esc_html_e( 'Display wishlist item count', 'advanced-wc-wishlist' ); ?></td>
                                <td><code>wishlist_id="1" show_text="yes" show_icon="yes"</code></td>
                            </tr>
                            <tr>
                                <td><code>[aww_wishlist_button]</code></td>
                                <td><?php esc_html_e( 'Display wishlist button for current product', 'advanced-wc-wishlist' ); ?></td>
                                <td><code>product_id="123" style="default" size="medium"</code></td>
                            </tr>
                            <tr>
                                <td><code>[aww_wishlist_products]</code></td>
                                <td><?php esc_html_e( 'Display wishlist products in a grid', 'advanced-wc-wishlist' ); ?></td>
                                <td><code>columns="3" show_price="yes" show_add_to_cart="yes"</code></td>
                            </tr>
                            <tr>
                                <td><code>[aww_popular_wishlisted]</code></td>
                                <td><?php esc_html_e( 'Display most popular wishlisted products', 'advanced-wc-wishlist' ); ?></td>
                                <td><code>limit="10" columns="4" show_count="yes"</code></td>
                            </tr>
                            <tr>
                                <td><code>[aww_wishlist_manager]</code></td>
                                <td><?php esc_html_e( 'Display wishlist management interface', 'advanced-wc-wishlist' ); ?></td>
                                <td><code>show_create="yes" show_rename="yes" show_delete="yes"</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .nav-tab-wrapper { margin-bottom: 20px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        <?php
    }

    /**
     * Analytics page
     */
    public function analytics_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $analytics = AWW()->database->get_analytics();
        $popular_products = AWW()->database->get_popular_wishlisted_products( 10 );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Wishlist Analytics', 'advanced-wc-wishlist' ); ?></h1>

            <div class="aww-analytics-grid">
                <div class="aww-analytics-card">
                    <h3><?php esc_html_e( 'Total Wishlist Items', 'advanced-wc-wishlist' ); ?></h3>
                    <div class="aww-number"><?php echo esc_html( $analytics['total_items'] ); ?></div>
                </div>

                <div class="aww-analytics-card">
                    <h3><?php esc_html_e( 'Unique Users', 'advanced-wc-wishlist' ); ?></h3>
                    <div class="aww-number"><?php echo esc_html( $analytics['unique_users'] ); ?></div>
                </div>

                <div class="aww-analytics-card">
                    <h3><?php esc_html_e( 'Guest Sessions', 'advanced-wc-wishlist' ); ?></h3>
                    <div class="aww-number"><?php echo esc_html( $analytics['guest_sessions'] ); ?></div>
                </div>

                <div class="aww-analytics-card">
                    <h3><?php esc_html_e( 'Added Today', 'advanced-wc-wishlist' ); ?></h3>
                    <div class="aww-number"><?php echo esc_html( $analytics['items_today'] ); ?></div>
                </div>

                <div class="aww-analytics-card">
                    <h3><?php esc_html_e( 'Added This Week', 'advanced-wc-wishlist' ); ?></h3>
                    <div class="aww-number"><?php echo esc_html( $analytics['items_this_week'] ); ?></div>
                </div>

                <div class="aww-analytics-card">
                    <h3><?php esc_html_e( 'Added This Month', 'advanced-wc-wishlist' ); ?></h3>
                    <div class="aww-number"><?php echo esc_html( $analytics['items_this_month'] ); ?></div>
                </div>
            </div>

            <div class="aww-actions">
                <button type="button" class="button button-primary" id="aww-export-csv">
                    <?php esc_html_e( 'Export CSV', 'advanced-wc-wishlist' ); ?>
                </button>
                <button type="button" class="button button-secondary" id="aww-clean-expired">
                    <?php esc_html_e( 'Clean Expired Items', 'advanced-wc-wishlist' ); ?>
                </button>
            </div>

            <h2><?php esc_html_e( 'Popular Wishlisted Products', 'advanced-wc-wishlist' ); ?></h2>
            <?php if ( ! empty( $popular_products ) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Product', 'advanced-wc-wishlist' ); ?></th>
                            <th><?php esc_html_e( 'Wishlist Count', 'advanced-wc-wishlist' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'advanced-wc-wishlist' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $popular_products as $product ) : ?>
                            <tr>
                                <td>
                                    <?php
                                    $wc_product = wc_get_product( $product->product_id );
                                    if ( $wc_product ) {
                                        echo '<a href="' . esc_url( get_edit_post_link( $product->product_id ) ) . '">';
                                        echo esc_html( $wc_product->get_name() );
                                        echo '</a>';
                                    } else {
                                        echo esc_html( $product->product_name );
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html( $product->wishlist_count ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( get_edit_post_link( $product->product_id ) ); ?>" class="button button-small">
                                        <?php esc_html_e( 'Edit', 'advanced-wc-wishlist' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No wishlist data available.', 'advanced-wc-wishlist' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        $settings = array(
            // General Settings
            'enable_guest_wishlist' => isset( $_POST['aww_enable_guest_wishlist'] ) ? 'yes' : 'no',
            'enable_social_sharing' => isset( $_POST['aww_enable_social_sharing'] ) ? 'yes' : 'no',
            'wishlist_expiry_days' => intval( $_POST['aww_wishlist_expiry_days'] ),
            
            // Button Settings
            'button_position' => sanitize_text_field( $_POST['aww_button_position'] ),
            'button_font_size' => absint( $_POST['aww_button_font_size'] ),
            'button_icon_size' => absint( $_POST['aww_button_icon_size'] ),
            'button_text' => sanitize_text_field( $_POST['aww_button_text'] ),
            'button_text_added' => sanitize_text_field( $_POST['aww_button_text_added'] ),
            'button_text_color' => sanitize_hex_color( $_POST['aww_button_text_color'] ),
            'button_icon_color' => sanitize_hex_color( $_POST['aww_button_icon_color'] ),
            'enable_hover_border' => isset( $_POST['aww_enable_hover_border'] ) ? 'yes' : 'no',
            'button_hover_border_color' => sanitize_hex_color( $_POST['aww_button_hover_border_color'] ),
            'button_tooltip' => sanitize_text_field( $_POST['aww_button_tooltip'] ),
            'button_custom_css' => sanitize_textarea_field( $_POST['aww_button_custom_css'] ),
            'button_icon' => sanitize_text_field( $_POST['aww_button_icon'] ),
            
            // Floating Icon Settings
            'enable_floating_icon' => isset( $_POST['aww_enable_floating_icon'] ) ? 'yes' : 'no',
            'floating_icon_position' => sanitize_text_field( $_POST['aww_floating_icon_position'] ),
            'floating_icon_style' => sanitize_text_field( $_POST['aww_floating_icon_style'] ),
            'floating_icon_custom_css' => sanitize_textarea_field( $_POST['aww_floating_icon_custom_css'] ),
            
            // Sharing Settings
            'enable_sharing' => isset( $_POST['aww_enable_sharing'] ) ? 'yes' : 'no',
            'sharing_networks' => sanitize_text_field( $_POST['aww_sharing_networks'] ),
            'sharing_message' => sanitize_text_field( $_POST['aww_sharing_message'] ),
            
            // Behavior Settings
            'require_login' => isset( $_POST['aww_require_login'] ) ? 'yes' : 'no',
            'merge_guest_on_login' => isset( $_POST['aww_merge_guest_on_login'] ) ? 'yes' : 'no',
            'remove_after_add_to_cart' => isset( $_POST['aww_remove_after_add_to_cart'] ) ? 'yes' : 'no',
            'redirect_to_cart' => isset( $_POST['aww_redirect_to_cart'] ) ? 'yes' : 'no',
            
            // Page & Shortcode Settings
            'wishlist_page' => sanitize_text_field( $_POST['aww_wishlist_page'] ),
            'wishlist_shortcode' => sanitize_text_field( $_POST['aww_wishlist_shortcode'] ),
            'wishlist_endpoint' => sanitize_text_field( $_POST['aww_wishlist_endpoint'] ),
            
            // Display & UX Settings
            'enable_modal' => isset( $_POST['aww_enable_modal'] ) ? 'yes' : 'no',
            'enable_tooltips' => isset( $_POST['aww_enable_tooltips'] ) ? 'yes' : 'no',
            'enable_ajax_feedback' => isset( $_POST['aww_enable_ajax_feedback'] ) ? 'yes' : 'no',
            'enable_responsive' => isset( $_POST['aww_enable_responsive'] ) ? 'yes' : 'no',
            'enable_accessibility' => isset( $_POST['aww_enable_accessibility'] ) ? 'yes' : 'no',
            'enable_rtl' => isset( $_POST['aww_enable_rtl'] ) ? 'yes' : 'no',
            'custom_css' => sanitize_textarea_field( $_POST['aww_custom_css'] ),

            // Loop Settings
            'loop_button_position' => isset( $_POST['aww_loop_button_position'] ) ? sanitize_text_field( $_POST['aww_loop_button_position'] ) : 'before_add_to_cart',
        );

        foreach ( $settings as $key => $value ) {
            update_option( 'aww_' . $key, $value );
        }

        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'advanced-wc-wishlist' ) . '</p></div>';
        });
    }

    /**
     * Get settings
     *
     * @return array
     */
    private function get_settings() {
        return array(
            // General Settings
            'enable_guest_wishlist' => get_option( 'aww_enable_guest_wishlist', 'yes' ),
            'enable_social_sharing' => get_option( 'aww_enable_social_sharing', 'yes' ),
            'wishlist_expiry_days' => get_option( 'aww_wishlist_expiry_days', 30 ),
            
            // Button Settings
            'button_position' => get_option( 'aww_button_position', 'after_add_to_cart' ),
            'button_font_size' => get_option( 'aww_button_font_size', 14 ),
            'button_icon_size' => get_option( 'aww_button_icon_size', 16 ),
            'button_text' => get_option( 'aww_button_text', __( 'Add to Wishlist', 'advanced-wc-wishlist' ) ),
            'button_text_added' => get_option( 'aww_button_text_added', __( 'Added to Wishlist', 'advanced-wc-wishlist' ) ),
            'button_text_color' => get_option( 'aww_button_text_color', '#000000' ),
            'button_icon_color' => get_option( 'aww_button_icon_color', '#000000' ),
            'button_tooltip' => get_option( 'aww_button_tooltip', '' ),
            'button_custom_css' => get_option( 'aww_button_custom_css', '' ),
            'button_icon' => get_option( 'aww_button_icon', '' ),
            
            // Floating Icon Settings
            'enable_floating_icon' => get_option( 'aww_enable_floating_icon', 'no' ),
            'floating_icon_position' => get_option( 'aww_floating_icon_position', 'top_right' ),
            'floating_icon_style' => get_option( 'aww_floating_icon_style', 'circle' ),
            'floating_icon_custom_css' => get_option( 'aww_floating_icon_custom_css', '' ),
            
            // Sharing Settings
            'enable_sharing' => get_option( 'aww_enable_sharing', 'yes' ),
            'sharing_networks' => get_option( 'aww_sharing_networks', 'facebook,twitter,whatsapp,email' ),
            'sharing_message' => get_option( 'aww_sharing_message', 'Check out this product from {site_name}: {product_name}' ),
            
            // Behavior Settings
            'require_login' => get_option( 'aww_require_login', 'no' ),
            'merge_guest_on_login' => get_option( 'aww_merge_guest_on_login', 'yes' ),
            'remove_after_add_to_cart' => get_option( 'aww_remove_after_add_to_cart', 'no' ),
            'redirect_to_cart' => get_option( 'aww_redirect_to_cart', 'no' ),
            
            // Page & Shortcode Settings
            'wishlist_page' => get_option( 'aww_wishlist_page', '' ),
            'wishlist_shortcode' => get_option( 'aww_wishlist_shortcode', '[aww_wishlist]' ),
            'wishlist_endpoint' => get_option( 'aww_wishlist_endpoint', 'wishlist' ),
            
            // Display & UX Settings
            'enable_modal' => get_option( 'aww_enable_modal', 'yes' ),
            'enable_tooltips' => get_option( 'aww_enable_tooltips', 'yes' ),
            'enable_ajax_feedback' => get_option( 'aww_enable_ajax_feedback', 'yes' ),
            'enable_responsive' => get_option( 'aww_enable_responsive', 'yes' ),
            'enable_accessibility' => get_option( 'aww_enable_accessibility', 'yes' ),
            'enable_rtl' => get_option( 'aww_enable_rtl', 'no' ),
            'custom_css' => get_option( 'aww_custom_css', '' ),

            // Loop Settings
            'loop_button_position' => get_option( 'aww_loop_button_position', 'before_add_to_cart' ),
        );
    }

    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'aww-wishlist-meta-box',
            __( 'Wishlist Information', 'advanced-wc-wishlist' ),
            array( $this, 'product_meta_box_content' ),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Product meta box content
     *
     * @param WP_Post $post Post object
     */
    public function product_meta_box_content( $post ) {
        $product_id = $post->ID;
        $wishlist_count = AWW()->database->get_wishlist_count_by_product( $product_id );
        $popular_products = AWW()->database->get_popular_wishlisted_products( 5 );

        $is_popular = false;
        foreach ( $popular_products as $product ) {
            if ( $product->product_id == $product_id ) {
                $is_popular = true;
                break;
            }
        }
        ?>
        <p>
            <strong><?php esc_html_e( 'Wishlist Count:', 'advanced-wc-wishlist' ); ?></strong>
            <?php echo esc_html( $wishlist_count ); ?>
        </p>
        <?php if ( $is_popular ) : ?>
            <p>
                <span class="dashicons dashicons-star-filled" style="color: #ffd700;"></span>
                <?php esc_html_e( 'Popular in wishlists', 'advanced-wc-wishlist' ); ?>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Add bulk actions
     *
     * @param array $bulk_actions Bulk actions
     * @return array
     */
    public function add_bulk_actions( $bulk_actions ) {
        $bulk_actions['aww_export_wishlist_data'] = __( 'Export Wishlist Data', 'advanced-wc-wishlist' );
        return $bulk_actions;
    }

    /**
     * Handle bulk actions
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action name
     * @param array  $post_ids Post IDs
     * @return string
     */
    public function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
        if ( 'aww_export_wishlist_data' !== $doaction ) {
            return $redirect_to;
        }

        $data = AWW()->database->export_wishlist_data( 'csv' );
        
        // Force download
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="wishlist-data-' . date( 'Y-m-d' ) . '.csv"' );
        echo $data;
        exit;
    }

    /**
     * Add plugin links
     *
     * @param array $links Plugin links
     * @return array
     */
    public function add_plugin_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=aww-settings' ) . '">' . __( 'Settings', 'advanced-wc-wishlist' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'aww_dashboard_widget',
            __( 'Wishlist Overview', 'advanced-wc-wishlist' ),
            array( $this, 'dashboard_widget_content' )
        );
    }

    /**
     * Dashboard widget content
     */
    public function dashboard_widget_content() {
        $analytics = AWW()->database->get_analytics();
        ?>
        <div class="aww-dashboard-widget">
            <p>
                <strong><?php esc_html_e( 'Total Wishlist Items:', 'advanced-wc-wishlist' ); ?></strong>
                <?php echo esc_html( $analytics['total_items'] ); ?>
            </p>
            <p>
                <strong><?php esc_html_e( 'Added Today:', 'advanced-wc-wishlist' ); ?></strong>
                <?php echo esc_html( $analytics['items_today'] ); ?>
            </p>
            <p>
                <strong><?php esc_html_e( 'Unique Users:', 'advanced-wc-wishlist' ); ?></strong>
                <?php echo esc_html( $analytics['unique_users'] ); ?>
            </p>
            <p>
                <a href="<?php echo admin_url( 'admin.php?page=aww-analytics' ); ?>" class="button button-small">
                    <?php esc_html_e( 'View Full Analytics', 'advanced-wc-wishlist' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Add wishlist column
     *
     * @param array $columns Columns
     * @return array
     */
    public function add_wishlist_column( $columns ) {
        $columns['wishlist_count'] = __( 'Wishlist Count', 'advanced-wc-wishlist' );
        return $columns;
    }

    /**
     * Wishlist column content
     *
     * @param string $column Column name
     * @param int    $post_id Post ID
     */
    public function wishlist_column_content( $column, $post_id ) {
        if ( 'wishlist_count' === $column ) {
            $count = AWW()->database->get_wishlist_count_by_product( $post_id );
            echo esc_html( $count );
        }
    }

    /**
     * Make wishlist column sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function make_wishlist_column_sortable( $columns ) {
        $columns['wishlist_count'] = 'wishlist_count';
        return $columns;
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'woocommerce_page_aww-analytics' === $hook ) {
            wp_enqueue_script(
                'aww-admin-analytics',
                AWW_PLUGIN_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                AWW_VERSION,
                true
            );

            wp_localize_script(
                'aww-admin-analytics',
                'aww_admin',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'aww_admin_nonce' ),
                )
            );
        }
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e( 'Advanced WooCommerce Wishlist requires WooCommerce to be installed and activated.', 'advanced-wc-wishlist' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * General settings section callback
     */
    public function general_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure general wishlist settings.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Button settings section callback
     */
    public function button_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure wishlist button appearance and behavior.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Floating icon settings section callback
     */
    public function floating_icon_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure floating wishlist icon/counter that appears on your site.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Sharing settings section callback
     */
    public function sharing_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure social sharing options for wishlist items.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Guest/User behavior settings section callback
     */
    public function guest_user_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure how guest and logged-in users interact with wishlists.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Add to cart behavior settings section callback
     */
    public function add_to_cart_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure what happens when users add items to cart from their wishlist.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Page & shortcode settings section callback
     */
    public function page_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure wishlist page, shortcodes, and endpoints.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Display & UX settings section callback
     */
    public function display_ux_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure display options, user experience features, and custom styling.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Display settings section callback
     */
    public function display_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure how wishlist items are displayed.', 'advanced-wc-wishlist' ) . '</p>';
    }

    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback( $args ) {
        $field = $args['field'];
        $value = Advanced_WC_Wishlist::get_option( $field, 'no' );
        ?>
        <label>
            <input type="checkbox" name="aww_<?php echo esc_attr( $field ); ?>" value="yes" <?php checked( $value, 'yes' ); ?> />
            <?php esc_html_e( 'Enable this feature', 'advanced-wc-wishlist' ); ?>
        </label>
        <?php
    }

    /**
     * Text field callback
     */
    public function text_field_callback( $args ) {
        $field = $args['field'];
        $value = Advanced_WC_Wishlist::get_option( $field, '' );
        ?>
        <input type="text" name="aww_<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <?php
    }

    /**
     * Number field callback
     */
    public function number_field_callback( $args ) {
        $options = get_option( 'aww_settings' );
        $field = $args['field'];
        $value = isset( $options[ $field ] ) ? $options[ $field ] : '';
        $min = isset( $args['min'] ) ? $args['min'] : 0;
        $max = isset( $args['max'] ) ? $args['max'] : 100;
        $step = isset( $args['step'] ) ? $args['step'] : 1;
        ?>
        <input type="number" id="<?php echo esc_attr( $field ); ?>" name="aww_settings[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" />
        <?php
    }

    /**
     * Color field callback
     */
    public function color_field_callback( $args ) {
        $field = $args['field'];
        $value = Advanced_WC_Wishlist::get_option( $field, '#e74c3c' );
        ?>
        <input type="color" name="aww_<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }

    /**
     * Select field callback
     */
    public function select_field_callback( $args ) {
        $field = $args['field'];
        $value = Advanced_WC_Wishlist::get_option( $field, '' );
        $options = $args['options'];
        ?>
        <select name="aww_<?php echo esc_attr( $field ); ?>">
            <?php foreach ( $options as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        // Sanitize each field
        foreach ( $input as $key => $value ) {
            switch ( $key ) {
                case 'enable_guest_wishlist':
                case 'enable_social_sharing':
                case 'enable_multiple_wishlists':
                case 'enable_guest_multiple_wishlists':
                case 'enable_price_drop_notifications':
                case 'enable_email_notifications':
                case 'enable_dashboard_notifications':
                case 'show_price':
                case 'show_stock':
                case 'show_date':
                    $sanitized[ $key ] = ( $value === 'yes' ) ? 'yes' : 'no';
                    break;
                    
                case 'button_text':
                case 'button_text_added':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
                    
                case 'button_color':
                case 'button_color_hover':
                    $sanitized[ $key ] = sanitize_hex_color( $value );
                    break;
                    
                case 'max_wishlists_per_user':
                case 'price_drop_threshold':
                case 'wishlist_expiry_days':
                    $sanitized[ $key ] = intval( $value );
                    break;
                    
                case 'price_drop_notification_frequency':
                    $sanitized[ $key ] = in_array( $value, array( 'daily', 'weekly', 'monthly' ) ) ? $value : 'daily';
                    break;
                    
                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }
        
        return $sanitized;
    }

    /**
     * Save product meta
     *
     * @param int $post_id Post ID
     */
    public function save_product_meta( $post_id ) {
        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check if user has permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check if nonce is valid
        if ( ! isset( $_POST['aww_wishlist_nonce'] ) || ! wp_verify_nonce( $_POST['aww_wishlist_nonce'], 'aww_wishlist_meta' ) ) {
            return;
        }

        // Save wishlist meta data if needed
        if ( isset( $_POST['aww_wishlist_featured'] ) ) {
            update_post_meta( $post_id, '_aww_wishlist_featured', sanitize_text_field( $_POST['aww_wishlist_featured'] ) );
        }
    }

    /**
     * Get analytics via AJAX
     */
    public function get_analytics() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to view analytics.', 'advanced-wc-wishlist' ) ) );
        }

        $analytics = AWW()->database->get_analytics();
        wp_send_json_success( $analytics );
    }

    /**
     * Export data via AJAX
     */
    public function export_data() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to export data.', 'advanced-wc-wishlist' ) ) );
        }

        $format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'csv';
        $data = AWW()->database->export_wishlist_data( $format );

        if ( $format === 'csv' ) {
            $filename = 'wishlist-export-' . date( 'Y-m-d' ) . '.csv';
            wp_send_json_success( array(
                'data' => $data,
                'filename' => $filename,
                'message' => __( 'Data exported successfully.', 'advanced-wc-wishlist' )
            ) );
        } else {
            wp_send_json_success( array(
                'data' => $data,
                'message' => __( 'Data exported successfully.', 'advanced-wc-wishlist' )
            ) );
        }
    }

    /**
     * Clean expired items via AJAX
     */
    public function clean_expired() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'aww_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'advanced-wc-wishlist' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to clean expired items.', 'advanced-wc-wishlist' ) ) );
        }

        $days = Advanced_WC_Wishlist::get_option( 'wishlist_expiry_days', 30 );
        $deleted_count = AWW()->database->clean_expired_items( $days );

        wp_send_json_success( array(
            'message' => sprintf( __( 'Successfully cleaned %d expired wishlist items.', 'advanced-wc-wishlist' ), $deleted_count ),
            'deleted_count' => $deleted_count
        ) );
    }

    /**
     * Get list of all published pages for dropdowns
     */
    private function get_pages_list() {
        $pages = get_pages(array('post_status' => 'publish'));
        $list = array('' => __('Select a page', 'advanced-wc-wishlist'));
        foreach ($pages as $page) {
            $list[$page->ID] = $page->post_title;
        }
        return $list;
    }
} 