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
class Altis_Opengraph_Author_Presenter extends Abstract_Indexable_Tag_Presenter {

	/**
	 * The tag format including placeholders.
	 *
	 * @var string
	 */
	protected $tag_format = '<meta property="og:article:author" content="%s" />';

	/**
	 * Returns the author posts url for the new author meta tag.
	 *
	 * @return void|string The author posts url if we're on a singular post.
	 */
	public function get() {
		if ( ! is_single() ) {
			return;
		}

		return get_author_posts_url( $this->presentation->model->author_id );
	}
}