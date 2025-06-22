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
$loop_position = isset($loop_position) ? $loop_position : (function_exists('Advanced_WC_Wishlist') ? Advanced_WC_Wishlist::get_option('loop_button_position', 'before_add_to_cart') : 'before_add_to_cart');

if (!$product || !is_object($product)) {
    return;
}

$product_id = $product->get_id();
$is_in_wishlist = AWW()->database->is_product_in_wishlist($product_id, $wishlist_id);

// Get button settings
$button_text_option = Advanced_WC_Wishlist::get_option('button_text', __('Add to wishlist', 'advanced-wc-wishlist'));
$button_text_added_option = Advanced_WC_Wishlist::get_option('button_text_added', __('Browse wishlist', 'advanced-wc-wishlist'));
$button_text = $is_in_wishlist ? $button_text_added_option : $button_text_option;

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
$button_text_color = Advanced_WC_Wishlist::get_option('button_text_color', '#000000');
$button_icon_color = Advanced_WC_Wishlist::get_option('button_icon_color', '#000000');

// Get custom sizes
$button_font_size = Advanced_WC_Wishlist::get_option('button_font_size');
$button_icon_size = Advanced_WC_Wishlist::get_option('button_icon_size');

// Get wishlist URL
$wishlist_url = AWW()->core->get_wishlist_url($wishlist_id);

$overlay = ($loop && $loop_position === 'on_image');
?>

<button 
    class="aww-wishlist-btn aww-wishlist-link<?php if ($loop) echo ' loop'; ?><?php if ($is_in_wishlist) echo ' added'; ?><?php if ($overlay) echo ' overlay'; ?>"
    data-product-id="<?php echo esc_attr($product_id); ?>"
    data-wishlist-id="<?php echo esc_attr($wishlist_id); ?>"
    data-wishlist-url="<?php echo esc_attr($wishlist_url); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>"
    type="button"
    aria-label="<?php echo esc_attr($button_text); ?>"
    title="<?php echo esc_attr($button_text); ?>"
    style="--aww-text-color: <?php echo esc_attr($button_text_color); ?>; --aww-icon-color: <?php echo esc_attr($button_icon_color); ?>;"
>
    <span class="aww-icon">♥</span>
    <?php if (!$overlay): ?>
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
    border: 2px solid transparent;
    background: #EEEEEE;
    color: var(--aww-text-color);
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
    background: #DDDDDD;
    border-color: transparent;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.aww-wishlist-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    border-color: #218838;
}

.aww-wishlist-btn.added {
    background: #28a745;
    border-color: #28a745;
}

.aww-wishlist-btn.added:hover {
    background: #218838;
    border-color: #218838;
}

.aww-wishlist-btn .aww-icon,
.aww-wishlist-btn .aww-icon:before {
    line-height: 1;
    color: var(--aww-icon-color) !important;
}

<?php if ( ! empty( $button_font_size ) ) : ?>
.aww-wishlist-btn .aww-text {
    font-size: <?php echo esc_attr($button_font_size); ?>px;
}
<?php endif; ?>

<?php if ( ! empty( $button_icon_size ) ) : ?>
.aww-wishlist-btn .aww-icon,
.aww-wishlist-btn .aww-icon:before {
    font-size: <?php echo esc_attr($button_icon_size); ?>px !important;
}
<?php endif; ?>

.aww-wishlist-btn .aww-text {
    font-weight: 500;
    color: var(--aww-text-color);
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
    color: var(--aww-text-color);
}

.aww-wishlist-btn.outline:hover {
    background: var(--aww-text-color);
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
    color: var(--aww-text-color);
}

.aww-wishlist-btn.ghost:hover {
    background: rgba(231, 76, 60, 0.1);
    border-color: var(--aww-text-color);
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
    outline-offset: 2px;
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
    outline: 2px solid var(--aww-text-color);
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

.aww-wishlist-btn.aww-wishlist-link {
    background: none !important;
    border: none !important;
    color: #e74c3c !important;
    font-size: 1.1em;
    font-weight: 500;
    padding: 0;
    margin: 0;
    box-shadow: none !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: color 0.2s;
}
.aww-wishlist-btn.aww-wishlist-link .aww-icon {
    font-size: 1.1em;
    color: #e74c3c;
    transition: color 0.2s;
}
.aww-wishlist-btn.aww-wishlist-link:hover,
.aww-wishlist-btn.aww-wishlist-link:focus {
    color: #c0392b !important;
    text-decoration: underline;
    outline: none;
}
.aww-wishlist-btn.aww-wishlist-link:hover .aww-icon,
.aww-wishlist-btn.aww-wishlist-link:focus .aww-icon {
    color: #c0392b;
}

/* Overlay (on image) wishlist button styles */
.aww-wishlist-overlay .aww-wishlist-btn.overlay {
    background: transparent !important;
    border: 2px solid #e74c3c !important;
    color: #e74c3c !important;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    min-width: 38px;
    min-height: 38px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    transition: background 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s;
    position: relative;
    z-index: 2;
}
.aww-wishlist-overlay .aww-wishlist-btn.overlay .aww-icon {
    color: #e74c3c;
    font-size: 20px;
    line-height: 1;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.aww-wishlist-overlay .aww-wishlist-btn.overlay.added {
    background: #e74c3c !important;
    border-color: #e74c3c !important;
    color: #fff !important;
    box-shadow: 0 4px 16px rgba(231,76,60,0.18);
}
.aww-wishlist-overlay .aww-wishlist-btn.overlay.added .aww-icon {
    color: #fff;
}
.aww-wishlist-overlay .aww-wishlist-btn.overlay:hover,
.aww-wishlist-overlay .aww-wishlist-btn.overlay:focus {
    border-color: #c0392b !important;
    color: #c0392b !important;
    box-shadow: 0 4px 16px rgba(192,57,43,0.18);
}
.aww-wishlist-overlay .aww-wishlist-btn.overlay.added:hover,
.aww-wishlist-overlay .aww-wishlist-btn.overlay.added:focus {
    background: #c0392b !important;
    border-color: #c0392b !important;
    color: #fff !important;
}
/* Remove any arrow or extra content for .added */
.aww-wishlist-btn.overlay.added::after,
.aww-wishlist-btn.overlay::after {
    display: none !important;
    content: none !important;
}
</style> 