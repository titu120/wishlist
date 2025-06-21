<?php
/**
 * Core Class for Advanced WooCommerce Wishlist
 *
 * @package Advanced_WC_Wishlist
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AWW_Core Class
 *
 * Handles core functionality, template loading, and WooCommerce integration
 *
 * @since 1.0.0
 */
class AWW_Core {

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
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Add wishlist query vars
        add_filter( 'query_vars', array( $this, 'add_wishlist_query_vars' ) );
        
        // Add wishlist button to product loops
        add_action( 'woocommerce_after_shop_loop_item', array( $this, 'add_wishlist_button_loop' ), 15 );
        
        // Add wishlist count to header
        add_action( 'wp_footer', array( $this, 'add_wishlist_count' ) );
        
        // Transfer guest wishlist on login
        add_action( 'wp_login', array( $this, 'transfer_guest_wishlist_on_login' ), 10, 2 );
        
        // Transfer guest wishlist on register
        add_action( 'user_register', array( $this, 'transfer_guest_wishlist_on_register' ) );
        
        // Add wishlist breadcrumb
        add_filter( 'woocommerce_get_breadcrumb', array( $this, 'add_wishlist_breadcrumb' ), 10, 2 );
        
        // Add wishlist meta box
        add_action( 'add_meta_boxes', array( $this, 'add_wishlist_meta_box' ) );
        
        // Output custom CSS
        add_action( 'wp_head', array( $this, 'output_custom_css' ) );
        
        // Display merge notice
        add_action( 'woocommerce_before_account_navigation', array( $this, 'display_merge_notice' ) );

        // Add custom script for button positioning
        add_action( 'wp_footer', array( $this, 'add_button_positioning_script' ) );
        
        // Add wishlist button to footer for JS positioning
        add_action( 'wp_footer', array( $this, 'add_wishlist_button_to_footer' ) );
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        $should_enqueue = false;

        // Always enqueue on WooCommerce pages, cart, checkout, account, and wishlist endpoint
        if (
            (function_exists('is_woocommerce') && is_woocommerce()) ||
            is_cart() ||
            is_checkout() ||
            is_account_page() ||
            (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('wishlist'))
        ) {
            $should_enqueue = true;
        }

        // Enqueue if shortcode is present on the current page
        if (is_singular() && has_shortcode(get_post()->post_content, 'aww_wishlist')) {
            $should_enqueue = true;
        }

        if (!$should_enqueue) {
            return;
        }

        wp_enqueue_style(
            'aww-frontend',
            AWW_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            AWW_VERSION
        );

        wp_enqueue_script(
            'aww-frontend',
            AWW_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            AWW_VERSION,
            true
        );
        

