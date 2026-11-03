<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_DB {

    public static function get_table_name($type = 'sheets') {
        global $wpdb;
        switch ($type) {
            case 'designs':
                return $wpdb->prefix . 'nexora_saved_designs';
            case 'media':
                return $wpdb->prefix . 'nexora_media_library';
            case 'sheets':
            default:
                return $wpdb->prefix . 'nexora_gang_sheets';
        }
    }

    /**
     * Save or update a gang sheet
     */
    public static function save_sheet($data) {
        global $wpdb;
        $table = self::get_table_name('sheets');

        $defaults = [
            'design_token' => wp_generate_password(24, false),
            'customer_id' => get_current_user_id(),
            'order_id' => 0,
            'product_id' => 0,
            'variation_id' => 0,
            'title' => 'Untitled Gang Sheet',
            'canvas_data' => '{}',
            'width' => 22.0,
            'height' => 60.0,
            'unit' => 'in',
            'item_count' => 0,
            'utilization_rate' => 0.0,
            'preview_url' => '',
            'print_file_url' => '',
            'pdf_file_url' => '',
            'dpi' => 300,
            'total_price' => 0.0,
            'status' => 'draft',
        ];

        $payload = wp_parse_args($data, $defaults);

        if (!empty($payload['id'])) {
            $id = intval($payload['id']);
            unset($payload['id']);
            $wpdb->update($table, $payload, ['id' => $id]);
            return $id;
        }

        // Check if existing token
        if (!empty($payload['design_token'])) {
            $existing = self::get_sheet_by_token($payload['design_token']);
            if ($existing) {
                $wpdb->update($table, $payload, ['design_token' => $payload['design_token']]);
                return $existing->id;
            }
        }

        $wpdb->insert($table, $payload);
        return $wpdb->insert_id;
    }

    /**
     * Get sheet by token
     */
    public static function get_sheet_by_token($token) {
        global $wpdb;
        $table = self::get_table_name('sheets');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE design_token = %s LIMIT 1", sanitize_text_field($token)));
    }

    /**
     * Get sheet by ID
     */
    public static function get_sheet_by_id($id) {
        global $wpdb;
        $table = self::get_table_name('sheets');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d LIMIT 1", intval($id)));
    }

    /**
     * Get sheets by Order ID
     */
    public static function get_sheets_by_order_id($order_id) {
        global $wpdb;
        $table = self::get_table_name('sheets');
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE order_id = %d ORDER BY id ASC", intval($order_id)));
    }

    /**
     * Get recent sheets for admin
     */
    public static function get_recent_sheets($limit = 50, $offset = 0, $search = '', $status = '') {
        global $wpdb;
        $table = self::get_table_name('sheets');
        
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (title LIKE %s OR design_token LIKE %s)";
            $wildcard = '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%';
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if (!empty($status) && $status !== 'all') {
            $where .= " AND status = %s";
            $params[] = sanitize_text_field($status);
        }

        $query = "SELECT * FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = intval($limit);
        $params[] = intval($offset);

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    public static function count_sheets($status = '') {
        global $wpdb;
        $table = self::get_table_name('sheets');
        if (!empty($status) && $status !== 'all') {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", sanitize_text_field($status)));
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Save customer user design
     */
    public static function save_user_design($data) {
        global $wpdb;
        $table = self::get_table_name('designs');

        $defaults = [
            'user_id' => get_current_user_id(),
            'session_id' => session_id() ?: '',
            'name' => 'My Gang Sheet ' . date_i18n('M j, Y H:i'),
            'thumbnail_url' => '',
            'canvas_json' => '{}',
            'width' => 22.0,
            'height' => 60.0,
            'unit' => 'in',
            'sheet_count' => 1,
            'metadata' => '[]',
        ];

        $payload = wp_parse_args($data, $defaults);

        if (!empty($payload['id'])) {
            $id = intval($payload['id']);
            unset($payload['id']);
            $wpdb->update($table, $payload, ['id' => $id]);
            return $id;
        }

        $wpdb->insert($table, $payload);
        return $wpdb->insert_id;
    }

    /**
     * Get user designs
     */
    public static function get_user_designs($user_id = 0, $session_id = '') {
        global $wpdb;
        $table = self::get_table_name('designs');

        if ($user_id > 0) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY updated_at DESC LIMIT 50",
                intval($user_id)
            ));
        } elseif (!empty($session_id)) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE session_id = %s ORDER BY updated_at DESC LIMIT 50",
                sanitize_text_field($session_id)
            ));
        }

        return [];
    }

    /**
     * Delete user design
     */
    public static function delete_user_design($id, $user_id = 0) {
        global $wpdb;
        $table = self::get_table_name('designs');
        if ($user_id > 0) {
            return $wpdb->delete($table, ['id' => intval($id), 'user_id' => intval($user_id)]);
        }
        return $wpdb->delete($table, ['id' => intval($id)]);
    }

    /**
     * Save uploaded media record
     */
    public static function save_media($data) {
        global $wpdb;
        $table = self::get_table_name('media');

        $defaults = [
            'user_id' => get_current_user_id(),
            'session_id' => session_id() ?: '',
            'file_name' => '',
            'file_url' => '',
            'file_path' => '',
            'original_width' => 0,
            'original_height' => 0,
            'original_dpi' => 300,
            'file_size' => 0,
            'mime_type' => 'image/png',
        ];

        $payload = wp_parse_args($data, $defaults);
        $wpdb->insert($table, $payload);
        return $wpdb->insert_id;
    }

    /**
     * Get media by user or session
     */
    public static function get_user_media($user_id = 0, $session_id = '', $limit = 60) {
        global $wpdb;
        $table = self::get_table_name('media');

        if ($user_id > 0) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT %d",
                intval($user_id),
                intval($limit)
            ));
        } elseif (!empty($session_id)) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE session_id = %s ORDER BY id DESC LIMIT %d",
                sanitize_text_field($session_id),
                intval($limit)
            ));
        }

        return [];
    }

    /**
     * Delete media item
     */
    public static function delete_media($id, $user_id = 0) {
        global $wpdb;
        $table = self::get_table_name('media');
        
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)));
        if ($item) {
            if ($user_id > 0 && intval($item->user_id) !== intval($user_id) && !current_user_can('manage_options')) {
                return false;
            }
            if (!empty($item->file_path) && file_exists($item->file_path)) {
                @unlink($item->file_path);
            }
            return $wpdb->delete($table, ['id' => intval($id)]);
        }
        return false;
    }
}

