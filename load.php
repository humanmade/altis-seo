<?php
/**
 * SEO Module
 *
 * @package hm-platform/seo
 */

namespace HM\Platform;

require_once __DIR__ . '/inc/namespace.php';

add_action( 'hm-platform.modules.init', function () {
	register_module( 'seo', __DIR__, 'SEO', [
		'enabled' => false,
		'redirects' => false,
		'metadata' => [
			'opengraph' => true,
			'twitter' => true,
			'json_ld' => true,
			'fallback_image' => false,
			'social_urls' => [
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
		'sitemap' => true,
		'amp' => false,
		'fbia' => false,
	], __NAMESPACE__ . '\\SEO\\bootstrap' );
} );
