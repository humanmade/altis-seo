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

function load_redirects() {
	require_once ROOT_DIR . '/vendor/humanmade/hm-redirects/hm-redirects.php';
}

function load_sitemaps() {
	require_once ROOT_DIR . '/vendor/humanmade/msm-sitemap/msm-sitemap.php';
}

function load_metadata() {
	require_once ROOT_DIR . '/vendor/humanmade/wp-seo/wp-seo.php';
	require_once __DIR__ . '/metadata/namespace.php';

	if ( get_config()['modules']['seo']['metadata']['twitter'] ?? false ) {
		require_once __DIR__ . '/metadata/twitter-namespace.php';
		Metadata\Twitter\bootstrap();
	}

	if ( get_config()['modules']['seo']['metadata']['opengraph'] ?? false ) {
		require_once __DIR__ . '/metadata/opengraph-namespace.php';
		Metadata\Opengraph\bootstrap();
	}

	if ( get_config()['modules']['seo']['metadata']['json-ld'] ?? false ) {
		require_once __DIR__ . '/metadata/json-ld-namespace.php';
		Metadata\JSONLD\bootstrap();
	}
}

function load_amp() {
	require_once ROOT_DIR . '/vendor/humanmade/amp/amp.php';
}

function load_instant_articles() {
	require_once ROOT_DIR . '/vendor/humanmade/wp-native-articles/wp-native-articles.php';
}
