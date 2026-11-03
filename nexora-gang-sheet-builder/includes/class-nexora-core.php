<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_Core {

    protected $admin;
    protected $api;
    protected $woocommerce;
    protected $shortcodes;

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        $this->admin = new Nexora_Admin();
        $this->api = new Nexora_API();
        $this->woocommerce = new Nexora_WooCommerce();
        $this->shortcodes = new Nexora_Shortcodes();
    }

    public function run() {
        // Init REST API
        add_action('rest_api_init', [$this->api, 'register_routes']);

        // Public scripts & modal
        add_action('wp_enqueue_scripts', [$this, 'register_public_assets']);
        add_filter('script_loader_tag', [$this, 'add_module_type_to_script'], 10, 3);
        add_action('wp_footer', [$this, 'render_global_builder_modal']);

        // Check if query param ?nexora_builder=1 is present to render full standalone template
        add_action('template_redirect', [$this, 'handle_standalone_builder_page']);
    }

    public function add_module_type_to_script($tag, $handle, $src) {
        if ('nexora-builder-script' === $handle) {
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }

    public function register_public_assets() {
        wp_register_style(
            'nexora-builder-style',
            NEXORA_PLUGIN_URL . 'public/css/nexora-builder.css',
            [],
            NEXORA_VERSION
        );

        wp_register_script(
            'nexora-builder-script',
            NEXORA_PLUGIN_URL . 'public/js/nexora-builder.js',
            [],
            NEXORA_VERSION,
            true
        );
    }

    public static function enqueue_builder_assets() {
        wp_enqueue_style('nexora-builder-style');
        wp_enqueue_script('nexora-builder-script');

        $currency = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/cart';
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '/checkout';

        wp_localize_script('nexora-builder-script', 'nexoraBuilderData', [
            'rest_url' => esc_url_raw(rest_url('nexora/v1/')),
            'ajax_url' => admin_url('admin-ajax.php'),
            'site_url' => site_url(),
            'default_product_id' => Nexora_WooCommerce::get_or_create_gang_sheet_product(),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => $currency,
            'cart_url' => $cart_url,
            'checkout_url' => $checkout_url,
            'is_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'user_name' => is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest',
            'presets' => get_option('nexora_sheet_presets', []),
            'pricing' => get_option('nexora_pricing_settings', []),
            'general' => get_option('nexora_general_settings', []),
            'cloud' => get_option('nexora_cloud_settings', []),
            'upload' => get_option('nexora_upload_settings', []),
            'export' => get_option('nexora_export_settings', []),
            'plugin_url' => NEXORA_PLUGIN_URL,
        ]);
    }

    /**
     * Renders global modal in footer for single product page trigger
     */
    public function render_global_builder_modal() {
        // Enqueue assets if on product page or if shortcode was used
        if (function_exists('is_product') && is_product()) {
            self::enqueue_builder_assets();
            global $product;
            $pid = $product ? $product->get_id() : 0;
            $width = $product ? (get_post_meta($pid, '_nexora_sheet_width', true) ?: 22) : 22;
            $height = $product ? (get_post_meta($pid, '_nexora_sheet_height', true) ?: 60) : 60;
            $wc_settings = get_option('nexora_woocommerce_settings', []);
            $redirect_target = !empty($wc_settings['redirect_target']) ? $wc_settings['redirect_target'] : 'builder_modal';
            ?>
            <div id="nexora-global-modal-overlay" class="nexora-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:999999; background:rgba(0,0,0,0.88); backdrop-filter:blur(6px);">
                <div class="nexora-modal-inner" style="position:relative; width:100%; height:100%;">
                    <button type="button" class="nexora-close-modal-btn" aria-label="Close" style="position:absolute; top:12px; right:16px; z-index:1000000; background:#1e293b; color:#fff; border:1px solid #475569; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:18px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.5);">&times;</button>
                    <div id="nexora-gang-sheet-builder-modal-root" data-product-id="<?php echo esc_attr($pid); ?>" data-initial-width="<?php echo esc_attr($width); ?>" data-initial-height="<?php echo esc_attr($height); ?>"></div>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.querySelector('.nexora-open-builder-btn');
                var overlay = document.getElementById('nexora-global-modal-overlay');
                var closeBtn = document.querySelector('.nexora-close-modal-btn');
                var redirectTarget = <?php echo wp_json_encode($redirect_target); ?>;
                var basePid = <?php echo intval($pid); ?>;
                
                if (btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Detect currently selected variation from single product form
                        var form = btn.closest('form.cart') || document.querySelector('form.variations_form.cart') || document.querySelector('form.cart');
                        var varId = 0;
                        if (form) {
                            var varInput = form.querySelector('input[name="variation_id"]');
                            if (varInput && varInput.value) {
                                varId = parseInt(varInput.value) || 0;
                            }
                        }

                        if (redirectTarget === 'builder_page') {
                            var targetUrl = '<?php echo esc_url(site_url('/?nexora_builder=1')); ?>&product_id=' + basePid;
                            if (varId > 0) {
                                targetUrl += '&variation_id=' + varId;
                            }
                            window.location.href = targetUrl;
                            return;
                        }

                        if (overlay) {
                            overlay.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                            var modalRoot = document.getElementById('nexora-gang-sheet-builder-modal-root');
                            if (modalRoot) {
                                modalRoot.dataset.productId = basePid;
                                modalRoot.dataset.variationId = varId;
                            }
                            if (window.nexoraMountBuilder) {
                                window.nexoraMountBuilder('nexora-gang-sheet-builder-modal-root', {
                                    productId: basePid,
                                    variationId: varId
                                });
                            }
                        }
                    });
                }
                if (closeBtn && overlay) {
                    closeBtn.addEventListener('click', function() {
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                    });
                }
            });
            </script>
            <?php
        }
    }

    /**
     * Handle standalone URL endpoint: ?nexora_builder=1
     */
    public function handle_standalone_builder_page() {
        if (isset($_GET['nexora_builder']) && $_GET['nexora_builder'] === '1') {
            self::enqueue_builder_assets();
            $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
            $variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : 0;
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            $width = isset($_GET['width']) ? floatval($_GET['width']) : 22;
            $height = isset($_GET['height']) ? floatval($_GET['height']) : 60;
            
            // Output standalone full-screen HTML
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Nexora Professional DTF Gang Sheet Builder</title>
                <?php wp_head(); ?>
                <style>
                    html, body {
                        margin: 0;
                        padding: 0;
                        width: 100%;
                        height: 100%;
                        overflow: hidden;
                        background: #0f1117;
                    }
                    #wpadminbar { display: none !important; }
                </style>
            </head>
            <body>
                <div id="nexora-gang-sheet-builder-root" 
                     data-product-id="<?php echo esc_attr($product_id); ?>"
                     data-variation-id="<?php echo esc_attr($variation_id); ?>"
                     data-initial-width="<?php echo esc_attr($width); ?>"
                     data-initial-height="<?php echo esc_attr($height); ?>"
                     data-token="<?php echo esc_attr($token); ?>">
                </div>
                <?php wp_footer(); ?>
            </body>
            </html>
            <?php
            exit;
        }
    }
}
