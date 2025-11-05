<?php
/**
 * Plugin Name: TYPE III AUDIO - Audio player & automatic narration
 * Plugin URI: https://type3.audio
 * Description: Audio player for your MP3s. Narrations for your web pages.
 * Version: 1.4
 * Text Domain: type_3_player
 * Author: TYPE III AUDIO
 * Author URI: https://type3.audio
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('T3A_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('T3A_PLUGIN_URL', plugins_url('', __FILE__));
define('T3A_VERSION', '1.4');

// Include required files
require_once T3A_PLUGIN_PATH . 'includes/shortcode-player.php';
require_once T3A_PLUGIN_PATH . 'includes/block-editor.php';
require_once T3A_PLUGIN_PATH . 'includes/admin-settings.php';
require_once T3A_PLUGIN_PATH . 'includes/regeneration.php';
require_once T3A_PLUGIN_PATH . 'includes/manage-narration-metabox.php';
