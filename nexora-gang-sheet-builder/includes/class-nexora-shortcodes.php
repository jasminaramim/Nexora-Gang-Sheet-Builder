<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_Shortcodes {

    public function __construct() {
        add_shortcode('nexora_gang_sheet_builder', [$this, 'render_builder_shortcode']);
        add_shortcode('nexora_gang_sheet_button', [$this, 'render_button_shortcode']);
    }

    /**
     * Render full Gang Sheet Builder
     * Usage: [nexora_gang_sheet_builder product_id="123" width="22" height="60"]
     */
    public function render_builder_shortcode($atts) {
        $atts = shortcode_atts([
            'product_id' => 0,
            'variation_id' => 0,
            'width' => 22.0,
            'height' => 60.0,
            'unit' => 'in',
            'theme' => 'dark',
            'token' => '',
        ], $atts, 'nexora_gang_sheet_builder');

        // Check if token in URL query parameter
        if (empty($atts['token']) && isset($_GET['token'])) {
            $atts['token'] = sanitize_text_field($_GET['token']);
        }
        if (empty($atts['product_id']) && isset($_GET['product_id'])) {
            $atts['product_id'] = intval($_GET['product_id']);
        }

        // Enqueue builder assets
        Nexora_Core::enqueue_builder_assets();

        ob_start();
        ?>
        <div id="nexora-gang-sheet-builder-root" 
             data-product-id="<?php echo esc_attr($atts['product_id']); ?>"
             data-variation-id="<?php echo esc_attr($atts['variation_id']); ?>"
             data-initial-width="<?php echo esc_attr($atts['width']); ?>"
             data-initial-height="<?php echo esc_attr($atts['height']); ?>"
             data-unit="<?php echo esc_attr($atts['unit']); ?>"
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             data-token="<?php echo esc_attr($atts['token']); ?>">
             <div class="nexora-loading-spinner" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:600px; background:#0f1117; color:#fff; font-family:sans-serif;">
                 <div style="width:48px; height:48px; border:4px solid #334155; border-top-color:#7c3aed; border-radius:50%; animation:nexora-spin 1s linear infinite;"></div>
                 <div style="margin-top:16px; font-weight:600; font-size:16px;">Loading Nexora DTF Gang Sheet Builder...</div>
                 <style>@keyframes nexora-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
             </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render customizable button
     * Usage: [nexora_gang_sheet_button product_id="123" text="Create Your Gang Sheet"]
     */
    public function render_button_shortcode($atts) {
        $atts = shortcode_atts([
            'product_id' => 0,
            'text' => __('Create Your Gang Sheet', 'nexora-gang-sheet-builder'),
            'class' => '',
            'target' => 'modal', // 'modal' or 'page'
            'url' => '',
            'width' => 22,
            'height' => 60,
        ], $atts, 'nexora_gang_sheet_button');

        Nexora_Core::enqueue_builder_assets();

        $btn_class = 'nexora-open-builder-btn ' . esc_attr($atts['class']);
        $product_id = intval($atts['product_id']);

        ob_start();
        ?>
        <button type="button" 
                class="button <?php echo esc_attr($btn_class); ?>" 
                data-product-id="<?php echo esc_attr($product_id); ?>"
                data-sheet-width="<?php echo esc_attr($atts['width']); ?>"
                data-sheet-height="<?php echo esc_attr($atts['height']); ?>"
                style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #ffffff; border: none; padding: 12px 26px; font-size: 15px; font-weight: 600; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4); transition: transform 0.15s ease;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
            </svg>
            <span><?php echo esc_html($atts['text']); ?></span>
        </button>
        <?php
        return ob_get_clean();
    }
}

