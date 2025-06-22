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
$button_icon = Advanced_WC_Wishlist::get_option('button_icon', 'heart');
$show_icon = Advanced_WC_Wishlist::get_option('show_icon', 'yes');
$show_text = Advanced_WC_Wishlist::get_option('show_text', 'yes');

// Get colors
$button_text_color = Advanced_WC_Wishlist::get_option('button_text_color', '#000000');
$button_icon_color = Advanced_WC_Wishlist::get_option('button_icon_color', '#000000');
$button_tooltip = Advanced_WC_Wishlist::get_option('button_tooltip', '');
$button_custom_css = Advanced_WC_Wishlist::get_option('button_custom_css', '');

// Get custom sizes
$button_font_size = Advanced_WC_Wishlist::get_option('button_font_size');
$button_icon_size = Advanced_WC_Wishlist::get_option('button_icon_size', '16');

// Get wishlist URL
$wishlist_url = AWW()->core->get_wishlist_url($wishlist_id);

$overlay = ($loop && $loop_position === 'on_image');

$style = "--aww-text-color: {$button_text_color}; --aww-icon-color: {$button_icon_color};";

// Define icons
$icons = [
    'heart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.007z" clip-rule="evenodd" /></svg>',
    'plus' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>',
    'bookmark' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" clip-rule="evenodd" /></svg>',
    'gift' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 116.75 0h.375c1.035 0 1.875-.84 1.875-1.875V15z" /><path d="M22.5 6.375c0-1.036-.84-1.875-1.875-1.875h-8.25V13.5h12V6.375zM13.5 15h10.5v2.625c0 1.035-.84 1.875-1.875-1.875h-.375a3 3 0 11-6.75 0h-.375c-1.035 0-1.875-.84-1.875-1.875V15z" /></svg>'
];
$selected_icon_svg = isset($icons[$button_icon]) ? $icons[$button_icon] : $icons['heart'];
?>

<button 
    class="aww-wishlist-btn<?php if (!$overlay) { echo ' aww-wishlist-link'; } ?><?php if ($loop) echo ' loop'; ?><?php if ($is_in_wishlist) echo ' added'; ?><?php if ($overlay) echo ' overlay'; ?>"
    data-product-id="<?php echo esc_attr($product_id); ?>"
    data-wishlist-id="<?php echo esc_attr($wishlist_id); ?>"
    data-wishlist-url="<?php echo esc_attr($wishlist_url); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>"
    type="button"
    aria-label="<?php echo esc_attr($button_text); ?>"
    title="<?php echo esc_attr($button_tooltip); ?>"
    style="<?php echo esc_attr($style); ?>"
>
    <?php if ($show_icon === 'yes') : ?>
    <span class="aww-icon">
        <?php echo $selected_icon_svg; // WPCS: XSS ok. ?>
    </span>
    <?php endif; ?>
    <?php if ($show_text === 'yes') : ?>
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
    transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    text-decoration: none;
    line-height: 1;
    min-height: 40px;
    position: relative;
    overflow: hidden;
}

.aww-wishlist-btn:hover {
    background: #DDDDDD;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-top-color: transparent !important;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
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
    color: var(--aww-text-color) !important;
    font-size: 1.1em;
    font-weight: 500;
    padding: 0;
    margin: 0;
    box-shadow: none !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: none;
}
.aww-wishlist-btn.aww-wishlist-link .aww-icon {
    font-size: 1.1em;
    color: var(--aww-icon-color);
    transition: none;
}
.aww-wishlist-btn.aww-wishlist-link:hover,
.aww-wishlist-btn.aww-wishlist-link:focus {
    text-decoration: underline;
    outline: none;
}
.aww-wishlist-btn.aww-wishlist-link:hover .aww-icon,
.aww-wishlist-btn.aww-wishlist-link:focus .aww-icon {
    color: var(--aww-icon-color);
}

/* Overlay (on image) wishlist button styles */
.aww-wishlist-btn.overlay {
    background: rgba(255, 255, 255, 0.8) !important;
    border: none !important;
    color: #000 !important;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    min-height: 0;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
    transition: transform 0.2s, box-shadow 0.2s;
}

.aww-wishlist-btn.overlay:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
}

.aww-wishlist-btn.overlay .aww-icon svg {
    fill: none;
    stroke: #000;
    stroke-width: 1.5;
}

.aww-wishlist-btn.overlay.added .aww-icon svg {
    fill: #e74c3c;
    stroke: #e74c3c;
}

.aww-wishlist-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 99;
}

.aww-wishlist-overlay .aww-wishlist-btn .aww-text {
    display: none !important;
}

<?php if ( ! empty( $button_custom_css ) ) : ?>
    <?php echo esc_html( $button_custom_css ); ?>
<?php endif; ?>
</style> 