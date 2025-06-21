<?php
/**
 * Shortcodes Class for Advanced WooCommerce Wishlist
 *
 * @package Advanced_WC_Wishlist
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AWW_Shortcodes Class
 *
 * Handles all shortcodes for wishlist functionality
 *
 * @since 1.0.0
 */
class AWW_Shortcodes {

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
        add_shortcode( 'aww_wishlist', array( $this, 'wishlist_shortcode' ) );
        add_shortcode( 'aww_wishlist_count', array( $this, 'wishlist_count_shortcode' ) );
        add_shortcode( 'aww_wishlist_button', array( $this, 'wishlist_button_shortcode' ) );
        add_shortcode( 'aww_wishlist_products', array( $this, 'wishlist_products_shortcode' ) );
        add_shortcode( 'aww_popular_wishlisted', array( $this, 'popular_wishlisted_shortcode' ) );
        
        // Multiple wishlist shortcodes
        add_shortcode( 'aww_wishlist_manager', array( $this, 'wishlist_manager_shortcode' ) );
        add_shortcode( 'aww_wishlist_selector', array( $this, 'wishlist_selector_shortcode' ) );
        add_shortcode( 'aww_price_drops', array( $this, 'price_drops_shortcode' ) );
    }

    /**
     * Wishlist shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wishlist_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'wishlist_id' => null,
            'show_title' => 'yes',
            'show_empty_message' => 'yes',
            'show_add_to_cart' => 'yes',
            'show_remove' => 'yes',
            'show_share' => 'yes',
            'show_price' => 'yes',
            'show_stock' => 'yes',
            'show_date' => 'no',
            'limit' => 0,
            'columns' => 4,
            'template' => 'table', // table, grid, list
        ), $atts, 'aww_wishlist' );

        $wishlist_id = $atts['wishlist_id'] ? intval( $atts['wishlist_id'] ) : AWW()->core->get_current_wishlist_id();
        $items = AWW()->database->get_wishlist_items( $wishlist_id, $atts['limit'] );

        ob_start();

        if ( 'yes' === $atts['show_title'] ) {
            echo '<h2>' . __( 'My Wishlist', 'advanced-wc-wishlist' ) . '</h2>';
        }

        if ( empty( $items ) ) {
            if ( 'yes' === $atts['show_empty_message'] ) {
                echo '<div class="aww-empty-wishlist">';
                echo '<p>' . __( 'Your wishlist is empty.', 'advanced-wc-wishlist' ) . '</p>';
                echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button">' . __( 'Continue Shopping', 'advanced-wc-wishlist' ) . '</a>';
                echo '</div>';
            }
        } else {
            // Load the wishlist table template
            AWW()->core->load_template( 'wishlist-table.php', array(
                'items' => $items,
                'wishlist_id' => $wishlist_id,
                'show_price' => 'yes' === $atts['show_price'],
                'show_stock' => 'yes' === $atts['show_stock'],
                'show_add_to_cart' => 'yes' === $atts['show_add_to_cart'],
                'show_remove' => 'yes' === $atts['show_remove'],
            ) );
        }

        return ob_get_clean();
    }

    /**
     * Wishlist count shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wishlist_count_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'wishlist_id' => null,
            'show_text' => 'yes',
            'show_icon' => 'yes',
        ), $atts, 'aww_wishlist_count' );

        $wishlist_id = $atts['wishlist_id'] ? intval( $atts['wishlist_id'] ) : AWW()->core->get_current_wishlist_id();
        $count = AWW()->database->get_wishlist_count( $wishlist_id );
        $url = AWW()->core->get_wishlist_url( $wishlist_id );

        ob_start();
        ?>
        <a href="<?php echo esc_url( $url ); ?>" class="aww-wishlist-count-shortcode" data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>">
            <?php if ( 'yes' === $atts['show_icon'] ) : ?>
                <span class="aww-icon">♥</span>
            <?php endif; ?>
            <span class="aww-count"><?php echo esc_html( $count ); ?></span>
            <?php if ( 'yes' === $atts['show_text'] ) : ?>
                <span class="aww-text">
                    <?php echo esc_html( _n( 'item', 'items', $count, 'advanced-wc-wishlist' ) ); ?>
                </span>
            <?php endif; ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Wishlist button shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wishlist_button_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'product_id' => 0,
            'wishlist_id' => null,
            'style' => 'default',
            'size' => 'medium',
        ), $atts, 'aww_wishlist_button' );

        if ( ! $atts['product_id'] ) {
            global $product;
            if ( $product ) {
                $atts['product_id'] = $product->get_id();
            } else {
                return '';
            }
        }

        $wishlist_id = $atts['wishlist_id'] ? intval( $atts['wishlist_id'] ) : AWW()->core->get_current_wishlist_id();
        return AWW()->core->get_wishlist_button_html( $atts['product_id'], $wishlist_id );
    }

    /**
     * Wishlist products shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wishlist_products_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'wishlist_id' => null,
            'limit' => 12,
            'columns' => 4,
            'orderby' => 'date_added',
            'order' => 'DESC',
            'show_price' => 'yes',
            'show_rating' => 'yes',
            'show_add_to_cart' => 'yes',
        ), $atts, 'aww_wishlist_products' );

        $wishlist_id = $atts['wishlist_id'] ? intval( $atts['wishlist_id'] ) : AWW()->core->get_current_wishlist_id();
        $items = AWW()->database->get_wishlist_items( $wishlist_id, $atts['limit'] );

        if ( empty( $items ) ) {
            return '<p class="aww-empty-wishlist">' . __( 'No products found in wishlist.', 'advanced-wc-wishlist' ) . '</p>';
        }

        $product_ids = wp_list_pluck( $items, 'product_id' );
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $atts['limit'],
            'post__in' => $product_ids,
            'orderby' => 'post__in',
        );

        $products = new WP_Query( $args );

        ob_start();
        if ( $products->have_posts() ) {
            echo '<div class="aww-wishlist-products-grid columns-' . esc_attr( $atts['columns'] ) . '">';
            while ( $products->have_posts() ) {
                $products->the_post();
                global $product;
                wc_get_template_part( 'content', 'product' );
            }
            echo '</div>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Popular wishlisted products shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function popular_wishlisted_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 10,
            'columns' => 4,
            'show_count' => 'yes',
            'show_price' => 'yes',
            'show_rating' => 'yes',
            'show_add_to_cart' => 'yes',
        ), $atts, 'aww_popular_wishlisted' );

        $popular_products = AWW()->database->get_popular_wishlisted_products( $atts['limit'] );

        if ( empty( $popular_products ) ) {
            return '<p class="aww-no-popular-products">' . __( 'No popular wishlisted products found.', 'advanced-wc-wishlist' ) . '</p>';
        }

        $product_ids = wp_list_pluck( $popular_products, 'product_id' );
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $atts['limit'],
            'post__in' => $product_ids,
            'orderby' => 'post__in',
        );

        $products = new WP_Query( $args );

        ob_start();
        if ( $products->have_posts() ) {
            echo '<div class="aww-popular-wishlisted-grid columns-' . esc_attr( $atts['columns'] ) . '">';
            while ( $products->have_posts() ) {
                $products->the_post();
                global $product;
                $wishlist_count = AWW()->database->get_wishlist_count_by_product( $product->get_id() );
                ?>
                <div class="aww-popular-product">
                    <?php wc_get_template_part( 'content', 'product' ); ?>
                    <?php if ( 'yes' === $atts['show_count'] ) : ?>
                        <div class="aww-wishlist-count">
                            <?php echo esc_html( sprintf( _n( '%s person has this in their wishlist', '%s people have this in their wishlist', $wishlist_count, 'advanced-wc-wishlist' ), $wishlist_count ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
            echo '</div>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Wishlist manager shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wishlist_manager_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_create' => 'yes',
            'show_rename' => 'yes',
            'show_delete' => 'yes',
            'show_selector' => 'yes',
        ), $atts );

        $wishlists = AWW()->database->get_wishlists();
        $current_wishlist_id = AWW()->core->get_current_wishlist_id();

        ob_start();
        ?>
        <div class="aww-wishlist-manager">
            <?php if ( 'yes' === $atts['show_selector'] && count( $wishlists ) > 1 ) : ?>
                <div class="aww-wishlist-selector">
                    <label for="aww-wishlist-select"><?php _e( 'Select Wishlist:', 'advanced-wc-wishlist' ); ?></label>
                    <select id="aww-wishlist-select" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                        <?php foreach ( $wishlists as $wishlist ) : ?>
                            <option value="<?php echo esc_attr( $wishlist->id ); ?>" <?php selected( $wishlist->id, $current_wishlist_id ); ?>>
                                <?php echo esc_html( $wishlist->name ); ?> (<?php echo AWW()->database->get_wishlist_count( $wishlist->id ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ( 'yes' === $atts['show_create'] ) : ?>
                <div class="aww-create-wishlist">
                    <button type="button" class="aww-create-wishlist-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                        <?php _e( 'Create New Wishlist', 'advanced-wc-wishlist' ); ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ( 'yes' === $atts['show_rename'] && $current_wishlist_id ) : ?>
                <div class="aww-rename-wishlist">
                    <button type="button" class="aww-rename-wishlist-btn" data-wishlist-id="<?php echo esc_attr( $current_wishlist_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                        <?php _e( 'Rename Wishlist', 'advanced-wc-wishlist' ); ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ( 'yes' === $atts['show_delete'] && $current_wishlist_id && count( $wishlists ) > 1 ) : ?>
                <div class="aww-delete-wishlist">
                    <button type="button" class="aww-delete-wishlist-btn" data-wishlist-id="<?php echo esc_attr( $current_wishlist_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                        <?php _e( 'Delete Wishlist', 'advanced-wc-wishlist' ); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Wishlist selector shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wishlist_selector_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_count' => 'yes',
            'style' => 'dropdown',
        ), $atts );

        $wishlists = AWW()->database->get_wishlists();
        $current_wishlist_id = AWW()->core->get_current_wishlist_id();

        if ( empty( $wishlists ) ) {
            return '';
        }

        ob_start();
        if ( 'tabs' === $atts['style'] ) {
            ?>
            <div class="aww-wishlist-tabs">
                <?php foreach ( $wishlists as $wishlist ) : ?>
                    <a href="<?php echo esc_url( AWW()->core->get_wishlist_url( $wishlist->id ) ); ?>" 
                       class="aww-wishlist-tab <?php echo $wishlist->id === $current_wishlist_id ? 'active' : ''; ?>"
                       data-wishlist-id="<?php echo esc_attr( $wishlist->id ); ?>">
                        <?php echo esc_html( $wishlist->name ); ?>
                        <?php if ( 'yes' === $atts['show_count'] ) : ?>
                            <span class="aww-count">(<?php echo AWW()->database->get_wishlist_count( $wishlist->id ); ?>)</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php
        } else {
            ?>
            <div class="aww-wishlist-dropdown">
                <select id="aww-wishlist-selector" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                    <?php foreach ( $wishlists as $wishlist ) : ?>
                        <option value="<?php echo esc_attr( $wishlist->id ); ?>" <?php selected( $wishlist->id, $current_wishlist_id ); ?>>
                            <?php echo esc_html( $wishlist->name ); ?>
                            <?php if ( 'yes' === $atts['show_count'] ) : ?>
                                (<?php echo AWW()->database->get_wishlist_count( $wishlist->id ); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    /**
     * Price drops shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function price_drops_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'wishlist_id' => null,
            'limit' => 10,
            'show_discount' => 'yes',
            'show_old_price' => 'yes',
            'show_new_price' => 'yes',
        ), $atts );

        $wishlist_id = $atts['wishlist_id'] ? intval( $atts['wishlist_id'] ) : AWW()->core->get_current_wishlist_id();
        $items = AWW()->database->get_wishlist_items( $wishlist_id, $atts['limit'] );
        $price_drops = array();

        foreach ( $items as $item ) {
            if ( $item->price_at_add ) {
                $product = wc_get_product( $item->product_id );
                if ( $product ) {
                    $current_price = $product->get_price();
                    if ( $current_price && $current_price < $item->price_at_add ) {
                        $price_drops[] = array(
                            'product' => $product,
                            'old_price' => $item->price_at_add,
                            'new_price' => $current_price,
                            'discount' => round( ( ( $item->price_at_add - $current_price ) / $item->price_at_add ) * 100, 2 ),
                        );
                    }
                }
            }
        }

        if ( empty( $price_drops ) ) {
            return '<p class="aww-no-price-drops">' . __( 'No price drops found in your wishlist.', 'advanced-wc-wishlist' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="aww-price-drops">
            <h3><?php _e( 'Price Drops in Your Wishlist', 'advanced-wc-wishlist' ); ?></h3>
            <div class="aww-price-drops-list">
                <?php foreach ( $price_drops as $drop ) : ?>
                    <div class="aww-price-drop-item">
                        <div class="aww-product-info">
                            <a href="<?php echo esc_url( $drop['product']->get_permalink() ); ?>">
                                <?php echo $drop['product']->get_image( 'thumbnail' ); ?>
                                <h4><?php echo esc_html( $drop['product']->get_name() ); ?></h4>
                            </a>
                        </div>
                        <div class="aww-price-info">
                            <?php if ( 'yes' === $atts['show_old_price'] ) : ?>
                                <span class="aww-old-price"><?php echo wc_price( $drop['old_price'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( 'yes' === $atts['show_new_price'] ) : ?>
                                <span class="aww-new-price"><?php echo wc_price( $drop['new_price'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( 'yes' === $atts['show_discount'] ) : ?>
                                <span class="aww-discount">-<?php echo esc_html( $drop['discount'] ); ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get shortcode documentation
     *
     * @return array
     */
    public static function get_shortcode_documentation() {
        return array(
            'aww_wishlist' => array(
                'description' => __( 'Display the full wishlist page', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'show_title' => __( 'Show wishlist title (yes/no)', 'advanced-wc-wishlist' ),
                    'show_empty_message' => __( 'Show message when wishlist is empty (yes/no)', 'advanced-wc-wishlist' ),
                    'show_add_to_cart' => __( 'Show add to cart buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'show_remove' => __( 'Show remove buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'show_share' => __( 'Show social sharing buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'show_price' => __( 'Show product prices (yes/no)', 'advanced-wc-wishlist' ),
                    'show_stock' => __( 'Show stock status (yes/no)', 'advanced-wc-wishlist' ),
                    'show_date' => __( 'Show date added (yes/no)', 'advanced-wc-wishlist' ),
                    'limit' => __( 'Limit number of items (0 for all)', 'advanced-wc-wishlist' ),
                    'columns' => __( 'Number of columns for grid layout', 'advanced-wc-wishlist' ),
                    'template' => __( 'Template type (table/grid/list)', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_wishlist_count' => array(
                'description' => __( 'Display wishlist item count', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'show_icon' => __( 'Show heart icon (yes/no)', 'advanced-wc-wishlist' ),
                    'show_text' => __( 'Show item text (yes/no)', 'advanced-wc-wishlist' ),
                    'link_to_wishlist' => __( 'Link to wishlist page (yes/no)', 'advanced-wc-wishlist' ),
                    'class' => __( 'Additional CSS classes', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_wishlist_button' => array(
                'description' => __( 'Display wishlist button for a specific product', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'product_id' => __( 'Product ID (optional, uses current product if not specified)', 'advanced-wc-wishlist' ),
                    'show_icon' => __( 'Show heart icon (yes/no)', 'advanced-wc-wishlist' ),
                    'show_text' => __( 'Show button text (yes/no)', 'advanced-wc-wishlist' ),
                    'class' => __( 'Additional CSS classes', 'advanced-wc-wishlist' ),
                    'style' => __( 'Inline CSS styles', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_wishlist_products' => array(
                'description' => __( 'Display wishlist products in a grid', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'limit' => __( 'Number of products to display', 'advanced-wc-wishlist' ),
                    'columns' => __( 'Number of columns', 'advanced-wc-wishlist' ),
                    'orderby' => __( 'Order by (date_added, title, price, etc.)', 'advanced-wc-wishlist' ),
                    'order' => __( 'Order (ASC/DESC)', 'advanced-wc-wishlist' ),
                    'show_price' => __( 'Show product prices (yes/no)', 'advanced-wc-wishlist' ),
                    'show_rating' => __( 'Show product ratings (yes/no)', 'advanced-wc-wishlist' ),
                    'show_add_to_cart' => __( 'Show add to cart buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'show_wishlist_button' => __( 'Show wishlist buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'template' => __( 'Template type (grid/list)', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_popular_wishlisted' => array(
                'description' => __( 'Display most popular wishlisted products', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'limit' => __( 'Number of products to display', 'advanced-wc-wishlist' ),
                    'columns' => __( 'Number of columns', 'advanced-wc-wishlist' ),
                    'show_count' => __( 'Show wishlist count (yes/no)', 'advanced-wc-wishlist' ),
                    'show_price' => __( 'Show product prices (yes/no)', 'advanced-wc-wishlist' ),
                    'show_rating' => __( 'Show product ratings (yes/no)', 'advanced-wc-wishlist' ),
                    'show_add_to_cart' => __( 'Show add to cart buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'show_wishlist_button' => __( 'Show wishlist buttons (yes/no)', 'advanced-wc-wishlist' ),
                    'min_count' => __( 'Minimum wishlist count to display', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_wishlist_manager' => array(
                'description' => __( 'Display wishlist manager', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'show_create' => __( 'Show create wishlist button (yes/no)', 'advanced-wc-wishlist' ),
                    'show_rename' => __( 'Show rename wishlist button (yes/no)', 'advanced-wc-wishlist' ),
                    'show_delete' => __( 'Show delete wishlist button (yes/no)', 'advanced-wc-wishlist' ),
                    'show_selector' => __( 'Show wishlist selector (yes/no)', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_wishlist_selector' => array(
                'description' => __( 'Display wishlist selector', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'show_count' => __( 'Show wishlist count (yes/no)', 'advanced-wc-wishlist' ),
                    'style' => __( 'Selector style (dropdown/tabs)', 'advanced-wc-wishlist' ),
                ),
            ),
            'aww_price_drops' => array(
                'description' => __( 'Display price drops in wishlist', 'advanced-wc-wishlist' ),
                'attributes' => array(
                    'wishlist_id' => __( 'Wishlist ID (optional, uses current wishlist if not specified)', 'advanced-wc-wishlist' ),
                    'limit' => __( 'Number of price drops to display', 'advanced-wc-wishlist' ),
                    'show_discount' => __( 'Show discount percentage (yes/no)', 'advanced-wc-wishlist' ),
                    'show_old_price' => __( 'Show old price (yes/no)', 'advanced-wc-wishlist' ),
                    'show_new_price' => __( 'Show new price (yes/no)', 'advanced-wc-wishlist' ),
                ),
            ),
        );
    }
} 