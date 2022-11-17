# Redirects

Redirects have many uses, not just for SEO and marketing but also for migrating sites or moving content around.
Altis offers multiple solutions for redirects:

1. To redirect HTTP requests that hit the web application, Altis incorporates the [HM-Redirects plugin](https://github.com/humanmade/hm-redirects) (this page). These are updatable within the CMS.
2. To manage HTTP requests that would result in NGINX 404 pages, because they do not reach WordPress, additional webserver configuration is required. [More info](https://docs.altis-dxp.com/cloud/nginx-configuration/). 
3. Requests to media assets are not affected by the above and cannot be redirected.

## HM-Redirects

The redirects component is architected for performance and scale by using indexed data in the CMS database. They can be exported and imported using standard WXR format files.

## Whitelisting redirect hosts

For security purposes redirects only work within the current host name by default. To redirect to other domains external to the current host use the following filter:

```php
add_filter( 'allowed_redirect_hosts', function ( array $hosts ) : array {
	$hosts[] = 'example.com';
	return $hosts;
}, 10 );
```

## CLI commands

**`wp hm-redirects find-domains`**

Extracts a list of unique target domains from your existing redirects. This is useful for populating the `allowed_redirect_hosts` filter.

**`wp hm-redirects insert-redirect <from-url> <to-url>`**

Adds a redirect rule.

**`wp hm-redirects import-from-csv --csv=<path-to-csv> [--verbose]`**

Imports redirects from a CSV file. The file must have 2 columns, column 1 being the "from" URL and column 2 being to the "to" URL.

The "to" URL can be a full URL, a relative path or an existing post ID.

**`wp hm-redirects import-from-meta --meta_key=<name-of-meta-key> [--start=<start-offset>] [--end=<end-offset>] [--dry_run] [--verbose]`**

If you have existing redirect URLs or paths stored as meta data on the posts they should redirect to you can use this command to migrate to the more performant structure.
