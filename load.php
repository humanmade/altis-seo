<?php
/**
 * SEO Module
 *
 * @package altis/seo
 */

namespace Altis\SEO;

use function Altis\register_module;

require_once __DIR__ . '/inc/namespace.php';

// Do not initialise if plugin.php hasn't been included yet.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

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
	];
	register_module( 'seo', __DIR__, 'SEO', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
