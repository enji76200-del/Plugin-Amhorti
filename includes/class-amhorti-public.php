<?php
/**
 * Public interface class
 */
class Amhorti_Public {
    
    private $database;
    
    public function __construct() {
        $this->database = new Amhorti_Database();
    }
    
    public function init() {
        add_action('wp_ajax_amhorti_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_nopriv_amhorti_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_amhorti_get_table_data', array($this, 'ajax_get_table_data'));
        add_action('wp_ajax_nopriv_amhorti_get_table_data', array($this, 'ajax_get_table_data'));
        add_action('wp_head', array($this, 'inject_custom_css'));

        // Shortcode to expose admin pages on frontend for allowed roles
        add_shortcode('amhorti_admin_portal', array($this, 'render_admin_portal'));
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'sheet' => '',
        ), $atts);
        
        $sheets = $this->database->get_sheets();
        if (empty($sheets)) {
            return '<p>No sheets available.</p>';
        }
        
        $current_sheet = $atts['sheet'] ? intval($atts['sheet']) : $sheets[0]->id;
        
        ob_start();
        ?>
        <div class="amhorti-schedule-container" data-current-sheet="<?php echo esc_attr($current_sheet); ?>">
            <!-- Sheet tabs -->
            <div class="amhorti-tabs">
                <?php foreach ($sheets as $sheet): ?>
                <button class="amhorti-tab <?php echo $sheet->id == $current_sheet ? 'active' : ''; ?>" 
                        data-sheet-id="<?php echo esc_attr($sheet->id); ?>">
                    <?php echo esc_html($sheet->name); ?>
                </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Loading indicator -->
            <div class="amhorti-loading" style="display: none;">
                <p>Loading...</p>
            </div>
            
            <!-- Schedule table container -->
            <div class="amhorti-table-container">
                <div class="amhorti-table-wrapper">
                    <!-- Table will be loaded via AJAX -->
                </div>
            </div>
            
            <!-- Navigation buttons -->
            <div class="amhorti-navigation">
                <button class="amhorti-nav-btn" data-direction="prev">← Semaine précédente</button>
                <button class="amhorti-nav-btn" data-direction="today">Aujourd'hui</button>
                <button class="amhorti-nav-btn" data-direction="next">Semaine suivante →</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render an admin-like portal in frontend with tabs for the admin pages
     * Access limited to users with 'manage_amhorti' capability (Administrateur or Organisateur)
     */
    public function render_admin_portal($atts) {
        if (!is_user_logged_in() || !current_user_can('manage_amhorti')) {
            return '<p>Accès refusé. Vous devez être connecté en tant qu\'Administrateur ou Organisateur.</p>';
        }

        // Prepare tabs list matching admin pages
        $tabs = array(
            'overview' => __('Accueil', 'amhorti-schedule'),
            'sheets' => __('Gérer les Feuilles', 'amhorti-schedule'),
            'schedules' => __('Gérer les Horaires', 'amhorti-schedule'),
            'advanced' => __('Configuration Avancée', 'amhorti-schedule'),
            'css' => __('Éditeur CSS', 'amhorti-schedule'),
        );

        // Default tab
        $active = isset($_GET['amhorti_tab']) && isset($tabs[$_GET['amhorti_tab']]) ? sanitize_key($_GET['amhorti_tab']) : 'overview';

    $nonce = wp_create_nonce('amhorti_admin_nonce');
    ob_start();
        ?>
    <div class="amhorti-admin-frontend" data-active-tab="<?php echo esc_attr($active); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
            <div class="amhorti-tabs">
                <?php foreach ($tabs as $key => $label): ?>
                    <button class="amhorti-tab <?php echo $active === $key ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>

            <div class="amhorti-admin-panels">
                <div class="amhorti-panel" data-panel="overview" style="display: <?php echo $active==='overview'?'block':'none'; ?>;">
                    <?php $this->render_admin_overview_panel(); ?>
                </div>
                <div class="amhorti-panel" data-panel="sheets" style="display: <?php echo $active==='sheets'?'block':'none'; ?>;">
                    <?php $this->render_admin_sheets_panel(); ?>
                </div>
                <div class="amhorti-panel" data-panel="schedules" style="display: <?php echo $active==='schedules'?'block':'none'; ?>;">
                    <?php $this->render_admin_schedules_panel(); ?>
                </div>
                <div class="amhorti-panel" data-panel="advanced" style="display: <?php echo $active==='advanced'?'block':'none'; ?>;">
                    <?php $this->render_admin_advanced_panel(); ?>
                </div>
                <div class="amhorti-panel" data-panel="css" style="display: <?php echo $active==='css'?'block':'none'; ?>;">
                    <?php $this->render_admin_css_panel(); ?>
                </div>
            </div>
        </div>

        <script>
        (function($){
            $(document).on('click', '.amhorti-admin-frontend .amhorti-tab', function(){
                var key = $(this).data('tab');
                $('.amhorti-admin-frontend .amhorti-tab').removeClass('active');
                $(this).addClass('active');
                $('.amhorti-admin-frontend .amhorti-panel').hide();
                $('.amhorti-admin-frontend .amhorti-panel[data-panel="'+key+'"]').show();
            });
        })(jQuery);
        </script>
        <?php
        return ob_get_clean();
    }

    // Panels renderers (frontend replicas using existing database methods and AJAX endpoints)
    private function render_admin_overview_panel() {
        echo '<div class="card"><h2>Planification Amhorti</h2><p>Accédez aux onglets pour gérer feuilles, horaires, configuration et CSS.</p></div>';
    }

    private function render_admin_sheets_panel() {
        // Reuse admin list of sheets with frontend forms and AJAX to admin endpoints
        $sheets = $this->database->get_sheets();
        ?>
        <div class="card">
            <h2>Ajouter une Nouvelle Feuille</h2>
            <form id="amhorti-add-sheet-form-frontend">
                <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                <p><label>Nom de la Feuille<br/>
                    <input type="text" name="sheet_name" id="sheet_name_front" required />
                </label></p>
                <p><label>Ordre de Tri<br/>
                    <input type="number" name="sort_order" id="sort_order_front" value="<?php echo count($sheets)+1; ?>" />
                </label></p>
                <p><button type="submit" class="button button-primary">Ajouter la Feuille</button></p>
            </form>
        </div>
        <div class="card">
            <h2>Feuilles Existantes</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Nom</th><th>Ordre</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($sheets as $sheet): ?>
                    <tr>
                        <td><?php echo esc_html($sheet->id); ?></td>
                        <td><?php echo esc_html($sheet->name); ?></td>
                        <td><?php echo esc_html($sheet->sort_order); ?></td>
                        <td><?php echo $sheet->is_active ? 'Actif' : 'Inactif'; ?></td>
                        <td>
                            <button class="button delete-sheet-front" data-id="<?php echo esc_attr($sheet->id); ?>">Supprimer</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        (function($){
            $(document).on('submit', '#amhorti-add-sheet-form-frontend', function(e){
                e.preventDefault();
                $.post(amhorti_admin_ajax.ajax_url, {
                    action: 'amhorti_admin_save_sheet',
                    sheet_name: $('#sheet_name_front').val(),
                    sort_order: $('#sort_order_front').val(),
                    nonce: $('.amhorti-admin-frontend').data('nonce')
                }, function(resp){
                    if(resp.success){ location.reload(); } else { alert('Erreur: '+resp.data); }
                });
            });
            $(document).on('click', '.delete-sheet-front', function(){
                if(!confirm('Supprimer cette feuille ?')) return;
                $.post(amhorti_admin_ajax.ajax_url, {
                    action: 'amhorti_admin_delete_sheet',
                    sheet_id: $(this).data('id'),
                    nonce: $('.amhorti-admin-frontend').data('nonce')
                }, function(resp){
                    if(resp.success){ location.reload(); } else { alert('Erreur: '+resp.data); }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    private function render_admin_schedules_panel() {
        $days = array('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche');
        ?>
        <div class="card">
            <h2>Ajouter un Nouveau Créneau</h2>
            <form id="amhorti-add-schedule-form-front">
                <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                <p><label>Jour
                    <select id="day_of_week_front"><?php foreach($days as $d){ echo '<option value="'.esc_attr($d).'">'.ucfirst($d).'</option>'; } ?></select>
                </label></p>
                <p><label>Heure de Début <input type="time" id="time_start_front" required></label></p>
                <p><label>Heure de Fin <input type="time" id="time_end_front" required></label></p>
                <p><label>Créneaux <input type="number" id="slot_count_front" value="2" min="1" max="10" required></label></p>
                <p><button type="submit" class="button button-primary">Ajouter</button></p>
            </form>
        </div>
        <div class="card">
            <h2>Créneaux par jour</h2>
            <?php foreach($days as $day): $schedules = $this->database->get_schedule_for_day($day); ?>
            <h3><?php echo ucfirst($day); ?></h3>
            <?php if(!empty($schedules)): ?>
            <table class="wp-list-table widefat fixed striped"><thead><tr><th>Début</th><th>Fin</th><th>Créneaux</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($schedules as $s): ?>
                <tr>
                    <td><?php echo esc_html($s->time_start); ?></td>
                    <td><?php echo esc_html($s->time_end); ?></td>
                    <td><?php echo esc_html($s->slot_count); ?></td>
                    <td><?php echo $s->is_active ? 'Actif' : 'Inactif'; ?></td>
                    <td><button class="button delete-schedule-front" data-id="<?php echo esc_attr($s->id); ?>">Supprimer</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php else: ?><p>Aucun créneau.</p><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <script>
        (function($){
            $(document).on('submit', '#amhorti-add-schedule-form-front', function(e){
                e.preventDefault();
                $.post(amhorti_admin_ajax.ajax_url, {
                    action: 'amhorti_admin_save_schedule',
                    day_of_week: $('#day_of_week_front').val(),
                    time_start: $('#time_start_front').val(),
                    time_end: $('#time_end_front').val(),
                    slot_count: $('#slot_count_front').val(),
                    nonce: $('.amhorti-admin-frontend').data('nonce')
                }, function(resp){
                    if(resp.success){ location.reload(); } else { alert('Erreur: '+resp.data); }
                });
            });
            $(document).on('click', '.delete-schedule-front', function(){
                if(!confirm('Supprimer ce créneau ?')) return;
                $.post(amhorti_admin_ajax.ajax_url, {
                    action: 'amhorti_admin_delete_schedule',
                    schedule_id: $(this).data('id'),
                    nonce: $('.amhorti-admin-frontend').data('nonce')
                }, function(resp){ if(resp.success){ location.reload(); } else { alert('Erreur: '+resp.data); } });
            });
        })(jQuery);
        </script>
        <?php
    }

    private function render_admin_advanced_panel() {
        $sheets = $this->database->get_sheets();
        $days_options = array('lundi'=>'Lundi','mardi'=>'Mardi','mercredi'=>'Mercredi','jeudi'=>'Jeudi','vendredi'=>'Vendredi','samedi'=>'Samedi','dimanche'=>'Dimanche');
        foreach ($sheets as $sheet) {
            $active_days = json_decode($sheet->days_config, true) ?: array_keys($days_options);
            echo '<div class="card">';
            echo '<h2>Configuration de "'.esc_html($sheet->name).'"</h2>';
            echo '<form class="amhorti-sheet-config-form-front" data-sheet-id="'.esc_attr($sheet->id).'">';
            wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce');
            echo '<p><label>Nom<br/><input type="text" name="sheet_name" value="'.esc_attr($sheet->name).'"/></label></p>';
            echo '<p>Jours actifs:<br/>';
            foreach($days_options as $k=>$label){
                $checked = in_array($k,$active_days) ? 'checked' : '';
                echo '<label><input type="checkbox" name="active_days[]" value="'.esc_attr($k).'" '.$checked.'/> '.esc_html($label).'</label> ';
            }
            $allow_beyond = isset($sheet->allow_beyond_7_days) ? intval($sheet->allow_beyond_7_days) : 0;
            echo '</p>';
            echo '<p><label><input type="checkbox" name="allow_beyond_7_days" value="1" '.($allow_beyond ? 'checked' : '').'/> Autoriser les inscriptions au-delà de +7 jours</label></p>';
            echo '<p><button type="submit" class="button button-primary">Sauvegarder</button></p>';
            echo '</form></div>';
        }
        ?>
        <script>
        (function($){
            $(document).on('submit', '.amhorti-sheet-config-form-front', function(e){
                e.preventDefault();
                var form = $(this);
                var activeDays = [];
                form.find('input[name="active_days[]"]:checked').each(function(){ activeDays.push($(this).val()); });
                $.post(amhorti_admin_ajax.ajax_url, {
                    action: 'amhorti_admin_update_sheet',
                    sheet_id: form.data('sheet-id'),
                    sheet_name: form.find('input[name="sheet_name"]').val(),
                    active_days: activeDays,
                    allow_beyond_7_days: form.find('input[name="allow_beyond_7_days"]').is(':checked') ? 1 : 0,
                    nonce: $('.amhorti-admin-frontend').data('nonce')
                }, function(resp){ if(resp.success){ alert('Configuration sauvegardée'); } else { alert('Erreur: '+resp.data); } });
            });
        })(jQuery);
        </script>
        <?php
    }

    private function render_admin_css_panel() {
        ?>
        <div class="card">
            <h2>Éditeur CSS</h2>
            <form id="amhorti-css-form-front">
                <?php wp_nonce_field('amhorti_admin_nonce', 'amhorti_admin_nonce'); ?>
                <p><textarea id="amhorti-css-editor-front" rows="12" style="width:100%"></textarea></p>
                <p>
                    <button type="submit" class="button button-primary">Sauvegarder</button>
                    <button type="button" id="amhorti-css-load-front" class="button">Charger</button>
                </p>
            </form>
        </div>
        <script>
        (function($){
            function loadCss(){
                $.post(amhorti_admin_ajax.ajax_url, { action:'amhorti_admin_get_css', nonce: $('.amhorti-admin-frontend').data('nonce') }, function(resp){
                    if(resp.success){ $('#amhorti-css-editor-front').val(resp.data.css || ''); }
                });
            }
            $('#amhorti-css-load-front').on('click', loadCss);
            $(document).ready(loadCss);
            $('#amhorti-css-form-front').on('submit', function(e){
                e.preventDefault();
                $.post(amhorti_admin_ajax.ajax_url, { action:'amhorti_admin_save_css', css_content: $('#amhorti-css-editor-front').val(), nonce: $('.amhorti-admin-frontend').data('nonce') }, function(resp){
                    if(resp.success){ alert('CSS sauvegardé'); } else { alert('Erreur: '+resp.data); }
                });
            });
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * Generate table HTML for a specific sheet and week
     */
    public function generate_table_html($sheet_id, $start_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d');
        }
        
        // Always start from today, don't show past dates
        $today = date('Y-m-d');
        if ($start_date < $today) {
            $start_date = $today;
        }
        
        // Calculate the start of the week (Monday) but ensure we don't go before today
        $date = new DateTime($start_date);
        $day_of_week = $date->format('N'); // 1 (Monday) to 7 (Sunday)
        $week_start = clone $date;
        $week_start->modify('-' . ($day_of_week - 1) . ' days');
        
        // If week start is before today, start from today
        $today_date = new DateTime($today);
        if ($week_start < $today_date) {
            $week_start = clone $today_date;
        }
        
        // Generate 7 days from the start date
        $dates = array();
        $current_date = clone $week_start;
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $current_date->format('Y-m-d');
            $current_date->modify('+1 day');
        }
        
        // Get French day names
        $french_days = array(
            'Monday' => 'lundi',
            'Tuesday' => 'mardi', 
            'Wednesday' => 'mercredi',
            'Thursday' => 'jeudi',
            'Friday' => 'vendredi',
            'Saturday' => 'samedi',
            'Sunday' => 'dimanche'
        );
        
        // Get sheet configuration
        $sheet_config = $this->database->get_sheet_config($sheet_id);
        $active_days = array();
        if ($sheet_config && $sheet_config->days_config) {
            $active_days = json_decode($sheet_config->days_config, true) ?: array();
        }

        // Filter dates to only include active days for this sheet (if configured)
        $display_dates = array();
        foreach ($dates as $date_item) {
            $day_name = date('l', strtotime($date_item));
            $french_day = $french_days[$day_name];
            if (empty($active_days) || in_array($french_day, $active_days)) {
                $display_dates[] = $date_item;
            }
        }
        
        // Get all time slots for the week
        $all_time_slots = array();
        foreach ($display_dates as $date) {
            $day_name = date('l', strtotime($date));
            $french_day = $french_days[$day_name];
            
            // Get sheet-specific schedule or fall back to global
            $day_schedule = $this->database->get_schedule_for_sheet_day($sheet_id, $french_day);
            
            foreach ($day_schedule as $slot) {
                $time_key = $slot->time_start . '-' . $slot->time_end;
                if (!in_array($time_key, $all_time_slots)) {
                    $all_time_slots[] = $time_key;
                }
            }
        }
        
        // Sort time slots
        sort($all_time_slots);
        
        // Get existing bookings for all dates
        $bookings = array();
        foreach ($dates as $date) {
            $date_bookings = $this->database->get_bookings($sheet_id, $date);
            foreach ($date_bookings as $booking) {
                $key = $date . '_' . $booking->time_start . '_' . $booking->time_end . '_' . $booking->slot_number;
                $bookings[$key] = $booking->booking_text;
            }
        }
        
        ob_start();
        ?>
        <table class="amhorti-schedule-table">
            <thead>
                <tr>
                    <th class="time-header">Horaires</th>
                    <?php foreach ($display_dates as $date): ?>
                        <?php 
                        $day_name = date('l', strtotime($date));
                        $french_day = ucfirst($french_days[$day_name]);
                        $formatted_date = date('d/m', strtotime($date));
                        ?>
                        <th class="date-header"><?php echo $french_day . ' ' . $formatted_date; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_time_slots as $time_slot): ?>
                    <?php 
                    list($start_time, $end_time) = explode('-', $time_slot);
                    $display_time = substr($start_time, 0, 5) . ' - ' . substr($end_time, 0, 5);
                    
                    // Find maximum slots for this time across all days
                    $max_slots = 0;
                    foreach ($display_dates as $date) {
                        $day_name = date('l', strtotime($date));
                        $french_day = $french_days[$day_name];
                        
                        $day_schedule = $this->database->get_schedule_for_sheet_day($sheet_id, $french_day);
                        
                        foreach ($day_schedule as $slot) {
                            if ($slot->time_start == $start_time && $slot->time_end == $end_time) {
                                $max_slots = max($max_slots, $slot->slot_count);
                                break;
                            }
                        }
                    }
                    
                    // Create rows for each slot
                    for ($slot_num = 1; $slot_num <= $max_slots; $slot_num++):
                    ?>
                    <tr class="time-row" data-time-start="<?php echo esc_attr($start_time); ?>" data-time-end="<?php echo esc_attr($end_time); ?>">
                        <?php if ($slot_num == 1): ?>
                        <td class="time-cell" rowspan="<?php echo $max_slots; ?>">
                            <?php echo esc_html($display_time); ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php foreach ($display_dates as $date): ?>
                            <?php 
                            $day_name = date('l', strtotime($date));
                            $french_day = $french_days[$day_name];
                            
                            $day_schedule = $this->database->get_schedule_for_sheet_day($sheet_id, $french_day);
                            
                            // Check if this time slot exists for this day
                            $slot_exists = false;
                            foreach ($day_schedule as $slot) {
                                if ($slot->time_start == $start_time && $slot->time_end == $end_time && $slot_num <= $slot->slot_count) {
                                    $slot_exists = true;
                                    break;
                                }
                            }
                            
                            if ($slot_exists) {
                                // Check if date is within valid range
                                $today = date('Y-m-d');
                                $max_days = (!empty($sheet_config) && !empty($sheet_config->allow_beyond_7_days) && intval($sheet_config->allow_beyond_7_days) === 1) ? 365 : 7;
                                $max_date = date('Y-m-d', strtotime('+' . $max_days . ' days', strtotime($today)));
                                $is_valid_date = ($date >= $today && $date <= $max_date);
                                
                                $booking_key = $date . '_' . $start_time . '_' . $end_time . '_' . $slot_num;
                                $booking_text = isset($bookings[$booking_key]) ? $bookings[$booking_key] : '';
                                
                                $cell_class = 'booking-cell';
                                $contenteditable = 'false';
                                
                                if ($is_valid_date) {
                                    $cell_class .= ' editable';
                                    $contenteditable = 'true';
                                } else {
                                    $cell_class .= ' disabled';
                                }
                                ?>
                                <td class="<?php echo $cell_class; ?>" 
                                    data-date="<?php echo esc_attr($date); ?>"
                                    data-time-start="<?php echo esc_attr($start_time); ?>"
                                    data-time-end="<?php echo esc_attr($end_time); ?>"
                                    data-slot="<?php echo esc_attr($slot_num); ?>"
                                    contenteditable="<?php echo $contenteditable; ?>"
                                    spellcheck="false"><?php echo esc_html($booking_text); ?></td>
                                <?php
                            } else {
                                ?>
                                <td class="booking-cell disabled"></td>
                                <?php
                            }
                            ?>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for getting table data
     */
    public function ajax_get_table_data() {
        check_ajax_referer('amhorti_nonce', 'nonce');
        
        $sheet_id = intval($_POST['sheet_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        
        $html = $this->generate_table_html($sheet_id, $start_date);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX handler for saving bookings
     */
    public function ajax_save_booking() {
        check_ajax_referer('amhorti_nonce', 'nonce');
        
        $sheet_id = intval($_POST['sheet_id']);
        $date = sanitize_text_field($_POST['date']);
        $time_start = sanitize_text_field($_POST['time_start']);
        $time_end = sanitize_text_field($_POST['time_end']);
        $slot_number = intval($_POST['slot_number']);
        $booking_text = sanitize_text_field($_POST['booking_text']);
        
        // Validate date is not in the past or more than N days in the future
        $booking_date = strtotime($date);
        $current_date = strtotime(date('Y-m-d')); // Start of today
        $max_days = 7;
        $sheet_config = $this->database->get_sheet_config($sheet_id);
        if ($sheet_config && !empty($sheet_config->allow_beyond_7_days) && intval($sheet_config->allow_beyond_7_days) === 1) {
            $max_days = 365;
        }
        $max_future = strtotime('+' . $max_days . ' days', $current_date);
        
        if ($booking_date < $current_date || $booking_date > $max_future) {
            if ($max_days > 7) {
                wp_send_json_error('Date de réservation hors plage autorisée');
            } else {
                wp_send_json_error('Les réservations ne sont possibles que pour les 7 prochains jours');
            }
            return;
        }
        
        $result = $this->database->save_booking($sheet_id, $date, $time_start, $time_end, $slot_number, $booking_text);
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save booking');
        }
    }
    
    /**
     * Inject custom CSS into page head
     */
    public function inject_custom_css() {
        $custom_css = $this->database->get_custom_css();
        if ($custom_css) {
            echo "<style type=\"text/css\" id=\"amhorti-custom-css\">\n" . $custom_css . "\n</style>\n";
        }
    }
}