# XML Sitemaps

XML Sitemaps are a means of providing key content and additional meta data around the frequency of updates on your website to search engines.

The sitemaps follow the specification detailed on https://www.sitemaps.org/ and are supported by Google and all major search engines.

The sitemap index can hold a maximum of 50000 sitemaps, and a single sitemap can hold a (filterable) maximum of 2000 entries.

By default, sitemaps are created for all public and publicly queryable post types and taxonomies, as well as for author archives and of course the homepage of the site if set to show posts.

A sitemap index is automatically linked to in the site's [./robots-txt.md](robots.txt) file at `/wp-sitemap.xml` so you don't need to manually submit them to the [Google Search Console](https://search.google.com/search-console/). You can resubmit them from the search console and get diagnostic information there at any time.

**Note:** sitemaps are only linked to in the `robots.txt` file is the site is public and in production. To debug locally you will need to define the constant `HM_DISABLE_INDEXING` as `false`.

## Google Site Verification

It is necessary to [verify the site with Google Search Console](https://support.google.com/webmasters/answer/9008080?hl=en) before you can access information about your site's search results performance. It is recommended to use the HTML file upload solution by committing the file to your project's root directory although it is possible to add the meta tag by filling in the [verification code on the Reading Settings page in the admin](admin://options-reading.php).

## Adding Custom Sitemaps
Sitemaps are provided for built-in content types like pages and author archives out of the box. If you want to add a custom sitemap with additional features such as a video sitemap you can register a custom sitemap provider.

To do so create a custom PHP class that extends the abstract `WP_Sitemaps_Provider` class. Then, you can use the `wp_register_sitemap_provider()` function to register it.

The example below shows a minimal implementation for a custom video sitemap:

```php
class Video_Sitemap_Provider extends WP_Sitemaps_Provider {

	/**
	 * Set the name and object type properties. Required.
	 */
	public function __construct() {
		$this->name = 'videos';
		$this->object_type = 'post';
	}

	/**
	 * Return the list of URLs for the current page.
	 */
	public function get_url_list( $page ) : array {
		$videos = new WP_Query( [
			'post_type' => 'video',
			'posts_per_page' => 2000,
			'fields' => 'ids',
			'paged' => $page,
		] );

		$urls = [];

		foreach ( $videos->posts as $video_id ) {
			$urls[] = get_post_meta( $video_id, 'video_url', true );
		}

		return $urls;
	}

	/**
	 * Return the maximum number of pages to output for this sitemap.
	 */
	public function get_max_num_pages() {
		$videos = new WP_Query( [
			'post_type' => 'video',
			'posts_per_page' => 2000,
			'fields' => 'ids',
		] );

		return $videos->max_num_pages;
	}

}

add_filter( 'init', function() {
	$provider = new Video_Sitemap_Provider();
	wp_register_sitemap_provider( 'video-sitemaps', $provider );
} );
```

The provider will be responsible for getting all sitemaps and sitemap entries, as well as determining pagination.

## Removing Specific Sitemaps
There are three existing sitemaps providers for standard object types - `posts`, `taxonomies`, and `users`. If you want to remove one of them such as the "users" provider, you can configure the module like so:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"xml-sitemaps": {
						"users": false
					}
				}
			}
		}
	}
}
```

Alternatively if you need to use more complex logic or work with custom sitemap providers you can use the `wp_sitemaps_add_provider` filter to do so for example:

```php
add_filter( 'wp_sitemaps_add_provider', function( $provider, $name ) {
	// Only switch off the user sitemaps for subsites on the network.
	if ( 'users' === $name && ! is_main_site() ) {
		return false;
	}

	return $provider;
}, 10, 2 );
```

If instead you want to disable sitemap generation for a specific post type or taxonomy, use the `wp_sitemaps_post_types` or `wp_sitemaps_taxonomies` filter, respectively.

To disable sitemaps for the page post type you would do the following:

```php
add_filter( 'wp_sitemaps_post_types', function( $post_types ) {
	unset( $post_types['page'] );
	return $post_types;
} );
```

To disable sitemaps for the `post_tag` taxonomy:

```php
add_filter( 'wp_sitemaps_taxonomies', function( $taxonomies ) {
	unset( $taxonomies['post_tag'] );
	return $taxonomies;
} );
```

## Adding Additional Tags to Sitemap Entries
The sitemaps protocol specifies a certain set of supported attributes for sitemap entries. Of those, only the URL (loc) tag is required. All others (e.g. `changefreq` and `priority`) are optional tags in the sitemaps protocol and not typically consumed by search engines, which is why only the URL itself is listed by default. You can still add those tags if you need to.

You can use the `wp_sitemaps_posts_entry`, `wp_sitemaps_users_entry` or `wp_sitemaps_taxonomies_entry` filters to add additional tags like `changefreq`, `priority`, or `lastmod` to single items in the sitemap.

To add the last modified date for posts:

```php
add_filter( 'wp_sitemaps_posts_entry', function( $entry, $post ) {
	$entry['lastmod'] = $post->post_modified_gmt;
	return $entry;
}, 10, 2 );
```

Similarly, you can use the `wp_sitemaps_index_entry` filter to add `lastmod` on the sitemap index.

Trying to add any unsupported tags will result in a PHP notice.

## Excluding a Single Post from the Sitemap
If you have a feature that allows setting specific posts or pages to `noindex`, it's a good idea to exclude those from the sitemap too.

The `wp_sitemaps_posts_query_args` filter can be used to exclude specific posts from the sitemap. Here's an example:

```php
add_filter( 'wp_sitemaps_posts_query_args', function( $args, $post_type ) {
	if ( 'post' !== $post_type ) {
		return $args;
	}

	$args['post__not_in'] = isset( $args['post__not_in'] ) ? $args['post__not_in'] : [];
	$args['post__not_in'][] = 123; // 123 is the ID of the post to exclude.
	return $args;
}, 10, 2 );
```
