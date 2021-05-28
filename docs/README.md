# SEO

![](./assets/banner-seo.png)

The Altis SEO module is powered by [Yoast SEO](https://yoast.com/wordpress/plugins/seo/) for its advanced features. The SEO module provides a suite of tools for enhancing and managing your networks visibility to search engines and social platforms including Facebook and Twitter.

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
					"xml-sitemaps": {
						"posts": true,
						"taxonomies": true,
						"users": true
					}
				}
			}
		}
	}
}
```

## Using Yoast SEO Premium

[Yoast SEO Premium](https://yoast.com/wordpress/plugins/seo/) adds more features and access to Yoast's support team. Altis SEO is configured in such a way that if you own a copy of Yoast SEO Premium, all you need to do is install it as a plugin or mu-plugin normally and it will work seamlessly without any additional configuration.

**Note** if you are using a headless architecture many of the features of Yoast SEO will not function as expected.
