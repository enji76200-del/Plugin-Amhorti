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
        $date_obj = new DateTime($start_date);
        $day_of_week = $date_obj->format('N'); // 1 (Monday) to 7 (Sunday)
        $week_start = clone $date_obj;
        $week_start->modify('-' . ($day_of_week - 1) . ' days');
        
        // If week start is before today, start from today
        $today_date = new DateTime($today);
        if ($week_start < $today_date) {
            $week_start = clone $today_date;
        }
        
        // Generate 7 days representing the week
        $week_dates = array();
        $current_date = clone $week_start;
        for ($i = 0; $i < 7; $i++) {
            $week_dates[] = $current_date->format('Y-m-d');
            $current_date->modify('+1 day');
        }
        
        // Get French day names mapping
        $french_days = array(
            'Monday' => 'lundi',
            'Tuesday' => 'mardi', 
            'Wednesday' => 'mercredi',
            'Thursday' => 'jeudi',
            'Friday' => 'vendredi',
            'Saturday' => 'samedi',
            'Sunday' => 'dimanche'
        );
        
        // Get sheet configuration & active days
        $sheet_config = $this->database->get_sheet_config($sheet_id);
        $active_days = array();
        if ($sheet_config && $sheet_config->days_config) {
            $active_days = json_decode($sheet_config->days_config, true) ?: array();
        }
        
        // Decide which dates to display: if no active_days configured -> show all (backward compatible)
        $display_dates = array();
        foreach ($week_dates as $d) {
            $day_name = date('l', strtotime($d));
            $french_day = $french_days[$day_name];
            if (empty($active_days) || in_array($french_day, $active_days)) {
                $display_dates[] = $d;
            }
        }
        
        // If no days active in this week, show message & exit early
        if (empty($display_dates)) {
            return '<p>Aucun jour actif configuré pour cette feuille.</p>';
        }
        
        // Collect unique time slots across the active days
        $all_time_slots = array();
        foreach ($display_dates as $d) {
            $day_name = date('l', strtotime($d));
            $french_day = $french_days[$day_name];
            $day_schedule = $this->database->get_schedule_for_sheet_day($sheet_id, $french_day);
            foreach ($day_schedule as $slot) {
                $time_key = $slot->time_start . '-' . $slot->time_end;
                if (!in_array($time_key, $all_time_slots)) {
                    $all_time_slots[] = $time_key;
                }
            }
        }
        sort($all_time_slots);
        
        // Load bookings only for displayed dates
        $bookings = array();
        foreach ($display_dates as $d) {
            $date_bookings = $this->database->get_bookings($sheet_id, $d);
            foreach ($date_bookings as $booking) {
                $key = $d . '_' . $booking->time_start . '_' . $booking->time_end . '_' . $booking->slot_number;
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
                            // Day already guaranteed active because filtered
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
                                // Check if date is within valid range (today to +7 days)
                                $today = date('Y-m-d');
                                $max_date = date('Y-m-d', strtotime('+7 days', strtotime($today)));
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
        
        // Validate date is not in the past or more than 7 days in the future
        $booking_date = strtotime($date);
        $current_date = strtotime(date('Y-m-d')); // Start of today
        $max_future = strtotime('+7 days', $current_date);
        
        if ($booking_date < $current_date || $booking_date > $max_future) {
            wp_send_json_error('Les réservations ne sont possibles que pour les 7 prochains jours');
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