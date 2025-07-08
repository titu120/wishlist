<?php
/**
 * Export/Import functionality for Advanced WooCommerce Wishlist
 *
 * @package Advanced_WC_Wishlist
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AWW_Export_Import Class
 *
 * Handles export and import of plugin settings via WordPress XML export/import
 *
 * @since 1.0.0
 */
class AWW_Export_Import {

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
        // WordPress export hooks
        add_filter( 'export_wp', array( $this, 'add_plugin_settings_to_export' ) );
        add_action( 'export_wp', array( $this, 'export_plugin_settings_xml' ), 10, 2 );
        
        // WordPress import hooks
        add_action( 'import_start', array( $this, 'import_start' ) );
        add_action( 'import_end', array( $this, 'import_end' ) );
        
        // Custom export format
        add_filter( 'export_args', array( $this, 'add_export_args' ) );
        
        // Add export option to WordPress export screen
        add_action( 'export_filters', array( $this, 'add_export_option' ) );
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
     * Export plugin settings as XML
     *
     * @param array $args Export arguments
     * @param string $type Export type
     */
    public function export_plugin_settings_xml( $args, $type ) {
        if ( isset( $args['aww_plugin_settings'] ) && ! empty( $args['aww_plugin_settings'] ) ) {
            $settings = $args['aww_plugin_settings'];
            
            // Create XML structure
            echo "\n\t<aww_plugin_settings>\n";
            echo "\t\t<plugin_name>" . esc_html( $settings['plugin_name'] ) . "</plugin_name>\n";
            echo "\t\t<version>" . esc_html( $settings['version'] ) . "</version>\n";
            echo "\t\t<export_date>" . esc_html( $settings['export_date'] ) . "</export_date>\n";
            echo "\t\t<settings>\n";
            
            foreach ( $settings['settings'] as $option_name => $option_value ) {
                echo "\t\t\t<option>\n";
                echo "\t\t\t\t<name>" . esc_html( $option_name ) . "</name>\n";
                echo "\t\t\t\t<value><![CDATA[" . esc_html( maybe_serialize( $option_value ) ) . "]]></value>\n";
                echo "\t\t\t</option>\n";
            }
            
            echo "\t\t</settings>\n";
            echo "\t</aww_plugin_settings>\n";
        }
    }

    /**
     * Add export arguments
     *
     * @param array $args Export arguments
     * @return array
     */
    public function add_export_args( $args ) {
        if ( isset( $_GET['content'] ) && $_GET['content'] === 'aww_plugin_settings' ) {
            $args['aww_plugin_settings'] = true;
        }
        return $args;
    }

    /**
     * Add export option to WordPress export screen
     */
    public function add_export_option() {
        ?>
        <p>
            <label>
                <input type="radio" name="content" value="aww_plugin_settings" />
                <?php esc_html_e( 'Advanced WooCommerce Wishlist Settings', 'advanced-wc-wishlist' ); ?>
            </label>
        </p>
        <?php
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
     */
    public function import_end() {
        // Check if imported XML contains our plugin settings
        if ( isset( $_POST['aww_plugin_settings'] ) && is_array( $_POST['aww_plugin_settings'] ) ) {
            $imported_settings = $_POST['aww_plugin_settings'];
            
            // Check if it's our enhanced format
            if ( isset( $imported_settings['plugin_name'] ) && $imported_settings['plugin_name'] === 'Advanced WooCommerce Wishlist' ) {
                // Import settings from enhanced format
                if ( isset( $imported_settings['settings'] ) && is_array( $imported_settings['settings'] ) ) {
                    foreach ( $imported_settings['settings'] as $option_name => $option_value ) {
                        if ( strpos( $option_name, 'aww_' ) === 0 ) {
                            update_option( $option_name, $option_value );
                        }
                    }
                    
                    // Store import success message
                    set_transient( 'aww_import_success', true, 60 );
                }
            } else {
                // Legacy format - direct settings array
                foreach ( $imported_settings as $option_name => $option_value ) {
                    if ( strpos( $option_name, 'aww_' ) === 0 ) {
                        update_option( $option_name, $option_value );
                    }
                }
                
                // Store import success message
                set_transient( 'aww_import_success', true, 60 );
            }
        } elseif ( ! empty( $this->pre_import_settings ) ) {
            // If no settings in import, restore pre-import settings
            foreach ( $this->pre_import_settings as $option ) {
                update_option( $option->option_name, maybe_unserialize( $option->option_value ) );
            }
            
            // Store no settings found message
            set_transient( 'aww_import_no_settings', true, 60 );
        }
    }

    /**
     * Show import notice
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
        }
    }

    /**
     * Parse XML and extract plugin settings
     *
     * @param string $xml XML content
     * @return array|false Plugin settings or false if not found
     */
    public function parse_xml_settings( $xml ) {
        // Simple XML parsing for plugin settings
        if ( strpos( $xml, '<aww_plugin_settings>' ) !== false ) {
            $settings = array();
            
            // Extract settings from XML
            preg_match_all( '/<option>\s*<name>(.*?)<\/name>\s*<value><!\[CDATA\[(.*?)\]\]><\/value>\s*<\/option>/s', $xml, $matches );
            
            if ( ! empty( $matches[1] ) ) {
                foreach ( $matches[1] as $index => $option_name ) {
                    $option_value = maybe_unserialize( $matches[2][ $index ] );
                    $settings[ $option_name ] = $option_value;
                }
                
                return array(
                    'plugin_name' => 'Advanced WooCommerce Wishlist',
                    'version' => AWW_VERSION,
                    'settings' => $settings
                );
            }
        }
        
        return false;
    }
} 