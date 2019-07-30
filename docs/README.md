# SEO

![](./assets/banner-seo.png)

The SEO module provides a suite of tools for enhancing and managing your networks visibility to search engines and social platforms including Facebook and Twitter.

## Configuration

The following JSON is the default configuration for the module and can be overridden in your project's `composer.json` file.

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"enabled": true,
					"metadata": {
						"opengraph": true,
						"twitter": true,
						"json-ld": true,
						"fallback-image": "",
						"social-urls": {
							"google": "",
							"facebook": "",
							"twitter": "",
							"instagram": "",
							"youtube": "",
							"linkedin": "",
							"myspace": "",
							"pinterest": "",
							"soundcloud": "",
							"tumblr": "",
						},
					},
					"redirects": true,
					"xml-sitemaps": true,
					"amp": false,
					"facebook-instant-articles": false,
				}
			}
		}
	}
}
```

## Using Yoast SEO as an alternative

Depending on your needs you may wish to install [Yoast SEO](https://yoast.com/wordpress/plugins/seo/) for its advanced features. It is recommended to disable the Metadata and XML Sitemaps components if you do this using the following configuration.

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"enabled": true,
					"metadata": false,
					"xml-sitemaps": false,
				}
			}
		}
	}
}
```

**Note** if you are using a headless architecture many of the features of Yoast SEO will not function as expected.