        wp_localize_script(
            'aww-frontend',
            'aww_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('aww_nonce'),
                'wishlist_url' => $this->get_wishlist_url(),
                'button_position' => Advanced_WC_Wishlist::get_option('button_position', 'after_add_to_cart'),
                'strings'  => array(
                    'added_to_wishlist'    => __('Added to wishlist!', 'advanced-wc-wishlist'),
                    'removed_from_wishlist'=> __('Removed from wishlist!', 'advanced-wc-wishlist'),
                    'error'                => __('An error occurred. Please try again.', 'advanced-wc-wishlist'),
                    'confirm_remove'       => __('Are you sure you want to remove this item from your wishlist?', 'advanced-wc-wishlist'),
                    'view_wishlist'        => __('View Wishlist', 'advanced-wc-wishlist'),
                    'button_text'          => Advanced_WC_Wishlist::get_option('button_text', __('Add to Wishlist', 'advanced-wc-wishlist')),
                    'button_text_added'    => Advanced_WC_Wishlist::get_option('button_text_added', __('Added to Wishlist', 'advanced-wc-wishlist')),
                ),
            )
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'woocommerce_page_aww-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'aww-admin',
            AWW_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AWW_VERSION
        );

        wp_enqueue_script(
            'aww-admin',
            AWW_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            AWW_VERSION,
            true
        );
    }

    /**
     * Add wishlist query vars
     *
     * @param array $vars Query vars
     * @return array
     */
    public function add_wishlist_query_vars( $vars ) {
        $vars[] = 'wishlist';
        return $vars;
    }

    /**
     * Add wishlist button to product loops
     */
    public function add_wishlist_button_loop() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $current_wishlist_id = $this->get_current_wishlist_id();
        $this->load_template( 'wishlist-button.php', array(
            'product' => $product,
            'loop' => true,
            'wishlist_id' => $current_wishlist_id,
            'loop_position' => Advanced_WC_Wishlist::get_option('loop_button_position', 'before_add_to_cart'),
        ) );
    }

    /**
     * Add wishlist count to footer
     */
    public function add_wishlist_count() {
        $current_wishlist_id = $this->get_current_wishlist_id();
        $count = AWW()->database->get_wishlist_count( $current_wishlist_id );
        ?>
        <div id="aww-wishlist-count" style="display: none;" data-count="<?php echo esc_attr( $count ); ?>" data-wishlist-id="<?php echo esc_attr( $current_wishlist_id ); ?>">
            <?php echo esc_html( $count ); ?>
        </div>
        <?php
    }

    /**
     * Transfer guest wishlist to user on login
     */
    public function transfer_guest_wishlist_on_login( $user_login, $user ) {
        // Check if merge guest wishlist is enabled
        if ( 'yes' !== Advanced_WC_Wishlist::get_option( 'merge_guest_on_login', 'yes' ) ) {
            return;
        }

        $session_id = $this->get_session_id();
        if ( ! $session_id ) {
            return;
        }

        // Get guest wishlist items
        $guest_items = $this->get_guest_wishlist_items( $session_id );
        if ( empty( $guest_items ) ) {
            return;
        }

        // Get user's default wishlist
        $user_wishlist_id = AWW()->database->get_default_wishlist_id( $user->ID );
        if ( ! $user_wishlist_id ) {
            // Create default wishlist if it doesn't exist
            $user_wishlist_id = AWW()->database->create_wishlist( __( 'My Wishlist', 'advanced-wc-wishlist' ), $user->ID );
        }

        $merged_count = 0;
        $skipped_count = 0;

        foreach ( $guest_items as $item ) {
            // Check if product is already in user's wishlist
            if ( ! AWW()->database->is_product_in_wishlist( $item->product_id, $user_wishlist_id ) ) {
                // Add to user's wishlist
                AWW()->database->add_to_wishlist( $item->product_id, $user_wishlist_id );
                $merged_count++;
            } else {
                $skipped_count++;
            }
        }

        // Remove guest wishlist items after successful merge
        if ( $merged_count > 0 ) {
            $this->remove_guest_wishlist_items( $session_id );
            
            // Add admin notice
            if ( $merged_count > 0 ) {
                $message = sprintf( 
                    _n( 
                        '%d item from your guest wishlist has been merged into your account.', 
                        '%d items from your guest wishlist have been merged into your account.', 
                        $merged_count, 
                        'advanced-wc-wishlist' 
                    ), 
                    $merged_count 
                );
                
                if ( $skipped_count > 0 ) {
                    $message .= ' ' . sprintf( 
                        _n( 
                            '%d duplicate item was skipped.', 
                            '%d duplicate items were skipped.', 
                            $skipped_count, 
                            'advanced-wc-wishlist' 
                        ), 
                        $skipped_count 
                    );
                }
                
                // Store notice in user meta to display on next page load
                update_user_meta( $user->ID, 'aww_merge_notice', $message );
            }
        }
    }

    /**
     * Transfer guest wishlist on register
     *
     * @param int $user_id User ID
     */
    public function transfer_guest_wishlist_on_register( $user_id ) {
        if ( ! session_id() ) {
            session_start();
        }

        $session_id = session_id();
        if ( $session_id ) {
            // Same logic as login transfer
            $guest_wishlists = AWW()->database->get_wishlists( null, $session_id );
            foreach ( $guest_wishlists as $guest_wishlist ) {
                $new_wishlist_id = AWW()->database->create_wishlist( $guest_wishlist->name, $user_id );
                $items = AWW()->database->get_wishlist_items( $guest_wishlist->id );
                foreach ( $items as $item ) {
                    AWW()->database->add_to_wishlist( $item->product_id, $new_wishlist_id );
                }
            }
            foreach ( $guest_wishlists as $guest_wishlist ) {
                AWW()->database->delete_wishlist( $guest_wishlist->id );
            }
        }
    }

    /**
     * Add wishlist breadcrumb
     *
     * @param array $crumbs Breadcrumbs
     * @param WC_Breadcrumb $breadcrumb Breadcrumb object
     * @return array
     */
    public function add_wishlist_breadcrumb( $crumbs, $breadcrumb ) {
        if ( is_wc_endpoint_url( 'wishlist' ) ) {
            $crumbs[] = array( __( 'Wishlist', 'advanced-wc-wishlist' ), '' );
        }

        return $crumbs;
    }

    /**
     * Add wishlist meta box
     */
    public function add_wishlist_meta_box() {
        add_meta_box(
            'aww-wishlist-meta-box',
            __( 'Wishlist Information', 'advanced-wc-wishlist' ),
            array( $this, 'wishlist_meta_box_content' ),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Wishlist meta box content
     *
     * @param WP_Post $post Post object
     */
    public function wishlist_meta_box_content( $post ) {
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
     * Load template
     *
     * @param string $template Template name
     * @param array  $args Template arguments
     */
    public function load_template( $template, $args = array() ) {
        if ( ! empty( $args ) ) {
            extract( $args );
        }

        $template_path = AWW_PLUGIN_DIR . 'templates/' . $template;

        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }

    /**
     * Get template path
     *
     * @param string $template Template name
     * @return string
     */
    public function get_template_path( $template ) {
        return AWW_PLUGIN_DIR . 'templates/' . $template;
    }

    /**
     * Get wishlist URL
     */
    public function get_wishlist_url( $wishlist_id = null ) {
        // Get the wishlist page ID from options
        $wishlist_page_id = get_option( 'aww_wishlist_page_id' );
        
        if ( $wishlist_page_id ) {
            $base_url = get_permalink( $wishlist_page_id );
        } else {
            // Fallback to home URL if page doesn't exist
            $base_url = home_url( '/wishlist/' );
        }
        
        if ( $wishlist_id ) {
            return add_query_arg( 'wishlist_id', $wishlist_id, $base_url );
        }
        return $base_url;
    }

    /**
     * Get wishlist button HTML
     */
    public function get_wishlist_button_html( $product_id, $wishlist_id = null, $loop = false ) {
        if ( ! $wishlist_id ) {
            $wishlist_id = $this->get_current_wishlist_id();
        }
        $is_in_wishlist = AWW()->database->is_product_in_wishlist( $product_id, $wishlist_id );
        $button_text = $is_in_wishlist ? 
            Advanced_WC_Wishlist::get_option( 'button_text_added', __( 'Added to Wishlist', 'advanced-wc-wishlist' ) ) :
            Advanced_WC_Wishlist::get_option( 'button_text', __( 'Add to Wishlist', 'advanced-wc-wishlist' ) );

        $button_class = 'aww-wishlist-btn';
        if ( $is_in_wishlist ) {
            $button_class .= ' added';
        }
        if ( $loop ) {
            $button_class .= ' loop';
        }

        $icon = file_get_contents( AWW_PLUGIN_DIR . 'assets/images/heart-icon.svg' );
        if ( ! $icon ) {
            $icon = '♥';
        } else {
            $icon_size = Advanced_WC_Wishlist::get_option('button_icon_size');
            if (!empty($icon_size)) {
                // Remove existing width/height attributes to avoid conflicts
                $icon = preg_replace('/(width|height)="[^"]*"/i', '', $icon);
                // Add the new width and height attributes to the <svg> tag
                $icon = preg_replace('/<svg/i', '<svg width="' . esc_attr($icon_size) . '" height="' . esc_attr($icon_size) . '"', $icon, 1);
            }
        }

        ob_start();
        ?>
        <button 
            class="<?php echo esc_attr( $button_class ); ?>" 
            data-product-id="<?php echo esc_attr( $product_id ); ?>"
            data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>"
            data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>"
            type="button"
        >
            <span class="aww-icon"><?php echo $icon; ?></span>
            <span class="aww-text"><?php echo esc_html( $button_text ); ?></span>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Get wishlist count HTML
     */
    public function get_wishlist_count_html( $wishlist_id = null ) {
        if ( ! $wishlist_id ) {
            $wishlist_id = $this->get_current_wishlist_id();
        }
        $count = AWW()->database->get_wishlist_count( $wishlist_id );
        $url = $this->get_wishlist_url( $wishlist_id );

        ob_start();
        ?>
        <a href="<?php echo esc_url( $url ); ?>" class="aww-wishlist-count" data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>">
            <span class="aww-icon">♥</span>
            <span class="aww-count"><?php echo esc_html( $count ); ?></span>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if guest wishlist is enabled
     *
     * @return bool
     */
    public function is_guest_wishlist_enabled() {
        return 'yes' === Advanced_WC_Wishlist::get_option( 'enable_guest_wishlist', 'yes' );
    }

    /**
     * Check if social sharing is enabled
     *
     * @return bool
     */
    public function is_social_sharing_enabled() {
        return 'yes' === Advanced_WC_Wishlist::get_option( 'enable_social_sharing', 'yes' );
    }

    /**
     * Get button position
     *
     * @return string
     */
    public function get_button_position() {
        return Advanced_WC_Wishlist::get_option( 'button_position', 'after_add_to_cart' );
    }

    /**
     * Get button color
     *
     * @return string
     */
    public function get_button_color() {
        return Advanced_WC_Wishlist::get_option( 'button_color', '#e74c3c' );
    }

    /**
     * Get button hover color
     *
     * @return string
     */
    public function get_button_hover_color() {
        return Advanced_WC_Wishlist::get_option( 'button_color_hover', '#c0392b' );
    }

    /**
     * Get current wishlist ID from request
     */
    public function get_current_wishlist_id() {
        $wishlist_id = isset( $_GET['wishlist_id'] ) ? intval( $_GET['wishlist_id'] ) : null;
        if ( ! $wishlist_id ) {
            $wishlist_id = AWW()->database->get_default_wishlist_id();
        }
        return $wishlist_id;
    }

    /**
     * Get current wishlist object
     */
    public function get_current_wishlist() {
        $wishlist_id = $this->get_current_wishlist_id();
        return AWW()->database->get_wishlist( $wishlist_id );
    }

    /**
     * Get all user wishlists
     */
    public function get_user_wishlists() {
        return AWW()->database->get_wishlists();
    }

    /**
     * Create new wishlist
     */
    public function create_wishlist( $name ) {
        return AWW()->database->create_wishlist( $name );
    }

    /**
     * Update wishlist name
     */
    public function update_wishlist( $wishlist_id, $name ) {
        return AWW()->database->update_wishlist( $wishlist_id, $name );
    }

    /**
     * Delete wishlist
     */
    public function delete_wishlist( $wishlist_id ) {
        return AWW()->database->delete_wishlist( $wishlist_id );
    }

    /**
     * Render floating wishlist icon/counter
     */
    public function render_floating_icon() {
        if ( 'yes' !== Advanced_WC_Wishlist::get_option( 'enable_floating_icon', 'no' ) ) {
            return;
        }

        $position = Advanced_WC_Wishlist::get_option( 'floating_icon_position', 'top_right' );
        $style = Advanced_WC_Wishlist::get_option( 'floating_icon_style', 'circle' );
        $custom_css = Advanced_WC_Wishlist::get_option( 'floating_icon_custom_css', '' );
        $wishlist_id = $this->get_current_wishlist_id();
        $count = AWW()->database->get_wishlist_count( $wishlist_id );
        $url = $this->get_wishlist_url( $wishlist_id );

        // Add custom CSS if provided
        if ( ! empty( $custom_css ) ) {
            echo '<style>' . esc_html( $custom_css ) . '</style>';
        }

        ob_start();
        ?>
        <div class="aww-floating-icon aww-floating-icon--<?php echo esc_attr( $position ); ?> aww-floating-icon--<?php echo esc_attr( $style ); ?>" 
             data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>"
             data-position="<?php echo esc_attr( $position ); ?>"
             data-style="<?php echo esc_attr( $style ); ?>">
            <a href="<?php echo esc_url( $url ); ?>" class="aww-floating-icon__link" title="<?php esc_attr_e( 'View Wishlist', 'advanced-wc-wishlist' ); ?>">
                <span class="aww-floating-icon__icon">♥</span>
                <span class="aww-floating-icon__count"><?php echo esc_html( $count ); ?></span>
            </a>
        </div>
        <?php
        echo ob_get_clean();
    }

    /**
     * Output custom CSS from settings
     */
    public function output_custom_css() {
        $custom_css = Advanced_WC_Wishlist::get_option( 'custom_css', '' );
        $button_custom_css = Advanced_WC_Wishlist::get_option( 'button_custom_css', '' );
        $floating_icon_custom_css = Advanced_WC_Wishlist::get_option( 'floating_icon_custom_css', '' );
        $button_font_size = Advanced_WC_Wishlist::get_option( 'button_font_size' );
        $button_icon_size = Advanced_WC_Wishlist::get_option( 'button_icon_size' );
        
        if ( ! empty( $custom_css ) || ! empty( $button_custom_css ) || ! empty( $floating_icon_custom_css ) || ! empty( $button_font_size ) || ! empty( $button_icon_size ) ) {
            echo '<style id="aww-custom-css">';
            
            if ( ! empty( $custom_css ) ) {
                echo esc_html( $custom_css );
            }
            
            if ( ! empty( $button_custom_css ) ) {
                echo esc_html( $button_custom_css );
            }
            
            if ( ! empty( $floating_icon_custom_css ) ) {
                echo esc_html( $floating_icon_custom_css );
            }

            if ( ! empty( $button_font_size ) ) {
                echo ".aww-wishlist-btn .aww-text { font-size: " . esc_attr($button_font_size) . "px; }";
            }

            if ( ! empty( $button_icon_size ) ) {
                $size = esc_attr($button_icon_size);
                echo ".aww-wishlist-btn .aww-icon { display: inline-flex; align-items: center; }";
                echo ".aww-wishlist-btn .aww-icon svg { width: {$size}px !important; height: {$size}px !important; }";
            }
            
            echo '</style>';
        }
    }

    /**
     * Render social sharing buttons
     *
     * @param int $wishlist_id Wishlist ID
     * @param string $product_name Product name for sharing
     * @param string $product_url Product URL for sharing
     */
    public function render_sharing_buttons( $wishlist_id = null, $product_name = '', $product_url = '' ) {
        if ( 'yes' !== Advanced_WC_Wishlist::get_option( 'enable_sharing', 'yes' ) ) {
            return;
        }

        if ( ! $wishlist_id ) {
            $wishlist_id = $this->get_current_wishlist_id();
        }

        $networks = Advanced_WC_Wishlist::get_option( 'sharing_networks', 'facebook,twitter,whatsapp,email' );
        $networks = array_map( 'trim', explode( ',', $networks ) );
        $message = Advanced_WC_Wishlist::get_option( 'sharing_message', 'Check out this product from {site_name}: {product_name}' );
        
        // Replace placeholders
        $message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
        $message = str_replace( '{product_name}', $product_name, $message );
        
        $wishlist_url = $this->get_wishlist_url( $wishlist_id );
        
        if ( empty( $product_url ) ) {
            $product_url = $wishlist_url;
        }

        ob_start();
        ?>
        <div class="aww-sharing-buttons">
            <h4><?php esc_html_e( 'Share this wishlist:', 'advanced-wc-wishlist' ); ?></h4>
            <div class="aww-share-buttons">
                <?php if ( in_array( 'facebook', $networks ) ) : ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $wishlist_url ); ?>" 
                       target="_blank" 
                       class="aww-share-btn aww-share-facebook" 
                       title="<?php esc_attr_e( 'Share on Facebook', 'advanced-wc-wishlist' ); ?>">
                        <span class="aww-share-icon">f</span>
                    </a>
                <?php endif; ?>
                
                <?php if ( in_array( 'twitter', $networks ) ) : ?>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode( $message ); ?>&url=<?php echo urlencode( $wishlist_url ); ?>" 
                       target="_blank" 
                       class="aww-share-btn aww-share-twitter" 
                       title="<?php esc_attr_e( 'Share on Twitter', 'advanced-wc-wishlist' ); ?>">
                        <span class="aww-share-icon">t</span>
                    </a>
                <?php endif; ?>
                
                <?php if ( in_array( 'whatsapp', $networks ) ) : ?>
                    <a href="https://wa.me/?text=<?php echo urlencode( $message . ' ' . $wishlist_url ); ?>" 
                       target="_blank" 
                       class="aww-share-btn aww-share-whatsapp" 
                       title="<?php esc_attr_e( 'Share on WhatsApp', 'advanced-wc-wishlist' ); ?>">
                        <span class="aww-share-icon">w</span>
                    </a>
                <?php endif; ?>
                
                <?php if ( in_array( 'email', $networks ) ) : ?>
                    <a href="mailto:?subject=<?php echo urlencode( $message ); ?>&body=<?php echo urlencode( $message . ' ' . $wishlist_url ); ?>" 
                       class="aww-share-btn aww-share-email" 
                       title="<?php esc_attr_e( 'Share via Email', 'advanced-wc-wishlist' ); ?>">
                        <span class="aww-share-icon">@</span>
                    </a>
                <?php endif; ?>
                
                <?php if ( in_array( 'pinterest', $networks ) ) : ?>
                    <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode( $wishlist_url ); ?>&description=<?php echo urlencode( $message ); ?>" 
                       target="_blank" 
                       class="aww-share-btn aww-share-pinterest" 
                       title="<?php esc_attr_e( 'Share on Pinterest', 'advanced-wc-wishlist' ); ?>">
                        <span class="aww-share-icon">p</span>
                    </a>
                <?php endif; ?>
                
                <?php if ( in_array( 'linkedin', $networks ) ) : ?>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( $wishlist_url ); ?>" 
                       target="_blank" 
                       class="aww-share-btn aww-share-linkedin" 
                       title="<?php esc_attr_e( 'Share on LinkedIn', 'advanced-wc-wishlist' ); ?>">
                        <span class="aww-share-icon">in</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get session ID for guest users
     */
    public function get_session_id() {
        if ( ! session_id() ) {
            session_start();
        }
        return session_id();
    }

    /**
     * Get guest wishlist items by session ID
     */
    public function get_guest_wishlist_items( $session_id ) {
        return AWW()->database->get_wishlist_items_by_session( $session_id );
    }

    /**
     * Remove guest wishlist items by session ID
     */
    public function remove_guest_wishlist_items( $session_id ) {
        return AWW()->database->delete_wishlist_items_by_session( $session_id );
    }

    /**
     * Display merge notice after login
     */
    public function display_merge_notice() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();
        $notice = get_user_meta( $user_id, 'aww_merge_notice', true );
        
        if ( $notice ) {
            echo '<div class="woocommerce-message aww-merge-notice">' . esc_html( $notice ) . '</div>';
            delete_user_meta( $user_id, 'aww_merge_notice' );
        }
    }

    /**
     * Add wishlist button to product pages
     */
    public function add_wishlist_button() {
        global $product;

        if ( ! $product ) {
            return;
        }

        echo '<div class="aww-wishlist-btn-container">';

        $current_wishlist_id = $this->get_current_wishlist_id();
        $this->load_template( 'wishlist-button.php', array(
            'product' => $product,
            'wishlist_id' => $current_wishlist_id,
        ) );

        echo '</div>';
    }

    /**
     * Add wishlist button as overlay on product image in loop
     */
    public function add_wishlist_button_loop_overlay() {
        global $product;
        if ( ! $product ) {
            return;
        }
        $current_wishlist_id = $this->get_current_wishlist_id();
        echo '<div class="aww-wishlist-overlay" style="position:absolute;top:10px;left:10px;right:auto;z-index:10;">';
        $this->load_template( 'wishlist-button.php', array(
            'product' => $product,
            'loop' => true,
            'wishlist_id' => $current_wishlist_id,
            'loop_position' => 'on_image',
        ) );
        echo '</div>';
    }

    /**
     * Add script to footer for robust button positioning.
     */
    public function add_button_positioning_script() {
        if ( ! is_product() ) {
            return;
        }

        $button_position = self::get_button_position();
        ?>
        <script type="text/javascript">
            (function($) {
                $(document).ready(function() {
                    function positionWishlistButton() {
                        var $container = $('.aww-wishlist-btn-container');
                        if (!$container.length) { return; }

                        var position = '<?php echo esc_js( $button_position ); ?>';
                        var $summary = $('div.summary.entry-summary');
                        if (!$summary.length) { return; }

                        switch (position) {
                            case 'before_add_to_cart':
                                $container.insertBefore($summary.find('.cart'));
                                break;
                            case 'after_add_to_cart':
                                $container.insertAfter($summary.find('.cart'));
                                break;
                            case 'after_title':
                                $container.insertAfter($summary.find('.product_title'));
                                break;
                            case 'after_price':
                                $container.insertAfter($summary.find('.price'));
                                break;
                            case 'after_meta':
                                $container.insertAfter($summary.find('.product_meta'));
                                break;
                            default:
                                $container.insertAfter($summary.find('.cart'));
                        }
                    }
                    // Run on document ready and again after a short delay for compatibility
                    positionWishlistButton();
                    setTimeout(positionWishlistButton, 500);
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Add the wishlist button to the footer on single product pages.
     * It will be moved into the correct position by JavaScript.
     */
    public function add_wishlist_button_to_footer() {
        if ( is_product() ) {
            // The button is output inside a hidden div, so it's not visible until moved.
            echo '<div style="display: none;">';
            $this->add_wishlist_button();
            echo '</div>';
        }
    }
} 