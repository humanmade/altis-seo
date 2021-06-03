# XML Sitemaps

XML Sitemaps are a means of providing key content and additional meta data around the frequency of updates on your website to search engines.

The sitemaps follow the specification detailed on https://www.sitemaps.org/ and are supported by Google and all major search engines.

The sitemap index can hold a maximum of 50000 sitemaps, and a single sitemap can hold a (filterable) maximum of 2000 entries.

By default, sitemaps are created for all public and publicly queryable post types and taxonomies, as well as for author archives and of course the homepage of the site if set to show posts.

The XML Sitemaps in Altis are provided by our integration with [Yoast SEO](https://developer.yoast.com/features/xml-sitemaps/overview). A sitemap index is automatically linked to in the site's [robots.txt](./robots-txt.md) file at `/sitemap_index.xml` so you don't need to manually submit them to the [Google Search Console](https://search.google.com/search-console/). You can resubmit them from the search console and get diagnostic information there at any time.

**Note:** Sitemaps are only linked to in the `robots.txt` file if the site is public and in production. To debug locally you will need to define the constant `HM_DISABLE_INDEXING` as `false`.

## Google Site Verification

It is necessary to [verify the site with Google Search Console](https://support.google.com/webmasters/answer/9008080?hl=en) before you can access information about your site's search results performance. It is recommended to use the HTML file upload solution by committing the file to your project's root directory, although it is possible to add the meta tag by filling in the [verification code on the Reading Settings page in the admin](admin://options-reading.php).

## Adding or Removing Post Types
Sitemaps are provided for public content types like posts and pages out of the box. If you want to add support for additional custom post types in your sitemaps (for example, non-public post types or otherwise excluded post types), you can do so by using the built-in Yoast SEO functions and filters.

To add a post type to the sitemaps, use the `register_sitemap` method of the `$wpseo_sitemaps` global like in the example below:

```php
add_action( 'init', function () {
	global $wpseo_sitemaps;

	if ( isset( $wpseo_sitemaps ) && ! empty( $wpseo_sitemaps ) ) {
		$wpseo_sitemaps->register_sitemap( 'video', 'create_video_sitemap' );
	}
} );
```

To remove a post type from your sitemaps, use the `wp_seo_sitemap_exclude_post_type` filter. The example below removes a post type registered as `recipe`.

```php
add_filter( 'wpseo_sitemap_exclude_post_type', function ( $excluded, $post_type ) {
	return $post_type === 'recipe';
}, 10, 2 );
```

You can similarly exclude specific posts, taxonomies or authors, using built in filters in Yoast SEO.

## Adding Additional Sitemaps

If you need to add additional sitemaps to the index, this can be done with the `wpseo_sitemap_index` filter. This filter allows you to add additional XML Sitemap URLs to the index.

```php
add_filter( 'wpseo_sitemap_index', function ( $sitemap_custom_items ) {
	$sitemap_custom_items .= '
<sitemap>
	<loc>https://example.altis.cloud/external-sitemap-1.xml</loc>
	<lastmod>2021-05-31T12:23:27+00:00</lastmod>
</sitemap>';

	return $sitemap_custom_items;
} )
```

Additional examples and documentation can be found in the [Yoast SEO developer documentation](https://developer.yoast.com/features/xml-sitemaps/api).
