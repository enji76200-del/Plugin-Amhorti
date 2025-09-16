<?php
/**
 * Main plugin class
 */
class Amhorti_Schedule {
    
    private $admin;
    private $public;
    private $database;
    
    public function __construct() {
        $this->database = new Amhorti_Database();
        $this->admin = new Amhorti_Admin();
        $this->public = new Amhorti_Public();
    }
    
    public function run() {
        // Hook into WordPress
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize admin and public classes
        $this->admin->init();
        $this->public->init();
        
        // Register shortcode
        add_shortcode('amhorti_schedule', array($this->public, 'render_shortcode'));
        
        // Schedule cleanup
        if (!wp_next_scheduled('amhorti_cleanup_hook')) {
            wp_schedule_event(time(), 'daily', 'amhorti_cleanup_hook');
        }
        add_action('amhorti_cleanup_hook', array($this->database, 'cleanup_old_bookings'));
    }
    
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('amhorti-schedule', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_public_scripts() {
        wp_enqueue_style('amhorti-public-css', AMHORTI_PLUGIN_URL . 'assets/css/public.css', array(), AMHORTI_VERSION);
        wp_enqueue_script('amhorti-public-js', AMHORTI_PLUGIN_URL . 'assets/js/public.js', array('jquery'), AMHORTI_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('amhorti-public-js', 'amhorti_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amhorti_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'amhorti') === false) {
            return;
        }
        
        wp_enqueue_style('amhorti-admin-css', AMHORTI_PLUGIN_URL . 'assets/css/admin.css', array(), AMHORTI_VERSION);
        wp_enqueue_script('amhorti-admin-js', AMHORTI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AMHORTI_VERSION, true);
        
        wp_localize_script('amhorti-admin-js', 'amhorti_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amhorti_admin_nonce')
        ));
    }
}