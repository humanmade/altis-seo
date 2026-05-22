<?php
/**
 * SEO module functions.
 *
 * @package altis/seo
 */

namespace Altis\SEO;

use Altis;
use Altis\Module;

/**
 * Bootstrap SEO Module.
 *
 * @param Module $module The SEO Module object.
 * @return void
 */
function bootstrap( Module $module ) {
	$settings = $module->get_settings();

	if ( $settings['redirects'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_redirects', 0 );
	}

	// Disallow indexing if the allow_indexing setting is false.
	if ( ! ( $settings['allow_indexing'] ?? true ) ) {
		add_filter( 'robots_txt', __NAMESPACE__ . '\\disallow_indexing', 10, 2 );
	}
}

/**
 * Load the redirects plugin.
 *
 * @return void
 */
function load_redirects() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/hm-redirects/hm-redirects.php';
}

/**
 * Disallow indexing in robots.txt.
 *
 * @param string $output The robots.txt output.
 * @param string $public Whether the site is public.
 * @return string Modified robots.txt output.
 */
function disallow_indexing( string $output, string $public ) : string {
	$output .= "\nUser-agent: *\nDisallow: /\n";
	return $output;
}
