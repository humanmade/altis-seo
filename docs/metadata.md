# Metadata

Website metadata provides machine readable information to search engines and other user agents such as those used by social platforms to understand how to present your website and web pages in a richer format than the standard hyperlink.

## SEO Metadata

Users can edit the title tag, meta description and meta keywords for each public page, post and taxonomy term to provide greater control over how content is interpreted by search engines and how the results are displayed on Search Engine Result Pages (SERPs).

In addition the default title tag content can be edited via the admin for each type of web page the CMS outputs too.

## Meta tags

Meta tag output is provided out of the box with some sensible defaults for [Opengraph](https://ogp.me), [Twitter cards](https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/abouts-cards) and [Schema.org](https://schema.org) (using JSON+LD).

These can be further configured in the admin via the Yoast SEO Social settings.

For social networks that support it, your social media profile URLs can be defined in the admin (via Yoast SEO Social controls) or via the Altis config.

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"metadata": {
						"social-urls": {
							"facebook": "https://facebook.com/YourCompanyProfile",
							"twitter": "YourTwitterHandle",
						},
					}
				}
			}
		}
	}
}
```

Opengraph or Twitter card meta tags can be disabled in the Altis config by passing `false` to their values in the Altis config file. The example below disables the Twitter metadata but retains the Opengraph metadata and defines the Facebook company profile URL.

```json
{
	"extra": {
		"altis": {
			"modules": {
				"seo": {
					"metadata": {
						"twitter": false,
						"social-urls": {
							"facebook": "https://facebook.com/YourCompanyProfile"
						},
					}
				}
			}
		}
	}
}
```

**Note:** Editing any of the SEO metadata values will require you to define in the configuration file any social media URLs and metadata settings you wish to use. Additionally, the Social settings page will not appear in the SEO menu as all available settings are configurable via the Altis config file.
