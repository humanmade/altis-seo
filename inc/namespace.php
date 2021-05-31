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
		add_filter( 'wp_sitemaps_enabled', '__return_true' );
		add_filter( 'wp_sitemaps_add_provider', __NAMESPACE__ . '\\configure_sitemaps', 10, 2 );
	} else {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
	}

	if ( $settings['metadata'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_metadata', 0 );
	}

	if ( $settings['site-verification'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\Site_Verification\\bootstrap' );
	}

	if ( Altis\get_config()['modules']['media']['tachyon'] ?? false ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\use_tachyon_img_in_metadata' );
	}

	// Load Yoast SEO late in case WP SEO Premium is installed as a plugin or mu-plugin.
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_wpseo', 1 );

	// Read config/robots.txt file into robots.txt route handled by WP.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 10 );

	// CSS overrides.
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_yoast_css_overrides', 11 );
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
 * Load Yoast SEO.
 */
function load_wpseo() {
	$wpseo_file = Altis\ROOT_DIR . '/vendor/yoast/wordpress-seo/wp-seo.php';

	// Define a fake WP SEO Premium File value if we don't have WP SEO Premium installed. This hides some of the upsell UI.
	if ( ! class_exists( 'WPSEO_Premium' ) ) {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			define( 'WPSEO_PREMIUM_FILE', $wpseo_file );
			define( 'WPSEO_PREMIUM_VERSION', 8 );
		}
		require_once $wpseo_file;
	}
}

/**
 * Load the SEO metadata plugin.
 *
 * @return void
 */
function load_metadata() {
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
 * Filter sitemap providers.
 *
 * @param WP_Sitemaps_Provider $provider The sitemap provider class.
 * @param string $name The name of the provider.
 * @return WP_Sitemaps_Provider|false
 */
function configure_sitemaps( $provider, string $name ) {
	$settings = Altis\get_config()['modules']['seo']['xml-sitemaps'];

	if ( ! is_array( $settings ) ) {
		return $provider;
	}

	if ( $name === 'users' && ( $settings['users'] ?? true ) === false ) {
		return false;
	}

	if ( $name === 'taxonomies' && ( $settings['taxonomies'] ?? true ) === false ) {
		return false;
	}

	if ( $name === 'posts' && ( $settings['posts'] ?? true ) === false ) {
		return false;
	}

	return $provider;
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

/**
 * Enqueue CSS.
 */
function enqueue_yoast_css_overrides() {
	$version = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? Altis\get_version() . '?' . time( 'Ymd' ) : Altis\get_version();

	wp_enqueue_style( 'altis-seo', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/global-styles.css', [], $version );
}
