<?php
/**
 * Altis SEO Google Site Verification.
 *
 * @package altis/seo
 */

namespace Altis\SEO\Site_Verification;

use Altis\Documentation;

const OPTION_NAME = 'altis_google_site_verification';

/**
 * Bootstrap.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register' );
	add_action( 'admin_init', __NAMESPACE__ . '\\register_ui' );
	add_action( 'wp_head', __NAMESPACE__ . '\\output_meta_tag' );
}

/**
 * Register the site verification setting.
 */
function register() {
	register_setting( 'reading', OPTION_NAME, [
		'type' => 'string',
		'description' => __( 'Google Search Console verification meta tag', 'altis' ),
		'sanitize_callback' => __NAMESPACE__ . '\\sanitize_field',
		'show_in_rest' => true,
	] );
}

/**
 * Register the field UI for the settings field.
 */
function register_ui() {
	add_settings_field(
		OPTION_NAME,
		__( 'Google Search Console verification meta tag', 'altis' ),
		__NAMESPACE__ . '\\render_field',
		'reading'
	);
}

/**
 * Render the field for the setting.
 */
function render_field() {
	$value = get_option( OPTION_NAME, '' );
	printf(
		'<input name="%s" type="text" value="%s" class="large-text code" />',
		esc_attr( OPTION_NAME ),
		esc_attr( $value )
	);
	echo '<p class="description">';
	printf(
		wp_kses(
			// translators: %s is replaced by the documentatin URL for google site verification.
			__( 'Enter the full meta tag from the "HTML tag" verification method. See <a href="%s">the documentation</a> for more information.', 'altis' ),
			[ 'a' => [ 'href' => [] ] ]
		),
		esc_attr( Documentation\get_url_for_page( 'seo', 'google-site-verification.md' ) )
	);
	echo '</p>';
}

/**
 * Sanitize the verification setting value.
 *
 * @param string $value Meta tag from the Google Search Console.
 * @return string|null Sanitized meta tag.
 */
function sanitize_field( $value ) {
	$did_match = preg_match( '/<meta name="google-site-verification" content="([^"]+)" \/>/', $value );
	if ( ! $did_match ) {
		return null;
	}

	return $value;
}

/**
 * Get the meta tag value, if set.
 *
 * @return string|null Meta tag HTML if set, or null otherwise.
 */
function get_meta_tag() {
	return get_option( OPTION_NAME, null );
}

/**
 * Output the meta tag value, if set.
 *
 * @return void Outputs the meta tag directly to the page.
 */
function output_meta_tag() {
	$tag = get_meta_tag();
	if ( $tag ) {
		echo wp_kses( $tag . "\n", [
			'meta' => [
				'name' => [],
				'content' => [],
			],
		] );
	}
}
