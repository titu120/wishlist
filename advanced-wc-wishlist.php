<?php
/**
 * Plugin Name: Advanced WooCommerce Wishlist
 * Plugin URI:  https://softivus.com/advanced-woocommerce-wishlist/
 * Description: Feature-rich wishlist plugin with AJAX functionality, guest wishlists, and social sharing for WooCommerce stores.
 * Version: 1.0.0
 * Author: Softivus
 * Author URI: https://profiles.wordpress.org/softivus/
 * Text Domain: advanced-wc-wishlist
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
        
        // Declare WooCommerce compatibility
        add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );
        
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        // Always ensure wishlist page exists in admin
        add_action( 'admin_init', array( $this, 'ensure_wishlist_page_exists' ) );
    }

    /**
     * Declare WooCommerce compatibility
     */
    public function declare_woocommerce_compatibility() {
        // Declare compatibility with WooCommerce features
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_editor', __FILE__, true );
    
        }
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

        // Check WooCommerce version compatibility
        if ( ! $this->is_woocommerce_version_compatible() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_version_notice' ) );
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
     * Check WooCommerce version compatibility
     *
     * @return bool
     */
    private function is_woocommerce_version_compatible() {
        // Check if WooCommerce is loaded
        if ( ! class_exists( 'WooCommerce' ) ) {
            return false;
        }
        
        // Check minimum WooCommerce version using a safer approach
        $wc_version = '0.0.0';
        
        // Try to get version from WC() object first
        if ( function_exists( 'WC' ) && is_object( WC() ) && property_exists( WC(), 'version' ) ) {
            $wc_version = WC()->version;
        } 
        // Fallback to constant if available
        elseif ( defined( 'WC_VERSION' ) ) {
            $wc_version = constant( 'WC_VERSION' );
        }
        
        if ( version_compare( $wc_version, '3.0', '<' ) ) {
            return false;
        }
        
        return true;
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
     * WooCommerce version compatibility notice
     */
    public function woocommerce_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Advanced WooCommerce Wishlist requires WooCommerce version 3.0 or higher.', 'advanced-wc-wishlist' ); ?></p>
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
     *
     * @param bool $network_wide Whether to activate for the entire network
     */
    public function activate( $network_wide = false ) {
        // Include the database class
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-database.php';
        // Create a local instance
        $database = new AWW_Database();
        // Create the tables
        $database->create_tables();
        // Force the default for all users
        update_option( 'aww_loop_button_position', 'on_image' );
        // Now run your page creation logic
        $this->create_wishlist_page();
        // (Any other activation logic you need)
    }

    private function run_activation() {
        // Include and initialize database class for activation
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-database.php';
        $database = new AWW_Database();
        
        // Create database table
        $database->create_table();

        // Set default options
        $this->set_default_options();

        if ( ! get_option( 'aww_wishlist_page' ) ) {
            // Check if page already exists using modern approach
            $page = get_page_by_path( 'wishlist' );
            
            if ( ! $page ) {
                // Try to find by title as fallback
                $pages = get_pages( array(
                    'title' => 'Wishlist',
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'numberposts' => 1
                ) );
                $page = ! empty( $pages ) ? $pages[0] : null;
            }

            if ( ! $page ) {
                // Create page
                $page_id = wp_insert_post( array(
                    'post_title'   => 'Wishlist',
                    'post_name'    => 'wishlist',
                    'post_content' => '[aww_wishlist]',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ) );

                if ( $page_id ) {
                    update_option( 'aww_wishlist_page', $page_id );
                }
            } else {
                update_option( 'aww_wishlist_page', $page->ID );
            }
        }
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
            'button_text' => __( 'Add to Wishlist', 'advanced-wc-wishlist' ),
            'button_text_added' => __( 'Added to Wishlist', 'advanced-wc-wishlist' ),
            'button_text_color' => '#000000',
            'button_icon_color' => '#000000',
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
            'loop_button_position' => 'on_image', // Ensure this is the default
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

    /**
     * Ensure the wishlist page exists.
     */
    public function ensure_wishlist_page_exists() {
        $wishlist_page_id = get_option( 'aww_wishlist_page' );

        if ( ! $wishlist_page_id || get_post_status( $wishlist_page_id ) !== 'publish' ) {
            $this->create_wishlist_page();
        }
    }

    /**
     * Create the wishlist page if it doesn't exist or is not published.
     */
    private function create_wishlist_page() {
        // Only log in debug mode
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
            error_log('AWW: create_wishlist_page() called');
        }
        $wishlist_page_id = get_option( 'aww_wishlist_page' );
        $page = $wishlist_page_id ? get_post( $wishlist_page_id ) : null;

        // If the page is missing, trashed, or not published, try to find or create it
        if ( ! $page || $page->post_status === 'trash' || $page->post_status !== 'publish' ) {
            // Only log in debug mode
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                error_log('AWW: Wishlist page missing or not published');
            }
            // Try to find a page with slug 'wishlist'
            $existing = get_page_by_path( 'wishlist' );
            if ( $existing ) {
                // Only log in debug mode
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                    error_log('AWW: Found existing page with slug wishlist, status: ' . $existing->post_status);
                }
                // If trashed, restore and publish
                if ( $existing->post_status === 'trash' ) {
                    wp_untrash_post( $existing->ID );
                    wp_publish_post( $existing->ID );
                    // Only log in debug mode
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                        error_log('AWW: Untrashed and published existing wishlist page');
                    }
                } elseif ( $existing->post_status !== 'publish' ) {
                    wp_publish_post( $existing->ID );
                    // Only log in debug mode
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                        error_log('AWW: Published existing wishlist page');
                    }
                }
                update_option( 'aww_wishlist_page', $existing->ID );
                flush_rewrite_rules();
                return;
            }
            // Create new page
            $page_id = wp_insert_post( array(
                'post_title'   => 'Wishlist',
                'post_name'    => 'wishlist',
                'post_content' => '[aww_wishlist]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ) );
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                // Only log in debug mode
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                    error_log('AWW: Created new wishlist page, ID: ' . $page_id);
                }
                update_option( 'aww_wishlist_page', $page_id );
                flush_rewrite_rules();
            } else {
                // Only log in debug mode
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                    error_log('AWW: Failed to create wishlist page: ' . ( is_wp_error($page_id) ? $page_id->get_error_message() : 'Unknown error' ));
                }
            }
        } else {
            // Only log in debug mode
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists('error_log') ) {
                error_log('AWW: Wishlist page already exists and is published, ID: ' . $wishlist_page_id);
            }
        }
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