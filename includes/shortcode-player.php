<?php
/**
 * Shortcode player functionality for TYPE III AUDIO
 */

if (!defined('ABSPATH')) {
    exit;
}

function t3a_enqueue_scripts() {
    wp_register_script('type-3-player', 'https://embed.type3.audio/player.js', array(), '1.0.0', true);
}

add_action('wp_enqueue_scripts', 't3a_enqueue_scripts');

function type_3_player($atts) {
    $attributes = '';

    foreach($atts as $key => $value) {
        $attributes .= $key .'="' . $value . '" ';
    }

    wp_enqueue_script('type-3-player');
    wp_script_add_data('type-3-player', array('type', 'crossorigin'), array('module', ''));

    $html = '
        <type-3-player
        ' . $attributes . '
        >
        </type-3-player>
    ';

    // If we're not serving a hardcoded MP3 URL, then we should only show 
    // the player if the post is published.
    //
    // (Narrations cannot be created before the post is published, since the
    // TYPE III AUDIO crawler won't be able to access the post URL.)
    
    if (!t3a_is_hardcoded_mp3_url($atts)) {
        if (!t3a_is_post_published()) {
            $html = "<p style='padding: 10px; border: 1px dashed #ccc; border-radius: 4px; text-align: center;'>The TYPE III AUDIO player will display here when this post is published.</p>";
            return $html;
        }
    }

    return $html;
}

function t3a_is_hardcoded_mp3_url($atts) {
    return isset($atts['mp3-url']) && $atts['mp3-url'] !== '';
}

function t3a_is_post_published() {
    global $post;
    return isset($post) && is_object($post) && $post->post_status === 'publish';
}

add_shortcode('type_3_player', 'type_3_player'); 