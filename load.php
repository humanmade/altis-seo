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
			'fallback-image-id' => '',
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
				'og-frontpage-title' => '',
				'og-frontpage-desc' => '',
				'og-frontpage-image' => '',
				'og-frontpage-image-id' => '',
			],
		],
		'site-verification' => true,
	];
	Altis\register_module( 'seo', __DIR__, 'SEO', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
