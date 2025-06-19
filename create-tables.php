<?php
/**
 * Manual database table creation script
 * Run this to create the wishlist tables
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Include database class
require_once __DIR__ . '/includes/class-aww-database.php';

// Create database object
$database = new AWW_Database();

// Create tables
$database->create_tables();

echo "Database tables created successfully!\n";
echo "Tables created:\n";
echo "- wp_aww_wishlist_lists\n";
echo "- wp_aww_wishlists\n";

// Verify tables exist
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_aww_%'");
echo "\nVerification:\n";
foreach ($tables as $table) {
    $table_name = array_values((array)$table)[0];
    echo "- $table_name exists\n";
} 