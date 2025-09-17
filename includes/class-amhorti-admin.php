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
        add_action('wp_ajax_amhorti_admin_update_sheet', array($this, 'ajax_update_sheet'));
        add_action('wp_ajax_amhorti_admin_add_sheet_schedule', array($this, 'ajax_add_sheet_schedule'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Planification Amhorti',
            'Planification Amhorti',
            'manage_options',
            'amhorti-schedule',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            26
        );
        
        add_submenu_page(
            'amhorti-schedule',
            'Gérer les Feuilles',
            'Gérer les Feuilles',
            'manage_options',
            'amhorti-sheets',
            array($this, 'sheets_page')
        );
        
        add_submenu_page(
            'amhorti-schedule',
            'Gérer les Horaires',
            'Gérer les Horaires',
            'manage_options',
            'amhorti-schedules',
            array($this, 'schedules_page')
        );
        
        add_submenu_page(
            'amhorti-schedule',
            'Configuration Avancée',
            'Configuration Avancée',
            'manage_options',
            'amhorti-advanced',
            array($this, 'advanced_page')
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
            <h1>Gérer les Feuilles</h1>
            
            <div class="amhorti-admin-content">
                <div class="card">
                    <h2>Ajouter une Nouvelle Feuille</h2>
                    <form id="amhorti-add-sheet-form">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Nom de la Feuille</th>
                                <td>
                                    <input type="text" name="sheet_name" id="sheet_name" required class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Ordre de Tri</th>
                                <td>
                                    <input type="number" name="sort_order" id="sort_order" value="<?php echo count($sheets) + 1; ?>" class="small-text" />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter Feuille" />
                        </p>
                    </form>
                </div>
                
                <div class="card">
                    <h2>Feuilles Existantes</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Ordre de Tri</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sheets as $sheet): ?>
                            <tr>
                                <td><?php echo esc_html($sheet->id); ?></td>
                                <td><?php echo esc_html($sheet->name); ?></td>
                                <td><?php echo esc_html($sheet->sort_order); ?></td>
                                <td><?php echo $sheet->is_active ? 'Actif' : 'Inactif'; ?></td>
                                <td>
                                    <button class="button edit-sheet" data-id="<?php echo esc_attr($sheet->id); ?>">Modifier</button>
                                    <button class="button button-link-delete delete-sheet" data-id="<?php echo esc_attr($sheet->id); ?>">Supprimer</button>
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
            <h1>Gérer les Horaires</h1>
            
            <div class="amhorti-admin-content">
                <div class="card">
                    <h2>Ajouter un Nouveau Créneau Horaire</h2>
                    <form id="amhorti-add-schedule-form">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Jour de la Semaine</th>
                                <td>
                                    <select name="day_of_week" id="day_of_week" required>
                                        <?php foreach ($days as $day): ?>
                                        <option value="<?php echo esc_attr($day); ?>"><?php echo ucfirst($day); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Heure de Début</th>
                                <td>
                                    <input type="time" name="time_start" id="time_start" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Heure de Fin</th>
                                <td>
                                    <input type="time" name="time_end" id="time_end" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Nombre de Créneaux</th>
                                <td>
                                    <input type="number" name="slot_count" id="slot_count" value="2" min="1" max="10" required />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter Créneau" />
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
    
    /**
     * AJAX handler for updating sheet configuration
     */
    public function ajax_update_sheet() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_sheets = $wpdb->prefix . 'amhorti_sheets';
        
        $sheet_id = intval($_POST['sheet_id']);
        $sheet_name = sanitize_text_field($_POST['sheet_name']);
        $active_days = isset($_POST['active_days']) ? $_POST['active_days'] : array();
        
        $result = $wpdb->update(
            $table_sheets,
            array(
                'name' => $sheet_name,
                'days_config' => json_encode($active_days)
            ),
            array('id' => $sheet_id)
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Échec de la mise à jour de la feuille');
        }
    }
    
    /**
     * AJAX handler for adding sheet-specific schedules
     */
    public function ajax_add_sheet_schedule() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        
        $result = $wpdb->insert(
            $table_schedules,
            array(
                'sheet_id' => intval($_POST['sheet_id']),
                'day_of_week' => sanitize_text_field($_POST['day_of_week']),
                'time_start' => sanitize_text_field($_POST['time_start']),
                'time_end' => sanitize_text_field($_POST['time_end']),
                'slot_count' => intval($_POST['slot_count'])
            )
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Échec de l\'ajout de l\'horaire');
        }
    }
    
    /**
     * Advanced configuration page
     */
    public function advanced_page() {
        $sheets = $this->database->get_sheets();
        $days_options = array(
            'lundi' => 'Lundi',
            'mardi' => 'Mardi', 
            'mercredi' => 'Mercredi',
            'jeudi' => 'Jeudi',
            'vendredi' => 'Vendredi',
            'samedi' => 'Samedi',
            'dimanche' => 'Dimanche'
        );
        
        ?>
        <div class="wrap">
            <h1>Configuration Avancée des Feuilles</h1>
            
            <div class="amhorti-admin-content">
                <?php foreach ($sheets as $sheet): ?>
                <div class="card">
                    <h2>Configuration de "<?php echo esc_html($sheet->name); ?>"</h2>
                    <form class="amhorti-sheet-config-form" data-sheet-id="<?php echo esc_attr($sheet->id); ?>">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Nom de la Feuille</th>
                                <td>
                                    <input type="text" name="sheet_name" value="<?php echo esc_attr($sheet->name); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Jours de la Semaine Actifs</th>
                                <td>
                                    <?php 
                                    $active_days = json_decode($sheet->days_config, true) ?: array_keys($days_options);
                                    foreach ($days_options as $day_key => $day_label): ?>
                                    <label>
                                        <input type="checkbox" name="active_days[]" value="<?php echo esc_attr($day_key); ?>" 
                                               <?php checked(in_array($day_key, $active_days)); ?> />
                                        <?php echo esc_html($day_label); ?>
                                    </label><br>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Sauvegarder Configuration" />
                        </p>
                    </form>
                    
                    <h3>Horaires Spécifiques à cette Feuille</h3>
                    <form class="amhorti-sheet-schedule-form" data-sheet-id="<?php echo esc_attr($sheet->id); ?>">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce_schedule_' . $sheet->id); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Jour</th>
                                <td>
                                    <select name="day_of_week" required>
                                        <option value="">Sélectionnez un jour</option>
                                        <?php foreach ($days_options as $day_key => $day_label): ?>
                                        <option value="<?php echo esc_attr($day_key); ?>"><?php echo esc_html($day_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Heure de début</th>
                                <td>
                                    <input type="time" name="time_start" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Heure de fin</th>
                                <td>
                                    <input type="time" name="time_end" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Nombre de créneaux</th>
                                <td>
                                    <input type="number" name="slot_count" min="1" max="20" value="1" required />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Ajouter Horaire" />
                        </p>
                    </form>
                    
                    <h4>Horaires existants pour cette feuille</h4>
                    <div class="existing-schedules">
                        <?php
                        global $wpdb;
                        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
                        $schedules = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $table_schedules WHERE sheet_id = %d AND is_active = 1 ORDER BY day_of_week, time_start",
                            $sheet->id
                        ));
                        
                        if ($schedules): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Heure de début</th>
                                    <th>Heure de fin</th>
                                    <th>Créneaux</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo esc_html($days_options[$schedule->day_of_week] ?? $schedule->day_of_week); ?></td>
                                    <td><?php echo esc_html($schedule->time_start); ?></td>
                                    <td><?php echo esc_html($schedule->time_end); ?></td>
                                    <td><?php echo esc_html($schedule->slot_count); ?></td>
                                    <td>
                                        <button class="button button-link-delete delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Supprimer</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>Aucun horaire spécifique configuré pour cette feuille.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.amhorti-sheet-config-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var sheetId = form.data('sheet-id');
                
                var data = {
                    action: 'amhorti_admin_update_sheet',
                    sheet_id: sheetId,
                    sheet_name: form.find('input[name="sheet_name"]').val(),
                    active_days: [],
                    nonce: form.find('input[name="amhorti_admin_nonce"]').val()
                };
                
                form.find('input[name="active_days[]"]:checked').each(function() {
                    data.active_days.push($(this).val());
                });
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        alert('Configuration sauvegardée avec succès !');
                    } else {
                        alert('Erreur : ' + response.data);
                    }
                });
            });
            
            $('.amhorti-sheet-schedule-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var sheetId = form.data('sheet-id');
                
                var data = {
                    action: 'amhorti_admin_add_sheet_schedule',
                    sheet_id: sheetId,
                    day_of_week: form.find('select[name="day_of_week"]').val(),
                    time_start: form.find('input[name="time_start"]').val(),
                    time_end: form.find('input[name="time_end"]').val(),
                    slot_count: form.find('input[name="slot_count"]').val(),
                    nonce: form.find('input[name="amhorti_admin_nonce"]').val()
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        alert('Horaire ajouté avec succès !');
                        location.reload();
                    } else {
                        alert('Erreur : ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
}