<?php

namespace WPBROS\RAVE_GIVE;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {
    
    private $api_query_var = 'rave-give-api';

    public function __construct()
    {
        $this->hooks();
    }
    
    public function hooks()
    {
        add_filter('give_currencies', array( $this, 'give_rave_add_currencies') );
        add_filter('give_payment_gateways', array( $this, 'give_rave_register_gateway' ), 1);
        add_action('give_gateway_rave-give', array( $this, 'give_process_rave_purchase') );
        add_action('give_rave-give_cc_form', array($this, 'give_rave_credit_card_form'), 10, 2 );
    }
    
    /**
     * Register gateway so it shows up as an option in the Give gateway settings
     *
     * @param array $gateways
     *
     * @return array
     */
    public function give_rave_register_gateway($gateways) {
        $gateways['rave-give'] = array(
            'admin_label' => esc_attr__('Flutterwave', 'rave-give'),
            'checkout_label' => esc_attr__('Flutterwave', 'rave-give'),
        );
        
        return $gateways;
    }
    
    /**
     * Check whether to remove billing details or not based on the option set in the setting
     *
     * @param $form_id
     * @param bool $echo
     * @return mixed
     */
    public function give_rave_credit_card_form($form_id, $echo = true) {
        $billing_fields_enabled = give_get_option('rave_billing_details');
    
        if ($billing_fields_enabled == 'enabled') {
            do_action('give_after_cc_fields');
        } else {
            //Remove Address Fields if user has option enabled
            remove_action('give_after_cc_fields', 'give_default_cc_address_fields');
        }
        return $form_id;
    }
    
    
    /**
     * This action will run the function attached to it when it's time to process the donation
     * submission.
     *
     * @param $purchase_data
     */
    public function give_process_rave_purchase($purchase_data) {
        // Make sure we don't have any left over errors present.
        give_clear_errors();
    
        // Any errors?
        $errors = give_get_errors();
    
        if (!$errors) {
            $form_id         = intval($purchase_data['post_data']['give-form-id']);
            $price_id        = !empty($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : 0;
            $donation_amount = !empty($purchase_data['price']) ? $purchase_data['price'] : 0;
    
            $payment_data = array(
                'price' => $donation_amount,
                'give_form_title' => $purchase_data['post_data']['give-form-title'],
                'give_form_id' => $form_id,
                'give_price_id' => $price_id,
                'date' => $purchase_data['date'],
                'user_email' => $purchase_data['user_email'],
                'first_name' => $purchase_data['user_info']['first_name'],
                'last_name' => $purchase_data['user_info']['last_name'],
                'purchase_key' => $purchase_data['purchase_key'],
                'currency' => give_get_currency(),
                'user_info' => $purchase_data['user_info'],
                'status' => 'pending',
                'gateway' => 'rave',
            );
    
            // Record the pending payment
            $payment = give_insert_payment($payment_data);
            
            if(!$payment) {
                // Record the error
                give_record_gateway_error(__('Payment Error', 'rave-give'), sprintf(__('Payment creation failed before sending donor to Flutterwave. Payment data: %s', 'rave-give'), json_encode($payment_data)), $payment);
    
                // Problems? send back
                give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway'] . "&message=-some weird error happened-&payment_id=" . json_encode($payment));
                
            } else {
                //Begin processing payment
                $get_payment_url = $this->get_payment_link( $payment_data );
    
                if ( isset( $get_payment_url->status ) && 'success' === $get_payment_url->status ) {
                    // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
                    wp_redirect( $get_payment_url->data->link );
        
                    exit;
                }
                
                $message = isset($get_payment_url->message) ? ' Reason: '. $get_payment_url->message: '';
    
                give_record_gateway_error(__('Payment Gateway Error', 'rave-give'), sprintf( __('Can&#8217;t connect to the gateway, please try again.%s', 'rave-give'), $message), $payment);
            }
            
            // Problems? send back
            give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway'] . "&message=-Can&#8217;t connect to the gateway-&payment_id=" . json_encode($payment));
        }
    }
    
    
    public function get_payment_link( $payment_data ) {
        if (give_is_test_mode()) {
            $public_key = give_get_option('rave_test_public_key');
            $secret_key = give_get_option('rave_test_secret_key');
        } else {
            $public_key = give_get_option('rave_live_public_key');
            $secret_key = give_get_option('rave_live_secret_key');
        }
    
        $ref = $payment_data['purchase_key']; // . '-' . time() . '-' . preg_replace("/[^0-9a-z_]/i", "_", $purchase_data['user_email']);
        $currency = give_get_currency();
    
        $verify_url = home_url(). '?'. http_build_query(
                [
                    $this->api_query_var => 'verify',
                    'reference' => $ref
                ]
            );
    
        $api_url = 'https://api.flutterwave.com/v3/payments';
    
        $headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $secret_key,
        );
    
        $body = array(
            'tx_ref'        => $ref,
            'amount'        => $payment_data['price'],
            'currency'      => $currency,
            'customer'      => array(
                'name'          => $payment_data['first_name'] . ' ' . $payment_data['last_name'],
                'email'         => $payment_data['user_email'],
            ),
            'customizations'    => array(
                'title'             => get_option('rave_give_custom_title'),
                'description'       => get_option('rave_give_custom_description'),
                'logo'              => get_option('rave_give_custom_title'),
            ),
            'redirect_url'  => $verify_url
        );
    
        $args = array(
            'body'          => wp_json_encode( $body ),
            'headers'       => $headers,
            'timeout'       => 60
        );
    
        $request = wp_remote_post( $api_url, $args );
    
        return json_decode( wp_remote_retrieve_body( $request ) );
    }
    
