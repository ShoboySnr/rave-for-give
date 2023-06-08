<?php

namespace WPBROS\RAVE_GIVE;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    
    public function __construct() {
        $this->hooks();
    }
    
    
    public function hooks() {
        add_filter( 'give_get_sections_gateways', array( $this, 'register_sections' ) );
        add_filter( 'give_get_settings_gateways', array( $this, 'register_settings' ) );
        add_filter(
            'plugin_action_links_' . plugin_basename( WPBROS_RAVE_GIVE_PLUGIN_FILE ),
            array(
                $this,
                'plugin_action_links',
            )
        );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ));
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
        $sections['rave-give'] = esc_html__( 'Flutterwave', 'rave-give' );
        
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
            case 'rave-give':
                $rave_webhook = 'https://app.flutterwave.com/dashboard/settings/webhooks/';
                
                $settings = [
                    [
                        'type'      => 'title',
                        'id'        => 'rave_title_gateway_settings_rave',
                        'desc'      => sprintf(__('Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL %shere%s to the URL below <br><br> <div style="color: red;">%s</div>', 'rave-give'), '<a href="'.esc_url($rave_webhook). '" target="_blank" title="Set Webhook URL">', '</a>', esc_url(home_url( '/give-api/WPBROS_Rave_Give_Webhook/' )))
                    ],
                    [
                        'name'          => esc_html__( 'Title', 'rave-give' ),
                        'desc'          => esc_html__( 'This controls the payment method title which the user sees during checkout', 'rave-give' ),
                        'id'            => 'rave_title',
                        'type'          => 'text',
                        'default'         => __('Flutterwave', 'rave-give'),
                        'class'   => 'give-rave-title',
                    ],
                    [
                        'name'        => esc_html__( 'Description', 'rave-give' ),
                        'desc'        => esc_html__( 'This controls the payment method description which the user sees during checkout', 'rave-give' ),
                        'id'          => 'rave_description',
                        'type'        => 'textarea',
                        'default'         => __('Make payment using your debit, credit card & bank account', 'rave-give'),
                        'class' => 'give-rave-description',
                    ],
                    [
                        'name'    => esc_html__( 'Enable Test Mode', 'rave-give' ),
                        'desc'    => esc_html__( 'Test Mode enables you to test payments before going live. Once you are live uncheck this.', 'rave-give' ),
                        'id'      => 'rave_enable_test_mode',
                        'type'    => 'checkbox',
                        'default' => 'on',
                    ],
                    [
                        'name'        => esc_html__( 'Test Public Key', 'rave-give' ),
                        'desc'        => esc_html__( 'Required: Enter your Test Public Key here', 'rave-give' ),
                        'id'          => 'rave_test_public_key',
                        'type'        => 'text',
                        'class' => 'give-rave-test-public-key',
                        'wrapper_class' => 'give-rave-check-mode test-key',
                    ],
                    [
                        'name'        => esc_html__( 'Test Secret Key', 'rave-give' ),
                        'desc'        => esc_html__( 'Required: Enter your Test Secret Key here', 'rave-give' ),
                        'id'          => 'rave_test_secret_key',
                        'type'        => 'text',
                        'class' => 'give-rave-test-secret-key',
                        'wrapper_class' => 'give-rave-check-mode test-key',
                    ],
                    [
                        'name'        => esc_html__( 'Live Public Key', 'rave-give' ),
                        'desc'        => esc_html__( 'Required: Enter your Live Public Key here', 'rave-give' ),
                        'id'          => 'rave_live_public_key',
                        'type'        => 'text',
                        'class' => 'give-rave-live-public-key',
                        'wrapper_class' => 'give-rave-check-mode live-key',
                    ],
                    [
                        'name'        => esc_html__( 'Live Secret Key', 'rave-give' ),
                        'desc'        => esc_html__( 'Required: Enter your Live Secret Key here', 'rave-give' ),
                        'id'          => 'rave_live_secret_key',
                        'type'        => 'text',
                        'class' => 'give-rave-live-secret-key',
                        'wrapper_class' => 'give-rave-check-mode live-key',
                    ],
                    [
                        'name'          => esc_html__( 'Custom Title', 'rave-give' ),
                        'desc'          => esc_html__( 'Optional: Text to be displayed as the title of the Payment modal', 'rave-give' ),
                        'id'            => 'rave_custom_title',
                        'type'          => 'text',
                        'class'   => 'give-rave-custom-title',
                    ],
                    [
                        'name'          => esc_html__( 'Custom Description', 'rave-give' ),
                        'desc'          => esc_html__( 'Optional: Text to be displayed as a short modal description', 'rave-give' ),
                        'id'            => 'rave_custom_description',
                        'type'          => 'textarea',
                        'class'   => 'give-rave-custom-description',
                    ],
                    [
                        'name'          => esc_html__( 'Custom Logo', 'rave-give' ),
                        'desc'          => esc_html__( 'Upload image to be displayed on the Payment modal. Preferable a square image.', 'rave-give' ),
                        'id'            => 'rave_custom_logo',
                        'type'          => 'media',
                        'class'   => 'give-rave-custom-logo',
                    ],
                    [
                        'name'    => esc_html__( 'Saved Cards', 'rave-give' ),
                        'desc'    => esc_html__( 'Enable Payment via Saved Cards.', 'rave-give' ),
                        'id'      => 'rave_enable_saved_cards',
                        'type'    => 'checkbox',
                    ],
                    [
                        'name'    => esc_html__( 'Billing Details', 'rave-give' ),
                        'desc'    => esc_html__( 'This will enable you to collect donor details. This is not required by Flutterwave (except email) but you might need to collect all information for record purposes', 'rave-give' ),
                        'id'      => 'rave_billing_details',
                        'type'    => 'radio_inline',
                        'default' => 'disabled',
                        'options' => [
                            'enabled'  => esc_html__( 'Enabled', 'rave-give' ),
                            'disabled' => esc_html__( 'Disabled', 'rave-give' ),
                        ],
                    ],
                    [
                        'type' => 'sectionend',
                        'id'   => 'give_title_gateway_settings_rave',
                    ]
                ];
                break;
        }
        
        return $settings;
    }
    
    /**
     * Plugin link actions
     *
     * @param $links
     * @return mixed
     */
    public function plugin_action_links( $links ) {
        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
        $settings_link = array(
            '<a href="' . admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=rave-give') . '">' . __('Settings', 'rave-give') . '</a>',
        );
        
        return array_merge($settings_link, $links);
    }
    
    
    /**
     * Register the JavaScript for the admin area.
     *
     */
    public function enqueue_scripts() {
        $current_section = give_get_current_setting_section();
    
        switch ( $current_section ) {
            case 'rave-give':
                wp_enqueue_script('rave-give-admin', WPBROS_RAVE_GIVE_ASSET_URL . 'admin/js/rave-give.js', array( 'jquery' ), WPBROS_RAVE_GIVE_VERSION, false);
            break;
        }
        
    }
    
    
    /**
     * Register the stylesheets for the admin area.
     *
     */
    public function enqueue_styles()
    {
        $current_section = give_get_current_setting_section();
    
        switch ( $current_section ) {
            case 'rave-give':
                wp_enqueue_style('rave-give-admin', WPBROS_RAVE_GIVE_ASSET_URL . 'admin/css/rave-give.css', array(), WPBROS_RAVE_GIVE_VERSION, 'all');
            break;
        }
        
    }
}

new namespace\Admin();