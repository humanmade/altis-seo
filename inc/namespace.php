<?php
/**
 * SEO module functions.
 *
 * @package hm-platform/seo
 */

namespace HM\Platform\SEO;

use const HM\Platform\ROOT_DIR;
use function HM\Platform\get_config;
use HM\Platform\Module;

function bootstrap( Module $module ) {
	$settings = $module->get_settings();

	if ( $settings['redirects'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_redirects' );
	}

	if ( $settings['sitemaps'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_sitemaps' );
	}

	if ( $settings['metadata'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_metadata' );
	}

	if ( $settings['amp'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_amp' );
	}

	if ( $settings['fbia'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_instant_articles' );
	}
}

function get_bool_callback( bool $condition ) {
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

	// Enable / disable plugin features.
	add_filter( 'hm.metatags.twitter', get_bool_callback( get_config()['modules']['seo']['metadata']['twitter'] ?? false ) );
	add_filter( 'hm.metatags.opengraph', get_bool_callback( get_config()['modules']['seo']['metadata']['opengraph'] ?? false ) );
	add_filter( 'hm.metatags.json_ld', get_bool_callback( get_config()['modules']['seo']['metadata']['json_ld'] ?? false ) );

	// Set plugin values from config.
	add_filter( 'hm.metatags.fallback_image', get_config()['modules']['seo']['metadata']['fallback_image'] ?? [] );
	add_filter( 'hm.metatags.social_urls', get_config()['modules']['seo']['metadata']['social_urls'] ?? [] );
}

function load_amp() {
	require_once ROOT_DIR . '/vendor/humanmade/amp/amp.php';
}

function load_instant_articles() {
	require_once ROOT_DIR . '/vendor/humanmade/wp-native-articles/wp-native-articles.php';
}
