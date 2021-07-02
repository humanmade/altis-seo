<?php
/**
 * Opengraph Meta Tag additions
 *
 * @package altis/seo
 */

namespace Altis\SEO\Opengraph;

use Yoast\WP\SEO\Presenters\Abstract_Indexable_Tag_Presenter;

/**
 * Adds a custom Opengraph Article Tag meta tag.
 */
class Altis_Opengraph_Tag_Presenter extends Abstract_Indexable_Tag_Presenter {
	/**
	 * The tag format including placeholders.
	 *
	 * @var void|string The opengraph tag if we're currently on a singular page.
	 */
	public function present() {
		if ( ! is_single() ) {
			return;
		}

		$terms = explode( ',', $this->get() );
		$tag_format = '<meta property="og:article:tag" content="%s" />';
		$output = '';

		foreach ( $terms as $term ) {
			$output .= sprintf( $tag_format, $term ) . "\n";
		}

		return $output;
	}

	/**
	 * Returns a Tag name for the new section meta tag.
	 *
	 * @return void|string The first tag if we're on a singular post.
	 */
	public function get() {
		$terms = get_the_terms( $this->presentation->model->object_id, 'post_tag' );

		if ( ! $terms ) {
			return;
		}

		$term_names = wp_list_pluck( $terms, 'name' );
		return implode( ',', $term_names );
	}
}