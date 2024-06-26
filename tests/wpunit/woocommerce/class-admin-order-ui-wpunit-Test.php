<?php

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WooCommerce\Admin_Order_UI
 */
class Admin_Order_UI_WPUnit_Test extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * @covers ::add_to_payment_url
	 * @covers ::__construct
	 */
	public function test_add_to_payment_url(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'add_autologin_to_url' => Expected::once(
					function ( string $payment_url, $user ) {
						assert( 'test@example.org' === $user );
						return 'added';
					}
				),
			)
		);

		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Admin_Order_UI( $api, $settings );

		$order = new \WC_Order();
		$order->set_billing_email( 'test@example.org' );
		$order->save();

		$payment_url = site_url() . '/checkout/';

		// Set `is_admin()` to true.
		$GLOBALS['current_screen'] = new class() {
			public function in_admin(): bool {
				return true;
			}
		};

		$result = $sut->add_to_payment_url( $payment_url, $order );

		$this->assertEquals( $result, 'added' );
	}
}
