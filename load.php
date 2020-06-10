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
			'json-ld' => true,
			'fallback-image' => false,
			'social-urls' => [
				'google' => '',
				'facebook' => '',
				'twitter' => '',
				'instagram' => '',
				'youtube' => '',
				'linkedin' => '',
				'myspace' => '',
				'pinterest' => '',
				'soundcloud' => '',
				'tumblr' => '',
			],
		],
		'xml-sitemaps' => true,
		'amp' => false,
		'facebook-instant-articles' => false,
		'site-verification' => true,
	];
	Altis\register_module( 'seo', __DIR__, 'SEO', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
