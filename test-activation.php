<?php
/**
 * Test file to check plugin activation
 */

// Simulate WordPress environment
define('ABSPATH', dirname(__FILE__) . '/../../../');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/');

// WordPress function stubs
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Stub function
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Stub function
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        // Stub function
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        // Stub function
    }
}

if (!function_exists('class_exists')) {
    function class_exists($class, $autoload = true) {
        return \class_exists($class, $autoload);
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce';
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        echo htmlspecialchars($text);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES);
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return htmlspecialchars($url);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text) {
        return trim(strip_tags($text));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('session_id')) {
    function session_id($id = null) {
        if ($id !== null) {
            return session_id($id);
        }
        return 'test_session_id';
    }
}

if (!function_exists('session_start')) {
    function session_start() {
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = 0) {
        return date('Y-m-d H:i:s');
    }
}

// Include the main plugin file
require_once __DIR__ . '/advanced-wc-wishlist.php';

echo "Plugin loaded successfully!\n";
echo "Plugin version: " . AWW_VERSION . "\n";
echo "Plugin directory: " . AWW_PLUGIN_DIR . "\n";
echo "Plugin URL: " . AWW_PLUGIN_URL . "\n";

// Test if classes are loaded
if (class_exists('Advanced_WC_Wishlist')) {
    echo "Main plugin class loaded successfully!\n";
}

if (class_exists('AWW_Database')) {
    echo "Database class loaded successfully!\n";
}

if (class_exists('AWW_Core')) {
    echo "Core class loaded successfully!\n";
}

if (class_exists('AWW_Ajax')) {
    echo "AJAX class loaded successfully!\n";
}

if (class_exists('AWW_Shortcodes')) {
    echo "Shortcodes class loaded successfully!\n";
}

if (class_exists('AWW_Admin')) {
    echo "Admin class loaded successfully!\n";
}

echo "All tests passed!\n"; 