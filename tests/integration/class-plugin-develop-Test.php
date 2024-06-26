<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\API\API;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\BH_WP_Autologin_URLs;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh-wp-autologin-urls', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh-wp-autologin-urls'] );
	}


	/**
	 * Enable the public functions as early as possible so they're
	 * available for other plugins.
	 */
	public function test_action_plugins_loaded_add_public_functions() {

		self::markTestSkipped( 'This is now added with an inline function which includes functions.php' );

		$action_name       = 'plugins_loaded';
		$expected_priority = 2;

		$function = 'define_add_autologin_to_url_function';

		$actual_action_priority = has_action( $action_name, $function );

		$this->assertNotFalse( $actual_action_priority );

		$this->assertEquals( $expected_priority, $actual_action_priority );
	}
}
