<?php
/**
 * Database Class for Advanced WooCommerce Wishlist
 *
 * @package Advanced_WC_Wishlist
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AWW_Database Class
 *
 * Handles all database operations for the wishlist functionality
 *
 * @since 1.0.0
 */
class AWW_Database {

    /**
     * Wishlist table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Lists table name
     *
     * @var string
     */
    private $lists_table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aww_wishlists';
        $this->lists_table_name = $wpdb->prefix . 'aww_wishlist_lists';
    }

    /**
     * Create database tables (multiple wishlist support)
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // New table for wishlist lists
        $sql_lists = "CREATE TABLE {$this->lists_table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            name varchar(255) NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";

        // Updated wishlist items table
        $sql_items = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wishlist_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            price_at_add decimal(20,6) DEFAULT NULL,
            date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wishlist_id (wishlist_id),
            KEY product_id (product_id),
            KEY date_added (date_added),
            UNIQUE KEY unique_wishlist_item (wishlist_id, product_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_lists );
        dbDelta( $sql_items );

        // Migration: Move old wishlist items to default list if upgrading
        $this->migrate_old_wishlist_schema();

        update_option( 'aww_db_version', AWW_VERSION );
    }

    /**
     * Create database table (alias for create_tables for backward compatibility)
     */
    public function create_table() {
        return $this->create_tables();
    }

    /**
     * Migrate old wishlist schema to new multiple wishlist schema
     */
    private function migrate_old_wishlist_schema() {
        global $wpdb;
        // Check if old columns exist
        $columns = $wpdb->get_results( "SHOW COLUMNS FROM {$this->table_name}" );
        $has_user_id = false;
        $has_session_id = false;
        $has_wishlist_id = false;
        foreach ( $columns as $col ) {
            if ( $col->Field === 'user_id' ) $has_user_id = true;
            if ( $col->Field === 'session_id' ) $has_session_id = true;
            if ( $col->Field === 'wishlist_id' ) $has_wishlist_id = true;
        }
        if ( $has_user_id || $has_session_id ) {
            // For each unique user/session, create a default wishlist and move items
            $users = $wpdb->get_results( "SELECT DISTINCT user_id, session_id FROM {$this->table_name}" );
            foreach ( $users as $u ) {
                $user_id = $u->user_id;
                $session_id = $u->session_id;
                $name = __( 'My Wishlist', 'advanced-wc-wishlist' );
                $wpdb->insert( $this->lists_table_name, [
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'name' => $name,
                    'date_created' => current_time( 'mysql' ),
                    'date_updated' => current_time( 'mysql' ),
                ] );
                $wishlist_id = $wpdb->insert_id;
                // Update items
                $where = [];
                if ( $user_id ) $where[] = $wpdb->prepare( 'user_id = %d', $user_id );
                if ( $session_id ) $where[] = $wpdb->prepare( 'session_id = %s', $session_id );
                $where_clause = implode( ' AND ', $where );
                $wpdb->query( "UPDATE {$this->table_name} SET wishlist_id = {$wishlist_id} WHERE {$where_clause}" );
            }
            // Remove old columns
            $wpdb->query( "ALTER TABLE {$this->table_name} DROP COLUMN user_id" );
            $wpdb->query( "ALTER TABLE {$this->table_name} DROP COLUMN session_id" );
        }
    }

    /**
     * Create a new wishlist
     * 
     * SECURITY: Validates input, sanitizes data, and checks user permissions
     * 
     * @param string $name Wishlist name
     * @param int|null $user_id User ID
     * @param string|null $session_id Session ID
     * @return int|false Wishlist ID on success, false on failure
     */
    public function create_wishlist($name = '', $user_id = null, $session_id = null) {
        global $wpdb;
        
        // SECURITY: Validate and sanitize input
        $name = sanitize_text_field( $name );
        $user_id = $user_id ? absint( $user_id ) : null;
        $session_id = $session_id ? sanitize_text_field( $session_id ) : null;
        
        // SECURITY: Validate user permissions if user_id provided
        if ( $user_id && ! current_user_can( 'edit_user', $user_id ) ) {
            error_log( 'Advanced WC Wishlist: Unauthorized wishlist creation attempt for user ' . $user_id );
            return false;
        }
        
        if (!$user_id && !$session_id) {
            $user_info = $this->get_user_info();
            $user_id = $user_info['user_id'];
            $session_id = $user_info['session_id'];
        }
        
        if (!$name) {
            $name = __('My Wishlist', 'advanced-wc-wishlist');
        }
        
        // SECURITY: Limit name length to prevent abuse
        if ( strlen( $name ) > 255 ) {
            $name = substr( $name, 0, 255 );
        }
        
        try {
            $result = $wpdb->insert($this->lists_table_name, [
                'user_id' => $user_id,
                'session_id' => $session_id,
                'name' => $name,
                'date_created' => current_time('mysql'),
                'date_updated' => current_time('mysql'),
            ]);
            
            if ( $result === false ) {
                error_log( 'Advanced WC Wishlist: Database error creating wishlist - ' . $wpdb->last_error );
                return false;
            }
            
            return $wpdb->insert_id;
        } catch ( Exception $e ) {
            error_log( 'Advanced WC Wishlist: Exception creating wishlist - ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Get all wishlists for current user/session
     * 
     * SECURITY: Validates input and checks user permissions
     * 
     * @param int|null $user_id User ID
     * @param string|null $session_id Session ID
     * @return array|false Wishlists on success, false on failure
     */
    public function get_wishlists($user_id = null, $session_id = null) {
        global $wpdb;
        
        // SECURITY: Validate and sanitize input
        $user_id = $user_id ? absint( $user_id ) : null;
        $session_id = $session_id ? sanitize_text_field( $session_id ) : null;
        
        // SECURITY: Validate user permissions if user_id provided
        if ( $user_id && ! current_user_can( 'edit_user', $user_id ) ) {
            error_log( 'Advanced WC Wishlist: Unauthorized wishlist access attempt for user ' . $user_id );
            return false;
        }
        
        if (!$user_id && !$session_id) {
            $user_info = $this->get_user_info();
            $user_id = $user_info['user_id'];
            $session_id = $user_info['session_id'];
        }
        
        try {
            $where = [];
            if ($user_id) $where[] = $wpdb->prepare('user_id = %d', $user_id);
            if ($session_id) $where[] = $wpdb->prepare('session_id = %s', $session_id);
            $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $query = "SELECT * FROM {$this->lists_table_name} $where_clause ORDER BY date_created ASC";
            $results = $wpdb->get_results($query);
            
            if ( $results === null ) {
                error_log( 'Advanced WC Wishlist: Database error getting wishlists - ' . $wpdb->last_error );
                return false;
            }
            
            return $results;
        } catch ( Exception $e ) {
            error_log( 'Advanced WC Wishlist: Exception getting wishlists - ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Get a single wishlist by ID
     * 
     * SECURITY: Validates input and checks user permissions
     * 
     * @param int $wishlist_id Wishlist ID
     * @return object|false Wishlist object on success, false on failure
     */
    public function get_wishlist($wishlist_id) {
        global $wpdb;
        
        // SECURITY: Validate input
        $wishlist_id = absint( $wishlist_id );
        if ( ! $wishlist_id ) {
            return false;
        }
        
        try {
            $wishlist = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->lists_table_name} WHERE id = %d", $wishlist_id));
            
            // SECURITY: Check if user has access to this wishlist
            if ( $wishlist && is_user_logged_in() ) {
                $current_user_id = get_current_user_id();
                if ( $wishlist->user_id && $wishlist->user_id != $current_user_id ) {
                    error_log( 'Advanced WC Wishlist: Unauthorized wishlist access attempt. User: ' . $current_user_id . ', Wishlist: ' . $wishlist_id );
                    return false;
                }
            }
            
            return $wishlist;
        } catch ( Exception $e ) {
            error_log( 'Advanced WC Wishlist: Exception getting wishlist - ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Update wishlist name
     * 
     * SECURITY: Validates input, sanitizes data, and checks user permissions
     * 
     * @param int $wishlist_id Wishlist ID
     * @param string $name New wishlist name
     * @return bool True on success, false on failure
     */
    public function update_wishlist($wishlist_id, $name) {
        global $wpdb;
        
        // SECURITY: Validate and sanitize input
        $wishlist_id = absint( $wishlist_id );
        $name = sanitize_text_field( $name );
        
        if ( ! $wishlist_id || empty( $name ) ) {
            return false;
        }
        
        // SECURITY: Check if user has access to this wishlist
        $wishlist = $this->get_wishlist( $wishlist_id );
        if ( ! $wishlist ) {
            return false;
        }
        
        // SECURITY: Limit name length to prevent abuse
        if ( strlen( $name ) > 255 ) {
            $name = substr( $name, 0, 255 );
        }
        
        try {
            $result = $wpdb->update($this->lists_table_name, [
                'name' => $name,
                'date_updated' => current_time('mysql'),
            ], [ 'id' => $wishlist_id ]);
            
            if ( $result === false ) {
                error_log( 'Advanced WC Wishlist: Database error updating wishlist - ' . $wpdb->last_error );
                return false;
            }
            
            return true;
        } catch ( Exception $e ) {
            error_log( 'Advanced WC Wishlist: Exception updating wishlist - ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Delete a wishlist and its items
     */
    public function delete_wishlist($wishlist_id) {
        global $wpdb;
        $wpdb->delete($this->table_name, [ 'wishlist_id' => $wishlist_id ]);
        return $wpdb->delete($this->lists_table_name, [ 'id' => $wishlist_id ]);
    }

    /**
     * Get default wishlist for user/session (create if not exists)
     */
    public function get_default_wishlist_id($user_id = null, $session_id = null) {
        $wishlists = $this->get_wishlists($user_id, $session_id);
        if (!empty($wishlists)) {
            return $wishlists[0]->id;
        }
        return $this->create_wishlist('', $user_id, $session_id);
    }

    /**
     * Add product to wishlist (now requires wishlist_id)
     */
    public function add_to_wishlist($product_id, $wishlist_id = null) {
        global $wpdb;
        if (!$wishlist_id) {
            $wishlist_id = $this->get_default_wishlist_id();
        }
        if (!$this->is_valid_product($product_id)) {
            return false;
        }
        if ($this->is_product_in_wishlist($product_id, $wishlist_id)) {
            return false;
        }
        $product = wc_get_product($product_id);
        $price = $product ? $product->get_price() : null;
        $result = $wpdb->insert(
            $this->table_name,
            [
                'wishlist_id' => $wishlist_id,
                'product_id' => $product_id,
                'price_at_add' => $price,
                'date_added' => current_time('mysql'),
            ],
            [ '%d', '%d', '%f', '%s' ]
        );
        if ($result) {
            do_action('aww_product_added_to_wishlist', $product_id, $wishlist_id);
            return $wpdb->insert_id;
        }
        return false;
    }

    /**
     * Remove product from wishlist (now requires wishlist_id)
     */
    public function remove_from_wishlist($product_id, $wishlist_id = null) {
        global $wpdb;
        if (!$wishlist_id) {
            $wishlist_id = $this->get_default_wishlist_id();
        }
        $result = $wpdb->delete($this->table_name, [
            'wishlist_id' => $wishlist_id,
            'product_id' => $product_id,
        ]);
        if ($result) {
            do_action('aww_product_removed_from_wishlist', $product_id, $wishlist_id);
            return true;
        }
        return false;
    }

    /**
     * Get wishlist items (now requires wishlist_id)
     */
    public function get_wishlist_items($wishlist_id = null, $limit = 0, $offset = 0) {
        global $wpdb;
        if (!$wishlist_id) {
            $wishlist_id = $this->get_default_wishlist_id();
        }
        $sql = "SELECT w.*, p.post_title as product_name, p.post_status 
                FROM {$this->table_name} w 
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
                WHERE w.wishlist_id = %d AND p.post_status = 'publish' 
                ORDER BY w.date_added DESC";
        if ($limit > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d', $limit);
            if ($offset > 0) {
                $sql .= $wpdb->prepare(' OFFSET %d', $offset);
            }
        }
        return $wpdb->get_results($wpdb->prepare($sql, $wishlist_id));
    }

    /**
     * Get wishlist count (now requires wishlist_id)
     */
    public function get_wishlist_count($wishlist_id = null) {
        global $wpdb;
        if (!$wishlist_id) {
            $wishlist_id = $this->get_default_wishlist_id();
        }
        $sql = "SELECT COUNT(*) 
                FROM {$this->table_name} w 
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
                WHERE w.wishlist_id = %d AND p.post_status = 'publish'";
        return (int) $wpdb->get_var($wpdb->prepare($sql, $wishlist_id));
    }

    /**
     * Check if product is in wishlist (now requires wishlist_id)
     */
    public function is_product_in_wishlist($product_id, $wishlist_id = null) {
        global $wpdb;
        if (!$wishlist_id) {
            $wishlist_id = $this->get_default_wishlist_id();
        }
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE product_id = %d AND wishlist_id = %d",
            $product_id, $wishlist_id
        ));
        return (bool) $result;
    }

    /**
     * Safely start a session if possible
     *
     * @return bool Whether session was successfully started
     */
    private function safe_session_start() {
        // Check if we can start a session safely
        if ( ! headers_sent() && ! session_id() ) {
            try {
                session_start();
                return true;
            } catch ( Exception $e ) {
                // Session start failed, log the error
                error_log( 'Advanced WC Wishlist: Could not start session: ' . $e->getMessage() );
                return false;
            }
        }
        return session_id() ? true : false;
    }

    /**
     * Get user info for wishlist operations
     *
     * @return array
     */
    public function get_user_info() {
        $user_id = get_current_user_id();
        $session_id = null;

        if ( ! $user_id ) {
            // Try to start session safely
            $this->safe_session_start();
            
            // Only use session_id if session was successfully started
            if ( session_id() ) {
                $session_id = session_id();
            }
        }

        return array(
            'user_id' => $user_id,
            'session_id' => $session_id,
        );
    }

    /**
     * Validate product
     *
     * @param int $product_id Product ID
     * @return bool
     */
    private function is_valid_product( $product_id ) {
        $product = wc_get_product( $product_id );
        return $product && $product->is_visible();
    }

    /**
     * Transfer guest wishlist to user
     *
     * @param string $session_id Session ID
     * @param int    $user_id User ID
     * @return bool
     */
    public function transfer_guest_wishlist( $session_id, $user_id ) {
        global $wpdb;

        if ( ! $session_id || ! $user_id ) {
            return false;
        }

        // Get guest wishlists
        $guest_wishlists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->lists_table_name} WHERE session_id = %s",
                $session_id
            )
        );

        if ( empty( $guest_wishlists ) ) {
            return true;
        }

        // Transfer each wishlist
        foreach ( $guest_wishlists as $guest_wishlist ) {
            // Create new wishlist for user
            $new_wishlist_id = $this->create_wishlist( $guest_wishlist->name, $user_id );
            
            if ( $new_wishlist_id ) {
                // Get items from guest wishlist
                $guest_items = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT product_id FROM {$this->table_name} WHERE wishlist_id = %d",
                        $guest_wishlist->id
                    )
                );

                // Transfer each item
                foreach ( $guest_items as $item ) {
                    $this->add_to_wishlist( $item->product_id, $new_wishlist_id );
                }

                // Delete guest wishlist
                $this->delete_wishlist( $guest_wishlist->id );
            }
        }

        return true;
    }

    /**
     * Clean expired wishlist items
     *
     * @param int $days Number of days to keep items
     * @return int Number of deleted items
     */
    public function clean_expired_items( $days = 30 ) {
        global $wpdb;

        $expiry_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Get guest wishlists older than expiry date
        $expired_wishlists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$this->lists_table_name} WHERE session_id IS NOT NULL AND date_created < %s",
                $expiry_date
            )
        );

        $deleted_count = 0;

        foreach ( $expired_wishlists as $wishlist ) {
            // Delete wishlist items
            $wpdb->delete( $this->table_name, array( 'wishlist_id' => $wishlist->id ) );
            // Delete wishlist
            $wpdb->delete( $this->lists_table_name, array( 'id' => $wishlist->id ) );
            $deleted_count++;
        }

        return $deleted_count;
    }

    /**
     * Get popular wishlisted products
     *
     * @param int $limit Number of products to return
     * @return array
     */
    public function get_popular_wishlisted_products( $limit = 10 ) {
        global $wpdb;

        $sql = "SELECT w.product_id, COUNT(*) as wishlist_count, p.post_title as product_name 
                FROM {$this->table_name} w 
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
                WHERE p.post_status = 'publish' 
                GROUP BY w.product_id 
                ORDER BY wishlist_count DESC 
                LIMIT %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $limit ) );
    }

    /**
     * Get wishlist analytics
     *
     * @return array
     */
    public function get_analytics() {
        global $wpdb;

        $analytics = array();

        // Total wishlist items
        $analytics['total_items'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

        // Total wishlists
        $analytics['total_wishlists'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->lists_table_name}" );

        // Total unique users
        $analytics['unique_users'] = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$this->lists_table_name} WHERE user_id IS NOT NULL" );

        // Total guest sessions
        $analytics['guest_sessions'] = $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$this->lists_table_name} WHERE session_id IS NOT NULL" );

        // Items added today
        $analytics['items_today'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE DATE(date_added) = %s",
                current_time( 'Y-m-d' )
            )
        );

        // Items added this week
        $analytics['items_this_week'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE YEARWEEK(date_added) = YEARWEEK(%s)",
                current_time( 'Y-m-d' )
            )
        );

        // Items added this month
        $analytics['items_this_month'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE YEAR(date_added) = YEAR(%s) AND MONTH(date_added) = MONTH(%s)",
                current_time( 'Y-m-d' ),
                current_time( 'Y-m-d' )
            )
        );

        return $analytics;
    }

    /**
     * Get wishlist count by product
     *
     * @param int $product_id Product ID
     * @return int
     */
    public function get_wishlist_count_by_product( $product_id ) {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE product_id = %d",
                $product_id
            )
        );

        return (int) $result;
    }

    /**
     * Export wishlist data
     *
     * @param string $format Export format (csv, json)
     * @return string|array
     */
    public function export_wishlist_data( $format = 'csv' ) {
        global $wpdb;

        $sql = "SELECT w.*, p.post_title as product_name, u.user_login, u.user_email, l.name as wishlist_name 
                FROM {$this->table_name} w 
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID 
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
                LEFT JOIN {$this->lists_table_name} l ON w.wishlist_id = l.id 
                ORDER BY w.date_added DESC";

        $data = $wpdb->get_results( $sql, ARRAY_A );

        if ( $format === 'json' ) {
            return $data;
        }

        // CSV format
        if ( empty( $data ) ) {
            return '';
        }

        $output = fopen( 'php://temp', 'r+' );
        fputcsv( $output, array_keys( $data[0] ) ); // Headers

        foreach ( $data as $row ) {
            fputcsv( $output, $row );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    /**
     * Get wishlist items by session ID (for guest users)
     */
    public function get_wishlist_items_by_session( $session_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aww_wishlists';
        
        $sql = $wpdb->prepare(
            "SELECT w.* FROM {$table_name} w 
             INNER JOIN {$wpdb->prefix}aww_wishlist_lists l ON w.wishlist_id = l.id 
             WHERE l.session_id = %s 
             ORDER BY w.date_added DESC",
            $session_id
        );
        
        return $wpdb->get_results( $sql );
    }

    /**
     * Delete wishlist items by session ID (for guest users)
     */
    public function delete_wishlist_items_by_session( $session_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aww_wishlists';
        
        $sql = $wpdb->prepare(
            "DELETE w FROM {$table_name} w 
             INNER JOIN {$wpdb->prefix}aww_wishlist_lists l ON w.wishlist_id = l.id 
             WHERE l.session_id = %s",
            $session_id
        );
        
        return $wpdb->query( $sql );
    }
} 