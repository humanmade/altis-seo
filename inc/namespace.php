<?php
/**
 * SEO module functions.
 *
 * @package altis/seo
 */

namespace Altis\SEO;

use const Altis\ROOT_DIR;
use function Altis\get_config;
use Altis\Module;

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

	// Read config/robots.txt file into robots.txt route handled by WP.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 10 );
}

function get_bool_callback( bool $condition ) : callable {
	return $condition ? '__return_true' : '__return_false';
}

function load_redirects() {
	require_once ROOT_DIR . '/vendor/humanmade/hm-redirects/hm-redirects.php';
}

function load_sitemaps() {
	require_once ROOT_DIR . '/vendor/humanmade/msm-sitemap/msm-sitemap.php';
}

function load_metadata() {
	require_once ROOT_DIR . '/vendor/humanmade/wp-seo/wp-seo.php';
	require_once ROOT_DIR . '/vendor/humanmade/meta-tags/plugin.php';

	$config = get_config()['modules']['seo']['metadata'] ?? [];

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

function load_amp() {
	require_once ROOT_DIR . '/vendor/humanmade/amp/amp.php';
}

function load_instant_articles() {
	require_once ROOT_DIR . '/vendor/humanmade/facebook-instant-articles-wp/facebook-instant-articles.php';
}

/**
 * Add robots.txt content if file is present.
 *
 * @param string $output
 * @return string
 */
function robots_txt( string $output ) : string {
	if ( file_exists( ROOT_DIR . '/config/robots.txt' ) ) {
		$output .= "\n" . file_get_contents( ROOT_DIR . '/config/robots.txt' ) . "\n";
	}

	return $output;
}
