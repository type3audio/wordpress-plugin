<?php
/**
 * Manage narration meta box for TYPE III AUDIO
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 't3a_register_manage_narration_metabox');
add_action('admin_enqueue_scripts', 't3a_enqueue_manage_narration_metabox_assets');

/**
 * Returns translated strings used by the Manage narration meta box UI.
 *
 * @return array<string,string>
 */
function t3a_get_manage_narration_strings() {
    return array(
        'loading' => __('Loading latest narration status…', 'type_3_player'),
        'unavailable' => __('Narration status is temporarily unavailable.', 'type_3_player'),
        'unexpected' => __('Received an unexpected response when requesting narration status.', 'type_3_player'),
        'manageButton' => __('Manage narration', 'type_3_player'),
        'narrationTypeLabel' => __('Narration type:', 'type_3_player'),
        'narrationTypeHuman' => __('Human narration', 'type_3_player'),
        'narrationTypeAi' => __('AI narration', 'type_3_player'),
        'podcastStatusLabel' => __('Podcast status:', 'type_3_player'),
        'podcastPublished' => __('Published', 'type_3_player'),
        'podcastNotPublished' => __('Not published', 'type_3_player'),
        'lastGeneratedLabel' => __('Last updated:', 'type_3_player'),
        'uploadedLabel' => __('Uploaded:', 'type_3_player'),
        'notFound' => __('Narration not found', 'type_3_player'),
    );
}

/**
 * Enqueues assets for the Manage narration meta box.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function t3a_enqueue_manage_narration_metabox_assets($hook_suffix) {
    if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || !in_array($screen->post_type, array('post', 'page'), true)) {
        return;
    }

    wp_enqueue_script(
        't3a-manage-narration',
        T3A_PLUGIN_URL . '/assets/js/manage-narration.js',
        array('wp-date'),
        T3A_VERSION,
        true
    );

    $date_format = get_option('date_format', 'F j, Y');
    $time_format = get_option('time_format', 'g:i a');

    $strings = t3a_get_manage_narration_strings();

    wp_localize_script(
        't3a-manage-narration',
        't3aManageNarration',
        array(
            'strings' => $strings,
            'formats' => array(
                'dateTime' => trim($date_format . ' ' . $time_format),
            ),
        )
    );
}

/**
 * Registers the Manage narration meta box for supported post types.
 *
 * @return void
 */
function t3a_register_manage_narration_metabox() {
    $post_types = array('post', 'page');

    foreach ($post_types as $post_type) {
        add_meta_box(
            't3a-manage-narration',
            __('Manage narration', 'type_3_player'),
            't3a_render_manage_narration_metabox',
            $post_type,
            'side',
            'low'
        );
    }
}

/**
 * Renders the Manage narration meta box content.
 *
 * @param WP_Post $post The post being edited.
 * @return void
 */
function t3a_render_manage_narration_metabox($post) {
    if (!($post instanceof WP_Post)) {
        $post = get_post($post);
    }

    $permalink = get_permalink($post);

    if (!$post) {
        echo '<p>' . esc_html__('Unable to determine the post being edited.', 'type_3_player') . '</p>';
        return;
    }

    if ($post->post_status !== 'publish') {
        echo '<p>' . esc_html__('Narrations can only be managed after the post is published.', 'type_3_player') . '</p>';
        return;
    }

    if (!$permalink) {
        echo '<p>' . esc_html__('Unable to determine the post URL.', 'type_3_player') . '</p>';
        return;
    }

    $allowed_paths = array(
        '/skills',
        '/agi/guide',
        '/career-reviews',
        '/problem-profiles',
        '/articles',
    );

    $path = (string) wp_parse_url($permalink, PHP_URL_PATH);
    $path_matches = false;

    foreach ($allowed_paths as $allowed_path) {
        if ($path === $allowed_path || strpos($path, $allowed_path . '/') === 0) {
            $path_matches = true;
            break;
        }
    }

    if (!$path_matches) {
        echo '<p>' . esc_html__('Narrations are only enabled for URLs that begin with:', 'type_3_player') . '</p>';
        echo '<ul>';

        foreach ($allowed_paths as $allowed_path) {
            echo '<li><code>' . esc_html($allowed_path) . '</code></li>';
        }

        echo '</ul>';
        return;
    }

    $current_host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $dashboard_base_url = ($current_host === 'wordpress.local')
        ? 'http://localhost:3011'
        : 'https://clients.type3.audio';

    $manage_url = $dashboard_base_url . '/narrations/manage?source_url=' . rawurlencode($permalink);

    $status_base_url = ($current_host === 'wordpress.local')
        ? 'http://localhost:3003'
        : 'https://clients.type3.audio';

    $status_url = $status_base_url . '/narration/status?source_url=' . rawurlencode($permalink);

    $strings = t3a_get_manage_narration_strings();
    $loading_text = isset($strings['loading']) ? $strings['loading'] : __('Loading latest narration status…', 'type_3_player');

    echo '<div class="t3a-manage-narration js-t3a-manage-narration" data-status-url="' . esc_attr($status_url) . '" data-manage-url="' . esc_attr($manage_url) . '">';
    echo '<p class="t3a-manage-narration__status js-t3a-manage-narration-status">' . esc_html($loading_text) . '</p>';
    echo '<p class="t3a-manage-narration__type js-t3a-manage-narration-type"></p>';
    echo '<p class="t3a-manage-narration__generated js-t3a-manage-narration-generated"></p>';
    echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
    echo '<p class="t3a-manage-narration__action js-t3a-manage-narration-action" style="margin: 0;"><a class="button" style="display:none;" href="' . esc_url($manage_url) . '" target="_blank" rel="noopener noreferrer"></a></p>';
    echo '<p class="js-t3a-manage-narration-login" style="display: none; color: #999; font-size: 11px; margin: 0;"><a href="https://google.com" target="_blank" rel="noopener noreferrer" style=" margin-top: 3px;  text-decoration: underline;"> Instructions</a></p>';
    echo '</div>';
    echo '</div>';
}
