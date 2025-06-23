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
        add_action( 'export_wp', array( $this, 'export_plugin_settings_xml' ), 10, 1 );
        
        // WordPress import hooks - Enhanced for XML parsing
        add_action( 'import_start', array( $this, 'import_start' ) );
        add_action( 'import_end', array( $this, 'import_end' ) );
        
        // Hook into WordPress import process to parse XML
        add_action( 'wp_import_posts', array( $this, 'parse_imported_xml' ), 10, 2 );
        
        // Custom export format
        add_filter( 'export_args', array( $this, 'add_export_args' ) );
        
        // Add export option to WordPress export screen
        add_action( 'export_filters', array( $this, 'add_export_option' ) );
        
        // Add import settings option to import screen
        add_action( 'import_start', array( $this, 'add_import_settings_option' ) );
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
     * SECURITY: Exports plugin settings with proper escaping and validation
     *
     * @param array $args Export arguments
     * @param string $type Export type (optional)
     */
    public function export_plugin_settings_xml( $args, $type = null ) {
        // SECURITY: Validate input
        if ( ! is_array( $args ) ) {
            return;
        }
        
        if ( isset( $args['aww_plugin_settings'] ) && ! empty( $args['aww_plugin_settings'] ) ) {
            $settings = $args['aww_plugin_settings'];
            
            // SECURITY: Validate settings structure
            if ( ! isset( $settings['plugin_name'] ) || ! isset( $settings['settings'] ) ) {
                return;
            }
            
            // Create XML structure with proper escaping
            echo "\n\t<aww_plugin_settings>\n";
            echo "\t\t<plugin_name>" . esc_html( $settings['plugin_name'] ) . "</plugin_name>\n";
            echo "\t\t<version>" . esc_html( $settings['version'] ) . "</version>\n";
            echo "\t\t<export_date>" . esc_html( $settings['export_date'] ) . "</export_date>\n";
            echo "\t\t<settings>\n";
            
            foreach ( $settings['settings'] as $option_name => $option_value ) {
                // SECURITY: Validate option name format
                if ( ! is_string( $option_name ) || strpos( $option_name, 'aww_' ) !== 0 ) {
                    continue;
                }
                
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
     * Parse imported XML for plugin settings
     * 
     * SECURITY: Safely parses XML content and extracts plugin settings
     *
     * @param array $posts Imported posts
     * @param object $importer WordPress importer object
     */
    public function parse_imported_xml( $posts, $importer ) {
        // SECURITY: Check if user wants to import plugin settings
        if ( ! isset( $_POST['import_aww_plugin_settings'] ) || $_POST['import_aww_plugin_settings'] !== 'on' ) {
            return;
        }
        
        // Get the XML file content from the importer
        if ( isset( $importer->file ) && file_exists( $importer->file ) ) {
            $xml_content = file_get_contents( $importer->file );
            
            if ( $xml_content !== false ) {
                // Parse XML for plugin settings
                $plugin_settings = $this->parse_xml_settings( $xml_content );
                
                if ( $plugin_settings !== false ) {
                    // Store settings for import_end to process
                    set_transient( 'aww_imported_settings', $plugin_settings, 300 );
                }
            }
        }
    }

    /**
     * Handle import end and restore plugin settings
     * 
     * SECURITY: Safely imports plugin settings with validation and sanitization
     */
    public function import_end() {
        // Check for imported settings from XML parsing
        $imported_settings = get_transient( 'aww_imported_settings' );
        
        if ( $imported_settings !== false ) {
            delete_transient( 'aww_imported_settings' );
            
            // SECURITY: Validate imported settings structure
            if ( isset( $imported_settings['plugin_name'] ) && 
                 $imported_settings['plugin_name'] === 'Advanced WooCommerce Wishlist' &&
                 isset( $imported_settings['settings'] ) && 
                 is_array( $imported_settings['settings'] ) ) {
                
                $imported_count = 0;
                
                foreach ( $imported_settings['settings'] as $option_name => $option_value ) {
                    // SECURITY: Validate option name format
                    if ( is_string( $option_name ) && strpos( $option_name, 'aww_' ) === 0 ) {
                        // SECURITY: Sanitize option value before saving
                        $sanitized_value = $this->sanitize_option_value( $option_value, $option_name );
                        update_option( $option_name, $sanitized_value );
                        $imported_count++;
                    }
                }
                
                if ( $imported_count > 0 ) {
                    set_transient( 'aww_import_success', $imported_count, 60 );
                } else {
                    set_transient( 'aww_import_no_valid_settings', true, 60 );
                }
            } else {
                set_transient( 'aww_import_invalid_format', true, 60 );
            }
        } elseif ( isset( $_POST['aww_plugin_settings'] ) && is_array( $_POST['aww_plugin_settings'] ) ) {
            // Legacy format support
            $imported_settings = $_POST['aww_plugin_settings'];
            
            // Check if it's our enhanced format
            if ( isset( $imported_settings['plugin_name'] ) && $imported_settings['plugin_name'] === 'Advanced WooCommerce Wishlist' ) {
                // Import settings from enhanced format
                if ( isset( $imported_settings['settings'] ) && is_array( $imported_settings['settings'] ) ) {
                    $imported_count = 0;
                    foreach ( $imported_settings['settings'] as $option_name => $option_value ) {
                        if ( strpos( $option_name, 'aww_' ) === 0 ) {
                            $sanitized_value = $this->sanitize_option_value( $option_value, $option_name );
                            update_option( $option_name, $sanitized_value );
                            $imported_count++;
                        }
                    }
                    
                    if ( $imported_count > 0 ) {
                        set_transient( 'aww_import_success', $imported_count, 60 );
                    }
                }
            } else {
                // Legacy format - direct settings array
                $imported_count = 0;
                foreach ( $imported_settings as $option_name => $option_value ) {
                    if ( strpos( $option_name, 'aww_' ) === 0 ) {
                        $sanitized_value = $this->sanitize_option_value( $option_value, $option_name );
                        update_option( $option_name, $sanitized_value );
                        $imported_count++;
                    }
                }
                
                if ( $imported_count > 0 ) {
                    set_transient( 'aww_import_success', $imported_count, 60 );
                }
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
     * Sanitize option value based on option name
     * 
     * SECURITY: Sanitizes different types of option values
     *
     * @param mixed $value Option value
     * @param string $option_name Option name
     * @return mixed Sanitized value
     */
    private function sanitize_option_value( $value, $option_name ) {
        // SECURITY: Sanitize based on option type
        if ( strpos( $option_name, 'aww_general_' ) === 0 ) {
            // General settings - sanitize text fields
            if ( is_string( $value ) ) {
                return sanitize_text_field( $value );
            }
        } elseif ( strpos( $option_name, 'aww_display_' ) === 0 ) {
            // Display settings - sanitize HTML
            if ( is_string( $value ) ) {
                return wp_kses_post( $value );
            }
        } elseif ( strpos( $option_name, 'aww_email_' ) === 0 ) {
            // Email settings - sanitize email content
            if ( is_string( $value ) ) {
                return wp_kses_post( $value );
            }
        } elseif ( strpos( $option_name, 'aww_analytics_' ) === 0 ) {
            // Analytics settings - validate boolean/array
            if ( is_bool( $value ) ) {
                return $value;
            } elseif ( is_array( $value ) ) {
                return array_map( 'sanitize_text_field', $value );
            }
        }
        
        // Default sanitization
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        } elseif ( is_array( $value ) ) {
            return array_map( array( $this, 'sanitize_option_value' ), $value );
        }
        
        return $value;
    }

    /**
     * Show import notice
     * 
     * SECURITY: Displays import results with proper escaping
     */
    public function import_notice() {
        if ( get_transient( 'aww_import_success' ) ) {
            $imported_count = get_transient( 'aww_import_success' );
            delete_transient( 'aww_import_success' );
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php 
                    if ( is_numeric( $imported_count ) ) {
                        printf( 
                            esc_html__( 'Advanced WooCommerce Wishlist settings have been successfully imported from the XML file. %d settings were imported.', 'advanced-wc-wishlist' ), 
                            intval( $imported_count ) 
                        );
                    } else {
                        esc_html_e( 'Advanced WooCommerce Wishlist settings have been successfully imported from the XML file.', 'advanced-wc-wishlist' );
                    }
                    ?>
                </p>
            </div>
            <?php
        } elseif ( get_transient( 'aww_import_no_settings' ) ) {
            delete_transient( 'aww_import_no_settings' );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php esc_html_e( 'No Advanced WooCommerce Wishlist settings found in the imported XML file. Your current settings have been preserved.', 'advanced-wc-wishlist' ); ?></p>
            </div>
            <?php
        } elseif ( get_transient( 'aww_import_no_valid_settings' ) ) {
            delete_transient( 'aww_import_no_valid_settings' );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php esc_html_e( 'No valid Advanced WooCommerce Wishlist settings found in the imported XML file. Your current settings have been preserved.', 'advanced-wc-wishlist' ); ?></p>
            </div>
            <?php
        } elseif ( get_transient( 'aww_import_invalid_format' ) ) {
            delete_transient( 'aww_import_invalid_format' );
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e( 'The imported XML file contains invalid Advanced WooCommerce Wishlist settings format. Your current settings have been preserved.', 'advanced-wc-wishlist' ); ?></p>
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

    /**
     * Add import settings option to import screen
     */
    public function add_import_settings_option() {
        ?>
        <p>
            <label>
                <input type="checkbox" name="import_aww_plugin_settings" />
                <?php esc_html_e( 'Import Advanced WooCommerce Wishlist settings', 'advanced-wc-wishlist' ); ?>
            </label>
        </p>
        <?php
    }
} 