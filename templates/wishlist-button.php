<?php
/**
 * Wishlist Button Template
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get product and wishlist data
$product = isset($product) ? $product : null;
$wishlist_id = isset($wishlist_id) ? $wishlist_id : AWW()->core->get_current_wishlist_id();
$loop = isset($loop) ? $loop : false;

if (!$product || !is_object($product)) {
    return;
}

$product_id = $product->get_id();
$is_in_wishlist = AWW()->database->is_product_in_wishlist($product_id, $wishlist_id);

// Get button settings
$button_text = $is_in_wishlist ? 
    Advanced_WC_Wishlist::get_option('button_text_added', __('Added to Wishlist', 'advanced-wc-wishlist')) :
    Advanced_WC_Wishlist::get_option('button_text', __('Add to Wishlist', 'advanced-wc-wishlist'));

$button_class = 'aww-wishlist-btn';
if ($is_in_wishlist) {
    $button_class .= ' added';
}
if ($loop) {
    $button_class .= ' loop';
}

// Get button style
$button_style = Advanced_WC_Wishlist::get_option('button_style', 'default');
$button_size = Advanced_WC_Wishlist::get_option('button_size', 'medium');

// Get icon
$icon = Advanced_WC_Wishlist::get_option('button_icon', 'heart');
$show_icon = Advanced_WC_Wishlist::get_option('show_icon', 'yes');
$show_text = Advanced_WC_Wishlist::get_option('show_text', 'yes');

// Get colors
$button_color = Advanced_WC_Wishlist::get_option('button_color', '#e74c3c');
$button_color_hover = Advanced_WC_Wishlist::get_option('button_color_hover', '#c0392b');

// Get wishlist URL
$wishlist_url = AWW()->core->get_wishlist_url($wishlist_id);
?>

<button 
    class="<?php echo esc_attr($button_class); ?>"
    data-product-id="<?php echo esc_attr($product_id); ?>"
    data-wishlist-id="<?php echo esc_attr($wishlist_id); ?>"
    data-wishlist-url="<?php echo esc_attr($wishlist_url); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>"
    type="button"
    aria-label="<?php echo esc_attr($button_text); ?>"
    title="<?php echo esc_attr($button_text); ?>"
>
    <?php if ($show_icon === 'yes'): ?>
        <span class="aww-icon">
            <?php if ($icon === 'heart'): ?>
                ♥
            <?php elseif ($icon === 'star'): ?>
                ★
            <?php elseif ($icon === 'plus'): ?>
                +
            <?php else: ?>
                ♥
            <?php endif; ?>
        </span>
    <?php endif; ?>
    
    <?php if ($show_text === 'yes'): ?>
        <span class="aww-text"><?php echo esc_html($button_text); ?></span>
    <?php endif; ?>
</button>

<style>
.aww-wishlist-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid <?php echo esc_attr($button_color); ?>;
    background: <?php echo esc_attr($button_color); ?>;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    line-height: 1;
    min-height: 40px;
    position: relative;
    overflow: hidden;
}

.aww-wishlist-btn:hover {
    background: <?php echo esc_attr($button_color_hover); ?>;
    border-color: <?php echo esc_attr($button_color_hover); ?>;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.aww-wishlist-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.aww-wishlist-btn.added {
    background: #28a745;
    border-color: #28a745;
}

.aww-wishlist-btn.added:hover {
    background: #218838;
    border-color: #218838;
}

.aww-wishlist-btn .aww-icon {
    font-size: 16px;
    line-height: 1;
}

.aww-wishlist-btn .aww-text {
    font-weight: 500;
}

/* Button sizes */
.aww-wishlist-btn.small {
    padding: 6px 12px;
    font-size: 12px;
    min-height: 32px;
}

.aww-wishlist-btn.small .aww-icon {
    font-size: 14px;
}

.aww-wishlist-btn.large {
    padding: 14px 20px;
    font-size: 16px;
    min-height: 48px;
}

.aww-wishlist-btn.large .aww-icon {
    font-size: 18px;
}

/* Button styles */
.aww-wishlist-btn.rounded {
    border-radius: 25px;
}

.aww-wishlist-btn.outline {
    background: transparent;
    color: <?php echo esc_attr($button_color); ?>;
}

.aww-wishlist-btn.outline:hover {
    background: <?php echo esc_attr($button_color); ?>;
    color: white;
}

.aww-wishlist-btn.outline.added {
    background: transparent;
    color: #28a745;
}

.aww-wishlist-btn.outline.added:hover {
    background: #28a745;
    color: white;
}

.aww-wishlist-btn.ghost {
    background: transparent;
    border-color: transparent;
    color: <?php echo esc_attr($button_color); ?>;
}

.aww-wishlist-btn.ghost:hover {
    background: rgba(231, 76, 60, 0.1);
    border-color: <?php echo esc_attr($button_color); ?>;
}

.aww-wishlist-btn.ghost.added {
    color: #28a745;
}

.aww-wishlist-btn.ghost.added:hover {
    background: rgba(40, 167, 69, 0.1);
    border-color: #28a745;
}

/* Loading state */
.aww-wishlist-btn.loading {
    pointer-events: none;
    opacity: 0.7;
}

.aww-wishlist-btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: aww-spin 1s linear infinite;
}

@keyframes aww-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Loop specific styles */
.aww-wishlist-btn.loop {
    width: 100%;
    margin-top: 10px;
}

/* Responsive styles */
@media (max-width: 768px) {
    .aww-wishlist-btn {
        padding: 8px 12px;
        font-size: 13px;
        min-height: 36px;
    }
    
    .aww-wishlist-btn .aww-icon {
        font-size: 14px;
    }
    
    .aww-wishlist-btn .aww-text {
        display: none;
    }
    
    .aww-wishlist-btn.loop .aww-text {
        display: inline;
    }
}

/* RTL support */
.rtl .aww-wishlist-btn {
    direction: rtl;
}

/* Accessibility */
.aww-wishlist-btn:focus {
    outline: 2px solid <?php echo esc_attr($button_color); ?>;
    outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .aww-wishlist-btn {
        border-width: 3px;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .aww-wishlist-btn {
        transition: none;
    }
    
    .aww-wishlist-btn:hover {
        transform: none;
    }
    
    .aww-wishlist-btn:active {
        transform: none;
    }
}

/* Print styles */
@media print {
    .aww-wishlist-btn {
        display: none;
    }
}
</style> 