<?php
/**
 * JSON LD Metadata functions.
 *
 * @package hm-platform/seo
 */

namespace HM\Platform\SEO\Metadata\JSONLD;

use WP_User;
use function HM\Platform\get_config;
use function HM\Platform\SEO\Metadata\get_contextual_data;
use function HM\Platform\SEO\Metadata\get_current_url;

function bootstrap() {
	add_action( 'wp_head', __NAMESPACE__ . '\\to_html' );
}

/**
 * Get a JSON LD array for a user.
 *
 * @param WP_User $user
 * @param array $data
 * @return array
 */
function get_person( WP_User $user, array $data = [] ) : array {
	$meta = [];
	$meta['@type'] = 'Person';
	$meta['@id'] = $data['url'] ?? get_author_posts_url( $user->ID );
	$meta['name'] = $user->get( 'display_name' );
	$meta['url'] = $meta['@id'];
	$meta['description'] = $data['description'] ?? $user->get( 'description' );

	// Extract URL values from contact methods.
	$contact_methods = wp_get_user_contact_methods( $user );
	$contact_methods = array_map( function ( $key ) use ( $data ) {
		return $user->get( $key );
	}, array_keys( $contact_methods ) );
	$contact_methods = array_filter( $contact_methods, function ( $method ) {
		return filter_var( $method, FILTER_VALIDATE_URL );
	} );
	$meta['sameAs'] = $contact_methods;

	return $meta;
}

/**
 * Get the knowledge graph for the site.
 *
 * @param array $data
 * @return array
 */
function get_knowledge_graph( array $data = [] ): array {
	$meta = [];
	$meta['@type'] = 'Organization';
	$meta['@id'] = '#organization';
	$meta['name'] = $data['site_name'] ?? '#site_name#';
	$meta['description'] = $data['site_description'] ?? '#site_description#';
	$meta['url'] = $data['site_url'] ?? get_home_url();
	$meta['logo'] = $data['logo'] ?? false;
	$meta['sameAs'] = array_values( get_config()['modules']['seo']['metadata']['social_urls'] ?? [] );

	return $meta;
}

/**
 * Transform the contextual data for JSON LD output.
 *
 * @return array
 */
function transform_context() : array {
	$data = get_contextual_data();

	$meta = [];

	if ( $data['context'] ?? 'default' === 'front-page' ) {
		$meta = array_merge( $meta, get_knowledge_graph( $data ) );
	}

	if ( in_array( $data['context'] ?? 'default', [ 'blog', 'post_type', 'taxonomy' ], true ) ) {
		$meta['@type'] = 'CollectionPage';
		$meta['@id'] = $data['url'] ?? get_current_url();
		$meta['headline'] = $data['title'] ?? false;
		$meta['description'] = $data['description'] ?? false;
		$meta['url'] = $data['url'] ?? false;
	}

	if ( $data['context'] ?? 'default' === 'single' ) {
		if ( $data['hierarchical'] ?? true ) {
			$meta['@type'] = 'WebPage';
		} else {
			$meta['@type'] = 'Article';
		}

		$meta['headline'] = $data['title'] ?? get_the_title( $data['object_id'] );
		$meta['datePublished'] = $data['published'] ?? '#date_published#';
		$meta['dateModified'] = $data['modified'] ?? '#date_modified#';
		$meta['mainEntityOfPage'] = $data['url'] ?? get_the_permalink( $data['object_id'] );
		$meta['author'] = [
			get_person( get_user_by( 'id', $data['object']->post_author ), $data )
		];
		$meta['keywords'] = $data['tags'] ?? '#tags#';
		$meta['image'] = $data['image'] ?? '#thumbnail_url#';
		$meta['url'] = $data['url'] ?? get_the_permalink( $data['object_id'] );
		$meta['publisher'] = [
			get_knowledge_graph( $data ),
		];
	}

	if ( $data['context'] ?? 'default' === 'author' ) {
		$meta = array_merge( $meta, get_person( $data['object'], $data ) );
	}

	if ( $data['context'] ?? 'default' === 'search' ) {
		$meta['@type'] = 'SearchResultsPage';
		$meta['@id'] = get_current_url();
		$meta['headline'] = $data['title'] ?? __( 'Search' );
		$meta['url'] = get_current_url( true );
	}

	/**
	 * Filter opengraph meta tags.
	 *
	 * @param array $meta Key value pairs, keys excluding the `og:` prefix.
	 * @param array $data Full contextual data used to derive meta tags.
	 */
	$meta = apply_filters( 'hm-platform.seo.metadata.jsonld', $meta, $data );

	// Format tags.
	$meta = array_map( 'HM\\Platform\\SEO\\Metadata\\format', $meta );
	$meta = array_filter( $meta );
	$meta = array_filter( $meta, function ( $value ) {
		return is_string( $value ) || is_array( $value );
	} );

	// Common value.
	if ( ! empty( $meta ) ) {
		$meta['@context'] = 'https://schema.org';
	}

	return $meta;
}

/**
 * Output the JSON LD meta data.
 */
function to_html() {
	$meta = transform_context();

	if ( empty( $meta ) ) {
		return;
	}

	$output = sprintf(
		'<script type="application/ld+json">%s</script>',
		wp_json_encode( $meta, JSON_UNESCAPED_UNICODE )
	);

	echo $output;
}
