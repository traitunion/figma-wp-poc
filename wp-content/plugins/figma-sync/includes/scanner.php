<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère tout le fichier Figma et liste les frames de niveau page.
 *
 * Retourne un tableau :
 * [
 *   [
 *     'id' => '4717:407',
 *     'name' => 'DAC Home Page',
 *     'page_name' => 'Design',
 *   ],
 *   ...
 * ]
 */
function figma_sync_scan_frames() {
    $settings = figma_sync_get_settings();
    if (empty($settings['file_key'])) {
        return new WP_Error('figma_sync_missing_file_key', 'File key Figma manquant.');
    }

    $data = figma_sync_api_get('/files/' . $settings['file_key']);

    if (is_wp_error($data)) {
        return $data;
    }

    if (empty($data['document']['children'])) {
        return [];
    }

    $frames = [];

    // children = pages Figma
    foreach ($data['document']['children'] as $page) {
        if (empty($page['children'])) {
            continue;
        }

        $page_name = $page['name'] ?? 'Page';

        // On ne garde que les nodes de type FRAME
        foreach ($page['children'] as $node) {
            if (($node['type'] ?? '') === 'FRAME') {
                $frames[] = [
                    'id'        => $node['id'],
                    'name'      => $node['name'] ?? 'Sans titre',
                    'page_name' => $page_name,
                ];
            }
        }
    }

    return $frames;
}

/**
 * Récupère le détail d'un frame spécifique (node) pour conversion.
 */
function figma_sync_get_frame($frame_id) {
    $settings = figma_sync_get_settings();
    if (empty($settings['file_key'])) {
        return new WP_Error('figma_sync_missing_file_key', 'File key Figma manquant.');
    }

    $data = figma_sync_api_get('/files/' . $settings['file_key'] . '/nodes', [
        'ids' => $frame_id,
    ]);

    if (is_wp_error($data)) {
        return $data;
    }

    if (!isset($data['nodes'][$frame_id]['document'])) {
        return new WP_Error('figma_sync_missing_frame', 'Frame non trouvé dans la réponse Figma.');
    }

    return $data['nodes'][$frame_id]['document'];
}
