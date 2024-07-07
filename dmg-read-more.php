<?php
/**
 * Plugin Name: DMG Read More
 * Description: A plugin that adds a 'Read More' block with a link to a post and CLI command to search posts with the block.
 * Version: 1.0
 * Author: Sam Zadworny
 * Author URI: https://linkedin.com/in/samzadworny
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once plugin_dir_path(__FILE__) . 'includes/class-dmg-read-more-block.php';
include_once plugin_dir_path(__FILE__) . 'includes/class-dmg-read-more-cli.php';

DMG_Read_More_Block::init();
DMG_Read_More_CLI::init();
?>
