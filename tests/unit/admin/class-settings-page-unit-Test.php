<?php
/**
 * Tests for Admin.
 *
 * @see Admin
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use Psr\Log\NullLogger;

/**
 * Class Admin_Test
 */
class Settings_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * WordPress's add_options_page should be called with 'Autologin URLs', the plugin name and the correct callback.
	 *
	 * @throws \Exception
	 */
	public function test_add_settings_page(): void {

		$logger        = new NullLogger();
		$settings      = $this->makeEmpty(
			Settings_Interface::class,
			array( 'get_plugin_slug' => 'bh-wp-autologin-urls' )
		);
		$settings_page = new Settings_Page( $settings, $logger );

		\WP_Mock::userFunction(
			'add_options_page',
			array(
				'args'  => array(
					'Autologin URLs',
					'Autologin URLs',
					'manage_options',
					'bh-wp-autologin-urls',
					// Actual value would be `array( $settings_page, 'display_plugin_admin_page' )`.
					\WP_Mock\Functions::type( 'array' ),
				),
				'times' => 1,
			)
		);

		$settings_page->add_settings_page();
	}

	/**
	 * Test the file to be included should exist.
	 */
	public function tests_display_plugin_admin_page_file_exists(): void {

		global $plugin_root_dir;

		// Verify the actual file exists.
		$this->assertFileExists( $plugin_root_dir . '/templates/admin/settings-page.php' );
	}

	/**
	 * Test the function includes the file correctly.
	 */
	public function tests_display_plugin_admin_page_file_included(): void {

		self::markTestIncomplete( 'It keeps appending `\Patchwork\CodeManipulation\Stream::reinstateWrapper()` to the output' );

		// Feed it the wrong folder name to test it includes files properly.

		$logger        = new NullLogger();
		$settings      = $this->makeEmpty( Settings_Interface::class );
		$settings_page = new Settings_Page( $settings, $logger );

		// The method first generates an example URL for the current user.
		\WP_Mock::userFunction(
			'site_url',
			array(
				'times'  => 1,
				'return' => 'http://example.org',
			)
		);

		\WP_Mock::passthruFunction( 'esc_url' );

		\WP_Mock::userFunction(
			'get_current_user_id'
		);

		global $plugin_root_dir;
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			define( 'WP_PLUGIN_DIR', $plugin_root_dir );
		}

		// Then it includes the template file.
		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'times'  => 1,
				'return' => __DIR__ . '/../',
			)
		);

		\WP_Mock::userFunction(
			'get_stylesheet_directory'
		);

		// TODO: Move this to `_data` folder!
		$mock_admin_display = __DIR__ . '/partials/admin-display.php';

		\WP_Mock::onFilter( 'bh_wp_autologin_urls_admin_settings_page_template' )
				->with( 'templates/admin/settings-page.php' )
				->reply( $mock_admin_display );

		ob_start();

		$settings_page->display_plugin_admin_page();

		$printed_output = ob_get_clean();

		$this->assertEquals( 'tests_display_plugin_admin_page', $printed_output );
	}

	/**
	 * WordPress's add_settings_section should be called with the correct name.
	 */
	public function test_setup_sections(): void {

		$logger        = new NullLogger();
		$settings      = $this->makeEmpty(
			Settings_Interface::class,
			array( 'get_plugin_slug' => 'bh-wp-autologin-urls' )
		);
		$settings_page = new Settings_Page( $settings, $logger );

		\WP_Mock::userFunction(
			'add_settings_section',
			array(
				'args'  => array(
					'default',
					'Settings',
					'*',
					'bh-wp-autologin-urls',
				),
				'times' => 1,
			)
		);

		$settings_page->setup_sections();
	}


	/**
	 * Successful execution would mean WordPress's add_settings_field and register_settings
	 * functions being called {$number_of_settings_elements} times each.
	 */
	public function test_setup_fields(): void {

		$logger        = new NullLogger();
		$settings      = $this->makeEmpty( Settings_Interface::class );
		$settings_page = new Settings_Page( $settings, $logger );

		$number_of_settings_elements = 6;

		\WP_Mock::userFunction(
			'add_settings_field',
			array(
				'times' => $number_of_settings_elements,
			)
		);

		\WP_Mock::userFunction(
			'register_setting',
			array(
				'times' => $number_of_settings_elements,
			)
		);

		\WP_Mock::userFunction(
			'wp_login_url',
		);
		\WP_Mock::userFunction(
			'trailingslashit',
		);
		\WP_Mock::userFunction(
			'site_url',
		);

		$settings_page->setup_fields();
	}
}
