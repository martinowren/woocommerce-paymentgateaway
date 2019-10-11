<?php
/*
 * Plugin Name: RM - Payment gateaway
 * Plugin URI: 
 * Description: Lager payment gateaways for Faktura og legger til kostnader
 * Author: Martin Owren
 * Author URI: https://martinowren.com
 * Version: 1.0.0
 *
 * 

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'rm_add_gateway_class' );
function rm_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_rm_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'rm_init_gateway_class' );
function rm_init_gateway_class() {
 
	class WC_rm_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
 
            $this->id = 'faktura-rm'; // payment gateway plugin ID
            $this->icon = 'https://cdn4.iconfinder.com/data/icons/lucid-files-and-folders/24/invoice_document_file_bill_business_invoice_document_-512.png'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Faktura 10 Dager - 60Kr';
            $this->method_description = 'Faktura 10 Dager - 60Kr'; // will be displayed on the options page
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->order_status = $this->get_option( 'order_status' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            

            // Customer Emails
            // add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 )

            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 
			$this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable RM Faktura Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Faktura 10 Dager - 60Kr',
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'description' => __( 'Choose whether order status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'class'       => 'wc-enhanced-select',
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Få faktura på Vipps/Nettbank med 10 dagers forfall',
                )
            );
        }
 
	 	
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.';
                    $this->description  = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ) );
            }
            
            // echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-faktura-form wc-payment-form" style="background:transparent;">';
            // //do_action( 'woocommerce_credit_card_form_start', $this->id );

            // echo '<div class="form-row form-row-wide"><label></label>
            // <input style="
            // min-width: 50%;
            // line-height: 30px;
            // font-size: 16px;
            // text-align: center;
            // border-radius: 8px;
            // border: 1px solid rgb(217, 217, 217);" 
            // id="personNR_RM" type="number" autocomplete="off" placeholder="Ditt Personnummer - 11 Siffer*" min="099999999" max="40000000000"></div>';

            // //do_action( 'woocommerce_credit_card_form_end', $this->id );
            // echo '</fieldset>';
            echo '<style>#personNR_RM {min-width: 50%;
                line-height: 30px;
                font-size: 16px;
                text-align: center;
                border-radius: 8px;
                border: 1px solid rgb(217, 217, 217);}</style>';
            woocommerce_form_field( 'personnummer', array(
                'type'          => 'number',
                'id'            => 'personNR_RM',
                'placeholder'         => __('Ditt Personnummer - 11 Siffer*'),
                'options'       => $this->options,
                'required'      => true,
                'custom_attributes' => array(
                    'min'       =>  10,
                    'max'       =>  40000000000,
                ),
            ), reset( $option_keys ) );
 
        }

        public function save_order_payment_type_meta_data( $order, $data ) {
            if ( $data['payment_method'] === $this->id && isset($_POST['personnummer']) )
                $order->update_meta_data('_personnummer', esc_attr($_POST['personnummer']) );
        }
        
        public function display_payment_type_order_edit_pages( $order ){
            if( $this->id === $order->get_payment_method() && $order->get_meta('_personnummer') ) {
                $options  = $this->options;
                echo '<p><strong>'.__('Transaction type').':</strong> ' . $options[$order->get_meta('_personnummer')] . '</p>';
            }
        }
  
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
		    if( empty( $_POST[ 'personnummer' ]) ) {
                wc_add_notice(  'Du må legge inn personnummeret ditt!', 'error' );
                return false;
            }
            return true;
 
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {

            global $woocommerce;
            $order = wc_get_order( $order_id );
            $order->update_meta_data('personnummer', esc_attr($_POST['personnummer']) );
            $order->payment_complete();
            $woocommerce->cart->empty_cart();
            return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);

 
	 	}
 
 	}
} 

add_action( 'woocommerce_cart_calculate_fees', 'add_fee_to_faktura', 10 );
  
function add_fee_to_faktura() {
    $chosen_gateway = WC()->session->get( 'chosen_payment_method' );
    if ( $chosen_gateway == 'faktura-rm' ) {
        WC()->cart->add_fee( 'Avgift for Faktura', 48, true );
    } 
    if ( $chosen_gateway == 'stripe' ) {
        $feeforkort = ( WC()->cart->cart_contents_total + WC()->cart->shipping_total ) * 0.02;
        if( $feeforkort > 150 ) {
            $feeforkort = 120;
        } elseif ( $feeforkort < 25 ) {
            $feeforkort = 20;
        }
//         if( $feeforkort > 48) {
//             wc_add_notice(  'Tips: Faktura er billigere', 'notice' );
//         }
        WC()->cart->add_fee( 'Avgift for kortbetaling', $feeforkort, true );
    }
    


}
add_action( 'cfw_before_footer', 'add_fee_to_fakturaREFRESH' );
  
function add_fee_to_fakturaREFRESH(){
    ?>
    <script type="text/javascript">
        (function($){
            $( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
                $('body').trigger('update_checkout');
            });
        })(jQuery);
    </script>
    <?php
}

/* Endring i CheckoutWC */

/**
 * Add the field to the checkout page
 */
add_action( 'cfw_checkout_after_customer_info_address', 'customise_checkout_field' );
add_filter('woocommerce_enable_order_notes_field', '__return_true');
function customise_checkout_field() {
	echo '<div id="gruppenavn_field">';
	cfw_form_field(
		'gruppenavn', array(
		'type'              => 'text',
		'label'             => 'Gruppenavn',
		'placeholder'       => 'Gruppenavn inkludert år - Eks: Domen 2019',
		'required'          => true,
		'class'             => array(),
		'autofocus'         => true,
		'input_class'       => array( 'garlic-auto-save' ),
		'priority'          => 10,
		'wrap'              => Objectiv\Plugins\Checkout\Main::instance()->get_form()->input_wrap( 'text', 12, 10 ),
		'label_class'       => 'cfw-input-label',
		'start'             => true,
		'end'               => true,
		'custom_attributes' => array(
			'data-parsley-trigger' => 'change focusout',
		),
	), WC()->checkout->get_value( 'customised_field_name' )
	);
	echo '</div>';
}
/**
 * Update value of field
 */
add_action( 'woocommerce_checkout_update_order_meta', 'customise_checkout_field_update_order_meta' );
function customise_checkout_field_update_order_meta( $order_id ) {
	if ( ! empty( $_POST['gruppenavn'] ) ) {
		update_post_meta( $order_id, 'gruppenavn', sanitize_text_field( $_POST['gruppenavn'] ) );
	}
}


add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

function custom_override_checkout_fields( $fields ) {
    unset($fields['billing']['billing_company']);
    unset($fields['shipping']['shipping_company']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['shipping']['shipping_address_2']);

    return $fields;
}