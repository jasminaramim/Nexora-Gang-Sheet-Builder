<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_WooCommerce {

    public function __construct() {
        // Product Page Button
        $wc_settings = get_option('nexora_woocommerce_settings', []);
        $hook = !empty($wc_settings['button_location']) ? $wc_settings['button_location'] : 'woocommerce_after_add_to_cart_button';
        add_action($hook, [$this, 'render_product_page_builder_button'], 20);

        // Product Metabox for Gang Sheet Settings
        add_action('add_meta_boxes', [$this, 'add_product_metabox']);
        add_action('save_post_product', [$this, 'save_product_metabox']);

        // WooCommerce Cart & Checkout Hooks
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_cart_item_from_session'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'calculate_custom_cart_price'], 20, 1);
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'render_cart_item_thumbnail'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'render_cart_item_data'], 10, 2);
        add_filter('woocommerce_cart_item_name', [$this, 'render_cart_item_name'], 10, 3);

        // WooCommerce Order & Email Hooks
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_meta'], 10, 4);
        add_action('woocommerce_thankyou', [$this, 'link_sheets_to_order'], 10, 1);
        add_filter('woocommerce_order_item_name', [$this, 'render_order_item_name'], 10, 3);

        // WooCommerce Admin Order Display
        add_action('woocommerce_after_order_itemmeta', [$this, 'render_order_item_admin_preview'], 10, 3);
        add_action('add_meta_boxes', [$this, 'add_order_metabox']);

        // Customer Account View Order
        add_action('woocommerce_order_item_meta_end', [$this, 'render_customer_order_preview'], 10, 3);

        // AJAX Add to Cart (Works for both logged in and guest customers)
        add_action('wp_ajax_nexora_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_nexora_add_to_cart', [$this, 'ajax_add_to_cart']);
    }

    /**
     * Get or create a guaranteed valid, purchasable product for Gang Sheets
     */
    public static function get_or_create_gang_sheet_product($preferred_id = 0) {
        if ($preferred_id > 0 && function_exists('wc_get_product')) {
            $p = wc_get_product($preferred_id);
            if ($p && ($p->is_type('simple') || $p->is_type('variable')) && $p->is_purchasable()) {
                return $preferred_id;
            }
        }

        $default_pid = intval(get_option('nexora_default_product_id', 0));
        if ($default_pid > 0 && function_exists('wc_get_product')) {
            $p = wc_get_product($default_pid);
            if ($p && $p->is_purchasable()) {
                return $default_pid;
            }
        }

        // Check if any published simple product exists
        if (function_exists('wc_get_products')) {
            $existing = wc_get_products([
                'status' => 'publish',
                'type' => 'simple',
                'limit' => 1,
            ]);
            if (!empty($existing)) {
                $pid = $existing[0]->get_id();
                update_option('nexora_default_product_id', $pid);
                return $pid;
            }
        }

        // Auto-create a dedicated simple product
        if (class_exists('WC_Product_Simple')) {
            $new_prod = new WC_Product_Simple();
            $new_prod->set_name('Custom DTF Gang Sheet');
            $new_prod->set_status('publish');
            $new_prod->set_catalog_visibility('visible');
            $new_prod->set_price(25.00);
            $new_prod->set_regular_price(25.00);
            $new_prod->set_description('Custom Direct-to-Film (DTF) Gang Sheet created with Nexora Builder.');
            $pid = $new_prod->save();
            update_option('nexora_default_product_id', $pid);
            return $pid;
        }

        return $preferred_id;
    }

    /**
     * Centralized, bulletproof Add to Cart for Gang Sheets (supports Simple & Variable products)
     */
    public static function add_gang_sheet_to_cart($body) {
        if (!function_exists('WC')) {
            return new WP_Error('wc_missing', __('WooCommerce is not installed or active.', 'nexora-gang-sheet-builder'));
        }

        if (defined('WC_ABSPATH')) {
            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
        }
        if (null === WC()->session) {
            $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
            WC()->session = new $session_class();
            WC()->session->init();
        }
        if (null === WC()->customer) {
            WC()->customer = new WC_Customer(get_current_user_id(), true);
        }
        if (null === WC()->cart) {
            WC()->cart = new WC_Cart();
        }
        WC()->cart->get_cart();

        $requested_product_id = isset($body['product_id']) ? intval($body['product_id']) : 0;
        $requested_variation_id = isset($body['variation_id']) ? intval($body['variation_id']) : 0;
        $quantity = isset($body['quantity']) ? max(1, intval($body['quantity'])) : 1;
        $passed_attributes = (isset($body['variation']) && is_array($body['variation'])) ? $body['variation'] : [];

        $sheet_w = isset($body['width']) ? floatval($body['width']) : 22.0;
        $sheet_h = isset($body['height']) ? floatval($body['height']) : 60.0;
        $sheet_unit = isset($body['unit']) ? sanitize_text_field($body['unit']) : 'in';
        $total_price = isset($body['total_price']) ? floatval($body['total_price']) : 0.0;

        $target_product_id = 0;
        $target_variation_id = 0;
        $variation_attributes = [];

        // Check if requested product exists
        $product = $requested_product_id > 0 ? wc_get_product($requested_product_id) : null;

        if ($product) {
            if ($product->is_type('variable')) {
                $target_product_id = $product->get_id();
                
                // If a specific variation ID was passed, verify it belongs to this parent
                if ($requested_variation_id > 0) {
                    $var_obj = wc_get_product($requested_variation_id);
                    if ($var_obj && $var_obj->is_type('variation') && $var_obj->get_parent_id() === $target_product_id) {
                        $target_variation_id = $requested_variation_id;
                        $variation_attributes = !empty($passed_attributes) ? $passed_attributes : $var_obj->get_variation_attributes();
                    }
                }

                // If no variation verified yet, try to auto-match variation by sheet dimensions
                if (!$target_variation_id) {
                    $available_vars = $product->get_available_variations();
                    
                    // Match dimensions in attributes (e.g. 22in-x-24in or 12-in-x-60-in)
                    $w_str = (string) round($sheet_w);
                    $h_str = (string) round($sheet_h);
                    foreach ($available_vars as $av) {
                        $attr_str = strtolower(json_encode($av['attributes']));
                        if (strpos($attr_str, $w_str) !== false && strpos($attr_str, $h_str) !== false) {
                            $target_variation_id = $av['variation_id'];
                            $var_obj = wc_get_product($target_variation_id);
                            $variation_attributes = $var_obj ? $var_obj->get_variation_attributes() : $av['attributes'];
                            break;
                        }
                    }

                    // Fallback to first available variation
                    if (!$target_variation_id && !empty($available_vars)) {
                        $target_variation_id = $available_vars[0]['variation_id'];
                        $var_obj = wc_get_product($target_variation_id);
                        $variation_attributes = $var_obj ? $var_obj->get_variation_attributes() : $available_vars[0]['attributes'];
                    }
                }
            } elseif ($product->is_type('variation')) {
                $target_variation_id = $product->get_id();
                $target_product_id = $product->get_parent_id();
                $variation_attributes = !empty($passed_attributes) ? $passed_attributes : $product->get_variation_attributes();
            } else {
                // Simple product
                $target_product_id = $product->get_id();
                $target_variation_id = 0;
                $variation_attributes = [];
            }
        }

        // Fallback to default product if target product is still missing or invalid
        if (!$target_product_id) {
            $target_product_id = self::get_or_create_gang_sheet_product();
            $target_variation_id = 0;
            $variation_attributes = [];
        }

        // Save sheet record in DB
        $token = !empty($body['design_token']) ? sanitize_text_field($body['design_token']) : ('nxr-' . wp_generate_password(20, false));
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

        // Server-Side Dynamic Price Determination: Fetch actual WooCommerce price
        $target_item_obj = $target_variation_id > 0 ? wc_get_product($target_variation_id) : wc_get_product($target_product_id);
        $server_unit_price = 0.0;

        if ($target_item_obj && is_a($target_item_obj, 'WC_Product')) {
            $server_unit_price = (float) $target_item_obj->get_price();
        }

        // If product/variation has no fixed price, calculate server-side using pricing tiers
        if ($server_unit_price <= 0) {
            $total_sq_in = $sheet_w * $sheet_h;
            $total_sq_ft = $total_sq_in / 144.0;
            $pricing_settings = get_option('nexora_pricing_settings', []);
            $mode = !empty($pricing_settings['pricing_mode']) ? $pricing_settings['pricing_mode'] : 'tiered';
            
            if ($mode === 'sq_in') {
                $rate = !empty($pricing_settings['price_per_sq_in']) ? floatval($pricing_settings['price_per_sq_in']) : 0.052;
                $server_unit_price = max(15.00, round($total_sq_in * $rate, 2));
            } elseif ($mode === 'sq_ft') {
                $rate = !empty($pricing_settings['price_per_sq_ft']) ? floatval($pricing_settings['price_per_sq_ft']) : 7.50;
                $server_unit_price = max(15.00, round($total_sq_ft * $rate, 2));
            } elseif ($mode === 'tiered' && !empty($pricing_settings['tiered_pricing'])) {
                $rate = 7.50;
                foreach ($pricing_settings['tiered_pricing'] as $tier) {
                    if ($total_sq_ft >= floatval($tier['min_sqft']) && $total_sq_ft <= floatval($tier['max_sqft'])) {
                        $rate = floatval($tier['rate']);
                        break;
                    }
                }
                $server_unit_price = max(15.00, round($total_sq_ft * $rate, 2));
            } else {
                $server_unit_price = 25.00;
            }
        }

        $total_price = round($server_unit_price * $quantity, 2);

        $sheet_id = Nexora_DB::save_sheet([
            'design_token' => $token,
            'customer_id' => get_current_user_id(),
            'product_id' => $target_product_id,
            'variation_id' => $target_variation_id,
            'title' => !empty($body['title']) ? sanitize_text_field($body['title']) : 'DTF Gang Sheet (' . $sheet_w . 'x' . $sheet_h . ')',
            'canvas_data' => !empty($body['canvas_data']) ? (is_string($body['canvas_data']) ? $body['canvas_data'] : wp_json_encode($body['canvas_data'])) : '{}',
            'width' => $sheet_w,
            'height' => $sheet_h,
            'unit' => $sheet_unit,
            'item_count' => isset($body['item_count']) ? intval($body['item_count']) : 0,
            'utilization_rate' => isset($body['utilization_rate']) ? floatval($body['utilization_rate']) : 0.0,
            'preview_url' => $preview_url,
            'print_file_url' => $print_url,
            'total_price' => $total_price,
            'status' => 'pending_order',
        ]);

        $custom_cart_data = [
            'nexora_sheet_id' => $sheet_id,
            'nexora_design_token' => $token,
            'nexora_width' => $sheet_w,
            'nexora_height' => $sheet_h,
            'nexora_unit' => $sheet_unit,
            'nexora_item_count' => isset($body['item_count']) ? intval($body['item_count']) : 0,
            'nexora_utilization' => isset($body['utilization_rate']) ? floatval($body['utilization_rate']) : 0.0,
            'nexora_preview_url' => $preview_url,
            'nexora_print_file_url' => $print_url,
            'nexora_custom_price' => $server_unit_price,
            'nexora_unit_price' => $server_unit_price,
            'nexora_total_price' => $total_price,
        ];

        // Attempt 1: Add target product/variation to cart
        $cart_item_key = WC()->cart->add_to_cart(
            $target_product_id,
            $quantity,
            $target_variation_id,
            $variation_attributes,
            ['nexora_data' => $custom_cart_data]
        );

        // Attempt 2: If failed, fallback to guaranteed default simple product
        if (!$cart_item_key) {
            $fallback_id = self::get_or_create_gang_sheet_product();
            $cart_item_key = WC()->cart->add_to_cart(
                $fallback_id,
                $quantity,
                0,
                [],
                ['nexora_data' => $custom_cart_data]
            );
            $target_product_id = $fallback_id;
            $target_variation_id = 0;
        }

        // Force session save & customer cookie so redirect to /cart/ displays the item immediately
        if (WC()->session) {
            WC()->session->save_data();
            if (!headers_sent() && !is_user_logged_in() && method_exists(WC()->session, 'set_customer_session_cookie')) {
                WC()->session->set_customer_session_cookie(true);
            }
        }
        if (method_exists(WC()->cart, 'maybe_set_cart_cookies')) {
            WC()->cart->maybe_set_cart_cookies();
        }

        $cart_url = wc_get_cart_url();
        $fallback_direct_url = add_query_arg([
            'add-to-cart' => ($target_variation_id > 0 ? $target_variation_id : $target_product_id),
            'nexora_token' => $token,
        ], $cart_url);

        return [
            'cart_item_key' => $cart_item_key,
            'cart_url' => $cart_url,
            'fallback_direct_url' => $fallback_direct_url,
            'redirect_url' => $cart_url,
            'sheet_id' => $sheet_id,
            'design_token' => $token,
            'preview_url' => $preview_url,
            'product_id' => $target_product_id,
            'variation_id' => $target_variation_id,
        ];
    }

    /**
     * AJAX handler for seamless Add to Cart
     */
    public function ajax_add_to_cart() {
        $raw_input = file_get_contents('php://input');
        $body = json_decode($raw_input, true);
        if (!$body && !empty($_POST)) {
            $body = $_POST;
        }

        $result = self::add_gang_sheet_to_cart($body ?: []);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * Render Builder button on Single Product Page
     */
    public function render_product_page_builder_button() {
        global $product;
        if (!$product) {
            return;
        }

        $wc_settings = get_option('nexora_woocommerce_settings', []);
        $enabled_global = !isset($wc_settings['enabled']) || $wc_settings['enabled'];
        if (!$enabled_global) {
            return;
        }

        $product_id = $product->get_id();
        $product_enabled = get_post_meta($product_id, '_nexora_enabled', true);

        // If not explicitly disabled, show button
        if ($product_enabled === 'no') {
            return;
        }

        $button_text = !empty($wc_settings['button_text']) ? esc_html($wc_settings['button_text']) : __('Build Your DTF Gang Sheet', 'nexora-gang-sheet-builder');
        $sheet_w = get_post_meta($product_id, '_nexora_sheet_width', true) ?: 22;
        $sheet_h = get_post_meta($product_id, '_nexora_sheet_height', true) ?: 60;

        echo '<div class="nexora-product-btn-wrapper" style="margin: 15px 0;">';
        echo '<button type="button" class="button alt nexora-open-builder-btn" data-product-id="' . esc_attr($product_id) . '" data-sheet-width="' . esc_attr($sheet_w) . '" data-sheet-height="' . esc_attr($sheet_h) . '" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff; border: none; padding: 12px 24px; font-weight: 600; font-size: 15px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);">';
        echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>';
        echo '<span>' . $button_text . '</span>';
        echo '</button>';
        echo '</div>';
    }

    /**
     * Add admin product metabox
     */
    public function add_product_metabox() {
        add_meta_box(
            'nexora_product_settings',
            __('Nexora DTF Gang Sheet Builder', 'nexora-gang-sheet-builder'),
            [$this, 'render_product_metabox'],
            'product',
            'side',
            'default'
        );
    }

    public function render_product_metabox($post) {
        wp_nonce_field('nexora_save_product_meta', 'nexora_meta_nonce');

        $enabled = get_post_meta($post->ID, '_nexora_enabled', true);
        if ($enabled === '') {
            $enabled = 'yes';
        }
        $width = get_post_meta($post->ID, '_nexora_sheet_width', true) ?: 22;
        $height = get_post_meta($post->ID, '_nexora_sheet_height', true) ?: 60;
        $pricing_mode = get_post_meta($post->ID, '_nexora_pricing_mode', true) ?: 'builder';
        ?>
        <p>
            <label>
                <input type="checkbox" name="_nexora_enabled" value="yes" <?php checked($enabled, 'yes'); ?> />
                <strong><?php _e('Enable Gang Sheet Builder', 'nexora-gang-sheet-builder'); ?></strong>
            </label>
        </p>
        <p>
            <label for="_nexora_sheet_width"><?php _e('Default Width (inches):', 'nexora-gang-sheet-builder'); ?></label><br/>
            <input type="number" step="0.1" name="_nexora_sheet_width" id="_nexora_sheet_width" value="<?php echo esc_attr($width); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="_nexora_sheet_height"><?php _e('Default Height (inches):', 'nexora-gang-sheet-builder'); ?></label><br/>
            <input type="number" step="0.1" name="_nexora_sheet_height" id="_nexora_sheet_height" value="<?php echo esc_attr($height); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="_nexora_pricing_mode"><?php _e('Pricing Source:', 'nexora-gang-sheet-builder'); ?></label><br/>
            <select name="_nexora_pricing_mode" id="_nexora_pricing_mode" style="width:100%;">
                <option value="builder" <?php selected($pricing_mode, 'builder'); ?>><?php _e('Calculated by Builder Engine', 'nexora-gang-sheet-builder'); ?></option>
                <option value="product" <?php selected($pricing_mode, 'product'); ?>><?php _e('WooCommerce Product Base Price', 'nexora-gang-sheet-builder'); ?></option>
            </select>
        </p>
        <?php
    }

    public function save_product_metabox($post_id) {
        if (!isset($_POST['nexora_meta_nonce']) || !wp_verify_nonce($_POST['nexora_meta_nonce'], 'nexora_save_product_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $enabled = isset($_POST['_nexora_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_nexora_enabled', $enabled);

        if (isset($_POST['_nexora_sheet_width'])) {
            update_post_meta($post_id, '_nexora_sheet_width', floatval($_POST['_nexora_sheet_width']));
        }
        if (isset($_POST['_nexora_sheet_height'])) {
            update_post_meta($post_id, '_nexora_sheet_height', floatval($_POST['_nexora_sheet_height']));
        }
        if (isset($_POST['_nexora_pricing_mode'])) {
            update_post_meta($post_id, '_nexora_pricing_mode', sanitize_text_field($_POST['_nexora_pricing_mode']));
        }
    }

    /**
     * Add custom gang sheet data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (!empty($cart_item_data['nexora_data'])) {
            return $cart_item_data;
        }

        if (!empty($_REQUEST['nexora_data'])) {
            $data = $_REQUEST['nexora_data'];
            if (is_string($data)) {
                $decoded = json_decode(stripslashes($data), true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
            $cart_item_data['nexora_data'] = $data;
        } elseif (!empty($_REQUEST['nexora_token'])) {
            $sheet = Nexora_DB::get_sheet_by_token(sanitize_text_field($_REQUEST['nexora_token']));
            if ($sheet) {
                $cart_item_data['nexora_data'] = [
                    'nexora_sheet_id' => $sheet->id,
                    'nexora_design_token' => $sheet->design_token,
                    'nexora_width' => $sheet->width,
                    'nexora_height' => $sheet->height,
                    'nexora_unit' => $sheet->unit,
                    'nexora_item_count' => $sheet->item_count,
                    'nexora_utilization' => $sheet->utilization_rate,
                    'nexora_preview_url' => $sheet->preview_url,
                    'nexora_print_file_url' => $sheet->print_file_url,
                    'nexora_custom_price' => $sheet->total_price,
                ];
            }
        }
        return $cart_item_data;
    }

    public function get_cart_item_from_session($cart_item, $values) {
        if (isset($values['nexora_data'])) {
            $cart_item['nexora_data'] = $values['nexora_data'];
        }
        return $cart_item;
    }

    /**
     * Recalculate price if calculated by builder
     */
    public function calculate_custom_cart_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['nexora_data']) && !empty($cart_item['nexora_data']['nexora_custom_price'])) {
                $custom_price = floatval($cart_item['nexora_data']['nexora_custom_price']);
                if ($custom_price > 0) {
                    $cart_item['data']->set_price($custom_price);
                }
            }
        }
    }

    /**
     * Replace cart item thumbnail with gang sheet preview
     */
    public function render_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (isset($cart_item['nexora_data']) && !empty($cart_item['nexora_data']['nexora_preview_url'])) {
            $preview_url = esc_url($cart_item['nexora_data']['nexora_preview_url']);
            return '<div style="position:relative; width: 64px; height: 64px; border-radius: 6px; overflow: hidden; background: repeating-conic-gradient(#333 0% 25%, #222 0% 50%) 50% / 12px 12px; border: 1px solid #7c3aed; display: flex; align-items: center; justify-content: center;">'
                . '<img src="' . $preview_url . '" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="Gang Sheet Preview" />'
                . '</div>';
        }
        return $thumbnail;
    }

    /**
     * Display thumbnail on Checkout Review Order table
     */
    public function render_cart_item_name($product_name, $cart_item, $cart_item_key) {
        if (is_checkout() && isset($cart_item['nexora_data']) && !empty($cart_item['nexora_data']['nexora_preview_url'])) {
            $preview_url = esc_url($cart_item['nexora_data']['nexora_preview_url']);
            $thumb_html = '<div style="display:inline-block; vertical-align:middle; width:44px; height:44px; margin-right:10px; border-radius:4px; overflow:hidden; background:#0f172a; border:1px solid #7c3aed; text-align:center;">'
                . '<img src="' . $preview_url . '" style="max-width:100%; max-height:100%; object-fit:contain;" alt="Preview" />'
                . '</div>';
            return $thumb_html . $product_name;
        }
        return $product_name;
    }

    /**
     * Display thumbnail on Order Received (Thank You) and Customer View Order
     */
    public function render_order_item_name($item_name, $item, $is_visible) {
        $preview_url = $item->get_meta('_nexora_preview_url');
        if ($preview_url) {
            $thumb_html = '<div style="display:inline-block; vertical-align:middle; width:44px; height:44px; margin-right:10px; border-radius:4px; overflow:hidden; background:#0f172a; border:1px solid #7c3aed; text-align:center;">'
                . '<img src="' . esc_url($preview_url) . '" style="max-width:100%; max-height:100%; object-fit:contain;" alt="Preview" />'
                . '</div>';
            return $thumb_html . $item_name;
        }
        return $item_name;
    }

    /**
     * Render sheet specs in cart item details
     */
    public function render_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['nexora_data'])) {
            $data = $cart_item['nexora_data'];
            
            // Sheet Dimensions & Area
            if (!empty($data['nexora_width']) && !empty($data['nexora_height'])) {
                $unit = !empty($data['nexora_unit']) ? $data['nexora_unit'] : 'in';
                $w = floatval($data['nexora_width']);
                $h = floatval($data['nexora_height']);
                $area_sq_in = round($w * $h, 1);
                $area_sq_ft = round($area_sq_in / 144.0, 2);

                $item_data[] = [
                    'key' => __('Sheet Dimensions', 'nexora-gang-sheet-builder'),
                    'value' => esc_html($w . ' ' . $unit . ' × ' . $h . ' ' . $unit),
                ];
                $item_data[] = [
                    'key' => __('Total Sheet Area', 'nexora-gang-sheet-builder'),
                    'value' => esc_html($area_sq_in . ' sq in (' . $area_sq_ft . ' sq ft)'),
                ];
            }

            // Designs Placed count
            if (isset($data['nexora_item_count'])) {
                $item_data[] = [
                    'key' => __('Designs Placed', 'nexora-gang-sheet-builder'),
                    'value' => intval($data['nexora_item_count']) . ' items',
                ];
            }

            // Sheet Utilization
            if (!empty($data['nexora_utilization'])) {
                $item_data[] = [
                    'key' => __('Sheet Utilization', 'nexora-gang-sheet-builder'),
                    'value' => round(floatval($data['nexora_utilization']), 1) . '%',
                ];
            }

            // Design Reference & Edit Link
            if (!empty($data['nexora_design_token'])) {
                $token = $data['nexora_design_token'];
                $item_data[] = [
                    'key' => __('Design Reference', 'nexora-gang-sheet-builder'),
                    'value' => '<code>' . esc_html(substr($token, 0, 12)) . '...</code>',
                ];

                $edit_url = site_url('/?nexora_builder=1&token=' . urlencode($token));
                $item_data[] = [
                    'key' => __('Artwork', 'nexora-gang-sheet-builder'),
                    'value' => '<a href="' . esc_url($edit_url) . '" target="_blank" style="color:#7c3aed; font-weight:600; text-decoration:underline;">' . __('Edit Gang Sheet ✎', 'nexora-gang-sheet-builder') . '</a>',
                ];
            }
        }
        return $item_data;
    }

    /**
     * Save Gang Sheet metadata to WooCommerce Order Line Item
     */
    public function save_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['nexora_data'])) {
            $data = $values['nexora_data'];
            
            if (!empty($data['nexora_sheet_id'])) {
                $item->add_meta_data('_nexora_sheet_id', $data['nexora_sheet_id']);
            }
            if (!empty($data['nexora_design_token'])) {
                $item->add_meta_data('_nexora_design_token', $data['nexora_design_token']);
                $item->add_meta_data('Design Reference', $data['nexora_design_token']);
            }
            if (!empty($data['nexora_width']) && !empty($data['nexora_height'])) {
                $unit = !empty($data['nexora_unit']) ? $data['nexora_unit'] : 'in';
                $w = floatval($data['nexora_width']);
                $h = floatval($data['nexora_height']);
                $dim_str = $w . ' ' . $unit . ' × ' . $h . ' ' . $unit;
                $area_str = round($w * $h, 1) . ' sq in (' . round(($w * $h) / 144.0, 2) . ' sq ft)';
                $item->add_meta_data('_nexora_dimensions', $dim_str);
                $item->add_meta_data('Sheet Dimensions', $dim_str);
                $item->add_meta_data('Total Sheet Area', $area_str);
            }
            if (!empty($data['nexora_item_count'])) {
                $item->add_meta_data('Designs Placed', $data['nexora_item_count'] . ' items');
            }
            if (!empty($data['nexora_utilization'])) {
                $item->add_meta_data('Sheet Utilization', round(floatval($data['nexora_utilization']), 1) . '%');
            }
            if (!empty($data['nexora_preview_url'])) {
                $item->add_meta_data('_nexora_preview_url', $data['nexora_preview_url']);
            }
            if (!empty($data['nexora_print_file_url'])) {
                $item->add_meta_data('_nexora_print_file_url', $data['nexora_print_file_url']);
            }
        }
    }

    /**
     * Link sheet database record with order ID after order completion
     */
    public function link_sheets_to_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        global $wpdb;
        $table = Nexora_DB::get_table_name('sheets');

        foreach ($order->get_items() as $item) {
            $sheet_id = $item->get_meta('_nexora_sheet_id');
            $design_token = $item->get_meta('_nexora_design_token');

            if ($sheet_id) {
                $wpdb->update($table, [
                    'order_id' => $order_id,
                    'customer_id' => $order->get_customer_id() ?: get_current_user_id(),
                    'status' => 'ordered'
                ], ['id' => intval($sheet_id)]);
            } elseif ($design_token) {
                $wpdb->update($table, [
                    'order_id' => $order_id,
                    'customer_id' => $order->get_customer_id() ?: get_current_user_id(),
                    'status' => 'ordered'
                ], ['design_token' => sanitize_text_field($design_token)]);
            }
        }
    }

    /**
     * Render Gang Sheet preview in WooCommerce Admin Order Details
     */
    public function render_order_item_admin_preview($item_id, $item, $product) {
        $preview_url = $item->get_meta('_nexora_preview_url');
        $print_file_url = $item->get_meta('_nexora_print_file_url');
        $sheet_id = $item->get_meta('_nexora_sheet_id');
        $token = $item->get_meta('_nexora_design_token');

        if (!$preview_url && !$print_file_url && !$sheet_id) {
            return;
        }

        echo '<div style="margin-top: 10px; padding: 12px; background: #0f172a; border-radius: 8px; color: #fff; max-width: 480px; border: 1px solid #334155;">';
        echo '<div style="font-weight: 600; color: #a78bfa; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">';
        echo '<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981;"></span>';
        echo __('Nexora DTF Print Production File', 'nexora-gang-sheet-builder');
        echo '</div>';

        if ($preview_url) {
            echo '<div style="margin-bottom: 10px; background: repeating-conic-gradient(#1e293b 0% 25%, #0f172a 0% 50%) 50% / 12px 12px; padding: 8px; border-radius: 6px; text-align: center;">';
            echo '<img src="' . esc_url($preview_url) . '" style="max-height: 180px; max-width: 100%; object-fit: contain; border-radius: 4px;" alt="Gang Sheet Preview" />';
            echo '</div>';
        }

        echo '<div style="display: flex; gap: 8px; flex-wrap: wrap;">';
        if ($print_file_url) {
            echo '<a href="' . esc_url($print_file_url) . '" download class="button button-primary" style="background:#7c3aed; border-color:#6d28d9; color:#fff;" target="_blank">';
            echo __('Download 300 DPI Print File', 'nexora-gang-sheet-builder');
            echo '</a>';
        } elseif ($preview_url) {
            echo '<a href="' . esc_url($preview_url) . '" download class="button button-primary" style="background:#7c3aed; border-color:#6d28d9; color:#fff;" target="_blank">';
            echo __('Download Preview Artwork', 'nexora-gang-sheet-builder');
            echo '</a>';
        }
        if ($token) {
            $builder_page = site_url('/?nexora_builder=1&token=' . esc_attr($token));
            echo '<a href="' . esc_url($builder_page) . '" target="_blank" class="button" style="background:#334155; color:#fff; border-color:#475569;">';
            echo __('Inspect & Export 300 DPI', 'nexora-gang-sheet-builder');
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Dedicated Order Metabox for quick downloads
     */
    public function add_order_metabox() {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') 
            && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'nexora_order_gang_sheets',
            __('Nexora DTF Gang Sheets for Print', 'nexora-gang-sheet-builder'),
            [$this, 'render_order_metabox'],
            $screen,
            'side',
            'high'
        );
    }

    public function render_order_metabox($post_or_order) {
        $order = ($post_or_order instanceof \WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
        if (!$order) {
            echo '<p>' . __('No order found.', 'nexora-gang-sheet-builder') . '</p>';
            return;
        }

        $sheets = Nexora_DB::get_sheets_by_order_id($order->get_id());

        if (empty($sheets)) {
            echo '<p style="color: #64748b;">' . __('No gang sheets attached to this order.', 'nexora-gang-sheet-builder') . '</p>';
            return;
        }

        echo '<div style="display: flex; flex-direction: column; gap: 12px;">';
        foreach ($sheets as $s) {
            echo '<div style="padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">';
            echo '<div style="font-weight: 600; font-size: 13px; color: #1e293b;">' . esc_html($s->title) . '</div>';
            echo '<div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">Size: ' . esc_html($s->width . ' ' . $s->unit . ' × ' . $s->height . ' ' . $s->unit) . ' (' . intval($s->item_count) . ' items)</div>';
            
            if ($s->preview_url) {
                echo '<img src="' . esc_url($s->preview_url) . '" style="width: 100%; max-height: 120px; object-fit: contain; background: #334155; border-radius: 4px; margin-bottom: 8px;" />';
            }

            if ($s->print_file_url) {
                echo '<a href="' . esc_url($s->print_file_url) . '" download class="button button-small button-primary" style="display:block; text-align:center; background:#7c3aed; border-color:#6d28d9; color:#fff;">' . __('Download Print File', 'nexora-gang-sheet-builder') . '</a>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render Preview in customer's View Order page
     */
    public function render_customer_order_preview($item_id, $item, $order) {
        $wc_settings = get_option('nexora_woocommerce_settings', []);
        $preview_url = $item->get_meta('_nexora_preview_url');
        $dimensions = $item->get_meta('_nexora_dimensions');

        if (!$preview_url) {
            return;
        }

        echo '<div class="nexora-customer-order-preview" style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; max-width: 320px;">';
        echo '<div style="font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">' . __('DTF Gang Sheet Design', 'nexora-gang-sheet-builder') . '</div>';
        echo '<img src="' . esc_url($preview_url) . '" style="max-height: 140px; max-width: 100%; object-fit: contain; border-radius: 4px; background: repeating-conic-gradient(#cbd5e1 0% 25%, #f1f5f9 0% 50%) 50% / 10px 10px;" />';
        if (!empty($wc_settings['enable_customer_download'])) {
            $print_file_url = $item->get_meta('_nexora_print_file_url');
            if ($print_file_url) {
                echo '<div style="margin-top: 6px;"><a href="' . esc_url($print_file_url) . '" download class="button" style="font-size: 11px; padding: 4px 8px;">' . __('Download Artwork', 'nexora-gang-sheet-builder') . '</a></div>';
            }
        }
        echo '</div>';
    }
}

