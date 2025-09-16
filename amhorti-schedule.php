<?php
/**
 * Plugin Name: Amhorti Schedule
 * Plugin URI: https://github.com/enji76200-del/Plugin-Amhorti
 * Description: A WordPress plugin that creates an Excel-like scheduling table with multiple sheets for booking time slots.
 * Version: 1.0.0
 * Author: Amhorti
 * License: GPL v2 or later
 * Text Domain: amhorti-schedule
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AMHORTI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AMHORTI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AMHORTI_VERSION', '1.0.0');

// Include required files
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-schedule.php';
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-database.php';
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-admin.php';
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-public.php';

// Initialize the plugin
function amhorti_schedule_init() {
    $plugin = new Amhorti_Schedule();
    $plugin->run();
}
add_action('plugins_loaded', 'amhorti_schedule_init');

// Activation hook
register_activation_hook(__FILE__, 'amhorti_schedule_activate');
function amhorti_schedule_activate() {
    $database = new Amhorti_Database();
    $database->create_tables();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'amhorti_schedule_deactivate');
function amhorti_schedule_deactivate() {
    // Clean up if needed
}