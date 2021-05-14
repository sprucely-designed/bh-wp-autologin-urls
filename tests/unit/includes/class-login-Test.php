<?php
/**
 * Tests for Login. Tests logging in of user with autologin code in URL.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\includes;

use BrianHenryIE\WP_Autologin_URLs\api\API_Interface;


/**
 * Class Login_Test
 */
class Login_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}


	/**
	 * The plugin name. Unlikely to change.
	 *
	 * @var string Plugin name.
	 */
	private $plugin_name = 'bh-wp-autologin-urls';

	/**
	 * The plugin version, matching the version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * Include required files.
	 */
	public function setUp(): void {
		\WP_Mock::setUp();

		global $project_root_dir;

		require_once $project_root_dir . '/vendor/wordpress/wordpress/src/wp-includes/class-wp-user.php';

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
		}
	}

	/**
	 * Basic success test.
	 */
	public function test_wp_init_process_autologin() {

		$api = $this->createMock( API_Interface::class );
		$api->method( 'verify_autologin_password' )->willReturn( true );

		$login = new Login( $this->plugin_name, $this->version, $api );

		$autologin_querystring = '123~testpassword';

		$_GET['autologin'] = $autologin_querystring;

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => $autologin_querystring,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return' => $autologin_querystring,
			)
		);

		// Return a different user id so this login continues.
		\WP_Mock::userFunction(
			'get_current_user_id',
			array(
				'times'  => 1,
				'return' => 321,
			)
		);

		$user = $this->createMock( '\WP_User' );
		$user->method( '__get' )
			->with( 'user_login' )
			->willReturn( 'username' );

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'id', 123 ),
				'times'  => 1,
				'return' => $user,
			)
		);

		\WP_Mock::userFunction(
			'wp_set_current_user',
			array(
				'args'  => array( 123, 'username' ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_set_auth_cookie',
			array(
				'args'  => array( 123 ),
				'times' => 1,
			)
		);

		\WP_Mock::expectAction( 'wp_login', 'username', $user );

		$success = $login->wp_init_process_autologin();

		$this->assertTrue( $success );
	}

	/**
	 * Basic fail test. If the querystring is absent it should return immediately.
	 */
	public function test_querystring_absent() {

		$api = null;

		$login = new Login( $this->plugin_name, $this->version, $api );

		$success = $login->wp_init_process_autologin();

		$this->assertFalse( $success );
	}

	/**
	 * Fail when the user id is non-numeric.
	 */
	public function test_nonnumeric_userid() {

		$api = null;

		$login = new Login( $this->plugin_name, $this->version, $api );

		$autologin_querystring = 'brian~testpassword';

		$_GET['autologin'] = $autologin_querystring;

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => $autologin_querystring,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return' => $autologin_querystring,
			)
		);

		$success = $login->wp_init_process_autologin();

		$this->assertFalse( $success );
	}

	/**
	 * Fail when the password is non-alphanumeric,
	 * suggesting it wasn't generated by us.
	 */
	public function test_nonalphanumeric_password() {

		$api = null;

		$login = new Login( $this->plugin_name, $this->version, $api );

		$autologin_querystring = '123~testp@ssword';

		$_GET['autologin'] = $autologin_querystring;

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => $autologin_querystring,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return' => $autologin_querystring,
			)
		);

		$success = $login->wp_init_process_autologin();

		$this->assertFalse( $success );
	}

	/**
	 * Fail when the user is already logged in.
	 */
	public function test_user_already_loggedin() {

		$api = null;

		$login = new Login( $this->plugin_name, $this->version, $api );

		$autologin_querystring = '123~testpassword';

		$_GET['autologin'] = $autologin_querystring;

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => $autologin_querystring,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return' => $autologin_querystring,
			)
		);

		// Return the same user id as through they're already logged in.
		\WP_Mock::userFunction(
			'get_current_user_id',
			array(
				'times'  => 1,
				'return' => 123,
			)
		);

		$success = $login->wp_init_process_autologin();

		$this->assertFalse( $success );
	}

	/**
	 * An unexpected odd situation where verify_autologin_password() passes but get_user_by()
	 * fails to return a WP_User. Could happen after a user account is deleted. No way to
	 * clear the wpdb of transients for a particular user, since that information is hashed.
	 */
	public function test_transient_success_but_no_user() {

		$api = $this->createMock( API_Interface::class );
		$api->method( 'verify_autologin_password' )->willReturn( true );

		$login = new Login( $this->plugin_name, $this->version, $api );

		$autologin_querystring = '123~testpassword';

		$_GET['autologin'] = $autologin_querystring;

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => $autologin_querystring,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return' => $autologin_querystring,
			)
		);

		// Return the same user id as through they're already logged in.
		\WP_Mock::userFunction(
			'get_current_user_id',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'id', 123 ),
				'times'  => 1,
				'return' => false,
			)
		);

		$success = $login->wp_init_process_autologin();

		$this->assertFalse( $success );

	}

}
