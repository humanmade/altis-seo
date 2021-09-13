# SEO

![](./assets/banner-seo.png)

The Altis SEO module is powered by [Yoast SEO](https://yoast.com/wordpress/plugins/seo/) for its advanced features. The SEO module provides a suite of tools for enhancing and managing your network's visibility to search engines and social platforms including Facebook and Twitter.

## Configuration

The following JSON is the default configuration for the module and can be overridden in your project's `composer.json` file.

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"development-mode": true,
					"enabled": true,
					"metadata": {
						"opengraph": true,
						"twitter": true,
						"fallback-image": false,
						"pinterest-verify": false,
						"social-urls": {
							"facebook": "",
							"twitter": "",
							"instagram": "",
							"linkedin": "",
							"myspace": "",
							"pinterest": "",
							"youtube": "",
							"wikipedia": ""
						},
						"opengraph-fallback": {
							"frontpage-title": "",
							"frontpage-desc": "",
							"frontpage-image": ""
						}
					},
					"redirects": true
				}
			}
		}
	}
}
```

## Developmnt Mode

Yoast SEO includes a _development mode_, this is enabled by default for non-production environments and will _pretty print_ the _yoast-schema-graph_ HTML rather than the minified default.
## Using Yoast SEO Premium

[Yoast SEO Premium](https://yoast.com/wordpress/plugins/seo/) adds more features and access to Yoast's support team. Altis SEO is configured in such a way that if you own a copy of Yoast SEO Premium, all you need to do is install it as a composer dependency and it will work seamlessly without any additional configuration.

To install Yoast SEO Premium using Composer follow the instructions linked to in the [Downloads section of MyYoast](https://my.yoast.com/downloads). You will be able to create a developer token and then be provided with the required `composer.json` updates and commands to run.

**Note** if you are using a headless architecture many of the features of Yoast SEO will not function as expected.

If you intend to install Yoast SEO Premium manually without using Composer, you will need to switch off the SEO module using the following configuration:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"enabled": false
				}
			}
		}
	}
}
```
