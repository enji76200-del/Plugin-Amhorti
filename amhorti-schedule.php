<?php
/**
 * Plugin Name: Amhorti Schedule
 * Plugin URI: https://github.com/enji76200-del/Plugin-Amhorti
 * Description: A WordPress plugin that creates an Excel-like scheduling table with multiple sheets for booking time slots.
 * Version: 1.2.0
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
define('AMHORTI_VERSION', '1.2.0');

// Include required files
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-schedule.php';
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-database.php';
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-admin.php';
require_once AMHORTI_PLUGIN_PATH . 'includes/class-amhorti-public.php';

// Initialize the plugin
function amhorti_schedule_init() {
    // Ensure DB schema is up-to-date even without reactivation
    $db = new Amhorti_Database();
    if (method_exists($db, 'ensure_schema')) {
        $db->ensure_schema();
    }
    $plugin = new Amhorti_Schedule();
    $plugin->run();
}
add_action('plugins_loaded', 'amhorti_schedule_init');

/**
 * Ensure custom roles and capabilities for the plugin exist
 */
function amhorti_ensure_roles_caps() {
    // Custom capability for this plugin
    $cap = 'manage_amhorti';

    // Add role Organisateur if missing
    if (!get_role('organisateur')) {
        add_role('organisateur', __('Organisateur', 'amhorti-schedule'), array('read' => true));
    }

    // Grant capability to Administrator and Organisateur
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap($cap)) {
        $admin->add_cap($cap);
    }
    $orga = get_role('organisateur');
    if ($orga && !$orga->has_cap($cap)) {
        $orga->add_cap($cap);
    }
}
add_action('init', 'amhorti_ensure_roles_caps');

// Activation hook
register_activation_hook(__FILE__, 'amhorti_schedule_activate');
function amhorti_schedule_activate() {
    $database = new Amhorti_Database();
    $database->ensure_schema();
    $database->create_tables();

    // Ensure roles and capabilities are set on activation
    amhorti_ensure_roles_caps();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'amhorti_schedule_deactivate');
function amhorti_schedule_deactivate() {
    // Clean up if needed
}