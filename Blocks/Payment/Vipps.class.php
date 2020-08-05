<?php
/**
 * Vipps payment gateway implementation for Gutenberg Blocks
 *
 * @package WooCommerce/Blocks
 * @since 3.0.0
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Assets\Api;

final class Vipps extends AbstractPaymentMethodType {
	protected $name = 'vipps';
	private $asset_api;
	public function __construct( Api $asset_api ) {
		$this->asset_api = $asset_api;
	}
	public function initialize() {
		$this->settings = get_option( 'woocommerce_vipps_settings', [] );
	}
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}
	public function get_payment_method_script_handles() {
		$this->asset_api->register_script(
			'wc-payment-method-vipps',
			'js/wc-payment-method-vipps.js'
		);
		return [ 'wc-payment-method-vipps' ];
	}
	public function get_payment_method_data() {
		return [
			'title'                    => $this->get_setting( 'title' ),
			'description'              => $this->get_setting( 'description' )
		];
	}
}