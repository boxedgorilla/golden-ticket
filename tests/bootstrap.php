<?php
// Basic test bootstrap for golden-ticket plugin.

// Prevent plugin from exiting if ABSPATH is not defined.
define('ABSPATH', __DIR__);

// Simple stubs for WordPress functions used during plugin loading.
if (!function_exists('add_filter')) {
    function add_filter(...$args) {}
}
if (!function_exists('add_action')) {
    function add_action(...$args) {}
}
if (!function_exists('register_setting')) {
    function register_setting(...$args) {}
}
if (!function_exists('add_options_page')) {
    function add_options_page(...$args) {}
}
if (!function_exists('plugin_basename')) {
    function plugin_basename($file) { return $file; }
}

// Stubs used by fle_sanitize_page_list
if (!function_exists('get_option')) {
    function get_option($name, $default = '') {
        return isset($GLOBALS['options'][$name]) ? $GLOBALS['options'][$name] : $default;
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return $str; }
}
if (!function_exists('absint')) {
    function absint($value) { return abs(intval($value)); }
}

// Include the plugin file once for all tests.
require_once __DIR__ . '/../golden-ticket.php';
