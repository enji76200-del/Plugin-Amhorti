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
                    <?php $schedules = $this->database->get_schedule_for_day($day); ?>
                    <div class="card">
                        <h2><?php echo ucfirst($day); ?></h2>
                        <?php if (!empty($schedules)): ?>
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
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo esc_html($schedule->time_start); ?></td>
                                    <td><?php echo esc_html($schedule->time_end); ?></td>
                                    <td><?php echo esc_html($schedule->slot_count); ?></td>
                                    <td><?php echo $schedule->is_active ? 'Actif' : 'Inactif'; ?></td>
                                    <td>
                                        <button class="button edit-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Modifier</button>
                                        <button class="button button-link-delete delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>">Supprimer</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>Aucun créneau horaire configuré pour ce jour.</p>
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
                        alert('Erreur : ' + response.data);
                    }
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