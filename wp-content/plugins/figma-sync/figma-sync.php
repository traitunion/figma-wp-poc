<?php
/**
 * Plugin Name: Figma Sync
 * Description: Synchronise automatiquement des frames Figma en pages Elementor.
 * Version: 0.1.0
 * Author: Alex
 */

if (!defined('ABSPATH')) {
    exit;
}

// Définir quelques constantes utiles
define('FIGMA_SYNC_DIR', plugin_dir_path(__FILE__));
define('FIGMA_SYNC_URL', plugin_dir_url(__FILE__));

// Chargement des fichiers du plugin
require_once FIGMA_SYNC_DIR . 'includes/api.php';
require_once FIGMA_SYNC_DIR . 'includes/scanner.php';
require_once FIGMA_SYNC_DIR . 'includes/converter.php';
require_once FIGMA_SYNC_DIR . 'includes/wp-pages.php';
require_once FIGMA_SYNC_DIR . 'includes/admin-ui.php';

// Activation : on peut éventuellement initialiser des options
register_activation_hook(__FILE__, function () {
    if (get_option('figma_sync_settings') === false) {
        add_option('figma_sync_settings', [
            'token'    => '',
            'file_key' => '',
        ]);
    }
});
