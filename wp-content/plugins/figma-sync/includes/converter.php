<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convertit un frame Figma en "page" Elementor.
 *
 * Retourne un array de type Elementor :
 * [
 *   'version' => '0.4',
 *   'title'   => 'Nom de la page',
 *   'type'    => 'page',
 *   'content' => [ ... elements Elementor ... ],
 * ]
 */
function figma_sync_convert_frame_to_elementor_template(array $frame) {
    $title = $frame['name'] ?? 'Figma Page';

    // On traite le frame comme une SECTION root unique.
    $section = figma_sync_frame_to_section($frame);

    $template = [
        'version' => '0.4',
        'title'   => $title,
        'type'    => 'page',
        'content' => [$section],
    ];

    return $template;
}

/**
 * Convertit un frame en section Elementor.
 */
function figma_sync_frame_to_section(array $frame) {
    $section = [
        'id'       => 'section-' . str_replace(':', '-', $frame['id']),
        'elType'   => 'section',
        'isInner'  => false,
        'settings' => [],
        'elements' => [],
    ];

    if (!empty($frame['children']) && is_array($frame['children'])) {
        foreach ($frame['children'] as $child) {
            $elem = figma_sync_node_to_elementor_element($child);
            if ($elem) {
                $section['elements'][] = $elem;
            }
        }
    }

    return $section;
}

/**
 * Convertit un node Figma (TEXT / FRAME / GROUP / etc.) en élément Elementor.
 *
 * MVP :
 * - TEXT → widget heading
 * - FRAME/GROUP → column avec ses enfants
 * - autres → ignorés (pour l'instant)
 */
function figma_sync_node_to_elementor_element(array $node) {
    $type = $node['type'] ?? '';

    switch ($type) {
        case 'TEXT':
            // On mappe tous les textes sur un widget "heading" pour commencer.
            $text = isset($node['characters']) ? $node['characters'] : '';

            return [
                'id'         => 'text-' . str_replace(':', '-', $node['id']),
                'elType'     => 'widget',
                'widgetType' => 'heading',
                'settings'   => [
                    'title' => $text,
                    'align' => 'left',
                ],
                'elements'   => [],
            ];

        case 'FRAME':
        case 'GROUP':
            // On traite ça comme une colonne simple.
            $column = [
                'id'       => 'column-' . str_replace(':', '-', $node['id']),
                'elType'   => 'column',
                'settings' => [],
                'elements' => [],
            ];

            if (!empty($node['children']) && is_array($node['children'])) {
                foreach ($node['children'] as $child) {
                    $elem = figma_sync_node_to_elementor_element($child);
                    if ($elem) {
                        $column['elements'][] = $elem;
                    }
                }
            }

            return $column;

        default:
            // Tout le reste ignoré pour l’instant (RECTANGLE, VECTOR, etc.)
            return null;
    }
}
