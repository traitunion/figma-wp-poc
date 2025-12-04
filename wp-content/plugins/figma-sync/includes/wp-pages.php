<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crée ou met à jour une page WordPress pour un frame Figma donné.
 *
 * @param array $frame_info   ['id' => '4717:407', 'name' => 'DAC Home Page', 'page_name' => 'Design']
 * @param array $template     Template Elementor (array)
 *
 * @return int|WP_Error       ID de la page WordPress ou erreur.
 */
function figma_sync_create_or_update_page(array $frame_info, array $template) {
    $title = $frame_info['name'] ?? 'Figma Page';

    // Chercher si une page existe déjà avec ce titre + meta qui lie au frame_id.
    $existing = get_posts([
        'post_type'      => 'page',
        'posts_per_page' => 1,
        'meta_key'       => '_figma_frame_id',
        'meta_value'     => $frame_info['id'],
        'post_status'    => 'any',
    ]);

    if (!empty($existing)) {
        $page_id = $existing[0]->ID;

        wp_update_post([
            'ID'         => $page_id,
            'post_title' => $title,
        ]);
    } else {
        $page_id = wp_insert_post([
            'post_title'   => $title,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_content' => '', // Elementor stocke dans la meta
        ]);

        if (is_wp_error($page_id)) {
            return $page_id;
        }

        update_post_meta($page_id, '_figma_frame_id', $frame_info['id']);
    }

    // Enregistrer le contenu Elementor dans la meta.
    // Elementor attend un JSON d'array d'éléments pour _elementor_data.
    $content = $template['content'] ?? [];
    update_post_meta($page_id, '_elementor_data', wp_slash(json_encode($content)));

    // Indiquer que cette page utilise Elementor
    update_post_meta($page_id, '_elementor_edit_mode', 'builder');
    update_post_meta($page_id, '_elementor_template_type', 'wp-page');

    return $page_id;
}

/**
 * Synchronise TOUTES les frames Figma → pages WP.
 *
 * Retourne un tableau des résultats.
 */
function figma_sync_sync_all_frames() {
    $frames = figma_sync_scan_frames();

    if (is_wp_error($frames)) {
        return $frames;
    }

    $results = [];

    foreach ($frames as $frame_info) {
        $frame = figma_sync_get_frame($frame_info['id']);

        if (is_wp_error($frame)) {
            $results[] = [
                'frame'  => $frame_info,
                'status' => 'error',
                'error'  => $frame->get_error_message(),
            ];
            continue;
        }

        $template = figma_sync_convert_frame_to_elementor_template($frame);
        $page_id  = figma_sync_create_or_update_page($frame_info, $template);

        if (is_wp_error($page_id)) {
            $results[] = [
                'frame'  => $frame_info,
                'status' => 'error',
                'error'  => $page_id->get_error_message(),
            ];
        } else {
            $results[] = [
                'frame'   => $frame_info,
                'status'  => 'ok',
                'page_id' => $page_id,
            ];
        }
    }

    return $results;
}
