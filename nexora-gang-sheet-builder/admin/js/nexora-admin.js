(function($) {
    'use strict';

    $(document).ready(function() {
        // Save All Changes button handler
        $('#nexora-save-all-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            // Determine active tab form
            var urlParams = new URLSearchParams(window.location.search);
            var tab = urlParams.get('tab') || 'dashboard';

            var postData = {
                action: 'nexora_admin_save_settings',
                nonce: nexoraAdminData.admin_nonce,
                tab: tab
            };

            if (tab === 'general') {
                postData.general = {
                    unit: $('#unit').val(),
                    default_dpi: $('#default_dpi').val(),
                    min_dpi_warning: $('#min_dpi_warning').val(),
                    default_sheet_width: $('input[name="default_sheet_width"]').val(),
                    default_sheet_height: $('input[name="default_sheet_height"]').val(),
                    print_margin: $('#print_margin').val(),
                    auto_save: $('input[name="auto_save"]').is(':checked') ? 1 : 0
                };
            } else if (tab === 'woocommerce') {
                postData.woocommerce = {
                    enabled: $('input[name="enabled"]').is(':checked') ? 1 : 0,
                    button_text: $('#button_text').val(),
                    button_location: $('#button_location').val(),
                    redirect_after_add: $('#redirect_after_add').val(),
                    enable_preview_in_cart: $('input[name="enable_preview_in_cart"]').is(':checked') ? 1 : 0,
                    enable_preview_in_order: $('input[name="enable_preview_in_order"]').is(':checked') ? 1 : 0,
                    enable_customer_download: $('input[name="enable_customer_download"]').is(':checked') ? 1 : 0
                };
            } else if (tab === 'pricing') {
                postData.pricing = {
                    pricing_mode: $('#pricing_mode').val(),
                    price_per_sq_ft: $('#price_per_sq_ft').val(),
                    price_per_sq_in: $('#price_per_sq_in').val(),
                    min_price: $('#min_price').val()
                };
            } else if (tab === 'presets') {
                var presets = [];
                $('#nexora-presets-table tbody tr').each(function() {
                    var $tr = $(this);
                    presets.push({
                        id: $tr.find('.preset-width').val() + 'x' + $tr.find('.preset-height').val(),
                        name: $tr.find('.preset-name').val(),
                        width: parseFloat($tr.find('.preset-width').val()) || 22,
                        height: parseFloat($tr.find('.preset-height').val()) || 60,
                        unit: 'in',
                        price: parseFloat($tr.find('.preset-price').val()) || 0,
                        is_default: $tr.find('.preset-default').is(':checked')
                    });
                });
                postData.presets = JSON.stringify(presets);
            } else if (tab === 'upload') {
                postData.upload = {
                    max_file_size_mb: $('#max_file_size_mb').val(),
                    retain_temp_files_days: $('#retain_temp_files_days').val()
                };
            } else if (tab === 'export') {
                postData.export = {
                    export_format: $('#export_format').val(),
                    default_dpi: 300,
                    transparent_bg: $('input[name="transparent_bg"]').is(':checked') ? 1 : 0
                };
            } else if (tab === 'cloud') {
                postData.cloud = {
                    google_drive_enabled: $('input[name="google_drive_enabled"]').is(':checked') ? 1 : 0,
                    google_client_id: $('input[name="google_client_id"]').val(),
                    dropbox_enabled: $('input[name="dropbox_enabled"]').is(':checked') ? 1 : 0,
                    dropbox_app_key: $('input[name="dropbox_app_key"]').val()
                };
            }

            $.post(nexoraAdminData.ajax_url, postData, function(response) {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Saved!');
                setTimeout(function() {
                    $btn.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> <span>Save Changes</span>');
                }, 2000);
            }).fail(function() {
                $btn.prop('disabled', false).text('Error Saving');
            });
        });

        // Add new preset row
        $('#nexora-add-preset-btn').on('click', function() {
            var newRow = '<tr>' +
                '<td><input type="text" class="preset-name regular-text" value="New Custom Sheet" style="width:240px;" /></td>' +
                '<td><input type="number" step="0.5" class="preset-width" value="22" style="width:80px;" /></td>' +
                '<td><input type="number" step="0.5" class="preset-height" value="48" style="width:80px;" /></td>' +
                '<td>$<input type="number" step="0.01" class="preset-price" value="45.00" style="width:90px;" /></td>' +
                '<td style="text-align:center;"><input type="radio" name="preset_default_radio" class="preset-default" /></td>' +
                '<td><button type="button" class="button button-small nexora-delete-preset-btn" style="color:#ef4444;">Delete</button></td>' +
                '</tr>';
            $('#nexora-presets-table tbody').append(newRow);
        });

        // Delete preset row
        $(document).on('click', '.nexora-delete-preset-btn', function() {
            if (confirm('Are you sure you want to delete this sheet preset?')) {
                $(this).closest('tr').remove();
            }
        });
    });
})(jQuery);

