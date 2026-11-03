<?php
if (!defined('ABSPATH')) {
    exit;
}

class Nexora_Deactivator {
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

