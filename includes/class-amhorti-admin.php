<?php
/**
 * Admin interface class
 */
class Amhorti_Admin {
    
    private $database;
    
    public function __construct() {
        $this->database = new Amhorti_Database();
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_amhorti_admin_save_sheet', array($this, 'ajax_save_sheet'));
        add_action('wp_ajax_amhorti_admin_save_schedule', array($this, 'ajax_save_schedule'));
        add_action('wp_ajax_amhorti_admin_delete_sheet', array($this, 'ajax_delete_sheet'));
        add_action('wp_ajax_amhorti_admin_delete_schedule', array($this, 'ajax_delete_schedule'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Amhorti Schedule',
            'Amhorti Schedule',
            'manage_options',
            'amhorti-schedule',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            26
        );
        
        add_submenu_page(
            'amhorti-schedule',
            'Manage Sheets',
            'Manage Sheets',
            'manage_options',
            'amhorti-sheets',
            array($this, 'sheets_page')
        );
        
        add_submenu_page(
            'amhorti-schedule',
            'Manage Schedules',
            'Manage Schedules',
            'manage_options',
            'amhorti-schedules',
            array($this, 'schedules_page')
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="amhorti-admin-content">
                <div class="card">
                    <h2>Welcome to Amhorti Schedule</h2>
                    <p>This plugin creates Excel-like scheduling tables with multiple sheets for booking time slots.</p>
                    
                    <h3>How to use:</h3>
                    <ol>
                        <li>Use the shortcode <code>[amhorti_schedule]</code> to display the schedule table on any page or post</li>
                        <li>Use the shortcode with a specific sheet: <code>[amhorti_schedule sheet="1"]</code></li>
                        <li>Manage your sheets and schedules using the menu items</li>
                    </ol>
                    
                    <h3>Features:</h3>
                    <ul>
                        <li>Excel-like table interface with tabs for different sheets</li>
                        <li>7-day view starting from current date</li>
                        <li>Editable cells for user bookings</li>
                        <li>Automatic cleanup of old bookings (14 days)</li>
                        <li>Responsive design for mobile and desktop</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sheets management page
     */
    public function sheets_page() {
        global $wpdb;
        $sheets = $this->database->get_sheets();
        
        ?>
        <div class="wrap">
            <h1>Manage Sheets</h1>
            
            <div class="amhorti-admin-content">
                <div class="card">
                    <h2>Add New Sheet</h2>
                    <form id="amhorti-add-sheet-form">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Sheet Name</th>
                                <td>
                                    <input type="text" name="sheet_name" id="sheet_name" required class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Sort Order</th>
                                <td>
                                    <input type="number" name="sort_order" id="sort_order" value="<?php echo count($sheets) + 1; ?>" class="small-text" />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Sheet" />
                        </p>
                    </form>
                </div>
                
                <div class="card">
                    <h2>Existing Sheets</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Sort Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sheets as $sheet): ?>
                            <tr>
                                <td><?php echo esc_html($sheet->id); ?></td>
                                <td><?php echo esc_html($sheet->name); ?></td>
                                <td><?php echo esc_html($sheet->sort_order); ?></td>
                                <td><?php echo $sheet->is_active ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <button class="button edit-sheet" data-id="<?php echo esc_attr($sheet->id); ?>">Edit</button>
                                    <button class="button button-link-delete delete-sheet" data-id="<?php echo esc_attr($sheet->id); ?>">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#amhorti-add-sheet-form').on('submit', function(e) {
                e.preventDefault();
                var data = {
                    action: 'amhorti_admin_save_sheet',
                    sheet_name: $('#sheet_name').val(),
                    sort_order: $('#sort_order').val(),
                    nonce: $('#amhorti_admin_nonce').val()
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            $('.delete-sheet').on('click', function() {
                if (confirm('Are you sure you want to delete this sheet?')) {
                    var data = {
                        action: 'amhorti_admin_delete_sheet',
                        sheet_id: $(this).data('id'),
                        nonce: $('#amhorti_admin_nonce').val()
                    };
                    
                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Schedules management page
     */
    public function schedules_page() {
        $days = array('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche');
        
        ?>
        <div class="wrap">
            <h1>Manage Schedules</h1>
            
            <div class="amhorti-admin-content">
                <div class="card">
                    <h2>Add New Time Slot</h2>
                    <form id="amhorti-add-schedule-form">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Day of Week</th>
                                <td>
                                    <select name="day_of_week" id="day_of_week" required>
                                        <?php foreach ($days as $day): ?>
                                        <option value="<?php echo esc_attr($day); ?>"><?php echo ucfirst($day); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Start Time</th>
                                <td>
                                    <input type="time" name="time_start" id="time_start" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">End Time</th>
                                <td>
                                    <input type="time" name="time_end" id="time_end" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Number of Slots</th>
                                <td>
                                    <input type="number" name="slot_count" id="slot_count" value="2" min="1" max="10" required />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Time Slot" />
                        </p>
                    </form>
                </div>
                
                <?php foreach ($days as $day): ?>
                    <?php $schedules = $this->database->get_schedule_for_day($day); ?>
                    <div class="card">
                        <h2><?php echo ucfirst($day); ?></h2>
                        <?php if (!empty($schedules)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Slots</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo esc_html($schedule->time_start); ?></td>
                                    <td><?php echo esc_html($schedule->time_end); ?></td>
                                    <td><?php echo esc_html($schedule->slot_count); ?></td>
                                    <td><?php echo $schedule->is_active ? 'Active' : 'Inactive'; ?></td>
                                    <td>
                                        <button class="button edit-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Edit</button>
                                        <button class="button button-link-delete delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>No time slots configured for this day.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#amhorti-add-schedule-form').on('submit', function(e) {
                e.preventDefault();
                var data = {
                    action: 'amhorti_admin_save_schedule',
                    day_of_week: $('#day_of_week').val(),
                    time_start: $('#time_start').val(),
                    time_end: $('#time_end').val(),
                    slot_count: $('#slot_count').val(),
                    nonce: $('#amhorti_admin_nonce').val()
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            $('.delete-schedule').on('click', function() {
                if (confirm('Are you sure you want to delete this time slot?')) {
                    var data = {
                        action: 'amhorti_admin_delete_schedule',
                        schedule_id: $(this).data('id'),
                        nonce: $('#amhorti_admin_nonce').val()
                    };
                    
                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for saving sheets
     */
    public function ajax_save_sheet() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_sheets = $wpdb->prefix . 'amhorti_sheets';
        
        $result = $wpdb->insert(
            $table_sheets,
            array(
                'name' => sanitize_text_field($_POST['sheet_name']),
                'sort_order' => intval($_POST['sort_order'])
            )
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save sheet');
        }
    }
    
    /**
     * AJAX handler for saving schedules
     */
    public function ajax_save_schedule() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        
        $result = $wpdb->insert(
            $table_schedules,
            array(
                'day_of_week' => sanitize_text_field($_POST['day_of_week']),
                'time_start' => sanitize_text_field($_POST['time_start']),
                'time_end' => sanitize_text_field($_POST['time_end']),
                'slot_count' => intval($_POST['slot_count'])
            )
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save schedule');
        }
    }
    
    /**
     * AJAX handler for deleting sheets
     */
    public function ajax_delete_sheet() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_sheets = $wpdb->prefix . 'amhorti_sheets';
        
        $result = $wpdb->update(
            $table_sheets,
            array('is_active' => 0),
            array('id' => intval($_POST['sheet_id']))
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete sheet');
        }
    }
    
    /**
     * AJAX handler for deleting schedules
     */
    public function ajax_delete_schedule() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        
        $result = $wpdb->update(
            $table_schedules,
            array('is_active' => 0),
            array('id' => intval($_POST['schedule_id']))
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete schedule');
        }
    }
}