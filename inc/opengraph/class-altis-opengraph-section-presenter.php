<?php
/**
 * Opengraph Meta Tag additions
 *
 * @package altis/seo
 */

namespace Altis\SEO\Opengraph;

use Yoast\WP\SEO\Presenters\Abstract_Indexable_Tag_Presenter;

/**
 * Adds a custom Opengraph Article Author meta tag.
 */
class Altis_Opengraph_Section_Presenter extends Abstract_Indexable_Tag_Presenter {
	/**
	 * The tag format including placeholders.
	 *
	 * @var string
	 */
	protected $tag_format = '<meta property="og:article:section" content="%s" />';

	/**
	 * Returns a category name for the new section meta tag.
	 *
	 * @return void|string The category if we're on a singular post.
	 */
	public function get() {
		if ( ! is_single() ) {
			return;
		}

		$terms = get_the_terms( $this->presentation->model->object_id, 'category' );

		if ( ! $terms ) {
			return;
		}

		return $terms[0]->name;
	}
}