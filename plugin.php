<?php
/**
 * Plugin Name: Woo Redact
 * Plugin URI: https://github.com/aantonop/woo-redact/
 * Description: Provides precise control over what order data & customer data is removed when WooCommerce removes personal data
 * Author: Ashfame
 * Author URI: https://ashfame.com/
 * Version: 0.1
 * License: GPL
 */

// die if called directly
defined( 'ABSPATH' ) || die();

class Woo_PII_Redact {

	// This list is essentially a mirror of what personal data WooCommerce removes from an order upon an anonymizing request
	// woocommerce/includes/class-wc-privacy-erasers.php
	private $woocommerce_privacy_list_order_personal_data_props = array(
		'customer_ip_address',
		'customer_user_agent',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_postcode',
		'billing_state',
		'billing_country',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_city',
		'shipping_postcode',
		'shipping_state',
		'shipping_country',
		'customer_id',
		'transaction_id',
	);
	private $woocommerce_privacy_list_order_personal_data_meta = array(
		'Payer first name',
		'Payer last name',
		'Payer PayPal address',
		'Transaction ID',
	);
	// This list is essentially a mirror of what personal data WooCommerce removes from a customer account upon an anonymizing request
	// woocommerce/includes/class-wc-privacy-erasers.php
	private $woocommerce_privacy_list_customer_personal_data_props = array(
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_postcode',
		'billing_state',
		'billing_country',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_city',
		'shipping_postcode',
		'shipping_state',
		'shipping_country',
	);

	public function __construct() {
		// Add settings
		add_action( 'woocommerce_account_settings', array( $this, 'add_settings' ) );

		// Precise control over what data gets removed based on configured options
		add_filter( 'woocommerce_privacy_remove_order_personal_data_props', array(
			$this,
			'control_remove_order_personal_data_props'
		) );
		add_filter( 'woocommerce_privacy_remove_order_personal_data_meta', array(
			$this,
			'control_remove_order_personal_data_meta'
		) );
		add_filter( 'woocommerce_privacy_erase_customer_personal_data_props', array(
			$this,
			'control_remove_customer_personal_data_props'
		) );

		// Clear customer's saved addresses routinely (default: daily)
		add_action( 'woocommerce_cleanup_personal_data', array( $this, 'clear_all_customers_saved_addresses' ) );
	}

	public function infer_name( $field ) {
		return str_replace( array( 'Ip', 'Id' ), array( 'IP', 'ID' ), ucwords( str_replace( '_', ' ', $field ) ) );
	}

	public function generate_field_name( $name ) {
		return strtolower( str_replace( ' ', '_', $name ) );
	}

