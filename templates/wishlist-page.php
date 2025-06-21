<?php
/**
 * Wishlist Page Template
 *
 * @package Advanced_WC_Wishlist
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current wishlist data
$current_wishlist = isset($current_wishlist) ? $current_wishlist : AWW()->core->get_current_wishlist();
$user_wishlists = isset($user_wishlists) ? $user_wishlists : AWW()->core->get_user_wishlists();
$current_wishlist_id = $current_wishlist ? $current_wishlist->id : AWW()->core->get_current_wishlist_id();
$items = AWW()->database->get_wishlist_items($current_wishlist_id);
?>

<div class="aww-wishlist-page">
    <!-- Wishlist Manager -->
    <div class="aww-wishlist-manager">
        <?php if (count($user_wishlists) > 1): ?>
            <div class="aww-wishlist-selector">
                <label for="aww-wishlist-select"><?php _e('Select Wishlist:', 'advanced-wc-wishlist'); ?></label>
                <select id="aww-wishlist-select" data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
                    <?php foreach ($user_wishlists as $wishlist): ?>
                        <option value="<?php echo esc_attr($wishlist->id); ?>" <?php selected($wishlist->id, $current_wishlist_id); ?>>
                            <?php echo esc_html($wishlist->name); ?> (<?php echo AWW()->database->get_wishlist_count($wishlist->id); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="aww-wishlist-actions">
            <button type="button" class="aww-create-wishlist-btn" data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
                <?php _e('Create New Wishlist', 'advanced-wc-wishlist'); ?>
            </button>
            
            <?php if ($current_wishlist_id): ?>
                <button type="button" class="aww-rename-wishlist-btn" data-wishlist-id="<?php echo esc_attr($current_wishlist_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
                    <?php _e('Rename Wishlist', 'advanced-wc-wishlist'); ?>
                </button>
                
                <?php if (count($user_wishlists) > 1): ?>
                    <button type="button" class="aww-delete-wishlist-btn" data-wishlist-id="<?php echo esc_attr($current_wishlist_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
                        <?php _e('Delete Wishlist', 'advanced-wc-wishlist'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Price Drop Notifications -->
    <div class="aww-price-drops-section" style="display: none;">
        <h3><?php _e('Price Drops', 'advanced-wc-wishlist'); ?></h3>
        <div id="aww-price-drops-list"></div>
    </div>

    <!-- Wishlist Content -->
    <div class="aww-wishlist-content">
        <?php if ($current_wishlist): ?>
            <h2 class="aww-wishlist-title"><?php echo esc_html($current_wishlist->name); ?></h2>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="aww-empty-wishlist">
                <p><?php _e('Your wishlist is empty.', 'advanced-wc-wishlist'); ?></p>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button">
                    <?php _e('Continue Shopping', 'advanced-wc-wishlist'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="aww-wishlist-actions-top">
                <button type="button" class="aww-add-all-to-cart" data-wishlist-id="<?php echo esc_attr($current_wishlist_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
                    <?php _e('Add All to Cart', 'advanced-wc-wishlist'); ?>
                </button>
                
                <div class="aww-share-wishlist">
                    <button type="button" class="aww-share-btn" data-wishlist-id="<?php echo esc_attr($current_wishlist_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
                        <?php _e('Share Wishlist', 'advanced-wc-wishlist'); ?>
                    </button>
                    <div class="aww-share-options" style="display: none;">
                        <a href="#" class="aww-share-facebook" data-url="<?php echo esc_url(AWW()->core->get_wishlist_url($current_wishlist_id)); ?>">
                            <?php _e('Facebook', 'advanced-wc-wishlist'); ?>
                        </a>
                        <a href="#" class="aww-share-twitter" data-url="<?php echo esc_url(AWW()->core->get_wishlist_url($current_wishlist_id)); ?>">
                            <?php _e('Twitter', 'advanced-wc-wishlist'); ?>
                        </a>
                        <a href="#" class="aww-share-whatsapp" data-url="<?php echo esc_url(AWW()->core->get_wishlist_url($current_wishlist_id)); ?>">
                            <?php _e('WhatsApp', 'advanced-wc-wishlist'); ?>
                        </a>
                        <a href="#" class="aww-share-email" data-url="<?php echo esc_url(AWW()->core->get_wishlist_url($current_wishlist_id)); ?>">
                            <?php _e('Email', 'advanced-wc-wishlist'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Wishlist Table -->
            <?php AWW()->core->load_template('wishlist-table.php', array(
                'items' => $items,
                'wishlist_id' => $current_wishlist_id,
                'show_price' => true,
                'show_stock' => true,
                'show_add_to_cart' => true,
                'show_remove' => true,
            )); ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Wishlist Modal -->
<div id="aww-create-wishlist-modal" class="aww-modal" style="display: none;">
    <div class="aww-modal-content">
        <span class="aww-modal-close">&times;</span>
        <h3><?php _e('Create New Wishlist', 'advanced-wc-wishlist'); ?></h3>
        <form id="aww-create-wishlist-form">
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
            <div class="aww-form-group">
                <label for="aww-wishlist-name"><?php _e('Wishlist Name:', 'advanced-wc-wishlist'); ?></label>
                <input type="text" id="aww-wishlist-name" name="name" required>
            </div>
            <div class="aww-form-actions">
                <button type="submit" class="button"><?php _e('Create Wishlist', 'advanced-wc-wishlist'); ?></button>
                <button type="button" class="button aww-modal-cancel"><?php _e('Cancel', 'advanced-wc-wishlist'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Rename Wishlist Modal -->
<div id="aww-rename-wishlist-modal" class="aww-modal" style="display: none;">
    <div class="aww-modal-content">
        <span class="aww-modal-close">&times;</span>
        <h3><?php _e('Rename Wishlist', 'advanced-wc-wishlist'); ?></h3>
        <form id="aww-rename-wishlist-form">
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
            <input type="hidden" name="wishlist_id" id="aww-rename-wishlist-id">
            <div class="aww-form-group">
                <label for="aww-rename-wishlist-name"><?php _e('Wishlist Name:', 'advanced-wc-wishlist'); ?></label>
                <input type="text" id="aww-rename-wishlist-name" name="name" required>
            </div>
            <div class="aww-form-actions">
                <button type="submit" class="button"><?php _e('Update Wishlist', 'advanced-wc-wishlist'); ?></button>
                <button type="button" class="button aww-modal-cancel"><?php _e('Cancel', 'advanced-wc-wishlist'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Wishlist Modal -->
<div id="aww-delete-wishlist-modal" class="aww-modal" style="display: none;">
    <div class="aww-modal-content">
        <span class="aww-modal-close">&times;</span>
        <h3><?php _e('Delete Wishlist', 'advanced-wc-wishlist'); ?></h3>
        <p><?php _e('Are you sure you want to delete this wishlist? This action cannot be undone.', 'advanced-wc-wishlist'); ?></p>
        <form id="aww-delete-wishlist-form">
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('aww_nonce')); ?>">
            <input type="hidden" name="wishlist_id" id="aww-delete-wishlist-id">
            <div class="aww-form-actions">
                <button type="submit" class="button aww-delete-btn"><?php _e('Delete Wishlist', 'advanced-wc-wishlist'); ?></button>
                <button type="button" class="button aww-modal-cancel"><?php _e('Cancel', 'advanced-wc-wishlist'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.aww-wishlist-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.aww-wishlist-manager {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.aww-wishlist-selector select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    min-width: 200px;
}

.aww-wishlist-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.aww-wishlist-actions button {
    padding: 8px 16px;
    border: 1px solid #007cba;
    background: #007cba;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.aww-wishlist-actions button:hover {
    background: #005a87;
    border-color: #005a87;
}

.aww-price-drops-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.aww-wishlist-content {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.aww-wishlist-title {
    margin: 0 0 30px 0;
    color: #333;
    font-size: 28px;
}

.aww-empty-wishlist {
    text-align: center;
    padding: 60px 20px;
}

.aww-empty-wishlist p {
    font-size: 18px;
    color: #666;
    margin-bottom: 20px;
}

.aww-wishlist-actions-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.aww-add-all-to-cart {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
}

.aww-add-all-to-cart:hover {
    background: #218838;
}

.aww-share-wishlist {
    position: relative;
}

.aww-share-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
}

.aww-share-btn:hover {
    background: #5a6268;
}

.aww-share-options {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    min-width: 150px;
}

.aww-share-options a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #eee;
    transition: background 0.3s ease;
}

.aww-share-options a:last-child {
    border-bottom: none;
}

.aww-share-options a:hover {
    background: #f8f9fa;
}

/* Modal Styles */
.aww-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.aww-modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
    position: relative;
}

.aww-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.aww-modal-close:hover,
.aww-modal-close:focus {
    color: black;
    text-decoration: none;
}

.aww-form-group {
    margin-bottom: 15px;
}

.aww-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.aww-form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.aww-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.aww-delete-btn {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
}

.aww-delete-btn:hover {
    background: #c82333 !important;
    border-color: #c82333 !important;
}
</style> 