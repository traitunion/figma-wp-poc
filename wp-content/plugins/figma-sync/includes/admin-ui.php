<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute l'entrée de menu admin.
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Figma Sync',
        'Figma Sync',
        'manage_options',
        'figma-sync',
        'figma_sync_admin_page',
        'dashicons-update',
        60
    );
});

/**
 * Page admin : réglages + bouton sync.
 */
function figma_sync_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Traitement formulaire de réglages
    if (isset($_POST['figma_sync_save_settings'])) {
        check_admin_referer('figma_sync_save_settings');

        $token    = isset($_POST['figma_token']) ? wp_unslash($_POST['figma_token']) : '';
$file_key = sanitize_text_field($_POST['figma_file_key'] ?? '');


        update_option('figma_sync_settings', [
            'token'    => $token,
            'file_key' => $file_key,
        ]);

        echo '<div class="notice notice-success"><p>Réglages Figma enregistrés.</p></div>';
    }

    // Traitement bouton "Synchroniser"
    $sync_results = null;
    if (isset($_POST['figma_sync_run_sync'])) {
        check_admin_referer('figma_sync_run_sync');
        $sync_results = figma_sync_sync_all_frames();
    }

    $settings = figma_sync_get_settings();

    ?>
    <div class="wrap">
        <h1>Figma Sync → Elementor</h1>

        <h2>Réglages Figma</h2>
        <form method="post">
            <?php wp_nonce_field('figma_sync_save_settings'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="figma_token">Figma API Token</label></th>
                    <td>
                        <input type="password" id="figma_token" name="figma_token" class="regular-text"
                               value="<?php echo esc_attr($settings['token']); ?>">
                        <p class="description">Token personnel Figma (Developers → Personal Access Tokens).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="figma_file_key">Figma File Key</label></th>
                    <td>
                        <input type="text" id="figma_file_key" name="figma_file_key" class="regular-text"
                               value="<?php echo esc_attr($settings['file_key']); ?>">
                        <p class="description">Exemple : dans l’URL Figma /design/<strong>YE3PhalQIb2lXcNLECMZHp</strong>/...</p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="figma_sync_save_settings" class="button button-primary">
                    Enregistrer les réglages
                </button>
            </p>
        </form>

        <hr>

        <h2>Synchroniser toutes les pages</h2>

        <form method="post">
            <?php wp_nonce_field('figma_sync_run_sync'); ?>
            <p>
                <button type="submit" name="figma_sync_run_sync" class="button button-secondary">
                    Synchroniser toutes les frames Figma → pages WordPress
                </button>
            </p>
        </form>

        <?php if ($sync_results !== null): ?>
            <h2>Résultats de la synchronisation</h2>
            <?php if (is_wp_error($sync_results)): ?>
                <div class="notice notice-error"><p><?php echo esc_html($sync_results->get_error_message()); ?></p></div>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th>Frame</th>
                        <th>Page Figma</th>
                        <th>Statut</th>
                        <th>Page WP</th>
                        <th>Message</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sync_results as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result['frame']['name']); ?></td>
                            <td><?php echo esc_html($result['frame']['page_name']); ?></td>
                            <td>
                                <?php if ($result['status'] === 'ok'): ?>
                                    <span style="color:green;">OK</span>
                                <?php else: ?>
                                    <span style="color:red;">Erreur</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($result['page_id']) && $result['status'] === 'ok') {
                                    $edit_link = get_edit_post_link($result['page_id']);
                                    echo '<a href="' . esc_url($edit_link) . '">Page #' . intval($result['page_id']) . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                echo !empty($result['error']) ? esc_html($result['error']) : '';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <hr>

        <h2>Frames détectés dans Figma</h2>
        <?php
        $frames = figma_sync_scan_frames();
        if (is_wp_error($frames)) {
            echo '<div class="notice notice-error"><p>' . esc_html($frames->get_error_message()) . '</p></div>';
        } elseif (empty($frames)) {
            echo '<p>Aucun frame trouvé. Vérifie ton fichier Figma.</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>Frame</th><th>Page</th><th>ID</th></tr></thead><tbody>';
            foreach ($frames as $frame) {
                echo '<tr>';
                echo '<td>' . esc_html($frame['name']) . '</td>';
                echo '<td>' . esc_html($frame['page_name']) . '</td>';
                echo '<td><code>' . esc_html($frame['id']) . '</code></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        ?>
    </div>
    <?php
}
