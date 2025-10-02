<?php
/**
 * Database operations class
 */
class Amhorti_Database {
    
    private $table_bookings;
    private $table_sheets;
    private $table_schedules;
    private $table_css_settings;
    
    public function __construct() {
        global $wpdb;
        $this->table_bookings = $wpdb->prefix . 'amhorti_bookings';
        $this->table_sheets = $wpdb->prefix . 'amhorti_sheets';
        $this->table_schedules = $wpdb->prefix . 'amhorti_schedules';
        $this->table_css_settings = $wpdb->prefix . 'amhorti_css_settings';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookings table
        $sql_bookings = "CREATE TABLE IF NOT EXISTS {$this->table_bookings} (
            id int(11) NOT NULL AUTO_INCREMENT,
            sheet_id int(11) NOT NULL,
            date date NOT NULL,
            time_start time NOT NULL,
            time_end time NOT NULL,
            slot_number int(3) NOT NULL,
            booking_text varchar(255) DEFAULT '',
            user_ip varchar(45) DEFAULT '',
            user_id bigint(20) DEFAULT NULL,
            version int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sheet_date (sheet_id, date),
            KEY idx_created_at (created_at),
            KEY idx_user_id (user_id)
        ) $charset_collate;";
        
        // Sheets table
        $sql_sheets = "CREATE TABLE IF NOT EXISTS {$this->table_sheets} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            days_config text DEFAULT NULL,
            allow_beyond_7_days tinyint(1) DEFAULT 0,
            max_booking_days int(11) DEFAULT 7,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Schedules table (for admin-configurable time slots)
        $sql_schedules = "CREATE TABLE IF NOT EXISTS {$this->table_schedules} (
            id int(11) NOT NULL AUTO_INCREMENT,
            sheet_id int(11) DEFAULT NULL,
            day_of_week varchar(20) NOT NULL,
            time_start time NOT NULL,
            time_end time NOT NULL,
            slot_count int(3) NOT NULL DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY idx_day (day_of_week),
            KEY idx_sheet_day (sheet_id, day_of_week)
        ) $charset_collate;";
        
        // CSS Settings table for custom styling
        $sql_css_settings = "CREATE TABLE IF NOT EXISTS {$this->table_css_settings} (
            id int(11) NOT NULL AUTO_INCREMENT,
            css_content longtext DEFAULT '',
            is_active tinyint(1) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_bookings);
        dbDelta($sql_sheets);
        dbDelta($sql_schedules);
        dbDelta($sql_css_settings);
        
        // Insert default data
        $this->insert_default_data();
    }

    /**
     * Ensure schema is up to date (lightweight migration)
     */
    public function ensure_schema() {
        global $wpdb;
        // Add allow_beyond_7_days column if missing
        $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$this->table_sheets} LIKE %s", 'allow_beyond_7_days'));
        if (!$col) {
            $wpdb->query("ALTER TABLE {$this->table_sheets} ADD COLUMN allow_beyond_7_days TINYINT(1) DEFAULT 0");
        }
        // Add max_booking_days column if missing
        $col2 = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$this->table_sheets} LIKE %s", 'max_booking_days'));
        if (!$col2) {
            $wpdb->query("ALTER TABLE {$this->table_sheets} ADD COLUMN max_booking_days INT(11) DEFAULT 7");
        }
        // Add user_id column to bookings if missing
        $col3 = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$this->table_bookings} LIKE %s", 'user_id'));
        if (!$col3) {
            $wpdb->query("ALTER TABLE {$this->table_bookings} ADD COLUMN user_id BIGINT(20) DEFAULT NULL");
            $wpdb->query("ALTER TABLE {$this->table_bookings} ADD KEY idx_user_id (user_id)");
        }
        // Add version column to bookings if missing
        $col4 = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$this->table_bookings} LIKE %s", 'version'));
        if (!$col4) {
            $wpdb->query("ALTER TABLE {$this->table_bookings} ADD COLUMN version INT(11) DEFAULT 1");
        }
    }
    
    /**
     * Insert default data
     */
    private function insert_default_data() {
        global $wpdb;
        
        // Insert default sheets if they don't exist
        $sheet_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_sheets}");
        if ($sheet_count == 0) {
            $default_sheets = array(
                array('name' => 'Feuille 1', 'sort_order' => 1),
                array('name' => 'Feuille 2', 'sort_order' => 2),
                array('name' => 'Feuille 3', 'sort_order' => 3),
                array('name' => 'Feuille 4', 'sort_order' => 4)
            );
            
            foreach ($default_sheets as $sheet) {
                $wpdb->insert($this->table_sheets, $sheet);
            }
        }
        
        // Insert default schedules if they don't exist
        $schedule_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_schedules}");
        if ($schedule_count == 0) {
            $default_schedules = array(
                // Lundi
                array('day_of_week' => 'lundi', 'time_start' => '06:00:00', 'time_end' => '07:00:00', 'slot_count' => 3),
                array('day_of_week' => 'lundi', 'time_start' => '07:30:00', 'time_end' => '08:30:00', 'slot_count' => 3),
                array('day_of_week' => 'lundi', 'time_start' => '08:30:00', 'time_end' => '10:00:00', 'slot_count' => 2),
                array('day_of_week' => 'lundi', 'time_start' => '10:00:00', 'time_end' => '11:30:00', 'slot_count' => 2),
                array('day_of_week' => 'lundi', 'time_start' => '11:30:00', 'time_end' => '13:00:00', 'slot_count' => 2),
                array('day_of_week' => 'lundi', 'time_start' => '13:00:00', 'time_end' => '14:30:00', 'slot_count' => 2),
                array('day_of_week' => 'lundi', 'time_start' => '14:30:00', 'time_end' => '16:00:00', 'slot_count' => 2),
                array('day_of_week' => 'lundi', 'time_start' => '16:00:00', 'time_end' => '17:30:00', 'slot_count' => 2),
                array('day_of_week' => 'lundi', 'time_start' => '17:30:00', 'time_end' => '19:00:00', 'slot_count' => 3),
                array('day_of_week' => 'lundi', 'time_start' => '19:00:00', 'time_end' => '20:00:00', 'slot_count' => 3),
                
                // Mardi
                array('day_of_week' => 'mardi', 'time_start' => '07:30:00', 'time_end' => '08:30:00', 'slot_count' => 3),
                array('day_of_week' => 'mardi', 'time_start' => '08:30:00', 'time_end' => '10:00:00', 'slot_count' => 2),
                array('day_of_week' => 'mardi', 'time_start' => '10:00:00', 'time_end' => '11:30:00', 'slot_count' => 2),
                array('day_of_week' => 'mardi', 'time_start' => '11:30:00', 'time_end' => '13:00:00', 'slot_count' => 2),
                array('day_of_week' => 'mardi', 'time_start' => '13:00:00', 'time_end' => '14:30:00', 'slot_count' => 2),
                array('day_of_week' => 'mardi', 'time_start' => '14:30:00', 'time_end' => '16:00:00', 'slot_count' => 2),
                array('day_of_week' => 'mardi', 'time_start' => '16:00:00', 'time_end' => '17:30:00', 'slot_count' => 2),
                array('day_of_week' => 'mardi', 'time_start' => '17:30:00', 'time_end' => '19:00:00', 'slot_count' => 3),
                array('day_of_week' => 'mardi', 'time_start' => '19:00:00', 'time_end' => '20:00:00', 'slot_count' => 3),
                
                // Mercredi
                array('day_of_week' => 'mercredi', 'time_start' => '07:30:00', 'time_end' => '08:30:00', 'slot_count' => 3),
                array('day_of_week' => 'mercredi', 'time_start' => '08:30:00', 'time_end' => '10:00:00', 'slot_count' => 2),
                array('day_of_week' => 'mercredi', 'time_start' => '10:00:00', 'time_end' => '11:30:00', 'slot_count' => 2),
                array('day_of_week' => 'mercredi', 'time_start' => '11:30:00', 'time_end' => '13:00:00', 'slot_count' => 2),
                array('day_of_week' => 'mercredi', 'time_start' => '13:00:00', 'time_end' => '14:30:00', 'slot_count' => 2),
                array('day_of_week' => 'mercredi', 'time_start' => '14:30:00', 'time_end' => '16:00:00', 'slot_count' => 2),
                array('day_of_week' => 'mercredi', 'time_start' => '16:00:00', 'time_end' => '17:30:00', 'slot_count' => 2),
                array('day_of_week' => 'mercredi', 'time_start' => '17:30:00', 'time_end' => '19:00:00', 'slot_count' => 3),
                array('day_of_week' => 'mercredi', 'time_start' => '19:00:00', 'time_end' => '20:00:00', 'slot_count' => 3),
                
                // Jeudi
                array('day_of_week' => 'jeudi', 'time_start' => '07:30:00', 'time_end' => '08:30:00', 'slot_count' => 3),
                array('day_of_week' => 'jeudi', 'time_start' => '08:30:00', 'time_end' => '10:00:00', 'slot_count' => 2),
                array('day_of_week' => 'jeudi', 'time_start' => '10:00:00', 'time_end' => '11:30:00', 'slot_count' => 2),
                array('day_of_week' => 'jeudi', 'time_start' => '11:30:00', 'time_end' => '13:00:00', 'slot_count' => 2),
                array('day_of_week' => 'jeudi', 'time_start' => '13:00:00', 'time_end' => '14:30:00', 'slot_count' => 2),
                array('day_of_week' => 'jeudi', 'time_start' => '14:30:00', 'time_end' => '16:00:00', 'slot_count' => 2),
                array('day_of_week' => 'jeudi', 'time_start' => '16:00:00', 'time_end' => '17:30:00', 'slot_count' => 2),
                array('day_of_week' => 'jeudi', 'time_start' => '17:30:00', 'time_end' => '19:00:00', 'slot_count' => 3),
                array('day_of_week' => 'jeudi', 'time_start' => '19:00:00', 'time_end' => '20:00:00', 'slot_count' => 3),
                
                // Vendredi
                array('day_of_week' => 'vendredi', 'time_start' => '07:30:00', 'time_end' => '08:30:00', 'slot_count' => 3),
                array('day_of_week' => 'vendredi', 'time_start' => '08:30:00', 'time_end' => '10:00:00', 'slot_count' => 2),
                array('day_of_week' => 'vendredi', 'time_start' => '10:00:00', 'time_end' => '11:30:00', 'slot_count' => 2),
                array('day_of_week' => 'vendredi', 'time_start' => '11:30:00', 'time_end' => '13:00:00', 'slot_count' => 2),
                array('day_of_week' => 'vendredi', 'time_start' => '13:00:00', 'time_end' => '14:30:00', 'slot_count' => 2),
                array('day_of_week' => 'vendredi', 'time_start' => '14:30:00', 'time_end' => '16:00:00', 'slot_count' => 2),
                array('day_of_week' => 'vendredi', 'time_start' => '16:00:00', 'time_end' => '17:30:00', 'slot_count' => 2),
                array('day_of_week' => 'vendredi', 'time_start' => '17:30:00', 'time_end' => '19:00:00', 'slot_count' => 3),
                array('day_of_week' => 'vendredi', 'time_start' => '19:00:00', 'time_end' => '20:00:00', 'slot_count' => 3),
                
                // Samedi
                array('day_of_week' => 'samedi', 'time_start' => '13:00:00', 'time_end' => '14:30:00', 'slot_count' => 2),
                array('day_of_week' => 'samedi', 'time_start' => '14:30:00', 'time_end' => '16:00:00', 'slot_count' => 2),
                array('day_of_week' => 'samedi', 'time_start' => '16:00:00', 'time_end' => '17:30:00', 'slot_count' => 2),
                array('day_of_week' => 'samedi', 'time_start' => '17:30:00', 'time_end' => '19:00:00', 'slot_count' => 3),
                array('day_of_week' => 'samedi', 'time_start' => '19:00:00', 'time_end' => '20:00:00', 'slot_count' => 3),
                
                // Dimanche - no slots by default
            );
            
            foreach ($default_schedules as $schedule) {
                $wpdb->insert($this->table_schedules, $schedule);
            }
        }
    }
    
    /**
     * Get all active sheets
     */
    public function get_sheets() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_sheets} WHERE is_active = 1 ORDER BY sort_order ASC"
        );
    }
    
    /**
     * Get schedule for a specific day
     */
    public function get_schedule_for_day($day_of_week) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_schedules} 
                WHERE day_of_week = %s AND is_active = 1 
                ORDER BY time_start ASC",
                $day_of_week
            )
        );
    }
    
    /**
     * Get bookings for a specific date and sheet
     */
    public function get_bookings($sheet_id, $date) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_bookings} 
                WHERE sheet_id = %d AND date = %s",
                $sheet_id, $date
            )
        );
    }
    
    /**
     * Save booking with optimistic concurrency control
     */
    public function save_booking($sheet_id, $date, $time_start, $time_end, $slot_number, $booking_text, $expected_version = null) {
        global $wpdb;
        
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        
        // Check if booking already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_bookings} 
                WHERE sheet_id = %d AND date = %s AND time_start = %s AND time_end = %s AND slot_number = %d",
                $sheet_id, $date, $time_start, $time_end, $slot_number
            )
        );
        
        if ($existing) {
            // Check version for optimistic concurrency control
            if ($expected_version !== null && intval($existing->version) !== intval($expected_version)) {
                return array('error' => 'conflict', 'message' => 'La réservation a été modifiée par un autre utilisateur');
            }
            
            // Update existing booking
            $new_version = intval($existing->version) + 1;
            $result = $wpdb->update(
                $this->table_bookings,
                array(
                    'booking_text' => $booking_text, 
                    'user_ip' => $user_ip,
                    'user_id' => $user_id,
                    'version' => $new_version
                ),
                array('id' => $existing->id)
            );
            
            if ($result !== false) {
                return array('success' => true, 'version' => $new_version, 'id' => $existing->id);
            }
            return array('error' => 'database', 'message' => 'Erreur lors de la mise à jour');
        } else {
            // Insert new booking
            $result = $wpdb->insert(
                $this->table_bookings,
                array(
                    'sheet_id' => $sheet_id,
                    'date' => $date,
                    'time_start' => $time_start,
                    'time_end' => $time_end,
                    'slot_number' => $slot_number,
                    'booking_text' => $booking_text,
                    'user_ip' => $user_ip,
                    'user_id' => $user_id,
                    'version' => 1
                )
            );
            
            if ($result !== false) {
                return array('success' => true, 'version' => 1, 'id' => $wpdb->insert_id);
            }
            return array('error' => 'database', 'message' => 'Erreur lors de la création');
        }
    }
    
    /**
     * Get booking by ID
     */
    public function get_booking_by_id($booking_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_bookings} WHERE id = %d",
                $booking_id
            )
        );
    }
    
    /**
     * Get booking by slot (sheet, date, time, slot number)
     */
    public function get_booking_by_slot($sheet_id, $date, $time_start, $time_end, $slot_number) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_bookings} 
                WHERE sheet_id = %d AND date = %s AND time_start = %s AND time_end = %s AND slot_number = %d",
                $sheet_id, $date, $time_start, $time_end, $slot_number
            )
        );
    }
    
    /**
     * Delete booking with ownership check
     */
    public function delete_booking($booking_id, $check_ownership = true) {
        global $wpdb;
        
        if ($check_ownership) {
            $booking = $this->get_booking_by_id($booking_id);
            if (!$booking) {
                return array('error' => 'not_found', 'message' => 'Réservation introuvable');
            }
            
            // Check if user has permission to delete
            $current_user_id = is_user_logged_in() ? get_current_user_id() : null;
            $is_admin = current_user_can('manage_amhorti');
            
            // Allow deletion if: user is admin OR user is the owner
            if (!$is_admin && $booking->user_id != $current_user_id) {
                return array('error' => 'permission_denied', 'message' => 'Vous n\'avez pas la permission de supprimer cette réservation');
            }
        }
        
        $result = $wpdb->delete(
            $this->table_bookings,
            array('id' => $booking_id),
            array('%d')
        );
        
        if ($result !== false) {
            return array('success' => true);
        }
        return array('error' => 'database', 'message' => 'Erreur lors de la suppression');
    }
    
    /**
     * Clean up old bookings (older than 14 days)
     */
    public function cleanup_old_bookings() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$this->table_bookings} 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)"
        );
    }
    
    /**
     * Get schedules for a specific sheet and day
     */
    public function get_schedule_for_sheet_day($sheet_id, $day_of_week) {
        global $wpdb;
        
        // First try to get sheet-specific schedules
        $sheet_schedules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_schedules} 
                WHERE sheet_id = %d AND day_of_week = %s AND is_active = 1 
                ORDER BY time_start ASC",
                $sheet_id, $day_of_week
            )
        );
        
        // If no sheet-specific schedules, fall back to global schedules
        if (empty($sheet_schedules)) {
            return $this->get_schedule_for_day($day_of_week);
        }
        
        return $sheet_schedules;
    }

    /**
     * Get all active schedules for a specific sheet (sheet-specific only)
     */
    public function get_schedules_for_sheet($sheet_id) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_schedules} WHERE sheet_id = %d AND is_active = 1 ORDER BY day_of_week, time_start",
                $sheet_id
            )
        );
    }
    
    /**
     * Get custom CSS
     */
    public function get_custom_css() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT css_content FROM {$this->table_css_settings} 
            WHERE is_active = 1 
            ORDER BY updated_at DESC 
            LIMIT 1"
        );
    }
    
    /**
     * Get sheet configuration including days
     */
    public function get_sheet_config($sheet_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_sheets} WHERE id = %d AND is_active = 1",
                $sheet_id
            )
        );
    }
}