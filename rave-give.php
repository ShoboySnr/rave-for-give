<?php
/*
Plugin Name:       Flutterwave Payment Gateway for GiveWP
Plugin URL:        https://techwithdee.com
Description:       Flutterwave payment gateway for GiveWP
Version:           1.0.0
Author:            Damilare Shobowale
Author URI:        https://techwithdee.com
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain:       rave-give
Domain Path:       /languages
*/


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Root File.
if ( ! defined( 'WPBROS_RAVE_GIVE_PLUGIN_FILE' ) ) {
    define( 'WPBROS_RAVE_GIVE_PLUGIN_FILE', __FILE__ );
}

// Plugin version.
if ( ! defined( 'WPBROS_RAVE_GIVE_VERSION' ) ) {
    define( 'WPBROS_RAVE_GIVE_VERSION', '1.0.0' );
}

// Plugin Folder Path.
if ( ! defined( 'WPBROS_RAVE_GIVE_PLUGIN_DIR' ) ) {
    define( 'WPBROS_RAVE_GIVE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL.
if ( ! defined( 'WPBROS_RAVE_GIVE_URL' ) ) {
    define( 'WPBROS_RAVE_GIVE_URL', plugin_dir_url( __FILE__ ) );
}

if( ! defined( 'WPBROS_RAVE_GIVE_ASSET_URL' ) ) {
    define( 'WPBROS_RAVE_GIVE_ASSET_URL', WPBROS_RAVE_GIVE_URL. 'assets/' );
}

function wpbros_wave_give_loader() {

    if(! class_exists('Give' ) ) {
        return;
    }
    
    require_once WPBROS_RAVE_GIVE_PLUGIN_DIR . 'includes/functions.php';
    require_once WPBROS_RAVE_GIVE_PLUGIN_DIR . 'includes/class-frontend.php';
    
    if ( is_admin() ) {
        require_once WPBROS_RAVE_GIVE_PLUGIN_DIR . 'includes/class-admin.php';
    }
}
add_action( 'plugins_loaded', 'wpbros_wave_give_loader', 100 );