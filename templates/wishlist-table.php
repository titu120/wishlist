<?php
/**
 * Wishlist Table Template
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get template variables
$items = isset( $items ) ? $items : array();
$wishlist_id = isset( $wishlist_id ) ? $wishlist_id : AWW()->core->get_current_wishlist_id();
$show_price = isset( $show_price ) ? $show_price : true;
$show_stock = isset( $show_stock ) ? $show_stock : true;
$show_add_to_cart = isset( $show_add_to_cart ) ? $show_add_to_cart : true;
$show_remove = isset( $show_remove ) ? $show_remove : true;
$columns = isset( $columns ) ? $columns : 4;

// Check if user has access
if ( ! is_user_logged_in() && ! AWW()->core->is_guest_wishlist_enabled() ) {
    echo '<p>' . __( 'Please log in to view your wishlist.', 'advanced-wc-wishlist' ) . '</p>';
    return;
}

// Get settings
$show_date = Advanced_WC_Wishlist::get_option( 'show_date', 'no' );
$show_share = AWW()->core->is_social_sharing_enabled();
?>

<div class="aww-wishlist-table-wrapper">
    <?php if ( ! empty( $items ) ) : ?>
        <div class="aww-table-responsive">
            <table class="aww-wishlist-table">
                <thead>
                    <tr>
                        <th class="aww-col-image"><?php esc_html_e( 'Product', 'advanced-wc-wishlist' ); ?></th>
                        <th class="aww-col-name"><?php esc_html_e( 'Name', 'advanced-wc-wishlist' ); ?></th>
                        <?php if ( $show_price ) : ?>
                            <th class="aww-col-price"><?php esc_html_e( 'Price', 'advanced-wc-wishlist' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $show_stock ) : ?>
                            <th class="aww-col-stock"><?php esc_html_e( 'Stock', 'advanced-wc-wishlist' ); ?></th>
                        <?php endif; ?>
                        <?php if ( $show_date ) : ?>
                            <th class="aww-col-date"><?php esc_html_e( 'Date Added', 'advanced-wc-wishlist' ); ?></th>
                        <?php endif; ?>
                        <th class="aww-col-actions"><?php esc_html_e( 'Actions', 'advanced-wc-wishlist' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                        <?php
                        $product = wc_get_product( $item->product_id );
                        if ( ! $product ) {
                            continue;
                        }
                        
                        $current_price = $product->get_price();
                        $has_price_drop = $item->price_at_add && $current_price && $current_price < $item->price_at_add;
                        $price_drop_percentage = $has_price_drop ? round((($item->price_at_add - $current_price) / $item->price_at_add) * 100, 2) : 0;
                        ?>
                        <tr class="aww-wishlist-row" data-product-id="<?php echo esc_attr( $item->product_id ); ?>" data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>">
                            <td class="aww-col-image">
                                <a href="<?php echo esc_url( $product->get_permalink() ); ?>">
                                    <?php echo $product->get_image( 'thumbnail' ); ?>
                                </a>
                            </td>
                            
                            <td class="aww-col-name">
                                <h4 class="aww-product-name">
                                    <a href="<?php echo esc_url( $product->get_permalink() ); ?>">
                                        <?php echo esc_html( $product->get_name() ); ?>
                                    </a>
                                </h4>
                                <?php if ( $product->get_short_description() ) : ?>
                                    <div class="aww-product-description">
                                        <?php echo wp_kses_post( wp_trim_words( $product->get_short_description(), 20 ) ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <?php if ( $show_price ) : ?>
                                <td class="aww-col-price">
                                    <?php if ( $has_price_drop ) : ?>
                                        <div class="aww-price-drop">
                                            <span class="aww-old-price"><?php echo wc_price( $item->price_at_add ); ?></span>
                                            <span class="aww-new-price"><?php echo wc_price( $current_price ); ?></span>
                                            <span class="aww-discount-badge">-<?php echo esc_html( $price_drop_percentage ); ?>%</span>
                                        </div>
                                    <?php else : ?>
                                        <?php echo $product->get_price_html(); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            
                            <?php if ( $show_stock ) : ?>
                                <td class="aww-col-stock">
                                    <?php
                                    $stock_status = $product->get_stock_status();
                                    $stock_text = '';
                                    $stock_class = '';
                                    
                                    switch ( $stock_status ) {
                                        case 'instock':
                                            $stock_text = __( 'In Stock', 'advanced-wc-wishlist' );
                                            $stock_class = 'in-stock';
                                            break;
                                        case 'outofstock':
                                            $stock_text = __( 'Out of Stock', 'advanced-wc-wishlist' );
                                            $stock_class = 'out-of-stock';
                                            break;
                                        case 'onbackorder':
                                            $stock_text = __( 'On Backorder', 'advanced-wc-wishlist' );
                                            $stock_class = 'on-backorder';
                                            break;
                                    }
                                    ?>
                                    <span class="aww-stock-status <?php echo esc_attr( $stock_class ); ?>">
                                        <?php echo esc_html( $stock_text ); ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            
                            <?php if ( $show_date ) : ?>
                                <td class="aww-col-date">
                                    <div class="aww-date-added">
                                        <?php echo date_i18n( get_option( 'date_format' ), strtotime( $item->date_added ) ); ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                            
                            <td class="aww-col-actions">
                                <div class="aww-action-buttons">
                                    <?php if ( $show_add_to_cart && $product->is_purchasable() && $product->is_in_stock() ) : ?>
                                        <button type="button" 
                                                class="aww-add-to-cart-btn" 
                                                data-product-id="<?php echo esc_attr( $item->product_id ); ?>"
                                                data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>"
                                                data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                                            <?php esc_html_e( 'Add to Cart', 'advanced-wc-wishlist' ); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show_remove ) : ?>
                                        <button type="button" 
                                                class="aww-remove-wishlist-btn" 
                                                data-product-id="<?php echo esc_attr( $item->product_id ); ?>"
                                                data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>"
                                                data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>">
                                            <?php esc_html_e( 'Remove', 'advanced-wc-wishlist' ); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="aww-wishlist-footer" style="display: flex; flex-direction: column; align-items: center; gap: 24px; margin-top: 32px;">
            <?php if ( is_user_logged_in() ) : ?>
                <button type="button" class="button button-primary aww-add-all-to-cart" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aww_nonce' ) ); ?>" style="margin-bottom: 8px; min-width: 220px; font-size: 18px; padding: 14px 32px;">
                    <?php esc_html_e( 'Add All to Cart', 'advanced-wc-wishlist' ); ?>
                </button>
            <?php endif; ?>
            <p class="aww-item-count" style="margin-bottom: 0;">
                <?php
                printf(
                    esc_html( _n( '%d item in wishlist', '%d items in wishlist', count( $items ), 'advanced-wc-wishlist' ) ),
                    count( $items )
                );
                ?>
            </p>
            <?php if ( $show_share ) : ?>
            <?php echo AWW()->core->render_sharing_buttons($wishlist_id); ?>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="aww-wishlist-empty">
            <div class="aww-empty-icon">♥</div>
            <h2><?php esc_html_e( 'Your wishlist is empty', 'advanced-wc-wishlist' ); ?></h2>
            <p><?php esc_html_e( 'Start adding products to your wishlist to see them here.', 'advanced-wc-wishlist' ); ?></p>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Continue Shopping', 'advanced-wc-wishlist' ); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.aww-wishlist-table-wrapper {
    overflow-x: auto;
    margin: 20px 0;
}

.aww-wishlist-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.aww-wishlist-table th {
    background: #f8f9fa;
    padding: 15px 10px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e9ecef;
}

.aww-wishlist-table td {
    padding: 15px 10px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: top;
}

.aww-wishlist-table tr:hover {
    background: #f8f9fa;
}

.aww-wishlist-table tr:last-child td {
    border-bottom: none;
}

.aww-product-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.aww-product-name h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    line-height: 1.4;
}

.aww-product-name h4 a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.aww-product-name h4 a:hover {
    color: #e74c3c;
}

.aww-product-description {
    margin: 0;
    font-size: 14px;
    color: #666;
    line-height: 1.4;
}

.aww-product-price {
    font-weight: 600;
    font-size: 16px;
}

.aww-price-drop {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.aww-old-price {
    text-decoration: line-through;
    color: #999;
    font-size: 14px;
    font-weight: normal;
}

.aww-new-price {
    color: #e74c3c;
    font-size: 16px;
    font-weight: 600;
}

.aww-discount-badge {
    background: #e74c3c;
    color: white;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    align-self: flex-start;
}

.aww-stock-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.aww-stock-status.in-stock {
    background: #d4edda;
    color: #155724;
}

.aww-stock-status.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

.aww-stock-status.on-backorder {
    background: #fff3cd;
    color: #856404;
}

.aww-product-date {
    font-size: 14px;
    color: #666;
}

.aww-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.aww-add-to-cart-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s ease;
}

.aww-add-to-cart-btn:hover {
    background: #218838;
}

.aww-remove-wishlist-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s ease;
}

.aww-remove-wishlist-btn:hover {
    background: #c82333;
}

/* Responsive styles */
@media (max-width: 768px) {
    .aww-wishlist-table {
        font-size: 14px;
    }
    
    .aww-wishlist-table th,
    .aww-wishlist-table td {
        padding: 10px 8px;
    }
    
    .aww-product-image img {
        width: 60px;
        height: 60px;
    }
    
    .aww-product-name h4 {
        font-size: 14px;
    }
    
    .aww-product-description {
        font-size: 12px;
    }
    
    .aww-action-buttons {
        flex-direction: row;
        gap: 6px;
    }
    
    .aww-add-to-cart-btn,
    .aww-remove-wishlist-btn {
        padding: 6px 8px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .aww-wishlist-table th:nth-child(3),
    .aww-wishlist-table td:nth-child(3),
    .aww-wishlist-table th:nth-child(4),
    .aww-wishlist-table td:nth-child(4) {
        display: none;
    }
    
    .aww-product-image img {
        width: 50px;
        height: 50px;
    }
    
    .aww-action-buttons {
        flex-direction: column;
        gap: 4px;
    }
}

/* Loading states */
.aww-wishlist-row.loading {
    opacity: 0.6;
    pointer-events: none;
}

.aww-wishlist-row.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #e74c3c;
    border-radius: 50%;
    animation: aww-spin 1s linear infinite;
}

@keyframes aww-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* RTL support */
.rtl .aww-wishlist-table th,
.rtl .aww-wishlist-table td {
    text-align: right;
}

/* Accessibility */
.aww-wishlist-table:focus-within {
    outline: 2px solid #e74c3c;
    outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .aww-wishlist-table {
        border: 2px solid currentColor;
    }
    
    .aww-wishlist-table th,
    .aww-wishlist-table td {
        border: 1px solid currentColor;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .aww-wishlist-table tr {
        transition: none;
    }
    
    .aww-wishlist-table tr:hover {
        background: inherit;
    }
}

/* Print styles */
@media print {
    .aww-wishlist-table {
        box-shadow: none;
        border: 1px solid #000;
    }
    
    .aww-action-buttons {
        display: none;
    }
}
</style> 