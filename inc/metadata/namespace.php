<?php
/**
 * HM Platform SEO Module Metadata output.
 *
 * @package hm-platform/seo
 */

namespace HM\Platform\SEO\Metadata;

use function HM\Platform\get_config;

/**
 * Get the current URL.
 *
 * @param bool $query_string Whether to append the current query string.
 * @return string
 */
function get_current_url( bool $query_string = false ) : string {
	$url = sprintf(
		'http://%s%s%s',
		$_SERVER['HTTP_HOST'],
		$_SERVER['REQUEST_URI'],
		$_SERVER['QUERY_STRING'] && $query_string ? '?' . $_SERVER['QUERY_STRING'] : ''
	);
	return set_url_scheme( $url );
}

/**
 * Recursively format data using WP SEO tag replacements.
 *
 * @param array|string $value
 * @return string
 */
function format( $value ) {
	if ( is_array( $value ) ) {
		return array_map( __NAMESPACE__ . '\\format', $value );
	}

	if ( ! is_string( $value ) ) {
		return $value;
	}

	return wp_seo()->format( $value );
}

/**
 * Builds up an array of contextual and global information used
 * by functions to output
 *
 * @return array
 */
function get_contextual_data() : array {
	if ( ! did_action( 'wp' ) ) {
		trigger_error( 'HM\Platform\SEO\Metadata\get_contextual_data() was called before the "wp" action', E_USER_WARNING );
		return [];
	}

	$object = get_queried_object();
	$object_id = get_queried_object_id();

	// Default values.
	$data = [
		'context' => 'default',
		'object' => $object,
		'object_id' => $object_id,
		'site_name' => '#site_name#',
		'site_url' => get_home_url(),
		'locale' => get_locale(),
		'logo' => get_config()['modules']['seo']['metadata']['logo'] ?? false,
		'fallback_image' => get_config()['modules']['seo']['metadata']['fallback_image'] ?? false,
		'title' => wp_title( '|', false, 'right' ),
		'description' => false,
		'image' => false,
		'url' => get_current_url(),
		'type' => false,
	];

	// Logo.
	if ( current_theme_supports( 'custom-logo' ) ) {
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$data['logo'] = wp_get_attachment_image_url( $logo_id, 'full' );
		}
	}

	// Fallback image defaults to logo.
	if ( empty( $data['fallback_image'] ) ) {
		$data['fallback_image'] = $data['logo'];
	}

	// Front page.
	if ( is_front_page() ) {
		$data['context'] = 'front-page';
		$data['description'] = '#site_description#';
		if ( $data['logo'] ) {
			$data['image'] = $data['logo'];
		}
	}

	// Post.
	if ( is_singular() ) {
		$data['context'] = 'single';
		$data['title'] = '#title#';
		$data['image'] = '#thumbmail_url#';
		$data['image_id'] = get_post_thumbnail_id( $object );
		$data['description'] = '#excerpt#';
		$data['url'] = get_the_permalink( $object );
		$data['author'] = '#author#';
		$data['author_id'] = $object->post_author;
		$data['published'] = '#date_published#';
		$data['modified'] = '#date_modified#';
		$data['type'] = 'article';
		$data['hierarchical'] = is_post_type_hierarchical( $object->post_type );

		if ( is_object_in_taxonomy( $object->post_type, 'post_tag' ) ) {
			$data['tags'] = '#tags#';
		}

		if ( is_object_in_taxonomy( $object->post_type, 'category' ) ) {
			$data['categories'] = '#categories#';
		}

		// Attachment.
		if ( is_attachment() ) {
			$data['mime_type'] = get_post_mime_type( $object_id );
			$data['image'] = wp_get_attachment_image_url( $object_id, 'full' );
			$data['url'] = wp_get_attachment_url( $object_id );
			$data['type'] = 'media';

			// Images.
			if ( wp_attachment_is_image( $object ) ) {
				$data['type'] = 'image';
			}

			// Videos.
			if ( strpos( $data['mime_type'], 'video/' ) === 0 ) {
				$data['type'] = 'video';
			}

			// PDFs.
			if ( strpos( $data['mime_type'], 'application/pdf' ) === 0 ) {
				$data['type'] = 'pdf';
			}
		}

		/**
		 * Filter contextual data for posts.
		 *
		 * @param array $data
		 * @param WP_Post $object
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.post', $data, $object );

		/**
		 * Filter contextual data for a specific post type.
		 *
		 * @param array $data
		 * @param WP_Post $object
		 */
		$data = apply_filters( "hm-platform.seo.metadata.context.post.{$object->post_type}", $data, $object );
	}

	// Home / blog.
	if ( is_home() && ! is_front_page() ) {
		$page = get_post( get_option( 'page_for_posts' ) );
		$data['context'] = 'blog';
		$data['title'] = get_the_title( $page );
		$data['description'] = get_the_excerpt( $page );
		$data['url'] = get_the_permalink( $page );

		$post_type_object = get_post_type_object( 'post' );

		/**
		 * Filter contextual data for post type archive.
		 *
		 * @param array $data
		 * @param WP_Post_Type $post_type_object
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.blog', $data, $post_type_object );
	}

	// Taxonomy term.
	if ( is_tax() || is_tag() || is_category() ) {
		$data['context'] = 'taxonomy';
		$data['title'] = '#term_name#';
		$data['description'] = '#term_description#';
		$data['url'] = get_term_link( $object, $object->taxonomy );

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $data
		 * @param WP_Term $object
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.taxonomy', $data, $object );
	}

	// Post type archive.
	if ( is_post_type_archive() ) {
		$data['context'] = 'post_type';
		$data['title'] = get_the_archive_title();
		$data['description'] = get_the_archive_description();
		$data['url'] = get_post_type_archive_link( $object->name );

		/**
		 * Filter contextual data for post type archive.
		 *
		 * @param array $data
		 * @param WP_Post_Type $object
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.post_type', $data, $object );
	}

	// Author.
	if ( is_author() ) {
		$data['context'] = 'author';
		$data['title'] = get_the_archive_title();
		$data['description'] = get_the_archive_description();
		$data['url'] = get_author_posts_url( $object_id );

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $data
		 * @param WP_User $object
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.author', $data, $object );
	}

	// Date.
	if ( is_date() ) {
		$data['context'] = 'date';
		$data['title'] = get_the_archive_title();
		$data['description'] = get_the_archive_description();

		/**
		 * Filter contextual data for date archives.
		 *
		 * @param array $data
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.date', $data );
	}

	// Search.
	if ( is_search() ) {
		$data['context'] = 'search';
		$data['search_term'] = '#search_term#';

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $data
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.search', $data );
	}

	// 404.
	if ( is_404() ) {
		$data['context'] = '404';

		/**
		 * Filter contextual data for terms.
		 *
		 * @param array $data
		 */
		$data = apply_filters( 'hm-platform.seo.metadata.context.404', $data );
	}

	/**
	 * Filter all contextual data.
	 *
	 * @param array $data
	 * @param mixed $object The queried object.
	 */
	$data = apply_filters( 'hm-platform.seo.metadata.context', $data, $object );

	// Ensure object and object ID are unmodified.
	$data['object'] = $object;
	$data['object_id'] = $object_id;

	return $data;
}
