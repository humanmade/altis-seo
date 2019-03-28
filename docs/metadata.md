# Metadata

Website metadata provides machine readable information to search engines and other user agents such as those used by social platforms to understand how to present your website and web pages in a richer format than the standard hyperlink.

## SEO Metadata

Users can edit the title tag, meta description and meta keywords for each public page, post and taxonomy term to provide greater control over how content is interpreted by search engines and how the results are displayed on Search Engine Result Pages (SERPs).

In addition the default title tag content can be edited via the admin for each type of web page the CMS outputs too.

## Meta tags

Meta tag output is provided out of the box with some sensible defaults for [Opengraph](https://ogp.me), [Twitter cards](https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/abouts-cards) and [Schema.org](https://schema.org) (using JSON+LD).

The meta tags that are output can be extended or modified using the following filters, where `<type>` should be replaced by one of `opengraph`, `twitter` or `json_ld`.

- `hm.metatags.context.<type>.front_page`
- `hm.metatags.context.<type>.singular`
- `hm.metatags.context.<type>.singular.<post-type>`
- `hm.metatags.context.<type>.blog` - default blog archive if different to front page
- `hm.metatags.context.<type>.taxonomy` - taxonomy term archive
- `hm.metatags.context.<type>.post_type` - post type archive
- `hm.metatags.context.<type>.author` - author archive
- `hm.metatags.context.<type>.date` - date archive
- `hm.metatags.context.<type>.search` - search results
- `hm.metatags.context.<type>.404`
- `hm.metatags.context.<type>` - catch all filter for any custom contexts

The filters receive two arguments, `$meta` is the array of data converted into HTML meta tags and `$context` which is an array of data derived from the current request context.

The following is an example that modifies the opengraph output for an "Event" custom post type.

```php
add_filter( `hm.metatags.context.opengraph.singular.event`, function ( array $meta, array $context ) : array {
	$meta['type'] = 'event';

	// Start and end times should be in ISO 8601 format.
	$meta['event:start_time'] = date( 'c', get_post_meta( $context['object_id'], 'event_start', true ) );
	$meta['event:end_time'] = date( 'c', get_post_meta( $context['object_id'], 'event_end', true ) );

	return $meta;
}, 10, 2 );
```
