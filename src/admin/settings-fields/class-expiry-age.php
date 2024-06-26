<?php
/**
 * This settings field is a text field to configure the number of seconds the autologin codes should be valid for.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package BH_WP_Autologin_URLs\admin\partials
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * Class Expiry_Age
 *
 * @package BH_WP_Autologin_URLs\admin\partials
 */
class Expiry_Age extends Settings_Section_Element_Abstract {

	/**
	 * Expiry_Age constructor.
	 *
	 * @param string             $settings_page_slug_name The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( $settings_page_slug_name, $settings ) {

		parent::__construct( $settings_page_slug_name );

		$this->value = $settings->get_expiry_age();

		$this->id    = 'bh_wp_autologin_urls_seconds_until_expiry';
		$this->title = 'Expiry age:';
		$this->page  = $settings_page_slug_name;

		$this->add_settings_field_args['helper']       = 'Number of seconds until each code expires.';
		$this->add_settings_field_args['supplemental'] = 'default: 604800 (one week)';
		$this->add_settings_field_args['default']      = 604800;
		$this->add_settings_field_args['placeholder']  = '';

		$this->register_setting_args['default'] = 604800;
	}

	/**
	 * The function used by WordPress Settings API to output the field.
	 *
	 * @param array{placeholder:string, helper:string, supplemental:string} $arguments Settings passed from WordPress do_settings_fields() function.
	 */
	public function print_field_callback( $arguments ): void {

		$value = $this->value;

		printf( '<input name="%1$s" id="%1$s" type="text" placeholder="%2$s" value="%3$s" />', esc_attr( $this->id ), esc_attr( $arguments['placeholder'] ), esc_attr( $value ) );

		printf( '<span class="helper">%s</span>', esc_html( $arguments['helper'] ) );

		printf( '<p class="description">%s</p>', esc_html( $arguments['supplemental'] ) );
	}

	/**
	 * Expiry age should always be a number. Accepts arithmetic, e.g. 60*60*24.
	 *
	 * @param string $value The value POSTed by the Settings API.
	 *
	 * @return int
	 */
	public function sanitize_callback( $value ) {

		if ( empty( $value ) ) {

			add_settings_error( $this->id, 'expiry-age-empty-error', 'Expiry age was empty. Previous value ' . $this->value . ' was saved.', 'error' );

			return $this->value;
		}

		// Remove anything that is not a digit or "*".
		$value = (string) preg_replace( '/[^0-9*]/', '', $value );

		if ( ! stristr( $value, '*' ) ) {
			return intval( abs( $value ) );
		}

		$value = explode( '*', $value );

		$result = array_product( $value );

		return intval( abs( $result ) );
	}
}
