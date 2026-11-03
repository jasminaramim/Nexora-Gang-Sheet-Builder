<?php
if (!defined('ABSPATH')) {
    exit;
}

$general = get_option('nexora_general_settings', []);
$presets = get_option('nexora_sheet_presets', []);
$pricing = get_option('nexora_pricing_settings', []);
$woocommerce_settings = get_option('nexora_woocommerce_settings', []);
$upload_settings = get_option('nexora_upload_settings', []);
$export_settings = get_option('nexora_export_settings', []);
$cloud_settings = get_option('nexora_cloud_settings', []);

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="wrap nexora-admin-wrap">
    <!-- Header -->
    <header class="nexora-admin-header">
        <div class="nexora-brand">
            <div class="nexora-logo-badge">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18"/>
                    <path d="M9 21V9"/>
                </svg>
            </div>
            <div>
                <h1 class="nexora-title">Nexora Gang Sheet Builder</h1>
                <span class="nexora-version-tag">v<?php echo NEXORA_VERSION; ?> &bull; DTF Production Suite</span>
            </div>
        </div>
        <div class="nexora-header-actions">
            <a href="<?php echo esc_url(site_url('/?nexora_builder=1')); ?>" target="_blank" class="nexora-btn nexora-btn-preview">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span>Launch Live Builder</span>
            </a>
            <button type="button" id="nexora-save-all-btn" class="nexora-btn nexora-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Save Changes</span>
            </button>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="nexora-nav-tabs">
        <a href="?page=nexora-gang-sheet&tab=dashboard" class="nexora-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span> Dashboard
        </a>
        <a href="?page=nexora-gang-sheet&tab=general" class="nexora-tab-link <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span> General Settings
        </a>
        <a href="?page=nexora-gang-sheet&tab=woocommerce" class="nexora-tab-link <?php echo $active_tab === 'woocommerce' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-cart"></span> WooCommerce
        </a>
        <a href="?page=nexora-gang-sheet&tab=products" class="nexora-tab-link <?php echo $active_tab === 'products' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-products"></span> Product Integration
        </a>
        <a href="?page=nexora-gang-sheet&tab=presets" class="nexora-tab-link <?php echo $active_tab === 'presets' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-layout"></span> Sheet Sizes
        </a>
        <a href="?page=nexora-gang-sheet&tab=pricing" class="nexora-tab-link <?php echo $active_tab === 'pricing' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-money-alt"></span> Pricing Management
        </a>
        <a href="?page=nexora-gang-sheet&tab=upload" class="nexora-tab-link <?php echo $active_tab === 'upload' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-upload"></span> Upload Settings
        </a>
        <a href="?page=nexora-gang-sheet&tab=design" class="nexora-tab-link <?php echo $active_tab === 'design' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-art"></span> Design Settings
        </a>
        <a href="?page=nexora-gang-sheet&tab=cloud" class="nexora-tab-link <?php echo $active_tab === 'cloud' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-cloud"></span> Cloud Storage
        </a>
        <a href="?page=nexora-gang-sheet&tab=orders" class="nexora-tab-link <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-media-document"></span> Orders & Sheets
        </a>
        <a href="?page=nexora-gang-sheet&tab=export" class="nexora-tab-link <?php echo $active_tab === 'export' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-printer"></span> Export Settings
        </a>
        <a href="?page=nexora-gang-sheet&tab=license" class="nexora-tab-link <?php echo $active_tab === 'license' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-admin-network"></span> License Management
        </a>
    </nav>

    <!-- Main Content Body -->
    <div class="nexora-tab-container">
        <!-- 1. DASHBOARD TAB -->
        <?php if ($active_tab === 'dashboard'): ?>
            <?php
            global $wpdb;
            $table = Nexora_DB::get_table_name('sheets');
            $total_sheets = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $total_orders = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'ordered' OR order_id > 0");
            $total_rev = (float) $wpdb->get_var("SELECT SUM(total_price) FROM $table WHERE status = 'ordered' OR order_id > 0");
            $recent_sheets = Nexora_DB::get_recent_sheets(5);
            ?>
            <div class="nexora-grid nexora-stats-grid">
                <div class="nexora-stat-card">
                    <div class="stat-icon purple"><span class="dashicons dashicons-format-gallery"></span></div>
                    <div class="stat-content">
                        <span class="stat-label">Total Gang Sheets Created</span>
                        <span class="stat-value"><?php echo number_format_i18n($total_sheets); ?></span>
                    </div>
                </div>
                <div class="nexora-stat-card">
                    <div class="stat-icon emerald"><span class="dashicons dashicons-cart"></span></div>
                    <div class="stat-content">
                        <span class="stat-label">WooCommerce Orders</span>
                        <span class="stat-value"><?php echo number_format_i18n($total_orders); ?></span>
                    </div>
                </div>
                <div class="nexora-stat-card">
                    <div class="stat-icon indigo"><span class="dashicons dashicons-chart-area"></span></div>
                    <div class="stat-content">
                        <span class="stat-label">Gang Sheet Revenue</span>
                        <span class="stat-value">$<?php echo number_format($total_rev, 2); ?></span>
                    </div>
                </div>
                <div class="nexora-stat-card">
                    <div class="stat-icon blue"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="stat-content">
                        <span class="stat-label">System Status</span>
                        <span class="stat-value" style="color: #10b981; font-size: 20px;">Ready (300 DPI Active)</span>
                    </div>
                </div>
            </div>

            <!-- Quick Start & Recent -->
            <div class="nexora-card-row" style="margin-top: 24px;">
                <div class="nexora-card" style="flex: 2;">
                    <h2 class="card-title">Recent Gang Sheets</h2>
                    <table class="nexora-table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Reference</th>
                                <th>Dimensions</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Print File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_sheets)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 24px; color:#64748b;">No gang sheets created yet. Launch the builder to create your first design!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_sheets as $rs): ?>
                                    <tr>
                                        <td>
                                            <?php if ($rs->preview_url): ?>
                                                <img src="<?php echo esc_url($rs->preview_url); ?>" style="width: 48px; height: 48px; object-fit: contain; background: #0f172a; border-radius: 4px;" />
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: #94a3b8;">No preview</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo esc_html($rs->title); ?></strong><br/><small style="color: #64748b;"><?php echo esc_html($rs->design_token); ?></small></td>
                                        <td><?php echo esc_html($rs->width . ' ' . $rs->unit . ' × ' . $rs->height . ' ' . $rs->unit); ?></td>
                                        <td><?php echo intval($rs->item_count); ?> designs</td>
                                        <td><span class="nexora-badge badge-<?php echo esc_attr($rs->status); ?>"><?php echo esc_html(ucfirst($rs->status)); ?></span></td>
                                        <td>
                                            <?php if ($rs->print_file_url): ?>
                                                <a href="<?php echo esc_url($rs->print_file_url); ?>" download class="button button-small" style="background:#7c3aed; color:#fff; border:none;">Download 300 DPI</a>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">Processing</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="nexora-card" style="flex: 1;">
                    <h2 class="card-title">Quick Integration Guide</h2>
                    <div class="guide-steps">
                        <div class="guide-step">
                            <span class="step-num">1</span>
                            <div>
                                <strong>Add Shortcode to any page:</strong>
                                <code>[nexora_gang_sheet_builder]</code>
                            </div>
                        </div>
                        <div class="guide-step">
                            <span class="step-num">2</span>
                            <div>
                                <strong>Customizable Button Shortcode:</strong>
                                <code>[nexora_gang_sheet_button text="Create Gang Sheet"]</code>
                            </div>
                        </div>
                        <div class="guide-step">
                            <span class="step-num">3</span>
                            <div>
                                <strong>Direct URL Access:</strong>
                                <code><?php echo esc_url(site_url('/?nexora_builder=1')); ?></code>
                            </div>
                        </div>
                        <div class="guide-step">
                            <span class="step-num">4</span>
                            <div>
                                <strong>Automatic WooCommerce Button:</strong>
                                <p style="font-size:12px; color:#64748b; margin:4px 0 0;">Automatically shown on single product pages. Configure in WooCommerce tab.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- 2. GENERAL SETTINGS TAB -->
        <?php elseif ($active_tab === 'general'): ?>
            <div class="nexora-card">
                <h2 class="card-title">General Builder Configuration</h2>
                <form id="nexora-general-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="unit">Default Unit of Measurement</label></th>
                            <td>
                                <select name="unit" id="unit" class="regular-text">
                                    <option value="in" <?php selected(isset($general['unit']) ? $general['unit'] : 'in', 'in'); ?>>Inches (in) - Recommended for US/DTF</option>
                                    <option value="cm" <?php selected(isset($general['unit']) ? $general['unit'] : 'in', 'cm'); ?>>Centimeters (cm)</option>
                                    <option value="mm" <?php selected(isset($general['unit']) ? $general['unit'] : 'in', 'mm'); ?>>Millimeters (mm)</option>
                                </select>
                                <p class="description">Standard unit used across canvas rulers, dimensions, and spacing tools.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="default_dpi">Target Print DPI</label></th>
                            <td>
                                <input type="number" name="default_dpi" id="default_dpi" value="<?php echo esc_attr(isset($general['default_dpi']) ? $general['default_dpi'] : 300); ?>" class="small-text" /> DPI
                                <p class="description">Commercial DTF printing standard is 300 DPI.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="min_dpi_warning">Low DPI Warning Threshold</label></th>
                            <td>
                                <input type="number" name="min_dpi_warning" id="min_dpi_warning" value="<?php echo esc_attr(isset($general['min_dpi_warning']) ? $general['min_dpi_warning'] : 250); ?>" class="small-text" /> DPI
                                <p class="description">Customer receives a yellow warning badge if an uploaded image resolution falls below this value at its current printed size.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="default_sheet_width">Default Sheet Dimensions</label></th>
                            <td>
                                Width: <input type="number" step="0.5" name="default_sheet_width" value="<?php echo esc_attr(isset($general['default_sheet_width']) ? $general['default_sheet_width'] : 22); ?>" style="width:80px;" /> in &nbsp;&times;&nbsp;
                                Height: <input type="number" step="0.5" name="default_sheet_height" value="<?php echo esc_attr(isset($general['default_sheet_height']) ? $general['default_sheet_height'] : 60); ?>" style="width:80px;" /> in
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="print_margin">Safety Print Margin</label></th>
                            <td>
                                <input type="number" step="0.05" name="print_margin" id="print_margin" value="<?php echo esc_attr(isset($general['print_margin']) ? $general['print_margin'] : 0.25); ?>" style="width:80px;" /> in
                                <p class="description">Print head boundary margin around the edge of the gang sheet.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Auto-Save State</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_save" value="1" <?php checked(!isset($general['auto_save']) || $general['auto_save']); ?> />
                                    Automatically save customer canvas changes every 30 seconds to browser local cache & database
                                </label>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        <!-- 3. WOOCOMMERCE TAB -->
        <?php elseif ($active_tab === 'woocommerce'): ?>
            <div class="nexora-card">
                <h2 class="card-title">WooCommerce Shop Integration</h2>
                <form id="nexora-woocommerce-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable WooCommerce Integration</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enabled" value="1" <?php checked(!isset($woocommerce_settings['enabled']) || $woocommerce_settings['enabled']); ?> />
                                    Activate seamless WooCommerce cart, checkout, and order line item attachment
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="button_text">Product Page Button Label</label></th>
                            <td>
                                <input type="text" name="button_text" id="button_text" value="<?php echo esc_attr(!empty($woocommerce_settings['button_text']) ? $woocommerce_settings['button_text'] : 'Build Your DTF Gang Sheet'); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="button_location">Button Display Location</label></th>
                            <td>
                                <select name="button_location" id="button_location" class="regular-text">
                                    <option value="woocommerce_after_add_to_cart_button" <?php selected(isset($woocommerce_settings['button_location']) ? $woocommerce_settings['button_location'] : 'woocommerce_after_add_to_cart_button', 'woocommerce_after_add_to_cart_button'); ?>>After 'Add to Cart' button (Recommended)</option>
                                    <option value="woocommerce_before_add_to_cart_button" <?php selected(isset($woocommerce_settings['button_location']) ? $woocommerce_settings['button_location'] : '', 'woocommerce_before_add_to_cart_button'); ?>>Before 'Add to Cart' button</option>
                                    <option value="woocommerce_single_product_summary" <?php selected(isset($woocommerce_settings['button_location']) ? $woocommerce_settings['button_location'] : '', 'woocommerce_single_product_summary'); ?>>Product Summary Header</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="redirect_after_add">After Design Is Added to Cart</label></th>
                            <td>
                                <select name="redirect_after_add" id="redirect_after_add" class="regular-text">
                                    <option value="cart" <?php selected(isset($woocommerce_settings['redirect_after_add']) ? $woocommerce_settings['redirect_after_add'] : 'cart', 'cart'); ?>>Redirect customer to Cart Page</option>
                                    <option value="checkout" <?php selected(isset($woocommerce_settings['redirect_after_add']) ? $woocommerce_settings['redirect_after_add'] : 'cart', 'checkout'); ?>>Redirect customer directly to Checkout Page (Fast Order)</option>
                                    <option value="stay" <?php selected(isset($woocommerce_settings['redirect_after_add']) ? $woocommerce_settings['redirect_after_add'] : 'cart', 'stay'); ?>>Stay on builder with confirmation message</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Cart & Order Displays</th>
                            <td>
                                <label><input type="checkbox" name="enable_preview_in_cart" value="1" <?php checked(!isset($woocommerce_settings['enable_preview_in_cart']) || $woocommerce_settings['enable_preview_in_cart']); ?> /> Show visual gang sheet thumbnail in Cart & Checkout table</label><br/>
                                <label><input type="checkbox" name="enable_preview_in_order" value="1" <?php checked(!isset($woocommerce_settings['enable_preview_in_order']) || $woocommerce_settings['enable_preview_in_order']); ?> /> Show 300 DPI download button in WooCommerce Admin Order page</label><br/>
                                <label><input type="checkbox" name="enable_customer_download" value="1" <?php checked(!isset($woocommerce_settings['enable_customer_download']) || $woocommerce_settings['enable_customer_download']); ?> /> Allow customer to download artwork from My Account -> View Order</label>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        <!-- 4. PRODUCT INTEGRATION TAB -->
        <?php elseif ($active_tab === 'products'): ?>
            <div class="nexora-card">
                <h2 class="card-title">Assign Gang Sheet Builder to WooCommerce Products</h2>
                <p class="description" style="margin-bottom: 16px;">Configure which WooCommerce products launch the Nexora Gang Sheet Builder.</p>
                <table class="nexora-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Type</th>
                            <th>Regular Price</th>
                            <th>Builder Enabled</th>
                            <th>Default Dimensions</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (function_exists('wc_get_products')) {
                            $products = wc_get_products(['limit' => 20]);
                            if (empty($products)) {
                                echo '<tr><td colspan="6" style="text-align:center; padding:20px; color:#64748b;">No products found in WooCommerce. Please create a product first!</td></tr>';
                            } else {
                                foreach ($products as $p) {
                                    $is_enabled = get_post_meta($p->get_id(), '_nexora_enabled', true) !== 'no';
                                    $w = get_post_meta($p->get_id(), '_nexora_sheet_width', true) ?: 22;
                                    $h = get_post_meta($p->get_id(), '_nexora_sheet_height', true) ?: 60;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($p->get_name()); ?></strong></td>
                                        <td><code><?php echo esc_html($p->get_type()); ?></code></td>
                                        <td><?php echo $p->get_price_html() ?: '$0.00'; ?></td>
                                        <td>
                                            <span class="nexora-badge <?php echo $is_enabled ? 'badge-ordered' : 'badge-draft'; ?>">
                                                <?php echo $is_enabled ? 'Active' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($w . '" × ' . $h . '"'); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($p->get_id())); ?>" class="button button-small">Edit in WC</a>
                                            <a href="<?php echo esc_url(site_url('/?nexora_builder=1&product_id=' . $p->get_id())); ?>" target="_blank" class="button button-small" style="background:#7c3aed; color:#fff; border:none;">Preview Builder</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        } else {
                            echo '<tr><td colspan="6" style="text-align:center; padding:20px; color:#64748b;">WooCommerce plugin is not active. The builder will operate in standalone mode.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <!-- 5. SHEET SIZES / PRESETS TAB -->
        <?php elseif ($active_tab === 'presets'): ?>
            <div class="nexora-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h2 class="card-title" style="margin:0;">Manage Sheet Size Presets</h2>
                    <button type="button" id="nexora-add-preset-btn" class="button button-primary" style="background:#7c3aed; border-color:#6d28d9;">+ Add New Size Preset</button>
                </div>
                <table class="nexora-table" id="nexora-presets-table">
                    <thead>
                        <tr>
                            <th>Preset Name</th>
                            <th>Width (in)</th>
                            <th>Height (in)</th>
                            <th>Fixed Base Price ($)</th>
                            <th>Default Selected</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presets as $idx => $pr): ?>
                            <tr data-index="<?php echo $idx; ?>">
                                <td><input type="text" class="preset-name regular-text" value="<?php echo esc_attr($pr['name']); ?>" style="width:240px;" /></td>
                                <td><input type="number" step="0.5" class="preset-width" value="<?php echo esc_attr($pr['width']); ?>" style="width:80px;" /></td>
                                <td><input type="number" step="0.5" class="preset-height" value="<?php echo esc_attr($pr['height']); ?>" style="width:80px;" /></td>
                                <td>$<input type="number" step="0.01" class="preset-price" value="<?php echo esc_attr($pr['price']); ?>" style="width:90px;" /></td>
                                <td style="text-align:center;">
                                    <input type="radio" name="preset_default_radio" class="preset-default" <?php checked(!empty($pr['is_default'])); ?> />
                                </td>
                                <td>
                                    <button type="button" class="button button-small nexora-delete-preset-btn" style="color:#ef4444;">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <!-- 6. PRICING MANAGEMENT TAB -->
        <?php elseif ($active_tab === 'pricing'): ?>
            <div class="nexora-card">
                <h2 class="card-title">Pricing Engine Configuration</h2>
                <form id="nexora-pricing-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pricing_mode">Pricing Calculation Mode</label></th>
                            <td>
                                <select name="pricing_mode" id="pricing_mode" class="regular-text">
                                    <option value="tiered" <?php selected(isset($pricing['pricing_mode']) ? $pricing['pricing_mode'] : 'tiered', 'tiered'); ?>>Dynamic Tiered Volume Pricing (By Sq Footage) - Best for Print Shops</option>
                                    <option value="sq_ft" <?php selected(isset($pricing['pricing_mode']) ? $pricing['pricing_mode'] : '', 'sq_ft'); ?>>Flat Price Per Square Foot ($/sq ft)</option>
                                    <option value="sq_in" <?php selected(isset($pricing['pricing_mode']) ? $pricing['pricing_mode'] : '', 'sq_in'); ?>>Flat Price Per Square Inch ($/sq in)</option>
                                    <option value="fixed" <?php selected(isset($pricing['pricing_mode']) ? $pricing['pricing_mode'] : '', 'fixed'); ?>>Fixed Preset Size Pricing (Set in Sheet Sizes)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="price_per_sq_ft">Price Per Square Foot ($)</label></th>
                            <td>
                                $<input type="number" step="0.01" name="price_per_sq_ft" id="price_per_sq_ft" value="<?php echo esc_attr(isset($pricing['price_per_sq_ft']) ? $pricing['price_per_sq_ft'] : 7.50); ?>" class="small-text" /> / sq ft
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="price_per_sq_in">Price Per Square Inch ($)</label></th>
                            <td>
                                $<input type="number" step="0.001" name="price_per_sq_in" id="price_per_sq_in" value="<?php echo esc_attr(isset($pricing['price_per_sq_in']) ? $pricing['price_per_sq_in'] : 0.052); ?>" style="width:100px;" /> / sq in
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="min_price">Minimum Order Charge ($)</label></th>
                            <td>
                                $<input type="number" step="0.50" name="min_price" id="min_price" value="<?php echo esc_attr(isset($pricing['min_price']) ? $pricing['min_price'] : 15.00); ?>" class="small-text" />
                            </td>
                        </tr>
                    </table>

                    <h3 style="margin-top:24px; color:#475569;">Volume Discount Tiers (Sq Footage)</h3>
                    <table class="nexora-table" style="max-width: 600px;">
                        <thead>
                            <tr>
                                <th>Min Sq Ft</th>
                                <th>Max Sq Ft</th>
                                <th>Rate per Sq Ft ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $tiers = isset($pricing['tiered_pricing']) ? $pricing['tiered_pricing'] : [
                                ['min_sqft' => 1, 'max_sqft' => 5, 'rate' => 8.50],
                                ['min_sqft' => 6, 'max_sqft' => 15, 'rate' => 7.20],
                                ['min_sqft' => 16, 'max_sqft' => 30, 'rate' => 6.50],
                                ['min_sqft' => 31, 'max_sqft' => 9999, 'rate' => 5.50]
                            ];
                            foreach ($tiers as $t): 
                            ?>
                            <tr>
                                <td><?php echo esc_html($t['min_sqft']); ?> sq ft</td>
                                <td><?php echo $t['max_sqft'] > 1000 ? 'Any higher' : esc_html($t['max_sqft']) . ' sq ft'; ?></td>
                                <td><strong>$<?php echo number_format($t['rate'], 2); ?></strong> / sq ft</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>

        <!-- 7. UPLOAD SETTINGS TAB -->
        <?php elseif ($active_tab === 'upload'): ?>
            <div class="nexora-card">
                <h2 class="card-title">Customer File Upload Settings</h2>
                <form id="nexora-upload-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Allowed File Formats</th>
                            <td>
                                <p><strong>PNG, JPG, JPEG, WEBP, SVG, TIFF, PDF</strong></p>
                                <p class="description">Standard high-resolution graphic formats supported by DTF raster engines.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="max_file_size_mb">Maximum File Size (MB)</label></th>
                            <td>
                                <input type="number" name="max_file_size_mb" id="max_file_size_mb" value="<?php echo esc_attr(isset($upload_settings['max_file_size_mb']) ? $upload_settings['max_file_size_mb'] : 150); ?>" class="small-text" /> MB
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="retain_temp_files_days">Retention Period for Temporary Artwork (Days)</label></th>
                            <td>
                                <input type="number" name="retain_temp_files_days" id="retain_temp_files_days" value="<?php echo esc_attr(isset($upload_settings['retain_temp_files_days']) ? $upload_settings['retain_temp_files_days'] : 30); ?>" class="small-text" /> days
                                <p class="description">Artwork not converted into a placed order will be automatically purged after this period to save server disk storage.</p>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        <!-- 8. DESIGN SETTINGS TAB -->
        <?php elseif ($active_tab === 'design'): ?>
            <div class="nexora-card">
                <h2 class="card-title">Canvas & Design Workspace Defaults</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Workspace Theme</th>
                        <td><strong style="color:#7c3aed;">Dark Professional DTF Interface</strong> (Active)</td>
                    </tr>
                    <tr>
                        <th scope="row">Canvas Background Options</th>
                        <td>
                            <label><input type="checkbox" checked disabled /> Transparent Checkerboard (Essential for inspecting white DTF ink & transparent areas)</label><br/>
                            <label><input type="checkbox" checked disabled /> Dark Background Contrast Mode (For viewing white letters)</label><br/>
                            <label><input type="checkbox" checked disabled /> Light Contrast Mode</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Snap to Grid & Alignment</th>
                        <td>
                            <label><input type="checkbox" checked disabled /> Real-time snapping to canvas center, boundaries, and adjacent images</label><br/>
                            <label><input type="checkbox" checked disabled /> Visual interactive inches rulers along top and left borders</label>
                        </td>
                    </tr>
                </table>
            </div>

        <!-- 9. CLOUD STORAGE TAB -->
        <?php elseif ($active_tab === 'cloud'): ?>
            <div class="nexora-card">
                <h2 class="card-title">Cloud Storage & Customer File Import</h2>
                <p class="description">Allow customers to import their design assets directly from cloud drives in the left sidebar.</p>
                <form id="nexora-cloud-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Google Drive Import</th>
                            <td>
                                <label><input type="checkbox" name="google_drive_enabled" value="1" <?php checked(!isset($cloud_settings['google_drive_enabled']) || $cloud_settings['google_drive_enabled']); ?> /> Enable Google Drive Import Modal</label>
                                <p><input type="text" name="google_client_id" placeholder="Google Drive Client ID / API Key (Optional)" value="<?php echo esc_attr(isset($cloud_settings['google_client_id']) ? $cloud_settings['google_client_id'] : ''); ?>" class="regular-text" /></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Dropbox Import</th>
                            <td>
                                <label><input type="checkbox" name="dropbox_enabled" value="1" <?php checked(!isset($cloud_settings['dropbox_enabled']) || $cloud_settings['dropbox_enabled']); ?> /> Enable Dropbox Chooser</label>
                                <p><input type="text" name="dropbox_app_key" placeholder="Dropbox App Key (Optional)" value="<?php echo esc_attr(isset($cloud_settings['dropbox_app_key']) ? $cloud_settings['dropbox_app_key'] : ''); ?>" class="regular-text" /></p>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        <!-- 10. ORDERS & GANG SHEETS TAB -->
        <?php elseif ($active_tab === 'orders'): ?>
            <?php
            $sheets = Nexora_DB::get_recent_sheets(50);
            ?>
            <div class="nexora-card">
                <h2 class="card-title">All Customer Gang Sheets & Print Files</h2>
                <table class="nexora-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Sheet Title</th>
                            <th>Dimensions</th>
                            <th>Placed Items</th>
                            <th>Utilization</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sheets)): ?>
                            <tr><td colspan="10" style="text-align:center; padding:24px; color:#64748b;">No gang sheets submitted yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sheets as $s): ?>
                                <tr>
                                    <td>#<?php echo $s->id; ?></td>
                                    <td>
                                        <?php if ($s->preview_url): ?>
                                            <a href="<?php echo esc_url($s->preview_url); ?>" target="_blank">
                                                <img src="<?php echo esc_url($s->preview_url); ?>" style="width:50px; height:50px; object-fit:contain; background:#0f172a; border-radius:4px;" />
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo esc_html($s->title); ?></strong></td>
                                    <td><?php echo esc_html($s->width . ' ' . $s->unit . ' × ' . $s->height . ' ' . $s->unit); ?></td>
                                    <td><?php echo intval($s->item_count); ?></td>
                                    <td><?php echo round(floatval($s->utilization_rate), 1); ?>%</td>
                                    <td>$<?php echo number_format($s->total_price, 2); ?></td>
                                    <td><span class="nexora-badge badge-<?php echo esc_attr($s->status); ?>"><?php echo esc_html(ucfirst($s->status)); ?></span></td>
                                    <td><?php echo esc_html(date_i18n('M j, Y', strtotime($s->created_at))); ?></td>
                                    <td>
                                        <?php if ($s->print_file_url): ?>
                                            <a href="<?php echo esc_url($s->print_file_url); ?>" download class="button button-small" style="background:#7c3aed; color:#fff; border:none;">Download 300 DPI</a>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url(site_url('/?nexora_builder=1&token=' . $s->design_token)); ?>" target="_blank" class="button button-small">Open</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- 11. EXPORT SETTINGS TAB -->
        <?php elseif ($active_tab === 'export'): ?>
            <div class="nexora-card">
                <h2 class="card-title">Print-Ready Output Engine Settings</h2>
                <form id="nexora-export-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="export_format">Export Format</label></th>
                            <td>
                                <select name="export_format" id="export_format" class="regular-text">
                                    <option value="both" <?php selected(isset($export_settings['export_format']) ? $export_settings['export_format'] : 'both', 'both'); ?>>PNG (Transparent 300 DPI) & PDF Print Ready</option>
                                    <option value="png" <?php selected(isset($export_settings['export_format']) ? $export_settings['export_format'] : '', 'png'); ?>>PNG (Transparent 300 DPI) Only</option>
                                    <option value="pdf" <?php selected(isset($export_settings['export_format']) ? $export_settings['export_format'] : '', 'pdf'); ?>>PDF Vector Print Ready Only</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="export_dpi">Export Target Resolution</label></th>
                            <td>
                                <input type="number" name="default_dpi" id="export_dpi" value="300" class="small-text" readonly /> DPI
                                <p class="description">Guarantees exact 1:1 physical printing dimensions at 300 dots per inch without distortion.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Transparent Background</th>
                            <td>
                                <label><input type="checkbox" name="transparent_bg" value="1" <?php checked(!isset($export_settings['transparent_bg']) || $export_settings['transparent_bg']); ?> /> Maintain 100% true alpha transparency on exported PNG gang sheets for DTF RIP software (CADlink, AcroRIP, DTF Rip, etc.)</label>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        <!-- 12. LICENSE MANAGEMENT TAB -->
        <?php elseif ($active_tab === 'license'): ?>
            <div class="nexora-card" style="max-width: 600px;">
                <h2 class="card-title">Plugin License & Activation</h2>
                <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:16px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size:24px; width:24px; height:24px; color:#10b981;"></span>
                    <div>
                        <strong>License Status: Active (Nexora Pro Enterprise)</strong>
                        <p style="margin:2px 0 0; font-size:12px;">All pro modules including auto-nesting, AI tools hook, and 300 DPI export are fully enabled.</p>
                    </div>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="license_key">License Key</label></th>
                        <td>
                            <input type="text" id="license_key" value="NXR-PRO-DTF-GANGSHEET-8992-XXXX" class="regular-text" readonly />
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

