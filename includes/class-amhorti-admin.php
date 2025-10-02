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
        add_action('wp_ajax_amhorti_admin_save_css', array($this, 'ajax_save_css'));
        add_action('wp_ajax_amhorti_admin_get_css', array($this, 'ajax_get_css'));
        add_action('wp_ajax_amhorti_admin_add_sheet_schedule', array($this, 'ajax_add_sheet_schedule'));
        add_action('wp_ajax_amhorti_admin_update_schedule', array($this, 'ajax_update_schedule'));
    add_action('wp_ajax_amhorti_admin_bulk_update_time_range', array($this, 'ajax_bulk_update_time_range'));
    add_action('wp_ajax_amhorti_admin_bulk_delete_schedules', array($this, 'ajax_bulk_delete_schedules'));
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
        
        add_submenu_page(
            'amhorti-schedule',
            'Éditeur CSS',
            'Éditeur CSS',
            'manage_options',
            'amhorti-css',
            array($this, 'css_editor_page')
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
                    <h2>Bienvenue dans Planification Amhorti</h2>
                    <p>Ce plugin crée des tableaux de planification similaires à Excel avec plusieurs feuilles pour la réservation de créneaux horaires.</p>
                    
                    <h3>Comment utiliser :</h3>
                    <ol>
                        <li>Utilisez le shortcode <code>[amhorti_schedule]</code> pour afficher le tableau de planification sur n'importe quelle page ou article</li>
                        <li>Utilisez le shortcode avec une feuille spécifique : <code>[amhorti_schedule sheet="1"]</code></li>
                        <li>Gérez vos feuilles et horaires en utilisant les éléments du menu</li>
                    </ol>
                    
                    <h3>Fonctionnalités :</h3>
                    <ul>
                        <li>Interface similaire à Excel avec onglets pour différentes feuilles</li>
                        <li>Vue sur 7 jours à partir de la date actuelle</li>
                        <li>Cellules éditables pour les réservations d'utilisateurs</li>
                        <li>Nettoyage automatique des anciennes réservations (14 jours)</li>
                        <li>Design responsive pour mobile et desktop</li>
                        <li>Réservations limitées aux 7 prochains jours</li>
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
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter la Feuille" />
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
                if (confirm('Êtes-vous sûr de vouloir supprimer cette feuille ?')) {
                    var data = {
                        action: 'amhorti_admin_delete_sheet',
                        sheet_id: $(this).data('id'),
                        nonce: $('#amhorti_admin_nonce').val()
                    };
                    
                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erreur : ' + response.data);
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
    $sheets = $this->database->get_sheets();
        
        ?>
        <div class="wrap">
            <h1>Gérer les Horaires</h1>
            
            <div class="amhorti-admin-content">
                <div class="card">
                    <h2>Ajouter un Nouveau Créneau</h2>
                    <form id="amhorti-add-schedule-form">
                        <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Feuille</th>
                                <td>
                                    <select name="sheet_id" id="sheet_id" required>
                                        <?php foreach ($sheets as $sheet): ?>
                                            <option value="<?php echo esc_attr($sheet->id); ?>"><?php echo esc_html($sheet->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
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
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter le Créneau" />
                        </p>
                    </form>
                </div>
                
                <?php foreach ($days as $day): ?>
                    <?php // Display per sheet to avoid global confusion
                    ?>
                    <div class="card">
                        <h2><?php echo ucfirst($day); ?> — par Feuille</h2>
                        <?php foreach ($sheets as $sheet): $schedules = $this->database->get_schedules_for_sheet($sheet->id); $daySchedules = array_filter($schedules, function($s) use ($day){ return $s->day_of_week === $day; }); ?>
                        <h3><?php echo esc_html($sheet->name); ?></h3>
                        <?php if (!empty($daySchedules)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Heure de Début</th>
                                    <th>Heure de Fin</th>
                                    <th>Créneaux</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daySchedules as $schedule): ?>
                                <tr>
                                    <td><?php echo esc_html($schedule->time_start); ?></td>
                                    <td><?php echo esc_html($schedule->time_end); ?></td>
                                    <td><?php echo esc_html($schedule->slot_count); ?></td>
                                    <td><?php echo $schedule->is_active ? 'Actif' : 'Inactif'; ?></td>
                                    <td>
                                        <button class="button edit-schedule" data-id="<?php echo esc_attr($schedule->id); ?>" data-day="<?php echo esc_attr($schedule->day_of_week); ?>" data-start="<?php echo esc_attr($schedule->time_start); ?>" data-end="<?php echo esc_attr($schedule->time_end); ?>" data-slots="<?php echo esc_attr($schedule->slot_count); ?>">Modifier</button>
                                        <button class="button button-link-delete delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Supprimer</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>Aucun créneau horaire configuré pour ce jour.</p>
                        <?php endif; ?>
                        <?php endforeach; ?>
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
                    sheet_id: $('#sheet_id').val(),
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
                        alert('Erreur : ' + response.data);
                    }
                });
            });
            
            // Edit schedule (simple prompt-based editor)
            $(document).on('click', '.edit-schedule', function(){
                var id = $(this).data('id');
                var timeStart = prompt('Heure de début (HH:MM:SS)', $(this).data('start'));
                if(timeStart===null) return;
                var timeEnd = prompt('Heure de fin (HH:MM:SS)', $(this).data('end'));
                if(timeEnd===null) return;
                var slots = prompt('Nombre de créneaux', $(this).data('slots'));
                if(slots===null) return;
                $.post(ajaxurl, {
                    action: 'amhorti_admin_update_schedule',
                    schedule_id: id,
                    time_start: timeStart,
                    time_end: timeEnd,
                    slot_count: slots,
                    nonce: $('#amhorti_admin_nonce').val()
                }, function(resp){
                    if(resp.success){ location.reload(); }
                    else { alert('Erreur: '+resp.data); }
                });
            });

            $('.delete-schedule').on('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer ce créneau horaire ?')) {
                    var data = {
                        action: 'amhorti_admin_delete_schedule',
                        schedule_id: $(this).data('id'),
                        nonce: $('#amhorti_admin_nonce').val()
                    };
                    
                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erreur : ' + response.data);
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
        
        if (!current_user_can('manage_amhorti')) {
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
        
        if (!current_user_can('manage_amhorti')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        
        // Force per-sheet schedules; reject if missing sheet_id
        $sheet_id = isset($_POST['sheet_id']) ? intval($_POST['sheet_id']) : 0;
        if (!$sheet_id) {
            wp_send_json_error('Feuille manquante pour le créneau');
        }

        $result = $wpdb->insert(
            $table_schedules,
            array(
                'sheet_id' => $sheet_id,
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
        
        if (!current_user_can('manage_amhorti')) {
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
        
        if (!current_user_can('manage_amhorti')) {
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
                                    <hr />
                                    <?php $allow_beyond = isset($sheet->allow_beyond_7_days) ? intval($sheet->allow_beyond_7_days) : 0; ?>
                                    <label>
                                        <input type="checkbox" name="allow_beyond_7_days" value="1" <?php checked(1, $allow_beyond); ?> />
                                        Autoriser les inscriptions au-delà de +7 jours
                                    </label>
                                    <p style="margin-top:8px;">
                                        <?php $max_days = isset($sheet->max_booking_days) ? intval($sheet->max_booking_days) : 7; ?>
                                        <label>Nombre max de jours à l'avance
                                            <input type="number" name="max_booking_days" min="7" max="3650" value="<?php echo esc_attr($max_days); ?>" class="small-text" />
                                        </label>
                                        <span class="description">(>= 7, ex: 30, 60, 365)</span>
                                    </p>
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
                                        <?php foreach ($days_options as $day_key => $day_label): ?>
                                        <option value="<?php echo esc_attr($day_key); ?>"><?php echo esc_html($day_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Heure de Début</th>
                                <td><input type="time" name="time_start" required /></td>
                            </tr>
                            <tr>
                                <th scope="row">Heure de Fin</th>
                                <td><input type="time" name="time_end" required /></td>
                            </tr>
                            <tr>
                                <th scope="row">Nombre de Créneaux</th>
                                <td><input type="number" name="slot_count" value="2" min="1" max="10" required /></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button" value="Ajouter Horaire" />
                        </p>
                    </form>
                    
                    <div class="amhorti-sheet-schedules">
                        <h4>Horaires Existants</h4>
                        <?php $this->display_sheet_schedules($sheet->id); ?>
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
                var activeDays = [];
                form.find('input[name="active_days[]"]:checked').each(function() {
                    activeDays.push($(this).val());
                });
                
                var data = {
                    action: 'amhorti_admin_update_sheet',
                    sheet_id: sheetId,
                    sheet_name: form.find('input[name="sheet_name"]').val(),
                    active_days: activeDays,
                    allow_beyond_7_days: form.find('input[name="allow_beyond_7_days"]').is(':checked') ? 1 : 0,
            max_booking_days: form.find('input[name="max_booking_days"]').val(),
                    nonce: form.find('#amhorti_admin_nonce').val()
                };
                
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
                    nonce: form.find('input[name*="amhorti_admin_nonce"]').val()
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

            // Bulk delete schedules
            $(document).on('click', '.bulk-delete-schedules', function(){
                if(!confirm('Supprimer les horaires sélectionnés ?')) return;
                var container = $(this).closest('.card');
                var ids = [];
                container.find('.schedule-checkbox:checked').each(function(){ ids.push($(this).val()); });
                if(ids.length === 0){ alert('Aucun horaire sélectionné'); return; }
                $.post(ajaxurl, {
                    action: 'amhorti_admin_bulk_delete_schedules',
                    schedule_ids: ids,
                    nonce: $('#amhorti_admin_nonce').val()
                }, function(resp){
                    if(resp.success){ location.reload(); }
                    else { alert('Erreur: '+resp.data); }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * CSS Editor page
     */
    public function css_editor_page() {
        ?>
        <div class="wrap">
            <h1>Éditeur CSS</h1>
            
            <div class="amhorti-admin-content">
                <div class="amhorti-css-editor-container">
                    <div class="amhorti-css-editor-panel">
                        <h2>Éditeur CSS Personnalisé</h2>
                        <form id="amhorti-css-form">
                            <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                            <textarea id="amhorti-css-editor" name="css_content" rows="20" style="width: 100%; font-family: monospace;">/* Votre CSS personnalisé ici */
.amhorti-schedule-container {
    /* Styles personnalisés pour le conteneur principal */
}

.amhorti-schedule-table {
    /* Styles personnalisés pour le tableau */
}

.booking-cell {
    /* Styles personnalisés pour les cellules de réservation */
}

.booking-cell.editable {
    /* Styles pour les cellules éditables */
}

.booking-cell.disabled {
    /* Styles pour les cellules désactivées */
}

.amhorti-tab {
    /* Styles pour les onglets */
}

.amhorti-nav-btn {
    /* Styles pour les boutons de navigation */
}</textarea>
                            <p class="submit">
                                <input type="submit" class="button button-primary" value="Sauvegarder CSS" />
                                <button type="button" id="amhorti-css-preview" class="button">Prévisualiser</button>
                                <button type="button" id="amhorti-css-reset" class="button">Réinitialiser</button>
                            </p>
                        </form>
                    </div>
                    
                    <div class="amhorti-css-preview-panel">
                        <h2>Aperçu en Temps Réel</h2>
                        <div id="amhorti-preview-container">
                            <div class="amhorti-schedule-container" style="max-width: 100%; margin: 20px 0;">
                                <div class="amhorti-tabs">
                                    <button class="amhorti-tab active">Feuille 1</button>
                                    <button class="amhorti-tab">Feuille 2</button>
                                </div>
                                <table class="amhorti-schedule-table" style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th class="time-header">Horaires</th>
                                            <th class="date-header">Lundi 18/11</th>
                                            <th class="date-header">Mardi 19/11</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="time-cell">09:00 - 10:30</td>
                                            <td class="booking-cell editable" contenteditable="true">Exemple</td>
                                            <td class="booking-cell disabled"></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="amhorti-navigation">
                                    <button class="amhorti-nav-btn">← Semaine précédente</button>
                                    <button class="amhorti-nav-btn">Aujourd'hui</button>
                                    <button class="amhorti-nav-btn">Semaine suivante →</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .amhorti-css-editor-container {
            display: flex;
            gap: 20px;
        }
        .amhorti-css-editor-panel,
        .amhorti-css-preview-panel {
            flex: 1;
        }
        .amhorti-css-preview-panel {
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
            max-height: 600px;
            overflow-y: auto;
        }
        #amhorti-preview-container {
            background: white;
            padding: 15px;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Load existing CSS
            $.post(ajaxurl, {
                action: 'amhorti_admin_get_css',
                nonce: $('#amhorti_admin_nonce').val()
            }, function(response) {
                if (response.success && response.data.css) {
                    $('#amhorti-css-editor').val(response.data.css);
                    updatePreview();
                }
            });
            
            // Live preview
            $('#amhorti-css-editor').on('input', function() {
                updatePreview();
            });
            
            // Save CSS
            $('#amhorti-css-form').on('submit', function(e) {
                e.preventDefault();
                var data = {
                    action: 'amhorti_admin_save_css',
                    css_content: $('#amhorti-css-editor').val(),
                    nonce: $('#amhorti_admin_nonce').val()
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        alert('CSS sauvegardé avec succès !');
                    } else {
                        alert('Erreur : ' + response.data);
                    }
                });
            });
            
            // Reset CSS
            $('#amhorti-css-reset').on('click', function() {
                if (confirm('Êtes-vous sûr de vouloir réinitialiser le CSS ?')) {
                    $('#amhorti-css-editor').val('/* CSS réinitialisé */');
                    updatePreview();
                }
            });
            
            function updatePreview() {
                var css = $('#amhorti-css-editor').val();
                $('#amhorti-preview-container').find('style').remove();
                $('#amhorti-preview-container').append('<style>' + css + '</style>');
            }
        });
        </script>
        <?php
    }
    
    /**
     * Display schedules for a specific sheet
     */
    private function display_sheet_schedules($sheet_id) {
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_schedules WHERE sheet_id = %d AND is_active = 1 ORDER BY day_of_week, time_start",
            $sheet_id
        ));
        
        if (empty($schedules)) {
            echo '<p>Aucun horaire spécifique configuré pour cette feuille.</p>';
            return;
        }

        ?>
        <div class="amhorti-sheet-schedules-controls" style="margin-bottom:8px;">
            <button type="button" class="button bulk-delete-schedules" data-sheet-id="<?php echo esc_attr($sheet_id); ?>">Supprimer la sélection</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:36px;"><input type="checkbox" class="select-all-schedules" /></th>
                    <th>Jour</th>
                    <th>Heure de Début</th>
                    <th>Heure de Fin</th>
                    <th>Créneaux</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                <tr>
                    <td><input type="checkbox" class="schedule-checkbox" value="<?php echo esc_attr($schedule->id); ?>" /></td>
                    <td><?php echo esc_html(ucfirst($schedule->day_of_week)); ?></td>
                    <td><?php echo esc_html($schedule->time_start); ?></td>
                    <td><?php echo esc_html($schedule->time_end); ?></td>
                    <td><?php echo esc_html($schedule->slot_count); ?></td>
                    <td>
                        <button class="button edit-schedule" data-id="<?php echo esc_attr($schedule->id); ?>" data-day="<?php echo esc_attr($schedule->day_of_week); ?>" data-start="<?php echo esc_attr($schedule->time_start); ?>" data-end="<?php echo esc_attr($schedule->time_end); ?>" data-slots="<?php echo esc_attr($schedule->slot_count); ?>">Modifier</button>
                        <button class="button button-link-delete delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Supprimer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>
        (function($){
            // Select all
            $('.select-all-schedules').on('change', function(){
                var checked = $(this).is(':checked');
                $(this).closest('table').find('.schedule-checkbox').prop('checked', checked);
            });
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * AJAX handler for updating sheets
     */
    public function ajax_update_sheet() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_amhorti')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_sheets = $wpdb->prefix . 'amhorti_sheets';
        
        $sheet_id = intval($_POST['sheet_id']);
        $sheet_name = sanitize_text_field($_POST['sheet_name']);
    $active_days = isset($_POST['active_days']) ? $_POST['active_days'] : array();
    $allow_beyond_7_days = isset($_POST['allow_beyond_7_days']) ? intval($_POST['allow_beyond_7_days']) : 0;
    $max_booking_days = isset($_POST['max_booking_days']) ? max(7, intval($_POST['max_booking_days'])) : 7;
        
        $result = $wpdb->update(
            $table_sheets,
            array(
                'name' => $sheet_name,
                'days_config' => json_encode($active_days),
                'allow_beyond_7_days' => $allow_beyond_7_days,
                'max_booking_days' => $max_booking_days
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
     * AJAX handler for saving CSS
     */
    public function ajax_save_css() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_amhorti')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_css = $wpdb->prefix . 'amhorti_css_settings';
        
        $css_content = wp_unslash($_POST['css_content']);
        
        // Check if CSS record exists
        $existing = $wpdb->get_var("SELECT id FROM $table_css WHERE is_active = 1 LIMIT 1");
        
        if ($existing) {
            $result = $wpdb->update(
                $table_css,
                array('css_content' => $css_content),
                array('id' => $existing)
            );
        } else {
            $result = $wpdb->insert(
                $table_css,
                array('css_content' => $css_content)
            );
        }
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Échec de la sauvegarde du CSS');
        }
    }
    
    /**
     * AJAX handler for getting CSS
     */
    public function ajax_get_css() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_amhorti')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_css = $wpdb->prefix . 'amhorti_css_settings';
        
        $css = $wpdb->get_var("SELECT css_content FROM $table_css WHERE is_active = 1 LIMIT 1");
        
        wp_send_json_success(array('css' => $css ?: ''));
    }
    
    /**
     * AJAX handler for adding sheet-specific schedules
     */
    public function ajax_add_sheet_schedule() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_amhorti')) {
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
     * AJAX handler for updating an existing schedule (time range / slot count)
     */
    public function ajax_update_schedule() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        if (!current_user_can('manage_amhorti')) { wp_die('Unauthorized'); }
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        $schedule_id = intval($_POST['schedule_id']);
        $time_start = sanitize_text_field($_POST['time_start']);
        $time_end = sanitize_text_field($_POST['time_end']);
        $slot_count = intval($_POST['slot_count']);

        $result = $wpdb->update(
            $table_schedules,
            array(
                'time_start' => $time_start,
                'time_end' => $time_end,
                'slot_count' => $slot_count
            ),
            array('id' => $schedule_id)
        );
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Échec de la mise à jour du créneau');
        }
    }

    /**
     * Bulk delete selected schedules (soft delete: set is_active = 0)
     */
    public function ajax_bulk_delete_schedules() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        if (!current_user_can('manage_amhorti')) { wp_die('Unauthorized'); }
        if (!isset($_POST['schedule_ids']) || !is_array($_POST['schedule_ids'])) {
            wp_send_json_error('Paramètres invalides');
        }
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';
        $ids = array_map('intval', $_POST['schedule_ids']);
        if (empty($ids)) { wp_send_json_error('Aucun ID'); }
        $in = implode(',', array_fill(0, count($ids), '%d'));
        // Build query safely via prepare
        $sql = $wpdb->prepare("UPDATE {$table_schedules} SET is_active = 0 WHERE id IN ($in)", $ids);
        $result = $wpdb->query($sql);
        if ($result !== false) { wp_send_json_success(array('updated' => intval($result))); }
        else { wp_send_json_error('Échec suppression multiple'); }
    }

    /**
     * Bulk update time range for all schedules on a sheet matching old range
     */
    public function ajax_bulk_update_time_range() {
        check_ajax_referer('amhorti_admin_nonce', 'nonce');
        if (!current_user_can('manage_amhorti')) { wp_die('Unauthorized'); }
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'amhorti_schedules';

        $sheet_id = intval($_POST['sheet_id']);
        $old_start = sanitize_text_field($_POST['old_start']);
        $old_end = sanitize_text_field($_POST['old_end']);
        $new_start = sanitize_text_field($_POST['new_start']);
        $new_end = sanitize_text_field($_POST['new_end']);

        if (!$sheet_id || !$old_start || !$old_end || !$new_start || !$new_end) {
            wp_send_json_error('Paramètres manquants');
        }

        // Update all schedules on this sheet having the exact old start-end
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_schedules} SET time_start = %s, time_end = %s WHERE sheet_id = %d AND time_start = %s AND time_end = %s AND is_active = 1",
            $new_start, $new_end, $sheet_id, $old_start, $old_end
        ));

        if ($result !== false) {
            wp_send_json_success(array('updated' => intval($result)));
        } else {
            wp_send_json_error('Échec de la mise à jour');
        }
    }
}