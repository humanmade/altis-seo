# XML Sitemaps

XML Sitemaps are a means of providing key content and additional meta data around the frequency of updates on your website to search engines.

The functionality for this component is provided by the [MSM Sitemap](https://github.com/humanmade/msm-sitemap) plugin.

The sitemaps follow the specification detailed on https://www.sitemaps.org/ and are supported by Google and all major search engines.

The sitemaps are automatically listed in the site's [./robots-txt.md](robots.txt) file so you don't need to manually submit them to the [Google Search Console](https://search.google.com/search-console/) but you can resubmit them there and get diagnostic information there at any time.

It is necessary to [verify the site with Google Search Console](https://support.google.com/webmasters/answer/9008080?hl=en) before you can access information about your site's search results performance. It is recommended to use the HTML file upload solution by committing the file to your project's root directory.

## Indexing content by year

In cases where you have a lot of content (50,000+ pages) you can split your sitemap up into multiple files by year using the following code:

```php
add_filter( 'msm_sitemap_index_by_year', '__return_true' );
```

## Modifying a sitemap entry

In order to modify or add extra data to a URL entry in the sitemap XML you can use the `msm_sitemap_entry` filter. Functions attached to this filter can access the current post data using `get_post()` or template tags such as `get_the_permalink()`.

The following example changes the update frequency value and priority for the home page:

```php
add_filter( 'msm_sitemap_entry', function ( SimpleXMLElement $url ) : SimpleXMLElement {
	if ( get_the_permalink() !== get_home_url( '/' ) ) {
		return $url;
	}

	$url->changefreq = new SimpleXMLElement( '<changefreq>always</changefreq>' );
	$url->priority = new SimpleXMLElement( '<priority>1.0</priority>' );

	return $url;
} );
```

You can use this filter to add more detailed information to enrich your sitemaps with [Video](https://support.google.com/webmasters/answer/80471), [Image](https://support.google.com/webmasters/answer/178636) and [News](https://support.google.com/webmasters/answer/74288) data.

## Adding an XML namespace

Some custom extensions to the standard sitemap format are supported but require the addition of one or more XML namespaces for them to validate. This is achieved using the `msm_sitemap_namespace` filter. The below example adds Google's video sitemap schema.

```php
add_filter( 'msm_sitemap_namespace', function ( array $namespaces ) : array {
	$namespaces['xmlns:video'] = "http://www.google.com/schemas/sitemap-video/1.1";
	return $namespaces;
} );
```

## Skipping a post

The `msm_sitemap_skip_post` filter is used to exclude posts from the sitemaps programmatically. The current post is available via `get_post()`.

```php
add_filter( 'msm_sitemap_skip_post', function () {
	if ( get_post_type() === 'hideme' ) {
		return true;
	}

	return false;
} );
```

## CLI commands

**`wp msm-sitemap generate-sitemap`**

Regenerates the sitemap file(s).
