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
	];
	$options = [
		'defaults' => $default_settings,
	];
	Altis\register_module( 'seo', __DIR__, 'SEO', $options, __NAMESPACE__ . '\\bootstrap' );
} );
