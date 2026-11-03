<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_Activator {

    public static function activate() {
        self::create_tables();
        self::create_directories();
        self::set_default_options();
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table for Gang Sheets (associated with carts/orders)
        $table_gang_sheets = $wpdb->prefix . 'nexora_gang_sheets';
        $sql1 = "CREATE TABLE $table_gang_sheets (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            design_token varchar(64) NOT NULL,
            customer_id bigint(20) unsigned DEFAULT 0,
            order_id bigint(20) unsigned DEFAULT 0,
            product_id bigint(20) unsigned DEFAULT 0,
            variation_id bigint(20) unsigned DEFAULT 0,
            title varchar(255) DEFAULT 'Untitled Gang Sheet',
            canvas_data longtext NOT NULL,
            width decimal(10,2) NOT NULL DEFAULT 22.00,
            height decimal(10,2) NOT NULL DEFAULT 60.00,
            unit varchar(10) NOT NULL DEFAULT 'in',
            item_count int(11) NOT NULL DEFAULT 0,
            utilization_rate decimal(5,2) DEFAULT 0.00,
            preview_url text DEFAULT NULL,
            print_file_url text DEFAULT NULL,
            pdf_file_url text DEFAULT NULL,
            dpi int(11) DEFAULT 300,
            total_price decimal(10,2) DEFAULT 0.00,
            status varchar(50) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY design_token (design_token),
            KEY customer_id (customer_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql1);

        // Table for Saved Customer Designs
        $table_saved_designs = $wpdb->prefix . 'nexora_saved_designs';
        $sql2 = "CREATE TABLE $table_saved_designs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0,
            session_id varchar(100) DEFAULT '',
            name varchar(255) NOT NULL DEFAULT 'My Saved Gang Sheet',
            thumbnail_url text DEFAULT NULL,
            canvas_json longtext NOT NULL,
            width decimal(10,2) NOT NULL DEFAULT 22.00,
            height decimal(10,2) NOT NULL DEFAULT 60.00,
            unit varchar(10) NOT NULL DEFAULT 'in',
            sheet_count int(11) NOT NULL DEFAULT 1,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        dbDelta($sql2);

        // Table for Uploaded Artwork Media
        $table_media = $wpdb->prefix . 'nexora_media_library';
        $sql3 = "CREATE TABLE $table_media (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0,
            session_id varchar(100) DEFAULT '',
            file_name varchar(255) NOT NULL,
            file_url text NOT NULL,
            file_path text NOT NULL,
            original_width int(11) DEFAULT 0,
            original_height int(11) DEFAULT 0,
            original_dpi int(11) DEFAULT 300,
            file_size bigint(20) DEFAULT 0,
            mime_type varchar(100) DEFAULT 'image/png',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        dbDelta($sql3);

        update_option('nexora_db_version', NEXORA_DB_VERSION);
    }

    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/nexora-gang-sheets';

        $subdirs = [
            $base_dir,
            $base_dir . '/previews',
            $base_dir . '/prints',
            $base_dir . '/uploads',
            $base_dir . '/pdfs',
            $base_dir . '/temp'
        ];

        foreach ($subdirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                // Put index.php silence guard
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
            }
        }

        // Add htaccess protect for prints folder to prevent hotlinking or unauthorized direct browsing
        $htaccess_path = $base_dir . '/prints/.htaccess';
        if (!file_exists($htaccess_path)) {
            $htaccess_content = "Options -Indexes\n";
            file_put_contents($htaccess_path, $htaccess_content);
        }
    }

    private static function set_default_options() {
        if (!get_option('nexora_general_settings')) {
            update_option('nexora_general_settings', [
                'unit' => 'in',
                'default_dpi' => 300,
                'min_dpi_warning' => 250,
                'default_sheet_width' => 22.0,
                'default_sheet_height' => 60.0,
                'print_margin' => 0.25,
                'snap_to_grid' => true,
                'grid_size' => 0.5,
                'auto_save_interval' => 30,
                'dark_mode' => true,
            ]);
        }

        if (!get_option('nexora_sheet_presets')) {
            update_option('nexora_sheet_presets', [
                ['id' => '22x24', 'name' => 'Small Gang Sheet (22" x 24")', 'width' => 22, 'height' => 24, 'unit' => 'in', 'price' => 24.99, 'is_default' => false],
                ['id' => '22x36', 'name' => 'Standard Gang Sheet (22" x 36")', 'width' => 22, 'height' => 36, 'unit' => 'in', 'price' => 34.99, 'is_default' => false],
                ['id' => '22x60', 'name' => 'Popular Gang Sheet (22" x 60")', 'width' => 22, 'height' => 60, 'unit' => 'in', 'price' => 54.99, 'is_default' => true],
                ['id' => '22x120', 'name' => 'Large Gang Sheet (22" x 120")', 'width' => 22, 'height' => 120, 'unit' => 'in', 'price' => 99.99, 'is_default' => false],
                ['id' => '22x240', 'name' => 'Roll 20ft (22" x 240")', 'width' => 22, 'height' => 240, 'unit' => 'in', 'price' => 189.99, 'is_default' => false],
                ['id' => '13x19', 'name' => 'Desktop Sheet (13" x 19")', 'width' => 13, 'height' => 19, 'unit' => 'in', 'price' => 14.99, 'is_default' => false]
            ]);
        }

        if (!get_option('nexora_pricing_settings')) {
            update_option('nexora_pricing_settings', [
                'pricing_mode' => 'tiered', // 'fixed', 'sq_ft', 'sq_in', 'tiered'
                'price_per_sq_ft' => 7.50,
                'price_per_sq_in' => 0.052,
                'min_price' => 15.00,
                'tiered_pricing' => [
                    ['min_sqft' => 1, 'max_sqft' => 5, 'rate' => 8.50],
                    ['min_sqft' => 6, 'max_sqft' => 15, 'rate' => 7.20],
                    ['min_sqft' => 16, 'max_sqft' => 30, 'rate' => 6.50],
                    ['min_sqft' => 31, 'max_sqft' => 9999, 'rate' => 5.50]
                ]
            ]);
        }

        if (!get_option('nexora_woocommerce_settings')) {
            update_option('nexora_woocommerce_settings', [
                'enabled' => true,
                'button_text' => 'Build Your DTF Gang Sheet',
                'button_location' => 'woocommerce_after_add_to_cart_button',
                'button_style' => 'primary',
                'redirect_target' => 'builder_modal', // 'builder_modal' or 'builder_page'
                'builder_page_id' => 0,
                'redirect_after_add' => 'cart', // 'cart' or 'checkout' or 'stay'
                'enable_preview_in_cart' => true,
                'enable_preview_in_order' => true,
                'enable_customer_download' => true,
            ]);
        }

        if (!get_option('nexora_upload_settings')) {
            update_option('nexora_upload_settings', [
                'allowed_extensions' => ['png', 'jpg', 'jpeg', 'webp', 'svg', 'tif', 'tiff', 'pdf'],
                'max_file_size_mb' => 150,
                'auto_remove_bg' => false,
                'retain_temp_files_days' => 30,
                'dpi_threshold_low' => 150,
                'dpi_threshold_good' => 250,
                'dpi_threshold_optimal' => 300
            ]);
        }

        if (!get_option('nexora_export_settings')) {
            update_option('nexora_export_settings', [
                'default_dpi' => 300,
                'export_format' => 'both', // 'png', 'pdf', 'both'
                'transparent_bg' => true,
                'bleed_margin' => 0.125,
                'mirror_print' => false,
                'include_cut_lines' => false,
                'color_profile' => 'sRGB'
            ]);
        }

        if (!get_option('nexora_cloud_settings')) {
            update_option('nexora_cloud_settings', [
                'google_drive_enabled' => true,
                'google_client_id' => '',
                'dropbox_enabled' => true,
                'dropbox_app_key' => '',
                'cloud_storage_enabled' => true
            ]);
        }
    }
}

