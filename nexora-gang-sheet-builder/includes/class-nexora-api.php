<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_API {

    const NAMESPACE = 'nexora/v1';

    public function register_routes() {
        // Public / Builder Config
        register_rest_route(self::NAMESPACE, '/config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_config'],
            'permission_callback' => '__return_true',
        ]);

        // Products for Builder
        register_rest_route(self::NAMESPACE, '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_products'],
            'permission_callback' => '__return_true',
        ]);

        // File Upload
        register_rest_route(self::NAMESPACE, '/upload', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_upload'],
            'permission_callback' => '__return_true',
        ]);

        // Media Library
        register_rest_route(self::NAMESPACE, '/media', [
            'methods' => 'GET',
            'callback' => [$this, 'get_media'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/media/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_media'],
            'permission_callback' => '__return_true',
        ]);

        // Gang Sheets
        register_rest_route(self::NAMESPACE, '/sheets/save', [
            'methods' => 'POST',
            'callback' => [$this, 'save_sheet'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/sheets/(?P<token>[a-zA-Z0-9_\-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sheet'],
            'permission_callback' => '__return_true',
        ]);

        // Saved Designs
        register_rest_route(self::NAMESPACE, '/designs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_designs'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/designs/save', [
            'methods' => 'POST',
            'callback' => [$this, 'save_design'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/designs/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_design'],
            'permission_callback' => '__return_true',
        ]);

        // WooCommerce Add to Cart
        register_rest_route(self::NAMESPACE, '/cart/add', [
            'methods' => 'POST',
            'callback' => [$this, 'add_to_cart'],
            'permission_callback' => '__return_true',
        ]);

        // Admin Endpoints
        register_rest_route(self::NAMESPACE, '/admin/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_admin_stats'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'save_admin_settings'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/sheets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_admin_sheets'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Get builder global config
     */
    public function get_config() {
        $general = get_option('nexora_general_settings', []);
        $presets = get_option('nexora_sheet_presets', []);
        $pricing = get_option('nexora_pricing_settings', []);
        $wc_settings = get_option('nexora_woocommerce_settings', []);
        $cloud = get_option('nexora_cloud_settings', []);
        $upload = get_option('nexora_upload_settings', []);
        $export = get_option('nexora_export_settings', []);

        $currency = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/cart';
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '/checkout';

        return rest_ensure_response([
            'success' => true,
            'version' => NEXORA_VERSION,
            'currency' => $currency,
            'cart_url' => $cart_url,
            'checkout_url' => $checkout_url,
            'user' => [
                'is_logged_in' => is_user_logged_in(),
                'user_id' => get_current_user_id(),
                'display_name' => is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest',
            ],
            'general' => $general,
            'presets' => $presets,
            'pricing' => $pricing,
            'woocommerce' => $wc_settings,
            'cloud' => $cloud,
            'upload' => $upload,
            'export' => $export,
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Get products for builder
     */
    public function get_products(WP_REST_Request $request) {
        $products_data = [];

        if (function_exists('wc_get_products')) {
            $args = [
                'status' => 'publish',
                'limit' => 50,
                'orderby' => 'date',
                'order' => 'DESC',
            ];

            $products = wc_get_products($args);

            foreach ($products as $product) {
                // Check if gang sheet builder enabled or general
                $enabled = get_post_meta($product->get_id(), '_nexora_enabled', true);
                $is_gang_sheet_product = ($enabled === 'yes' || empty($enabled)); // allow all or explicitly enabled

                $variations = [];
                if ($product->is_type('variable')) {
                    foreach ($product->get_available_variations() as $var) {
                        $labels = [];
                        $parsed_w = 0;
                        $parsed_h = 0;
                        foreach ($var['attributes'] as $attr_key => $attr_val) {
                            $taxonomy = str_replace('attribute_', '', $attr_key);
                            $term = taxonomy_exists($taxonomy) ? get_term_by('slug', $attr_val, $taxonomy) : null;
                            $readable_name = $term ? $term->name : ucwords(str_replace(['-', '_'], ' ', $attr_val));
                            $labels[] = $readable_name;

                            // Parse dimensions like 22in-x-24in or 12-in-x-60-in or 10-x-10
                            $normalized = preg_replace('/(\d+)-(\d+)-in/i', '$1.$2-in', $attr_val);
                            if (preg_match('/(\d+(?:\.\d+)?)(?:-?in)?-?x-?(\d+(?:\.\d+)?)/i', $normalized, $m)) {
                                $parsed_w = floatval($m[1]);
                                $parsed_h = floatval($m[2]);
                            }
                        }

                        $label_str = !empty($labels) ? implode(', ', $labels) : ('Variation #' . $var['variation_id']);
                        $price_val = (float) $var['display_price'];

                        $variations[] = [
                            'id' => $var['variation_id'],
                            'name' => $label_str,
                            'label' => $label_str . ' (' . strip_tags(wc_price($price_val)) . ')',
                            'attributes' => $var['attributes'],
                            'price' => $price_val,
                            'display_price_html' => wc_price($price_val),
                            'is_in_stock' => $var['is_in_stock'],
                            'parsed_width' => $parsed_w,
                            'parsed_height' => $parsed_h,
                        ];
                    }
                }

                $image_id = $product->get_image_id();
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

                $products_data[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'slug' => $product->get_slug(),
                    'type' => $product->get_type(),
                    'price' => (float) $product->get_price(),
                    'regular_price' => (float) $product->get_regular_price(),
                    'price_html' => $product->get_price_html(),
                    'image' => $image_url,
                    'is_variable' => $product->is_type('variable'),
                    'variations' => $variations,
                    'builder_enabled' => ($enabled === 'yes'),
                    'sheet_width' => get_post_meta($product->get_id(), '_nexora_sheet_width', true) ?: 22,
                    'sheet_height' => get_post_meta($product->get_id(), '_nexora_sheet_height', true) ?: 60,
                ];
            }
        } else {
            // Fallback mock WooCommerce data if WooCommerce is not yet active on the WordPress install
            $products_data = [
                [
                    'id' => 101,
                    'name' => 'Custom DTF Gang Sheet (22" Wide)',
                    'slug' => 'custom-dtf-gang-sheet',
                    'type' => 'variable',
                    'price' => 35.00,
                    'regular_price' => 35.00,
                    'price_html' => '$35.00 - $189.99',
                    'image' => '',
                    'is_variable' => true,
                    'variations' => [
                        ['id' => 1011, 'attributes' => ['attribute_pa_size' => '22x24'], 'price' => 24.99, 'display_price_html' => '$24.99', 'is_in_stock' => true],
                        ['id' => 1012, 'attributes' => ['attribute_pa_size' => '22x36'], 'price' => 34.99, 'display_price_html' => '$34.99', 'is_in_stock' => true],
                        ['id' => 1013, 'attributes' => ['attribute_pa_size' => '22x60'], 'price' => 54.99, 'display_price_html' => '$54.99', 'is_in_stock' => true],
                        ['id' => 1014, 'attributes' => ['attribute_pa_size' => '22x120'], 'price' => 99.99, 'display_price_html' => '$99.99', 'is_in_stock' => true],
                    ],
                    'builder_enabled' => true,
                    'sheet_width' => 22,
                    'sheet_height' => 60,
                ],
                [
                    'id' => 102,
                    'name' => 'DTF Gang Sheet By The Foot ($7.50 / linear ft)',
                    'slug' => 'dtf-gang-sheet-by-foot',
                    'type' => 'simple',
                    'price' => 7.50,
                    'regular_price' => 7.50,
                    'price_html' => '$7.50 / linear ft',
                    'image' => '',
                    'is_variable' => false,
                    'variations' => [],
                    'builder_enabled' => true,
                    'sheet_width' => 22,
                    'sheet_height' => 12,
                ]
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'products' => $products_data,
        ]);
    }

    /**
     * File upload handler
     */
    public function handle_upload(WP_REST_Request $request) {
        $files = $request->get_file_params();
        if (empty($files) || !isset($files['file'])) {
            return new WP_Error('no_file', 'No file was provided.', ['status' => 400]);
        }

        $upload_result = Nexora_Export::handle_file_upload($files['file']);
        if (is_wp_error($upload_result)) {
            return $upload_result;
        }

        // Save into media library database
        $session_id = $request->get_header('x-session-id') ?: '';
        $media_id = Nexora_DB::save_media([
            'file_name' => $upload_result['file_name'],
            'file_url' => $upload_result['file_url'],
            'file_path' => $upload_result['file_path'],
            'original_width' => $upload_result['width'],
            'original_height' => $upload_result['height'],
            'original_dpi' => $upload_result['dpi'],
            'file_size' => $upload_result['size'],
            'mime_type' => $upload_result['mime_type'],
            'session_id' => $session_id,
        ]);

        return rest_ensure_response([
            'success' => true,
            'media' => [
                'id' => $media_id,
                'name' => $upload_result['file_name'],
                'url' => $upload_result['file_url'],
                'width' => $upload_result['width'],
                'height' => $upload_result['height'],
                'dpi' => $upload_result['dpi'],
                'size' => $upload_result['size'],
            ]
        ]);
    }

    /**
     * Get user uploaded media
     */
    public function get_media(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $session_id = $request->get_param('session_id') ?: ($request->get_header('x-session-id') ?: '');

        $media = Nexora_DB::get_user_media($user_id, $session_id, 100);

        return rest_ensure_response([
            'success' => true,
            'media' => $media,
        ]);
    }

    /**
     * Delete media
     */
    public function delete_media(WP_REST_Request $request) {
        $id = $request->get_param('id');
        $user_id = get_current_user_id();
        $deleted = Nexora_DB::delete_media($id, $user_id);

        return rest_ensure_response([
            'success' => (bool)$deleted,
        ]);
    }

    /**
     * Save gang sheet
     */
    public function save_sheet(WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (empty($body)) {
            return new WP_Error('empty_data', 'No sheet data provided.', ['status' => 400]);
        }

        $token = !empty($body['design_token']) ? sanitize_text_field($body['design_token']) : wp_generate_password(24, false);
        
        $preview_url = !empty($body['preview_url']) ? esc_url_raw($body['preview_url']) : '';
        if (!empty($body['preview_base64'])) {
            $saved_preview = Nexora_Export::save_preview_image($body['preview_base64'], 'preview-' . $token);
            if ($saved_preview) {
                $preview_url = $saved_preview['file_url'];
            }
        }

        $print_url = !empty($body['print_file_url']) ? esc_url_raw($body['print_file_url']) : '';
        if (!empty($body['print_base64'])) {
            $saved_print = Nexora_Export::save_base64_png($body['print_base64'], 'print-' . $token);
            if ($saved_print) {
                $print_url = $saved_print['file_url'];
            }
        }

        $data = [
            'design_token' => $token,
            'customer_id' => get_current_user_id(),
            'product_id' => isset($body['product_id']) ? intval($body['product_id']) : 0,
            'variation_id' => isset($body['variation_id']) ? intval($body['variation_id']) : 0,
            'title' => !empty($body['title']) ? sanitize_text_field($body['title']) : 'Gang Sheet ' . date('M j, Y'),
            'canvas_data' => is_string($body['canvas_data']) ? $body['canvas_data'] : wp_json_encode($body['canvas_data']),
            'width' => isset($body['width']) ? floatval($body['width']) : 22.0,
            'height' => isset($body['height']) ? floatval($body['height']) : 60.0,
            'unit' => isset($body['unit']) ? sanitize_text_field($body['unit']) : 'in',
            'item_count' => isset($body['item_count']) ? intval($body['item_count']) : 0,
            'utilization_rate' => isset($body['utilization_rate']) ? floatval($body['utilization_rate']) : 0.0,
            'preview_url' => $preview_url,
            'print_file_url' => $print_url,
            'pdf_file_url' => !empty($body['pdf_file_url']) ? esc_url_raw($body['pdf_file_url']) : '',
            'dpi' => isset($body['dpi']) ? intval($body['dpi']) : 300,
            'total_price' => isset($body['total_price']) ? floatval($body['total_price']) : 0.0,
            'status' => 'draft',
        ];

        $sheet_id = Nexora_DB::save_sheet($data);

        return rest_ensure_response([
            'success' => true,
            'sheet_id' => $sheet_id,
            'design_token' => $token,
            'preview_url' => $preview_url,
            'print_file_url' => $print_url,
        ]);
    }

    /**
     * Get sheet by token
     */
    public function get_sheet(WP_REST_Request $request) {
        $token = $request->get_param('token');
        $sheet = Nexora_DB::get_sheet_by_token($token);

        if (!$sheet) {
            return new WP_Error('not_found', 'Gang sheet not found', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'sheet' => $sheet,
        ]);
    }

    /**
     * Get user saved designs
     */
    public function get_designs(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $session_id = $request->get_param('session_id') ?: ($request->get_header('x-session-id') ?: '');

        $designs = Nexora_DB::get_user_designs($user_id, $session_id);

        return rest_ensure_response([
            'success' => true,
            'designs' => $designs,
        ]);
    }

    /**
     * Save user design
     */
    public function save_design(WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (empty($body)) {
            return new WP_Error('empty_data', 'No design data provided', ['status' => 400]);
        }

        $user_id = get_current_user_id();
        $session_id = $request->get_header('x-session-id') ?: '';

        $thumbnail_url = !empty($body['thumbnail_url']) ? esc_url_raw($body['thumbnail_url']) : '';
        if (!empty($body['thumbnail_base64'])) {
            $saved = Nexora_Export::save_preview_image($body['thumbnail_base64'], 'thumb');
            if ($saved) {
                $thumbnail_url = $saved['file_url'];
            }
        }

        $design_id = Nexora_DB::save_user_design([
            'id' => isset($body['id']) ? intval($body['id']) : 0,
            'user_id' => $user_id,
            'session_id' => $session_id,
            'name' => !empty($body['name']) ? sanitize_text_field($body['name']) : 'My Gang Sheet ' . date('M j, Y H:i'),
            'thumbnail_url' => $thumbnail_url,
            'canvas_json' => is_string($body['canvas_json']) ? $body['canvas_json'] : wp_json_encode($body['canvas_json']),
            'width' => isset($body['width']) ? floatval($body['width']) : 22.0,
            'height' => isset($body['height']) ? floatval($body['height']) : 60.0,
            'unit' => isset($body['unit']) ? sanitize_text_field($body['unit']) : 'in',
            'sheet_count' => isset($body['sheet_count']) ? intval($body['sheet_count']) : 1,
            'metadata' => isset($body['metadata']) ? (is_string($body['metadata']) ? $body['metadata'] : wp_json_encode($body['metadata'])) : '[]',
        ]);

        return rest_ensure_response([
            'success' => true,
            'design_id' => $design_id,
            'thumbnail_url' => $thumbnail_url,
        ]);
    }

    /**
     * Delete user design
     */
    public function delete_design(WP_REST_Request $request) {
        $id = $request->get_param('id');
        $user_id = get_current_user_id();
        $deleted = Nexora_DB::delete_user_design($id, $user_id);

        return rest_ensure_response([
            'success' => (bool)$deleted,
        ]);
    }

    /**
     * Add gang sheet to WooCommerce cart
     */
    public function add_to_cart(WP_REST_Request $request) {
        $body = $request->get_json_params() ?: [];
        $result = Nexora_WooCommerce::add_gang_sheet_to_cart($body);

        if (is_wp_error($result)) {
            return new WP_Error('cart_error', $result->get_error_message(), ['status' => 400]);
        }

        $wc_settings = get_option('nexora_woocommerce_settings', []);
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : site_url('/checkout/');
        if (isset($wc_settings['redirect_after_add']) && $wc_settings['redirect_after_add'] === 'checkout') {
            $result['redirect_url'] = $checkout_url;
        }

        return rest_ensure_response(array_merge(['success' => true], $result));
    }

    /**
     * Admin stats
     */
    public function get_admin_stats() {
        global $wpdb;
        $table = Nexora_DB::get_table_name('sheets');
        
        $total_sheets = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $ordered_sheets = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'ordered' OR order_id > 0");
        $total_revenue = (float) $wpdb->get_var("SELECT SUM(total_price) FROM $table WHERE status = 'ordered' OR order_id > 0");

        $recent_sheets = Nexora_DB::get_recent_sheets(10);

        return rest_ensure_response([
            'success' => true,
            'stats' => [
                'total_sheets' => $total_sheets,
                'ordered_sheets' => $ordered_sheets,
                'total_revenue' => $total_revenue,
            ],
            'recent_sheets' => $recent_sheets,
        ]);
    }

    /**
     * Save admin settings
     */
    public function save_admin_settings(WP_REST_Request $request) {
        $body = $request->get_json_params();

        if (isset($body['general'])) {
            update_option('nexora_general_settings', $body['general']);
        }
        if (isset($body['presets'])) {
            update_option('nexora_sheet_presets', $body['presets']);
        }
        if (isset($body['pricing'])) {
            update_option('nexora_pricing_settings', $body['pricing']);
        }
        if (isset($body['woocommerce'])) {
            update_option('nexora_woocommerce_settings', $body['woocommerce']);
        }
        if (isset($body['upload'])) {
            update_option('nexora_upload_settings', $body['upload']);
        }
        if (isset($body['export'])) {
            update_option('nexora_export_settings', $body['export']);
        }
        if (isset($body['cloud'])) {
            update_option('nexora_cloud_settings', $body['cloud']);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Settings updated successfully.',
        ]);
    }

    /**
     * Admin sheets list
     */
    public function get_admin_sheets(WP_REST_Request $request) {
        $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 25;
        $offset = $request->get_param('offset') ? intval($request->get_param('offset')) : 0;
        $search = $request->get_param('search') ?: '';
        $status = $request->get_param('status') ?: '';

        $sheets = Nexora_DB::get_recent_sheets($limit, $offset, $search, $status);
        $total = Nexora_DB::count_sheets($status);

        return rest_ensure_response([
            'success' => true,
            'sheets' => $sheets,
            'total' => $total,
        ]);
    }
}

