<?php
/**
 * SEO Module
 *
 * @package altis/seo
 */

namespace Altis\SEO; // phpcs:ignore

use Altis;

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => true,
		'redirects' => true,
		'metadata' => [
			'opengraph' => true,
			'twitter' => true,
			'fallback-image' => false,
			'pinterest-verify' => false,
			'social-urls' => [
				'facebook' => '',
				'twitter' => '',
				'instagram' => '',
				'linkedin' => '',
				'myspace' => '',
				'pinterest' => '',
				'youtube' => '',
				'wikipedia' => '',
			],
			'opengraph-fallback' => [
				'frontpage-title' => '',
				'frontpage-desc' => '',
				'frontpage-image' => '',
			],
		],
		'site-verification' => true,
	];
	$options = [
		'defaults' => $default_settings,
	];
	Altis\register_module( 'seo', __DIR__, 'SEO', $options, __NAMESPACE__ . '\\bootstrap' );
} );
