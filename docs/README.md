# SEO

![SEO banner](./assets/banner-seo.png)

## Configuration

The following JSON is the default configuration for the module and can be overridden in your project's `composer.json` file.

```json
{
    "extra": {
        "altis": {
            "modules": {
                "seo": {
                    "enabled": true,
                    "redirects": true,
                    "index": true
                }
            }
        }
    }
}
```

### Configuration Options

- **enabled** (bool): Enable or disable the SEO module. Default: `true`
- **redirects** (bool): Enable or disable the redirects functionality. Default: `true`
- **index** (bool): Allow search engines to index the site. Default: `true` for production environments, `false` for all other environments (development, staging, etc.). When set to `false`, the robots.txt file will include directives to disallow indexing.
