<?php
/**
 * Opengraph meta tag handling.
 *
 * @package hm-platform/seo
 */

namespace HM\Platform\SEO\Metadata\Opengraph;

use function HM\Platform\SEO\Metadata\get_contextual_data;

function bootstrap() {
	add_filter( 'language_attributes', __NAMESPACE__ . '\\add_xmlns' );
	add_action( 'wp_head', __NAMESPACE__ . '\\to_html' );
}

/**
 * Add opengraph namespace to html tag.
 *
 * @param string $xmlns
 * @return string
 */
function add_xmlns( string $xmlns ) : string {
	return $xmlns . ' xmlns:og="http://ogp.me/ns#"';
}

/**
 * Transform the contextual data to opengraph key/value pairs.
 *
 * @return array
 */
function transform_context() : array {
	$data = get_contextual_data();

	$meta = [];
	$meta['site_name'] = $data['site_name'] ?? '#site_name#';
	$meta['locale'] = $data['locale'] ?? get_locale();
	$meta['type'] = 'website';
	$meta['title'] = $data['title'] ?? '#title#';
	$meta['description'] = $data['description'] ?? false;
	$meta['image'] = $data['fallback_image'] ?? false;

	if ( $data['image_id'] ?? false ) {
		$meta['image:alt'] = get_post_meta( $data['image_id'], '_wp_attachment_image_alt', true );
	}

	if ( $data['context'] ?? 'default' === 'single' ) {
		$meta['type'] = 'article';
		$meta['article:published_time'] = $data['published'] ?? '#date_published#';
		$meta['article:modified_time'] = $data['modified'] ?? '#date_modified#';
		$meta['article:expiration_time'] = false;
		$meta['article:author'] = get_author_posts_url( $data['object']->post_author );
		$meta['article:section'] = $data['categories'] ?? '#categories#';
		if ( is_object_in_taxonomy( $data['object']->post_type, 'post_tag' ) ) {
			$tags = get_the_terms( $data['object'], 'post_tag' );
			if ( is_array( $tags ) ) {
				$meta['article:tag'] = wp_list_pluck( $tags, 'name' );
			}
		}
	}

	if ( $data['context'] ?? 'default' === 'author' ) {
		$meta['type'] = 'profile';
		$meta['profile:first_name'] = $data['object']->get( 'first_name' );
		$meta['profile:last_name'] = $data['object']->get( 'last_name' );
	}

	/**
	 * Filter opengraph meta tags.
	 *
	 * @param array $meta Key value pairs, keys excluding the `og:` prefix.
	 * @param array $data Full contextual data used to derive meta tags.
	 */
	$meta = apply_filters( 'hm-platform.seo.metadata.opengraph', $meta, $data );

	// Format tags.
	$meta = array_map( 'HM\\Platform\\SEO\\Metadata\\format', $meta );
	$meta = array_filter( $meta );
	$meta = array_filter( $meta, function ( $value ) {
		return is_string( $value ) || is_array( $value );
	} );

	return $meta;
}

/**
 * Output the opengraph meta tags.
 */
function to_html() {
	$meta = transform_context();

	// Generate meta tags.
	$output = array_reduce( array_keys( $meta ), function ( $carry, $key ) use ( $meta ) {
		if ( empty( $meta[ $key ] ) ) {
			return $carry;
		}

		if ( ! is_array( $meta[ $key ] ) ) {
			$meta[ $key ] = [ $meta[ $key ] ];
		}

		foreach( $meta[ $key ] as $value ) {
			$carry = sprintf(
				"%s\n\t\t<meta property=\"og:%s\" content=\"%s\" />",
				$carry,
				sanitize_key( $key ),
				esc_attr( $value )
			);
		}

		return $carry;
	}, '' );

	echo $output;
}
