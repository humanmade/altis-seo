<?php
/**
 * @package hm-platform/seo
 */

namespace HM\Platform\SEO\Metadata\Twitter;

use function HM\Platform\SEO\Metadata\get_contextual_data;

function bootstrap() {
	add_filter( 'user_contactmethods', __NAMESPACE__ . '\\add_contact_method' );
	add_action( 'wp_head', __NAMESPACE__ . '\\to_html' );
}

/**
 * Add a contact method for twitter username / URL.
 *
 * @param array $methods
 * @return array
 */
function add_contact_method( array $methods ) : array {
	$methods['twitter'] = esc_html__( 'Twitter' );
	return $methods;
}

/**
 * Sanitize a twitter URL or username to the @handle version.
 *
 * @param string $handle
 * @return string
 */
function sanitize_username( string $string ) : string {
	if ( empty( $string ) ) {
		return '';
	}

	// URLs.
	if ( strpos( $string, 'twitter.com' ) !== false ) {
		return preg_replace( '#twitter\.com/([A-Za-z0-9_]{1,15})#', '@$1', $string );
	}

	return '@' . substr( ltrim( $string, '@' ), 0, 15 );
}

/**
 * Modify the contextual data to output twitter card meta.
 *
 * @return array Key value pairs of meta tags.
 */
function transform_context() : array {
	$data = get_contextual_data();

	$meta = [];
	$meta['card'] = 'summary_large_image';
	$meta['site'] = sanitize_username( get_config()['modules']['seo']['metadata']['social_urls']['twitter'] ?? '' );
	$meta['title'] = $data['title'] ?? '#title#';
	$meta['description'] = $data['description'] ?? '';
	$meta['image'] = $data['image'] ?? '#thumbmail_url#';

	if ( $data['context'] === 'single' ) {
		$meta['creator'] = sanitize_username( get_the_author_meta( 'twitter' ) );
	}

	/**
	 * Filter twitter meta tags.
	 *
	 * @param array $meta Key value pairs, keys excluding the `twitter:` prefix.
	 * @param array $data Full contextual data used to derive meta tags.
	 */
	$meta = apply_filters( 'hm-platform.seo.metadata.twitter', $meta, $data );

	// Format tags.
	$meta = array_map( 'HM\\Platform\\SEO\\Metadata\\format', $meta );
	$meta = array_filter( $meta );
	$meta = array_filter( $meta, 'is_string' );

	return $meta;
}

/**
 * Output the twitter meta tags.
 */
function to_html() {
	$meta = transform_context();

	// Generate meta tags.
	$output = array_reduce( array_keys( $meta ), function ( $carry, $key ) use ( $meta ) {
		return sprintf(
			"%s\n\t\t<meta name=\"twitter:%s\" content=\"%s\" />",
			$carry,
			sanitize_key( $key ),
			esc_attr( $meta[ $key ] )
		);
	}, '' );

	echo $output;
}
