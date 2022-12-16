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
}

/**
 * Load the redirects plugin.
 *
 * @return void
 */
function load_redirects() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/hm-redirects/hm-redirects.php';
}
