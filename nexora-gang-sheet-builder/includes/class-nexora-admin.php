<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_nexora_admin_save_settings', [$this, 'ajax_save_settings']);
    }

    public function register_admin_menu() {
        add_menu_page(
            __('Nexora Gang Sheet', 'nexora-gang-sheet-builder'),
            __('Nexora Gang Sheet', 'nexora-gang-sheet-builder'),
            'manage_options',
            'nexora-gang-sheet',
            [$this, 'render_admin_page'],
            'dashicons-format-gallery',
            56
        );

        add_submenu_page(
            'nexora-gang-sheet',
            __('Dashboard', 'nexora-gang-sheet-builder'),
            __('Dashboard', 'nexora-gang-sheet-builder'),
            'manage_options',
            'nexora-gang-sheet',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'nexora-gang-sheet',
            __('Gang Sheets & Orders', 'nexora-gang-sheet-builder'),
            __('Gang Sheets', 'nexora-gang-sheet-builder'),
            'manage_options',
            'nexora-gang-sheet-orders',
            [$this, 'render_orders_page']
        );

        add_submenu_page(
            'nexora-gang-sheet',
            __('Sheet Presets', 'nexora-gang-sheet-builder'),
            __('Sheet Sizes', 'nexora-gang-sheet-builder'),
            'manage_options',
            'nexora-gang-sheet-presets',
            [$this, 'render_presets_page']
        );

        add_submenu_page(
            'nexora-gang-sheet',
            __('Settings', 'nexora-gang-sheet-builder'),
            __('Settings', 'nexora-gang-sheet-builder'),
            'manage_options',
            'nexora-gang-sheet-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'nexora') === false) {
            return;
        }

        wp_enqueue_style(
            'nexora-admin-style',
            NEXORA_PLUGIN_URL . 'admin/css/nexora-admin.css',
            [],
            NEXORA_VERSION
        );

        wp_enqueue_script(
            'nexora-admin-script',
            NEXORA_PLUGIN_URL . 'admin/js/nexora-admin.js',
            ['jquery'],
            NEXORA_VERSION,
            true
        );

        wp_localize_script('nexora-admin-script', 'nexoraAdminData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url('nexora/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'admin_nonce' => wp_create_nonce('nexora_admin_action'),
        ]);
    }

    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        include_once NEXORA_PLUGIN_DIR . 'admin/views/admin-main.php';
    }

    public function render_orders_page() {
        $_GET['tab'] = 'orders';
        $this->render_admin_page();
    }

    public function render_presets_page() {
        $_GET['tab'] = 'presets';
        $this->render_admin_page();
    }

    public function render_settings_page() {
        $_GET['tab'] = 'general';
        $this->render_admin_page();
    }

    public function ajax_save_settings() {
        check_ajax_referer('nexora_admin_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'general';

        if ($tab === 'general' && isset($_POST['general'])) {
            $data = wp_unslash($_POST['general']);
            update_option('nexora_general_settings', $data);
        } elseif ($tab === 'pricing' && isset($_POST['pricing'])) {
            $data = wp_unslash($_POST['pricing']);
            update_option('nexora_pricing_settings', $data);
        } elseif ($tab === 'woocommerce' && isset($_POST['woocommerce'])) {
            $data = wp_unslash($_POST['woocommerce']);
            update_option('nexora_woocommerce_settings', $data);
        } elseif ($tab === 'upload' && isset($_POST['upload'])) {
            $data = wp_unslash($_POST['upload']);
            update_option('nexora_upload_settings', $data);
        } elseif ($tab === 'export' && isset($_POST['export'])) {
            $data = wp_unslash($_POST['export']);
            update_option('nexora_export_settings', $data);
        } elseif ($tab === 'cloud' && isset($_POST['cloud'])) {
            $data = wp_unslash($_POST['cloud']);
            update_option('nexora_cloud_settings', $data);
        } elseif ($tab === 'presets' && isset($_POST['presets'])) {
            $data = json_decode(wp_unslash($_POST['presets']), true);
            if (is_array($data)) {
                update_option('nexora_sheet_presets', $data);
            }
        }

        wp_send_json_success(['message' => __('Settings saved successfully.', 'nexora-gang-sheet-builder')]);
    }
}

