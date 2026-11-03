<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_Export {

    /**
     * Get paths for exports
     */
    public static function get_export_dir($sub = 'prints') {
        $upload_dir = wp_upload_dir();
        $target = $upload_dir['basedir'] . '/nexora-gang-sheets/' . sanitize_file_name($sub);
        if (!file_exists($target)) {
            wp_mkdir_p($target);
        }
        return $target;
    }

    public static function get_export_url($sub = 'prints') {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/nexora-gang-sheets/' . sanitize_file_name($sub);
    }

    /**
     * Save base64 image as PNG file
     */
    public static function save_base64_png($base64_string, $prefix = 'gang-sheet-print') {
        if (empty($base64_string)) {
            return false;
        }

        // Strip data:image/png;base64,
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $type = 'png';
        }

        $image_data = base64_decode($base64_string);
        if ($image_data === false) {
            return false;
        }

        $filename = sanitize_file_name($prefix . '-' . uniqid() . '-' . time() . '.' . $type);
        $filepath = self::get_export_dir('prints') . '/' . $filename;

        $saved = file_put_contents($filepath, $image_data);
        if ($saved === false) {
            return false;
        }

        return [
            'file_name' => $filename,
            'file_path' => $filepath,
            'file_url' => self::get_export_url('prints') . '/' . $filename,
            'file_size' => filesize($filepath),
        ];
    }

    /**
     * Save preview thumbnail
     */
    public static function save_preview_image($base64_string, $prefix = 'preview') {
        if (empty($base64_string)) {
            return false;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $type = 'png';
        }

        $image_data = base64_decode($base64_string);
        if ($image_data === false) {
            return false;
        }

        $filename = sanitize_file_name($prefix . '-' . uniqid() . '.' . $type);
        $filepath = self::get_export_dir('previews') . '/' . $filename;

        file_put_contents($filepath, $image_data);

        return [
            'file_name' => $filename,
            'file_path' => $filepath,
            'file_url' => self::get_export_url('previews') . '/' . $filename,
        ];
    }

    /**
     * Save uploaded artwork file safely
     */
    public static function handle_file_upload($file_array) {
        if (empty($file_array) || !isset($file_array['tmp_name'])) {
            return new WP_Error('upload_error', __('No file was uploaded.', 'nexora-gang-sheet-builder'));
        }

        $upload_settings = get_option('nexora_upload_settings', []);
        $allowed_exts = isset($upload_settings['allowed_extensions']) ? $upload_settings['allowed_extensions'] : ['png', 'jpg', 'jpeg', 'webp', 'svg', 'tif', 'tiff', 'pdf'];
        $max_size_mb = isset($upload_settings['max_file_size_mb']) ? intval($upload_settings['max_file_size_mb']) : 150;

        $filesize = filesize($file_array['tmp_name']);
        if ($filesize > ($max_size_mb * 1024 * 1024)) {
            return new WP_Error('file_too_large', sprintf(__('File size exceeds the limit of %d MB.', 'nexora-gang-sheet-builder'), $max_size_mb));
        }

        $file_info = pathinfo($file_array['name']);
        $ext = strtolower($file_info['extension']);

        if (!in_array($ext, $allowed_exts, true)) {
            return new WP_Error('invalid_file_type', sprintf(__('File type .%s is not allowed.', 'nexora-gang-sheet-builder'), $ext));
        }

        // Verify MIME type
        $check = wp_check_filetype_and_ext($file_array['tmp_name'], $file_array['name']);
        if (!$check['ext'] && !in_array($ext, ['svg', 'tif', 'tiff'], true)) {
            return new WP_Error('invalid_mime', __('File validation failed.', 'nexora-gang-sheet-builder'));
        }

        $unique_name = sanitize_file_name(pathinfo($file_array['name'], PATHINFO_FILENAME) . '-' . uniqid() . '.' . $ext);
        $target_dir = self::get_export_dir('uploads');
        $target_path = $target_dir . '/' . $unique_name;

        if (!move_uploaded_file($file_array['tmp_name'], $target_path)) {
            return new WP_Error('move_error', __('Could not move uploaded file.', 'nexora-gang-sheet-builder'));
        }

        // Extract image dimensions
        $width = 0;
        $height = 0;
        $dpi = 300;

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $dimensions = @getimagesize($target_path);
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        $file_url = self::get_export_url('uploads') . '/' . $unique_name;

        return [
            'file_name' => $file_array['name'],
            'stored_name' => $unique_name,
            'file_path' => $target_path,
            'file_url' => $file_url,
            'width' => $width,
            'height' => $height,
            'dpi' => $dpi,
            'size' => $filesize,
            'mime_type' => $file_array['type'] ?: 'image/' . $ext,
        ];
    }
}

