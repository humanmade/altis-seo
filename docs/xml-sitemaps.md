# XML Sitemaps

XML Sitemaps are a means of providing key content and additional meta data around the frequency of updates on your website to search engines.

The functionality for this component is provided by the [MSM Sitemap](https://github.com/humanmade/msm-sitemap) plugin for WordPress.

Performance problems are solved by generating the XML ahead of time and storing it in the database.

## Indexing content by year

In cases where you have a lot of content you can split your sitemap up into multiple files by year using the following code:

```php
add_filter( 'msm_sitemap_index_by_year', '__return_true' );
```

## Modifying a sitemap entry

In order to modify or add extra data to a URL entry in the sitemap XML you can use the `msm_sitemap_entry` filter. Functions attached to this filter can access the current post using `$GLOBALS['post']` or template tags such as `get_the_permalink()`.

The following example changes the update frequency value and priority for the home page:

```php
add_filter( 'msm_sitemap_entry', function ( SimpleXMLElement $url ) : SimpleXMLElement {
	if ( get_the_permalink() !== get_home_url( '/' ) ) {
		return $url;
	}

	$url->{'changefreq'} = new SimpleXMLElement( '<changefreq>always</changefreq>' );
	$url->{'priority'} = new SimpleXMLElement( '<priority>1.0</priority>' );

	return $url;
} );
```

## Skipping a post

The `msm_sitemap_skip_post` filter is used to exclude posts from the sitemaps programmatically. The current post is available via `$GLOBALS['post']`.

```php
add_filter( 'msm_sitemap_skip_post', function () {
	if ( $GLOBALS['post']->post_type === 'hideme' ) {
		return true;
	}

	return false;
} );
```

## CLI commands

**`wp msm-sitemap generate-sitemap`**

Regenerates the sitemap file(s).
