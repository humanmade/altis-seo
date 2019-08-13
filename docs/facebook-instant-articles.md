# Facebook Instant Articles

Facebook Instant Articles enables publishers to monetize and make content available natively within Facebook's website and apps using a custom feed.

This component is powered by the [official Facebook Instant Articles plugin](https://github.com/humanmade/facebook-instant-articles-wp).

## Pre-requisites

* A Facebook page for your organization
* A Facebook App

## Configuration

Configuration is managed via the CMS admin under the Instant Articles menu item. Follow the wizard to complete the setup before moving onto customizing how your content will be transformed and displayed.

## Content Transformation

The plugin will parse and transform your content automatically by following the [default configuration rules JSON](https://github.com/humanmade/facebook-instant-articles-wp/blob/master/rules-configuration.json). You can find the [documentation for the transformer rules in Facebook's documentation here](https://developers.facebook.com/docs/instant-articles/sdk/transformer-rules/).

You can provide your own custom file using the `instant_articles_transformer_rules_configuration_json_file_path` filter:

```php
add_filter( 'instant_articles_transformer_rules_configuration_json_file_path', function ( string $path ) : string {
	return __DIR__ . '/custom-rules.json';
} );
```

The `Transform` object that interprets those rules provided by the Facebook SDK can be accessed and modified using the `instant_articles_transformer_rules_loaded` filter.

```php
use Facebook\InstantArticles\Transformer\Transformer;
add_filter( 'instant_articles_transformer_rules_loaded', function ( Transformer $transformer ) : Transformer {
	// Add more custom rules.
	$transformer->loadRules( __DIR__ . '/custom-rules.json' );

	return $transformer;
} );
```

See the [documentation for the `Transformer` class and creating custom rule classes](https://developers.facebook.com/docs/instant-articles/sdk/transformer) and the [Instant Articles PHP SDK GitHub repository](https://github.com/facebook/facebook-instant-articles-sdk-php) for more information.
