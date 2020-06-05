<?php
/**
 * SEO module functions.
 *
 * @package altis/seo
 */

namespace Altis\SEO;

use Altis;
use Altis\Module;

/**
 * Bootstrap SEO Module.
 *
 * @param Module $module The SEO Module object.
 * @return void
 */
function bootstrap( Module $module ) {
	$settings = $module->get_settings();

	if ( $settings['redirects'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_redirects', 0 );
	}

	if ( $settings['xml-sitemaps'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_sitemaps', 0 );
	}

	if ( $settings['metadata'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_metadata', 0 );
	}

	if ( $settings['amp'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_amp', 0 );
	}

	if ( $settings['facebook-instant-articles'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_instant_articles', 0 );
	}

	if ( $settings['site-verification'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\Site_Verification\\bootstrap' );
	}

	if ( Altis\get_config()['modules']['media']['tachyon'] ?? false ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\use_tachyon_img_in_metadata' );
	}

	// Read config/robots.txt file into robots.txt route handled by WP.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 10 );
}

/**
 * Get a corresponding callable for a boolean value.
 *
 * @param boolean $condition Condition to check.
 * @return callable
 */
function get_bool_callback( bool $condition ) : callable {
	return $condition ? '__return_true' : '__return_false';
}

/**
 * Load the redirects plugin.
 *
 * @return void
 */
function load_redirects() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/hm-redirects/hm-redirects.php';
}

/**
 * Load the sitemaps plugin.
 *
 * @return void
 */
function load_sitemaps() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/msm-sitemap/msm-sitemap.php';
}

/**
 * Load the SEO metadata plugin.
 *
 * @return void
 */
function load_metadata() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/wp-seo/wp-seo.php';
	require_once Altis\ROOT_DIR . '/vendor/humanmade/meta-tags/plugin.php';

	$config = Altis\get_config()['modules']['seo']['metadata'] ?? [];

	// Enable / disable plugin features.
	add_filter( 'hm.metatags.twitter', get_bool_callback( $config['twitter'] ?? true ) );
	add_filter( 'hm.metatags.opengraph', get_bool_callback( $config['opengraph'] ?? true ) );
	add_filter( 'hm.metatags.json_ld', get_bool_callback( $config['json-ld'] ?? true ) );

	// Set plugin values from config.
	add_filter( 'hm.metatags.fallback_image', function () use ( $config ) {
		return $config['fallback-image'] ?? '';
	} );
	add_filter( 'hm.metatags.social_urls', function () use ( $config ) {
		return $config['social-urls'] ?? [];
	} );
}

/**
 * Add filters to use Tachyon image URLs for metadata images.
 * Image size and crop depend on the social media type.
 */
function use_tachyon_img_in_metadata() {
	add_filter( 'hm.metatags.context.default', __NAMESPACE__ . '\\metadata_img_as_tachyon' );
	add_filter( 'hm.metatags.context.twitter', __NAMESPACE__ . '\\metadata_img_as_tachyon_twitter' );
	add_filter( 'hm.metatags.context.opengraph', __NAMESPACE__ . '\\metadata_img_as_tachyon_opengraph' );
}

/**
 * Update twitter metadata to use Tachyon img URL.
 *
 * @param array $meta Twitter metadata.
 *
 * @return array Twitter metadata with image using Tachyon URL, if any.
 */
function metadata_img_as_tachyon_twitter( array $meta ) : array {
	return metadata_img_as_tachyon( $meta, [
		'resize' => '1200,600', // crop.
	] );
}

/**
 * Update opengraph metadata to use Tachyon img URL.
 *
 * @param array $meta opengraph metadata.
 *
 * @return array opengraph metadata with image using Tachyon URL, if any.
 */
function metadata_img_as_tachyon_opengraph( array $meta ) : array {
	return metadata_img_as_tachyon( $meta, [
		'fit' => '1200,627', // no crop.
	] );
}

/**
 * Update metadata image URL to use Tachyon URL with specified image settings.
 *
 * @param array $meta          Metadata per social media type.
 * @param array $img_settings Image settings: size and crop to be used in Tachyon URL.
 *
 * @return array Metadata with updated image URL using Tachyon, if an image is specified.
 */
function metadata_img_as_tachyon( array $meta, array $img_settings = [] ) : array {
	// Stop - no image for metadata.
	if ( ! isset( $meta['image'] ) ) {
		return $meta;
	}

	// Default image settings.
	$img_settings = $img_settings ?: [ 'fit' => '1200,1200' ]; // no crop.

	// Already a Tachyon enabled image URL. Add crop params.
	if ( false !== strpos( $meta['image'], TACHYON_URL ) ) {
		// Remove any Tachyon query args that might already be set.
		$meta['image'] = remove_query_arg( [ 'w', 'h', 'fit', 'resize' ], $meta['image'] );
		$meta['image'] = add_query_arg( $img_settings, $meta['image'] );
	} else {
		// Update image URL to use Tachyon.
		$meta['image'] = tachyon_url( $meta['image'], $img_settings );
	}

	return $meta;
}

/**
 * Load the AMP plugin.
 *
 * @return void
 */
function load_amp() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/amp/amp.php';
}

/**
 * Load the instant articles plugin.
 *
 * @return void
 */
function load_instant_articles() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/facebook-instant-articles-wp/facebook-instant-articles.php';
}

/**
 * Add robots.txt content if file is present.
 *
 * @param string $output robots.txt file content generated by WP.
 *
 * @return string robots.txt file content including custom configuration if any.
 */
function robots_txt( string $output ) : string {
	$robots_file = Altis\ROOT_DIR . '/.config/robots.txt';

	// Legacy file will be in the `/config` dir instead of `/.config`.
	$legacy_file = Altis\ROOT_DIR . '/config/robots.txt';

	if ( file_exists( $robots_file ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$output .= "\n" . file_get_contents( $robots_file ) . "\n";
	} elseif ( file_exists( $legacy_file ) ) {
		// If the legacy-style file exists, load it, but warn.
		trigger_error( 'The "config/robots.txt" file is deprecated as of Altis 2.0. Use ".config/robots.txt" instead.', E_USER_DEPRECATED );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$output .= "\n" . file_get_contents( $legacy_file ) . "\n";
	}

	return $output;
}
