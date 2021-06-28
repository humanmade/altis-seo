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

	if ( $settings['metadata'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_metadata', 0 );
	}

	if ( $settings['site-verification'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\Site_Verification\\bootstrap' );
	}

	// Load Yoast SEO late in case WP SEO Premium is installed as a plugin or mu-plugin.
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_wpseo', 1 );

	// Remove Yoast SEO dashboard widget.
	add_action( 'admin_init', __NAMESPACE__ . '\\remove_yoast_dashboard_widget' );

	// Remove the Yoast Premium submenu page.
	add_action( 'admin_init', __NAMESPACE__ . '\\remove_yoast_submenu_page' );

	// Remove Helpscout.
	add_filter( 'wpseo_helpscout_show_beacon', '__return_false' );

	// Hide the HUGE SEO ISSUE warning and disable admin bar menu.
	add_filter( 'pre_option_wpseo', __NAMESPACE__ . '\\override_yoast_seo_options' );

	// Read config/robots.txt file into robots.txt route handled by WP.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 10 );

	// Add sitemap to robots.txt.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\add_sitemap_index_to_robots', 11, 2 );

	// CSS overrides.
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_yoast_css_overrides', 11 );
	add_action( 'wpseo_configuration_wizard_head', __NAMESPACE__ . '\\override_wizard_styles' );
	add_action( 'admin_head', __NAMESPACE__ . '\\hide_yoast_premium_social_previews' );
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
 * Checks if Yoast SEO Premium is installed.
 *
 * @return bool
 */
function is_yoast_premium() : bool {
	return class_exists( 'WPSEO_Premium' );
}

/**
 * Load Yoast SEO.
 */
function load_wpseo() {
	if ( is_yoast_premium() ) {
		return;
	}

	require_once Altis\ROOT_DIR . '/vendor/yoast/wordpress-seo/wp-seo.php';
}

/**
 * Remove the Yoast SEO dashboard widget.
 */
function remove_yoast_dashboard_widget() {
	remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );

	// This script & style are enqueued by Yoast.
	wp_dequeue_script( 'dashboard-widget' );
	wp_dequeue_style( 'wp-dashboard' );
}

/**
 * Remove the Premium submenu.
 */
function remove_yoast_submenu_page() {
	remove_submenu_page( 'wpseo_dashboard', 'wpseo_licenses' );
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
 * Override the Yoast SEO options.
 *
 * Disables the Search Engines Discouraged warning on non-production environments and the admin bar menu.
 *
 * @param mixed $options The option to retrieve.
 *
 * @return array The updated WPSEO options.
 */
function override_yoast_seo_options( $options ) : ?array {
	$options['enable_admin_bar_menu'] = false;

	if ( Altis\get_environment_type() === 'production' ) {
		return $options;
	}

	$options['ignore_search_engines_discouraged_notice'] = true;

	return $options;
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
 * Add the Yoast SEO sitemap index to the robots.txt file.
 *
 * @param string $output The original robots.txt content.
 * @param bool $public Whether the site is public.
 *
 * @return string The filtered robots.txt content.
 */
function add_sitemap_index_to_robots( string $output, bool $public ) : string {
	if ( $public ) {
		$output .= sprintf( "Sitemap: %s\n", site_url( '/sitemap_index.xml' ) );
	}

	return $output;
}

/**
 * Enqueue CSS.
 */
function enqueue_yoast_css_overrides() {
	wp_enqueue_style( 'altis-seo', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/altis-seo.css', [], '2021-06-04-5' );
}

/**
 * Override the Yoast wizard styles.
 *
 * The Yoast setup wizard bails early, before our styles are loaded, but we can
 * hook into their action to load in our style overrides.
 */
function override_wizard_styles() {
	wp_register_style( 'altis-seo', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/global-styles.css', [], '2021-06-04-5' );
	wp_print_styles( 'altis-seo' );
}

/**
 * Hide the social previews if Yoast Premium is not active.
 */
function hide_yoast_premium_social_previews() {
	$screen = get_current_screen();

	// Bail early if Yoast Premium is active or if we aren't on a post edit screen.
	if ( is_yoast_premium() || $screen->base !== 'post' ) {
		return;
	}

	/**
	 * This targets the 6th and 7th components panel in the Yoast
	 * sidebar, which corresponds to the Facebook and Twitter social
	 * preview buttons. If Yoast ever adds more panels to this sidebar,
	 * this will need to be updated.
	 */
	$styles = 'div.components-panel div:nth-child(6n) div.yoast.components-panel__body, div.components-panel div:nth-child(7n) div.yoast.components-panel__body {
		display: none;
	}';

	/**
	 * Hide the Social tab in the Yoast Metabox.
	 *
	 * The Google preview is in the basic SEO tab and social previews
	 * are only available for Yoast SEO Premium.
	 */
	$styles .= '.wpseo-metabox-menu .yoast-aria-tabs li:last-of-type {
		display:none;
	}';

	echo "<style>$styles</style>"; // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
}
