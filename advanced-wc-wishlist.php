<?php
/**
 * Plugin Name: Advanced WooCommerce Wishlist
 * Plugin URI: https://example.com/advanced-wc-wishlist
 * Description: Feature-rich wishlist plugin with AJAX functionality, guest wishlists, social sharing, and analytics for WooCommerce stores
 * Version: 1.0.0
 * Author: [Your Name]
 * Author URI: https://example.com
 * Text Domain: advanced-wc-wishlist
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'AWW_VERSION', '1.0.0' );
define( 'AWW_PLUGIN_FILE', __FILE__ );
define( 'AWW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AWW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AWW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AWW_TEXT_DOMAIN', 'advanced-wc-wishlist' );

/**
 * Main Advanced WooCommerce Wishlist Class
 *
 * @since 1.0.0
 */
final class Advanced_WC_Wishlist {

    /**
     * Plugin instance
     *
     * @var Advanced_WC_Wishlist
     */
    private static $instance = null;

    /**
     * Core class instance
     *
     * @var AWW_Core
     */
    public $core;

    /**
     * AJAX class instance
     *
     * @var AWW_Ajax
     */
    public $ajax;

    /**
     * Database class instance
     *
     * @var AWW_Database
     */
    public $database;

    /**
     * Shortcodes class instance
     *
     * @var AWW_Shortcodes
     */
    public $shortcodes;

    /**
     * Admin class instance
     *
     * @var AWW_Admin
     */
    public $admin;

    /**
     * Get plugin instance
     *
     * @return Advanced_WC_Wishlist
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Register shortcodes early, regardless of WooCommerce status
        add_action( 'init', array( $this, 'register_shortcodes' ), 5 );
        
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return;
        }

        // Load required files
        $this->includes();

        // Initialize classes
        $this->init_classes();

        // Hook into WooCommerce
        $this->hook_woocommerce();
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Advanced WooCommerce Wishlist requires WooCommerce to be installed and activated.', 'advanced-wc-wishlist' ); ?></p>
        </div>
        <?php
    }

    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Advanced WooCommerce Wishlist requires PHP 7.4 or higher.', 'advanced-wc-wishlist' ); ?></p>
        </div>
        <?php
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-database.php';
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-core.php';
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-ajax.php';
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-shortcodes.php';
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-admin.php';
    }

    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        $this->database = new AWW_Database();
        $this->core = new AWW_Core();
        $this->ajax = new AWW_Ajax();
        // Shortcodes are now registered separately in register_shortcodes()
        
        if ( is_admin() ) {
            $this->admin = new AWW_Admin();
        }
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Include shortcodes class if not already included
        if ( ! class_exists( 'AWW_Shortcodes' ) ) {
            require_once AWW_PLUGIN_DIR . 'includes/class-aww-shortcodes.php';
        }
        
        // Create shortcodes instance
        $this->shortcodes = new AWW_Shortcodes();
    }

    /**
     * Hook into WooCommerce
     */
    private function hook_woocommerce() {
        // Add wishlist button to product pages
        // The button is now added via JS to avoid theme conflicts
        
        // Add wishlist button to product loops
        $loop_button_position = self::get_option( 'loop_button_position', 'before_add_to_cart' );
        switch ( $loop_button_position ) {
            case 'before_add_to_cart':
                add_action( 'woocommerce_before_shop_loop_item_title', array( $this->core, 'add_wishlist_button_loop' ), 20 );
                break;
            case 'after_add_to_cart':
                add_action( 'woocommerce_after_shop_loop_item', array( $this->core, 'add_wishlist_button_loop' ), 20 );
                break;
            case 'on_image':
                add_action( 'woocommerce_before_shop_loop_item', array( $this->core, 'add_wishlist_button_loop_overlay' ), 5 );
                break;
            case 'shortcode':
            default:
                // Do not hook automatically
                break;
        }
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'advanced-wc-wishlist',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Include database class
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-database.php';
        
        // Create database object and tables
        $database = new AWW_Database();
        $database->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Auto-create Wishlist page if not exists
        $wishlist_page = get_page_by_path( 'wishlist' );
        if ( ! $wishlist_page ) {
            $page_id = wp_insert_post( array(
                'post_title'   => __( 'Wishlist', 'advanced-wc-wishlist' ),
                'post_name'    => 'wishlist',
                'post_content' => '[aww_wishlist]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ) );
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'aww_wishlist_page_id', $page_id );
            }
        } else {
            update_option( 'aww_wishlist_page_id', $wishlist_page->ID );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'aww_check_price_drops' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'button_position' => 'after_add_to_cart',
            'button_style' => 'default',
            'button_icon' => 'heart',
            'button_text' => __( 'Add to Wishlist', 'advanced-wc-wishlist' ),
            'button_text_added' => __( 'Added to Wishlist', 'advanced-wc-wishlist' ),
            'button_color' => '#e74c3c',
            'button_color_hover' => '#c0392b',
            'enable_guest_wishlist' => 'yes',
            'enable_social_sharing' => 'yes',
            'enable_multiple_wishlists' => 'yes',
            'max_wishlists_per_user' => 10,
            'enable_price_drop_notifications' => 'yes',
            'price_drop_threshold' => 5,
            'price_drop_notification_frequency' => 'daily',
            'enable_email_notifications' => 'yes',
            'enable_dashboard_notifications' => 'yes',
            'wishlist_expiry_days' => 30,
            'show_price' => 'yes',
            'show_stock' => 'yes',
            'show_date' => 'no',
            'loop_button_position' => 'on_image',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( 'aww_' . $key ) ) {
                update_option( 'aww_' . $key, $value );
            }
        }
    }

    /**
     * Get option
     *
     * @param string $key Option key
     * @param mixed  $default Default value
     * @return mixed
     */
    public static function get_option( $key, $default = false ) {
        return get_option( 'aww_' . $key, $default );
    }

    /**
     * Update option
     *
     * @param string $key Option key
     * @param mixed  $value Option value
     * @return bool
     */
    public static function update_option( $key, $value ) {
        return update_option( 'aww_' . $key, $value );
    }

    /**
     * Get wishlist count
     *
     * @param int $wishlist_id Wishlist ID
     * @return int
     */
    public static function get_wishlist_count( $wishlist_id = null ) {
        if ( ! function_exists( 'AWW' ) ) {
            return 0;
        }
        return AWW()->database->get_wishlist_count( $wishlist_id );
    }

    /**
     * Check if product is in wishlist
     *
     * @param int $product_id Product ID
     * @param int $wishlist_id Wishlist ID
     * @return bool
     */
    public static function is_product_in_wishlist( $product_id, $wishlist_id = null ) {
        if ( ! function_exists( 'AWW' ) ) {
            return false;
        }
        return AWW()->database->is_product_in_wishlist( $product_id, $wishlist_id );
    }
}

/**
 * Get plugin instance
 *
 * @return Advanced_WC_Wishlist
 */
function AWW() {
    return Advanced_WC_Wishlist::instance();
}

// Initialize plugin
AWW(); 