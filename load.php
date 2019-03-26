<?php
/**
 * SEO Module
 *
 * @package hm-platform/seo
 */

namespace HM\Platform\SEO;

use function HM\Platform\register_module;

require_once __DIR__ . '/inc/namespace.php';

add_action( 'hm-platform.modules.init', function () {
	register_module( 'seo', __DIR__, 'SEO', [
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
	], __NAMESPACE__ . '\\SEO\\bootstrap' );
} );