	public function add_settings( $settings ) {
		// Add option to toggle clearing of customer's addresses when daily cron runs
		$toggle_customer_addresses_wipe_setting = array(
			'desc'          => esc_html__( 'Remove saved address under customers\' account daily', 'woocommerce' ),
			'id'            => 'woocommerce_pii_enable_cron_remove_saved_addresses',
			// this is what by which it will be saved in options table
			'desc_tip'      => sprintf( esc_html__(
				'When enabled saved addresses under customers\' account are removed daily. Currently %d customer(s) have addresses saved under their account.',
				'woocommerce'
			), $this->get_count_customers_with_saved_addresses() ),
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => 'end',
			'autoload'      => false,
		);

		// We want to inject it after "woocommerce_allow_bulk_remove_personal_data" setting so lets find the index for it first
		foreach ( $settings as $index => $setting ) {
			if ( $setting[ 'id' ] == 'woocommerce_allow_bulk_remove_personal_data' ) {
				break;
			}
		}

		// insert new setting after it
		array_splice( $settings, $index + 1, 0, array( $toggle_customer_addresses_wipe_setting ) );

		// Add precise control fields at the end of the current settngs screen
		$precise_control_settings = array(
			array(
				'title' => __( 'Precise Control for Data Removal', 'woocommerce' ),
				'desc'  => __( 'Choose what fields are removed while removing personal data', 'woocommerce' ),
				'type'  => 'title',
				'id'    => 'data_removal_precise_control',
			)
		);

		foreach ( $this->woocommerce_privacy_list_order_personal_data_props as $key => $prop ) {
			$setting = array(
				'desc'          => esc_html__( $this->infer_name( $prop ), 'woocommerce' ),
				'id'            => 'woocommerce_pii_remove_order_prop_' . $prop,
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => false,
			);
			if ( $key === 0 ) {
				$setting[ 'title' ]         = __( 'Order Data', 'woocommerce' );
				$setting[ 'checkboxgroup' ] = 'start';
			}

			$precise_control_settings[] = $setting;
		};

		foreach ( $this->woocommerce_privacy_list_order_personal_data_meta as $key => $meta ) {
			$setting            = array(
				'desc'          => esc_html__( $meta, 'woocommerce' ),
				'id'            => 'woocommerce_pii_remove_order_meta_' . $this->generate_field_name( $meta ),
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => false,
			);
			$setting[ 'title' ] = __( 'Order Data [Payment Meta]', 'woocommerce' );
			if ( $key === 0 ) {
				$setting[ 'checkboxgroup' ] = 'start';
			}

			$precise_control_settings[] = $setting;
		};

		foreach ( $this->woocommerce_privacy_list_customer_personal_data_props as $key => $prop ) {
			$setting = array(
				'desc'          => __( $this->infer_name( $prop ), 'woocommerce' ),
				'id'            => 'woocommerce_pii_remove_customer_prop_' . $prop,
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => false,
			);
			if ( $key === 0 ) {
				if ( "yes" === get_option( 'woocommerce_pii_enable_cron_remove_saved_addresses' ) ) {
					$setting[ 'title' ] = __( 'Customer Account Data', 'woocommerce' );
				} else {
					$setting[ 'title' ] = __( 'Customer Account Data [Currently not enabled]', 'woocommerce' );
				}
				$setting[ 'checkboxgroup' ] = 'start';
			}

			$precise_control_settings[] = $setting;
		};

		$settings = array_merge( $settings, $precise_control_settings );

		return $settings;
	}

	public function control_remove_order_personal_data_props( $props_list ) {
		foreach ( $props_list as $prop => $datatype ) {
			// exclude from here to prevent its purge
			if ( get_option( 'woocommerce_pii_remove_order_prop_' . $prop ) == "no" ) {
				unset( $props_list[ $prop ] );
			}
		}

		return $props_list;
	}

	public function control_remove_order_personal_data_meta( $meta_list ) {
		foreach ( $meta_list as $meta => $datatype ) {
			// exclude from here to prevent its purge
			if ( get_option( 'woocommerce_pii_remove_order_meta_' . $this->generate_field_name( $meta ) ) == "no" ) {
				unset( $meta_list[ $meta ] );
			}
		}

		return $meta_list;
	}

	public function control_remove_customer_personal_data_props( $props_list ) {
		foreach ( array_keys( $props_list ) as $prop ) {
			// exclude from here to prevent its purge
			if ( get_option( 'woocommerce_pii_remove_customer_prop_' . $prop ) == "no" ) {
				unset( $props_list[ $prop ] );
			}
		}

		return $props_list;
	}

	public function get_customers_with_saved_addresses() {
		global $wpdb;

		return array_map( 'intval', $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE ( meta_key like 'billing_%' OR meta_key like 'shipping_%' ) AND meta_value <> '';"
		) );
	}

	public function get_count_customers_with_saved_addresses() {
		return count( $this->get_customers_with_saved_addresses() );
	}

	public function clear_all_customers_saved_addresses() {
		$user_ids = $this->get_customers_with_saved_addresses();

		foreach ( $user_ids as $user_id ) {
			$user = get_user_by( 'ID', $user_id );
			WC_Privacy_Erasers::customer_data_eraser( $user->user_email, 1 );
		}
	}
}

new Woo_PII_Redact();