    /**
     * Register flutterwave supported currencies
     *
     * @param $currencies
     * @return array|array[]
     */
    public function give_rave_add_currencies($currencies) {
        $add_currencies = array(
            'NGN' => array(
                'admin_label' => sprintf(__('Nigerian Naira (%1$s)', 'rave-give'), '&#8358;'),
                'symbol' => '&#8358;',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => ',',
                    'decimal_separator' => '.',
                    'number_decimals' => 2,
                ),
            ),
            'KES' => array(
                'admin_label' => sprintf(__('Kenyan Shilling (%1$s)', 'rave-give'), 'KSh'),
                'symbol' => 'KSh;',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'GHS' => array(
                'admin_label' => sprintf(__('Ghanaian Cedi (%1$s)', 'rave-give'), '&#x20b5;'),
                'symbol' => '&#x20b5;',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'ZAR' => array(
                'admin_label' => sprintf(__('South African Rand (%1$s)', 'rave-give'), '&#82;'),
                'symbol' => '&#82;',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'UGX' => array(
                'admin_label' => sprintf(__('Ugandan Shilling (%1$s)', 'rave-give'), 'UGX'),
                'symbol' => 'UGX',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'RWF' => array(
                'admin_label' => sprintf(__('Rwandan Franc (%1$s)', 'rave-give'), 'Fr'),
                'symbol' => 'Fr',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'TZS' => array(
                'admin_label' => sprintf(__('Tanzanian Shilling (%1$s)', 'rave-give'), 'Sh'),
                'symbol' => 'Sh',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'SLL' => array(
                'admin_label' => sprintf(__('Sierra Leonean Leone (%1$s)', 'rave-give'), 'Le'),
                'symbol' => 'Le',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'XAF' => array(
                'admin_label' => sprintf(__('Central African CFA franc (%1$s)', 'rave-give'), 'CFA'),
                'symbol' => 'CFA',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
            'ZMW' => array(
                'admin_label' => sprintf(__('Zambian Kwacha (%1$s)', 'rave-give'), 'ZK'),
                'symbol' => 'ZK',
                'setting' => array(
                    'currency_position' => 'before',
                    'thousands_separator' => '.',
                    'decimal_separator' => ',',
                    'number_decimals' => 2,
                ),
            ),
        );
        return array_merge($add_currencies, $currencies);
    }
}

new namespace\Frontend();
