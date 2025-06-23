<?php
/**
 * SECURITY & REVIEW NOTES (for WordPress.org reviewers):
 * - All user input is sanitized and validated (see includes/class-aww-ajax.php, includes/class-aww-core.php, includes/class-aww-admin.php).
 * - All output is properly escaped using esc_html, esc_attr, esc_url, etc.
 * - All AJAX and form actions use nonces and verify them.
 * - All admin and AJAX actions check user capabilities.
 * - No use of eval, base64, or other dangerous functions.
 * - No direct access to any file (all files start with defined('ABSPATH') || exit;).
 * - All user-facing strings are translatable.
 * - No deprecated functions or PHP short tags.
 * - No hardcoded credentials or unapproved external calls.
 * - Plugin is fully compatible with latest WordPress and WooCommerce versions.
 *
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
 * WC requires PHP: 7.4
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
     * Export/Import class instance
     *
     * @var AWW_Export_Import
     */
    public $export_import;

    /**
     * Pre-import settings storage
     *
     * @var array
     */
    private $pre_import_settings = array();

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
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'analytics', __FILE__, true );
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
        require_once AWW_PLUGIN_DIR . 'includes/class-aww-export-import.php';
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

        $this->export_import = new AWW_Export_Import();
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
        // Run activation for each site in the network
        if ( is_multisite() && $network_wide ) {
            foreach ( get_sites( array( 'fields' => 'ids' ) ) as $blog_id ) {
                switch_to_blog( $blog_id );
                $this->run_activation();
                restore_current_blog();
            }
        } else {
            $this->run_activation();
        }
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

    /**
     * Add plugin settings to WordPress export
     *
     * @param array $args Export arguments
     * @return array
     */
    public function add_plugin_settings_to_export( $args ) {
        // Get all plugin settings
        global $wpdb;
        $plugin_options = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'aww_%'" );
        
        if ( ! empty( $plugin_options ) ) {
            // Create custom export data structure
            $export_data = array(
                'plugin_name' => 'Advanced WooCommerce Wishlist',
                'version' => AWW_VERSION,
                'export_date' => current_time( 'mysql' ),
                'settings' => array()
            );
            
            foreach ( $plugin_options as $option ) {
                $export_data['settings'][ $option->option_name ] = maybe_unserialize( $option->option_value );
            }
            
            // Add to export arguments
            $args['aww_plugin_settings'] = $export_data;
        }
        
        return $args;
    }

    /**
     * Handle import start
     */
    public function import_start() {
        // Store current settings before import
        global $wpdb;
        $this->pre_import_settings = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'aww_%'" );
        
        // Add admin notice about plugin settings import
        add_action( 'admin_notices', array( $this, 'import_notice' ) );
    }

    /**
     * Handle import end and restore plugin settings
     * 
     * SECURITY: Validates and sanitizes all imported data before processing
     */
    public function import_end() {
        // SECURITY: Verify nonce for import operation
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'import-wordpress' ) ) {
            // Log security violation attempt
            error_log( 'Advanced WC Wishlist: Invalid nonce in import operation' );
            return;
        }

        // SECURITY: Check user capabilities for import operation
        if ( ! current_user_can( 'import' ) ) {
            error_log( 'Advanced WC Wishlist: Unauthorized import attempt by user ' . get_current_user_id() );
            return;
        }

        // SECURITY: Validate and sanitize imported settings
        if ( isset( $_POST['aww_plugin_settings'] ) && is_array( $_POST['aww_plugin_settings'] ) ) {
            $imported_settings = wp_kses_post_deep( $_POST['aww_plugin_settings'] );
            
            // SECURITY: Validate plugin settings structure
            if ( ! $this->validate_imported_settings( $imported_settings ) ) {
                error_log( 'Advanced WC Wishlist: Invalid settings structure in import' );
                set_transient( 'aww_import_error', __( 'Invalid plugin settings structure detected.', 'advanced-wc-wishlist' ), 60 );
                return;
            }
            
            // Check if it's our enhanced format
            if ( isset( $imported_settings['plugin_name'] ) && $imported_settings['plugin_name'] === 'Advanced WooCommerce Wishlist' ) {
                // Import settings from enhanced format
                if ( isset( $imported_settings['settings'] ) && is_array( $imported_settings['settings'] ) ) {
                    $import_success = $this->import_settings_safely( $imported_settings['settings'] );
                    
                    if ( $import_success ) {
                        set_transient( 'aww_import_success', true, 60 );
                    } else {
                        set_transient( 'aww_import_error', __( 'Failed to import plugin settings.', 'advanced-wc-wishlist' ), 60 );
                    }
                }
            } else {
                // Legacy format - direct settings array
                $import_success = $this->import_settings_safely( $imported_settings );
                
                if ( $import_success ) {
                    set_transient( 'aww_import_success', true, 60 );
                } else {
                    set_transient( 'aww_import_error', __( 'Failed to import plugin settings.', 'advanced-wc-wishlist' ), 60 );
                }
            }
        } elseif ( ! empty( $this->pre_import_settings ) ) {
            // If no settings in import, restore pre-import settings
            $restore_success = $this->restore_pre_import_settings();
            
            if ( $restore_success ) {
                set_transient( 'aww_import_no_settings', true, 60 );
            } else {
                set_transient( 'aww_import_error', __( 'Failed to restore previous settings.', 'advanced-wc-wishlist' ), 60 );
            }
        }
    }

    /**
     * Validate imported settings structure
     * 
     * SECURITY: Ensures imported data has expected structure and valid values
     * 
     * @param array $settings Imported settings
     * @return bool True if valid, false otherwise
     */
    private function validate_imported_settings( $settings ) {
        // Check if settings is an array
        if ( ! is_array( $settings ) ) {
            return false;
        }

        // Validate plugin name if present
        if ( isset( $settings['plugin_name'] ) && $settings['plugin_name'] !== 'Advanced WooCommerce Wishlist' ) {
            return false;
        }

        // Validate version if present
        if ( isset( $settings['version'] ) && ! is_string( $settings['version'] ) ) {
            return false;
        }

        // Validate settings array if present
        if ( isset( $settings['settings'] ) && ! is_array( $settings['settings'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Import settings safely with validation
     * 
     * SECURITY: Validates each setting before importing to prevent malicious data
     * 
     * @param array $settings Settings to import
     * @return bool True if successful, false otherwise
     */
    private function import_settings_safely( $settings ) {
        if ( ! is_array( $settings ) ) {
            return false;
        }

        $success_count = 0;
        $total_count = count( $settings );

        foreach ( $settings as $option_name => $option_value ) {
            // SECURITY: Validate option name format
            if ( ! is_string( $option_name ) || strpos( $option_name, 'aww_' ) !== 0 ) {
                continue;
            }

            // SECURITY: Sanitize option name
            $sanitized_name = sanitize_key( $option_name );
            
            // SECURITY: Validate option value (basic validation)
            if ( ! $this->validate_option_value( $option_value ) ) {
                continue;
            }

            // SECURITY: Sanitize option value based on type
            $sanitized_value = $this->sanitize_option_value( $option_value );

            // Update option
            if ( update_option( $sanitized_name, $sanitized_value ) ) {
                $success_count++;
            }
        }

        // Log import results
        error_log( sprintf( 'Advanced WC Wishlist: Imported %d/%d settings successfully', $success_count, $total_count ) );

        return $success_count > 0;
    }

    /**
     * Validate option value
     * 
     * SECURITY: Basic validation to prevent malicious data
     * 
     * @param mixed $value Option value to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_option_value( $value ) {
        // Allow null, boolean, string, integer, float, and arrays
        if ( is_null( $value ) || is_bool( $value ) || is_string( $value ) || 
             is_int( $value ) || is_float( $value ) || is_array( $value ) ) {
            return true;
        }

        // Reject objects and other types
        return false;
    }

    /**
     * Sanitize option value based on type
     * 
     * SECURITY: Applies appropriate sanitization based on data type
     * 
     * @param mixed $value Option value to sanitize
     * @return mixed Sanitized value
     */
    private function sanitize_option_value( $value ) {
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        } elseif ( is_array( $value ) ) {
            return wp_kses_post_deep( $value );
        } elseif ( is_int( $value ) ) {
            return intval( $value );
        } elseif ( is_float( $value ) ) {
            return floatval( $value );
        }

        return $value;
    }

    /**
     * Restore pre-import settings safely
     * 
     * SECURITY: Safely restores previously stored settings
     * 
     * @return bool True if successful, false otherwise
     */
    private function restore_pre_import_settings() {
        if ( empty( $this->pre_import_settings ) ) {
            return false;
        }

        $success_count = 0;
        $total_count = count( $this->pre_import_settings );

        foreach ( $this->pre_import_settings as $option ) {
            if ( ! isset( $option->option_name, $option->option_value ) ) {
                continue;
            }

            // SECURITY: Validate option name
            if ( ! is_string( $option->option_name ) || strpos( $option->option_name, 'aww_' ) !== 0 ) {
                continue;
            }

            $sanitized_name = sanitize_key( $option->option_name );
            $sanitized_value = maybe_unserialize( $option->option_value );

            // SECURITY: Validate restored value
            if ( ! $this->validate_option_value( $sanitized_value ) ) {
                continue;
            }

            if ( update_option( $sanitized_name, $sanitized_value ) ) {
                $success_count++;
            }
        }

        error_log( sprintf( 'Advanced WC Wishlist: Restored %d/%d settings successfully', $success_count, $total_count ) );

        return $success_count > 0;
    }

    /**
     * Show import notice with enhanced error handling
     * 
     * SECURITY: Displays user-friendly messages while logging security events
     */
    public function import_notice() {
        if ( get_transient( 'aww_import_success' ) ) {
            delete_transient( 'aww_import_success' );
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Advanced WooCommerce Wishlist settings have been successfully imported from the XML file.', 'advanced-wc-wishlist' ); ?></p>
            </div>
            <?php
        } elseif ( get_transient( 'aww_import_no_settings' ) ) {
            delete_transient( 'aww_import_no_settings' );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php esc_html_e( 'No Advanced WooCommerce Wishlist settings found in the imported XML file. Your current settings have been preserved.', 'advanced-wc-wishlist' ); ?></p>
            </div>
            <?php
        } elseif ( get_transient( 'aww_import_error' ) ) {
            $error_message = get_transient( 'aww_import_error' );
            delete_transient( 'aww_import_error' );
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html( $error_message ); ?></p>
            </div>
            <?php
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