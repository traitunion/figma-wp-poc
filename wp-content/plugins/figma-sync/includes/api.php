<?php

/**
 * Gestion des appels à l’API Figma
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retourne les réglages figma (token, file_key)
 */
function figma_sync_get_settings() {

    // 🔥 Priorité absolue aux constantes wp-config.php
    if (defined('FIGMA_SYNC_TOKEN') && defined('FIGMA_SYNC_FILE_KEY')) {
        return [
            'token'    => FIGMA_SYNC_TOKEN,
            'file_key' => FIGMA_SYNC_FILE_KEY,
        ];
    }

    // Sinon fallback sur les options (ne devrait plus être utilisé)
    $settings = get_option('figma_sync_settings', []);

    $defaults = [
        'token'    => '',
        'file_key' => '',
    ];

    return array_merge($defaults, is_array($settings) ? $settings : []);
}


/**
 * Appelle l'API Figma (GET) et retourne un tableau PHP.
 */
function figma_sync_api_get($endpoint, $params = []) {
    $settings = figma_sync_get_settings();

    if (empty($settings['token']) || empty($settings['file_key'])) {
        return new WP_Error('figma_sync_missing_settings', 'Token ou file_key Figma manquant.');
    }

    $url = 'https://api.figma.com/v1' . $endpoint;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $settings['token'],
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code >= 200 && $code < 300) {
        return $data;
    }

    return new WP_Error('figma_sync_http_error', 'Erreur API Figma : ' . $code, $data);
}
