<?php
/**
 * Test file for Advanced WooCommerce Wishlist
 * This file helps debug wishlist functionality
 * 
 * Access this file via: http://localhost/plugin/wp-content/plugins/advanced-wc-wishlist/test-wishlist.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Test if plugin is loaded
if ( ! function_exists( 'AWW' ) ) {
    echo "<h2>Plugin not loaded!</h2>";
    echo "<p>Make sure the Advanced WooCommerce Wishlist plugin is activated.</p>";
    exit;
}

echo "<h1>Advanced WooCommerce Wishlist - Test Results</h1>";

// Test wishlist page ID
$wishlist_page_id = get_option( 'aww_wishlist_page_id' );
echo "<h3>Wishlist Page ID:</h3>";
echo "<p>" . ($wishlist_page_id ? $wishlist_page_id : 'Not set') . "</p>";

// Test wishlist page URL
if ( $wishlist_page_id ) {
    $wishlist_url = get_permalink( $wishlist_page_id );
    echo "<h3>Wishlist Page URL:</h3>";
    echo "<p><a href='" . $wishlist_url . "' target='_blank'>" . $wishlist_url . "</a></p>";
}

// Test plugin wishlist URL
$plugin_wishlist_url = AWW()->core->get_wishlist_url();
echo "<h3>Plugin Wishlist URL:</h3>";
echo "<p><a href='" . $plugin_wishlist_url . "' target='_blank'>" . $plugin_wishlist_url . "</a></p>";

// Test if user is logged in
echo "<h3>User Status:</h3>";
echo "<p>User logged in: " . (is_user_logged_in() ? 'Yes' : 'No') . "</p>";
if ( is_user_logged_in() ) {
    echo "<p>Current user ID: " . get_current_user_id() . "</p>";
}

// Test current wishlist ID
$current_wishlist_id = AWW()->core->get_current_wishlist_id();
echo "<h3>Current Wishlist ID:</h3>";
echo "<p>" . $current_wishlist_id . "</p>";

// Test wishlist count
$wishlist_count = AWW()->database->get_wishlist_count( $current_wishlist_id );
echo "<h3>Wishlist Count:</h3>";
echo "<p>" . $wishlist_count . "</p>";

// Test user wishlists
$user_wishlists = AWW()->database->get_wishlists();
echo "<h3>User Wishlists:</h3>";
echo "<p>Total wishlists: " . count( $user_wishlists ) . "</p>";

if ( ! empty( $user_wishlists ) ) {
    echo "<ul>";
    foreach ( $user_wishlists as $wishlist ) {
        echo "<li>Wishlist ID: " . $wishlist->id . ", Name: " . $wishlist->name . "</li>";
    }
    echo "</ul>";
}

// Test if we're on the wishlist page
echo "<h3>Page Status:</h3>";
echo "<p>Is on wishlist page: " . (is_page( $wishlist_page_id ) ? 'Yes' : 'No') . "</p>";

// Test template path
$template_path = AWW()->core->get_template_path( 'wishlist-page.php' );
echo "<h3>Template Path:</h3>";
echo "<p>" . $template_path . "</p>";
echo "<p>Template exists: " . (file_exists( $template_path ) ? 'Yes' : 'No') . "</p>";

// Test shortcode
echo "<h3>Shortcode Test:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";
echo do_shortcode('[aww_wishlist]');
echo "</div>";

// Test database tables
echo "<h3>Database Tables:</h3>";
global $wpdb;
$wishlists_table = $wpdb->prefix . 'aww_wishlist_lists';
$items_table = $wpdb->prefix . 'aww_wishlists';

$wishlists_exists = $wpdb->get_var("SHOW TABLES LIKE '$wishlists_table'") == $wishlists_table;
$items_exists = $wpdb->get_var("SHOW TABLES LIKE '$items_table'") == $items_table;

echo "<p>Wishlists table exists: " . ($wishlists_exists ? 'Yes' : 'No') . "</p>";
echo "<p>Items table exists: " . ($items_exists ? 'Yes' : 'No') . "</p>";

if ( $wishlists_exists ) {
    $wishlists_count = $wpdb->get_var("SELECT COUNT(*) FROM $wishlists_table");
    echo "<p>Total wishlists in database: " . $wishlists_count . "</p>";
}

if ( $items_exists ) {
    $items_count = $wpdb->get_var("SELECT COUNT(*) FROM $items_table");
    echo "<p>Total items in database: " . $items_count . "</p>";
}

echo "<h3>Actions:</h3>";
echo "<p><a href='" . admin_url('plugins.php') . "' target='_blank'>Go to Plugins Page</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=aww-settings') . "' target='_blank'>Go to Wishlist Settings</a></p>";
echo "<p><a href='" . home_url('/wishlist/') . "' target='_blank'>Visit Wishlist Page</a></p>";
?> 