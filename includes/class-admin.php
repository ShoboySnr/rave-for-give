<?php

namespace WPBROS\WAVE_GIVE;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    
    public function __construct() {
        $this->hooks();
    }
    
    
    public function hooks() {
        add_filter( 'give_get_sections_gateways', [ $this, 'register_sections' ] );
        add_filter( 'give_get_settings_gateways', [ $this, 'register_settings' ] );
    }
    
    /**
     * Register Admin Section.
     *
     * @param array $sections List of sections.
     *
     * @since  1.0.0
     * @access public
     *
     * @return array
     */
    public function register_sections( $sections ) {
        $sections['flutterwave'] = esc_html__( 'Flutterwave', 'rave-give' );
        
        return $sections;
    }
    
    /**
     * Register Admin Settings.
     *
     * @param array $settings List of settings.
     *
     * @since  1.0.0
     * @access public
     *
     * @return array
     */
    public function register_settings( $settings ) {
        $current_section = give_get_current_setting_section();
    
        switch ( $current_section ) {
            case 'flutterwave':
        
                break;
        }
        
        return $settings;
    }
}