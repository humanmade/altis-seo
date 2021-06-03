# XML Sitemaps

XML Sitemaps are a means of providing key content and additional meta data around the frequency of updates on your website to search engines.

The sitemaps follow the specification detailed on https://www.sitemaps.org/ and are supported by Google and all major search engines.

The sitemap index can hold a maximum of 50000 sitemaps, and a single sitemap can hold a (filterable) maximum of 2000 entries.

By default, sitemaps are created for all public and publicly queryable post types and taxonomies, as well as for author archives and of course the homepage of the site if set to show posts.

The XML Sitemaps in Altis are provided by our integration with [Yoast SEO](https://developer.yoast.com/features/xml-sitemaps/overview). A sitemap index is automatically linked to in the site's [robots.txt](./robots-txt.md) file at `/sitemap_index.xml` so you don't need to manually submit them to the [Google Search Console](https://search.google.com/search-console/). You can resubmit them from the search console and get diagnostic information there at any time.

**Note:** Sitemaps are only linked to in the `robots.txt` file if the site is public and in production. To debug locally you will need to define the constant `HM_DISABLE_INDEXING` as `false`.

## Google Site Verification

It is necessary to [verify the site with Google Search Console](https://support.google.com/webmasters/answer/9008080?hl=en) before you can access information about your site's search results performance. It is recommended to use the HTML file upload solution by committing the file to your project's root directory, although it is possible to add the meta tag by filling in the [verification code on the Reading Settings page in the admin](admin://options-reading.php).

## Adding Content to XML Sitemaps
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

The above example works if `video` is the name of a post type that has not been previously set to render sitemaps. In that case, the second parameter is not explicitly used, as Yoast SEO will use its own matching `WPSEO_Sitemap_Provider` subclass to handle building the actual sitemap. The same is true if you wish to generate a (previously-excluded) taxonomy sitemap (where the first parameter is the taxonomy name).

If a matching sitemap provider is not registered or cannot be found, the second parameter in the `register_sitemap` method must correspond to a callback function which must generate the entire sitemap XML.

## Excluding Content From XML Sitemaps

### `wpseo_sitemap_exclude_post_type`

Excludes a post type from your sitemaps. The example below removes a post type registered as `recipe`.

**Parameters**

**`$excluded`** _(bool)_ Whether the post type is excluded by default.

**`$post_type`** _(string)_ The post type to exclude.

**Example**

```php
add_filter( 'wpseo_sitemap_exclude_post_type', function ( bool $excluded, string $post_type ) : bool {
	return $post_type === 'recipe';
}, 10, 2 );
```

### `wpseo_sitemap_exclude_taxonomy`

Excludes a taxonomy from your sitemaps. The example below removes a taxonomy that has been registered as `cuisine`.

**Parameters**

**`$excluded`** _(bool)_ Whether the taxonomy is excluded by default.

**`$taxonomy`** _(string)_ The taxonomy to exclude.

**Example**

```php
add_filter( 'wpseo_sitemap_exclude_taxonomy', function ( bool $excluded, string $taxonomy ) : bool {
	return $taxonomy === 'cuisine';
}, 10, 2 )
```

### `wpseo_exclude_from_sitemap_by_post_ids`

Excludes specific posts from a sitemap by post IDs.

**Example**

```php
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids' function () : array {
	return [ 1, 2, 3 ];
} );
```

### `wpseo_exclude_from_sitemap_by_term_ids`

Excludes specific taxonomy terms from a sitemap by term IDs.

**Parameters**

**`$terms`** _(array)_ Array of term IDs already excluded.

**Example**

```php
add_filter( 'wpseo_exclude_from_sitemap_by_term_ids', function ( array $terms ) : array {
	$term_ids_to_exclude = [ 3, 5, 9 ];

	// Check if our excluded terms are already excluded.
	foreach ( $term_ids_to_exclude as $i => $term_id ) {
		if ( in_array( $term_id, $terms, true ) ) {
			unset( $term_ids_to_exclude[ $i ] );
		}
	}

	return $term_ids_to_exclude;
} )
```

### `wpseo_sitemap_exclude_author`

Exclude a specific author from the authors sitemap. The example below excludes an author with the slug `chris-reynolds` from the authors sitemap.

**Parameters**

**`$users`** _(array)_ Array of User objects to filter through.

**Example**

```php
add_filter( 'wpseo_sitemap_exclude_author', function ( array $users ) : array {
	$author_to_exclude = get_user_by( 'slug', 'chris-reynolds' );
	return array_filter( $users, function ( $user ) use ( $author_to_exclude ) : bool {
		if ( $user->ID === $author_to_exclude->ID ) {
			return false;
		}

		return true;
	} );
} );
```

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
