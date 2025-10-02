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
        
        // Migrate global schedules to per-sheet schedules (one-time migration)
        $this->migrate_global_schedules_to_sheets();

        // Backfill: ensure each active sheet has at least one schedule; if none, create defaults
        $sheets = $wpdb->get_results("SELECT id FROM {$this->table_sheets} WHERE is_active = 1");
        foreach ($sheets as $s) {
            $cnt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_schedules} WHERE sheet_id = %d AND is_active = 1", $s->id));
            if (intval($cnt) === 0) {
                $this->create_default_schedules_for_sheet($s->id);
            }
        }
    }
    
    /**
     * Migrate global schedules (sheet_id IS NULL or 0) to sheet-specific schedules
     */
    private function migrate_global_schedules_to_sheets() {
        global $wpdb;
        
        // Check if migration already happened by looking for a migration marker
        $migration_marker = get_option('amhorti_schedules_migrated_v1_2_0');
        if ($migration_marker) {
            return; // Already migrated
        }
        
        // Get all global schedules (sheet_id IS NULL or 0)
        $global_schedules = $wpdb->get_results(
            "SELECT * FROM {$this->table_schedules} WHERE (sheet_id IS NULL OR sheet_id = 0) AND is_active = 1"
        );
        
        // Get all sheets
        $sheets = $wpdb->get_results("SELECT id FROM {$this->table_sheets} WHERE is_active = 1");
        
        // If we have global schedules and sheets, duplicate them for each sheet
        if (!empty($global_schedules) && !empty($sheets)) {
            foreach ($sheets as $sheet) {
                // Check if this sheet already has any schedules
                $existing_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_schedules} WHERE sheet_id = %d AND is_active = 1",
                    $sheet->id
                ));
                
                // Only migrate if sheet has no schedules
                if ($existing_count == 0) {
                    foreach ($global_schedules as $global_schedule) {
                        $wpdb->insert(
                            $this->table_schedules,
                            array(
                                'sheet_id' => $sheet->id,
                                'day_of_week' => $global_schedule->day_of_week,
                                'time_start' => $global_schedule->time_start,
                                'time_end' => $global_schedule->time_end,
                                'slot_count' => $global_schedule->slot_count,
                                'is_active' => 1
                            )
                        );
                    }
                }
            }
        }
        
        // Mark migration as completed
        update_option('amhorti_schedules_migrated_v1_2_0', true);
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
                // Create per-sheet default schedules on initial install
                $new_id = $wpdb->insert_id;
                if ($new_id) {
                    $this->create_default_schedules_for_sheet($new_id);
                }
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
     * Get schedule for a specific day (global schedules only - kept for backward compatibility)
     */
    public function get_schedule_for_day($day_of_week) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_schedules} 
                WHERE (sheet_id IS NULL OR sheet_id = 0) AND day_of_week = %s AND is_active = 1 
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
     * Get schedules for a specific sheet and day (no global fallback)
     */
    public function get_schedule_for_sheet_day($sheet_id, $day_of_week) {
        global $wpdb;
        
        // Get sheet-specific schedules only (no global fallback)
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_schedules} 
                WHERE sheet_id = %d AND day_of_week = %s AND is_active = 1 
                ORDER BY time_start ASC",
                $sheet_id, $day_of_week
            )
        );
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
     * Create default schedules for a given sheet.
     * This mirrors the initial defaults but attaches them to the sheet_id.
     */
    public function create_default_schedules_for_sheet($sheet_id) {
        global $wpdb;
        $defaults = array(
            // Lundi
            array('lundi', '06:00:00', '07:00:00', 3),
            array('lundi', '07:30:00', '08:30:00', 3),
            array('lundi', '08:30:00', '10:00:00', 2),
            array('lundi', '10:00:00', '11:30:00', 2),
            array('lundi', '11:30:00', '13:00:00', 2),
            array('lundi', '13:00:00', '14:30:00', 2),
            array('lundi', '14:30:00', '16:00:00', 2),
            array('lundi', '16:00:00', '17:30:00', 2),
            array('lundi', '17:30:00', '19:00:00', 3),
            array('lundi', '19:00:00', '20:00:00', 3),
            // Mardi
            array('mardi', '07:30:00', '08:30:00', 3),
            array('mardi', '08:30:00', '10:00:00', 2),
            array('mardi', '10:00:00', '11:30:00', 2),
            array('mardi', '11:30:00', '13:00:00', 2),
            array('mardi', '13:00:00', '14:30:00', 2),
            array('mardi', '14:30:00', '16:00:00', 2),
            array('mardi', '16:00:00', '17:30:00', 2),
            array('mardi', '17:30:00', '19:00:00', 3),
            array('mardi', '19:00:00', '20:00:00', 3),
            // Mercredi
            array('mercredi', '07:30:00', '08:30:00', 3),
            array('mercredi', '08:30:00', '10:00:00', 2),
            array('mercredi', '10:00:00', '11:30:00', 2),
            array('mercredi', '11:30:00', '13:00:00', 2),
            array('mercredi', '13:00:00', '14:30:00', 2),
            array('mercredi', '14:30:00', '16:00:00', 2),
            array('mercredi', '16:00:00', '17:30:00', 2),
            array('mercredi', '17:30:00', '19:00:00', 3),
            array('mercredi', '19:00:00', '20:00:00', 3),
            // Jeudi
            array('jeudi', '07:30:00', '08:30:00', 3),
            array('jeudi', '08:30:00', '10:00:00', 2),
            array('jeudi', '10:00:00', '11:30:00', 2),
            array('jeudi', '11:30:00', '13:00:00', 2),
            array('jeudi', '13:00:00', '14:30:00', 2),
            array('jeudi', '14:30:00', '16:00:00', 2),
            array('jeudi', '16:00:00', '17:30:00', 2),
            array('jeudi', '17:30:00', '19:00:00', 3),
            array('jeudi', '19:00:00', '20:00:00', 3),
            // Vendredi
            array('vendredi', '07:30:00', '08:30:00', 3),
            array('vendredi', '08:30:00', '10:00:00', 2),
            array('vendredi', '10:00:00', '11:30:00', 2),
            array('vendredi', '11:30:00', '13:00:00', 2),
            array('vendredi', '13:00:00', '14:30:00', 2),
            array('vendredi', '14:30:00', '16:00:00', 2),
            array('vendredi', '16:00:00', '17:30:00', 2),
            array('vendredi', '17:30:00', '19:00:00', 3),
            array('vendredi', '19:00:00', '20:00:00', 3),
            // Samedi
            array('samedi', '13:00:00', '14:30:00', 2),
            array('samedi', '14:30:00', '16:00:00', 2),
            array('samedi', '16:00:00', '17:30:00', 2),
            array('samedi', '17:30:00', '19:00:00', 3),
            array('samedi', '19:00:00', '20:00:00', 3),
            // Dimanche: none by default
        );
        foreach ($defaults as $d) {
            $wpdb->insert($this->table_schedules, array(
                'sheet_id' => intval($sheet_id),
                'day_of_week' => $d[0],
                'time_start' => $d[1],
                'time_end' => $d[2],
                'slot_count' => $d[3],
                'is_active' => 1
            ));
        }
    }

    /**
     * Update an existing schedule row by id
     */
    public function update_schedule($schedule_id, $fields) {
        global $wpdb;
        $allowed = array('day_of_week','time_start','time_end','slot_count','is_active');
        $data = array();
        foreach ($allowed as $k) {
            if (isset($fields[$k])) {
                $data[$k] = $fields[$k];
            }
        }
        if (empty($data)) return false;
        return $wpdb->update($this->table_schedules, $data, array('id' => intval($schedule_id)));
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
    
    // Note: Single update_schedule method retained (fields-based) to avoid duplicate signatures
}