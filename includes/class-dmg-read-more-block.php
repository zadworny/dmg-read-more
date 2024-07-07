<?php
class DMG_Read_More_Block {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_block' ) );
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
    }

    public static function register_block() {
        wp_register_script(
            'dmg-read-more-block',
            plugins_url( '../assets/js/block.js', __FILE__ ),
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ),
            filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/block.js' )
        );

        register_block_type( 'dmg/read-more', array(
            'editor_script' => 'dmg-read-more-block',
        ) );
    }

    public static function enqueue_styles() {
        $style_url = plugins_url( '../assets/css/style.css', __FILE__ );
        $style_path = plugin_dir_path( __FILE__ ) . '../assets/css/style.css';

        wp_enqueue_style(
            'dmg-read-more-style',
            $style_url,
            array(),
            filemtime( $style_path )
        );
    }
}

DMG_Read_More_Block::init();
?>
