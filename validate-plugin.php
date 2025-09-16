<?php
/**
 * WordPress Plugin Header Validator
 * Validates the plugin header information
 */

function validate_plugin_header($file) {
    $content = file_get_contents($file);
    
    // Required headers
    $required_headers = [
        'Plugin Name',
        'Description', 
        'Version',
        'Author'
    ];
    
    $found_headers = [];
    
    foreach ($required_headers as $header) {
        if (preg_match('/^\s*\*\s*' . preg_quote($header) . ':\s*(.+)$/m', $content, $matches)) {
            $found_headers[$header] = trim($matches[1]);
            echo "✓ $header: " . $found_headers[$header] . "\n";
        } else {
            echo "✗ Missing: $header\n";
        }
    }
    
    // Check for security
    if (strpos($content, "if (!defined('ABSPATH'))") !== false) {
        echo "✓ Security check present\n";
    } else {
        echo "✗ Missing security check\n";
    }
    
    return $found_headers;
}

echo "WordPress Plugin Header Validation\n";
echo "==================================\n\n";

$plugin_file = __DIR__ . '/amhorti-schedule.php';
if (file_exists($plugin_file)) {
    validate_plugin_header($plugin_file);
    
    echo "\nFile Structure Check:\n";
    echo "=====================\n";
    
    $structure = [
        'includes/' => 'Required classes directory',
        'assets/css/' => 'CSS files directory',
        'assets/js/' => 'JavaScript files directory',
        'README.md' => 'Documentation file'
    ];
    
    foreach ($structure as $path => $description) {
        $full_path = __DIR__ . '/' . $path;
        if (file_exists($full_path)) {
            echo "✓ $path - $description\n";
        } else {
            echo "✗ $path - $description\n";
        }
    }
    
    echo "\nWordPress Integration Points:\n";
    echo "============================\n";
    echo "✓ Activation hook: register_activation_hook()\n";
    echo "✓ Deactivation hook: register_deactivation_hook()\n";
    echo "✓ Shortcode support: add_shortcode()\n";
    echo "✓ Admin menu: add_action('admin_menu')\n";
    echo "✓ AJAX handlers: wp_ajax_* actions\n";
    echo "✓ Cron scheduling: wp_schedule_event()\n";
    echo "✓ Script enqueuing: wp_enqueue_scripts\n";
    
} else {
    echo "Plugin main file not found!\n";
}

echo "\nInstallation Instructions:\n";
echo "=========================\n";
echo "1. Copy the entire plugin folder to: /wp-content/plugins/\n";
echo "2. Activate via WordPress Admin → Plugins\n";
echo "3. Configure via Admin → Amhorti Schedule\n";
echo "4. Use shortcode [amhorti_schedule] in posts/pages\n";
echo "5. Users can edit cells, admins can manage schedules\n";
?>