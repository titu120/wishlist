<?php
/**
 * Activation script for Advanced WooCommerce Wishlist
 * Run this file to ensure the wishlist page is created and plugin is working
 * 
 * Access this file via: http://localhost/plugin/wp-content/plugins/advanced-wc-wishlist/activate-wishlist.php
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Advanced WooCommerce Wishlist - Activation Script</h1>";

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
    echo "<h2>Error: WooCommerce is not active!</h2>";
    echo "<p>Please activate WooCommerce first.</p>";
    exit;
}

// Check if plugin is loaded
if ( ! function_exists( 'AWW' ) ) {
    echo "<h2>Error: Plugin not loaded!</h2>";
    echo "<p>Please activate the Advanced WooCommerce Wishlist plugin first.</p>";
    exit;
}

echo "<h2>Step 1: Creating/Checking Wishlist Page</h2>";

// Check if wishlist page exists
$wishlist_page = get_page_by_path( 'wishlist' );
if ( ! $wishlist_page ) {
    echo "<p>Wishlist page does not exist. Creating...</p>";
    
    $page_id = wp_insert_post( array(
        'post_title'   => __( 'Wishlist', 'advanced-wc-wishlist' ),
        'post_name'    => 'wishlist',
        'post_content' => '[aww_wishlist]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ) );
    
    if ( $page_id && ! is_wp_error( $page_id ) ) {
        update_option( 'aww_wishlist_page_id', $page_id );
        echo "<p>✅ Wishlist page created successfully! Page ID: " . $page_id . "</p>";
    } else {
        echo "<p>❌ Failed to create wishlist page.</p>";
        exit;
    }
} else {
    update_option( 'aww_wishlist_page_id', $wishlist_page->ID );
    echo "<p>✅ Wishlist page already exists. Page ID: " . $wishlist_page->ID . "</p>";
}

echo "<h2>Step 2: Creating Database Tables</h2>";

// Create database tables
$database = new AWW_Database();
$database->create_tables();
echo "<p>✅ Database tables created/checked.</p>";

echo "<h2>Step 3: Setting Default Options</h2>";

// Set default options
$defaults = array(
    'button_position' => 'after_add_to_cart',
    'button_text' => __( 'Add to Wishlist', 'advanced-wc-wishlist' ),
    'button_text_added' => __( 'Added to Wishlist', 'advanced-wc-wishlist' ),
    'button_color' => '#e74c3c',
    'button_color_hover' => '#c0392b',
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
echo "<p>✅ Default options set.</p>";

echo "<h2>Step 4: Flushing Rewrite Rules</h2>";
flush_rewrite_rules();
echo "<p>✅ Rewrite rules flushed.</p>";

echo "<h2>Step 5: Testing Wishlist Functionality</h2>";

// Test wishlist page ID
$wishlist_page_id = get_option( 'aww_wishlist_page_id' );
echo "<p>Wishlist Page ID: " . ($wishlist_page_id ? $wishlist_page_id : 'Not set') . "</p>";

// Test wishlist page URL
if ( $wishlist_page_id ) {
    $wishlist_url = get_permalink( $wishlist_page_id );
    echo "<p>Wishlist Page URL: <a href='" . $wishlist_url . "' target='_blank'>" . $wishlist_url . "</a></p>";
}

// Test plugin wishlist URL
$plugin_wishlist_url = AWW()->core->get_wishlist_url();
echo "<p>Plugin Wishlist URL: <a href='" . $plugin_wishlist_url . "' target='_blank'>" . $plugin_wishlist_url . "</a></p>";

// Test current wishlist ID
$current_wishlist_id = AWW()->core->get_current_wishlist_id();
echo "<p>Current Wishlist ID: " . $current_wishlist_id . "</p>";

// Test wishlist count
$wishlist_count = AWW()->database->get_wishlist_count( $current_wishlist_id );
echo "<p>Wishlist Count: " . $wishlist_count . "</p>";

// Test user wishlists
$user_wishlists = AWW()->database->get_wishlists();
echo "<p>User Wishlists: " . count( $user_wishlists ) . "</p>";

// Test shortcode
echo "<h3>Shortcode Test:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";
echo do_shortcode('[aww_wishlist]');
echo "</div>";

echo "<h2>✅ Activation Complete!</h2>";
echo "<p>The Advanced WooCommerce Wishlist plugin has been activated successfully.</p>";
echo "<p><a href='" . home_url('/wishlist/') . "' target='_blank'>Visit Wishlist Page</a></p>";
echo "<p><a href='" . admin_url('plugins.php') . "' target='_blank'>Go to Plugins Page</a></p>";
?> 