# AMP

The [AMP project](https://amp.dev/) takes your content and delivers it via their infrastructure optimized for mobile devices and slower internet connections.

Web pages with AMP versions may also be highlighted in Google search results pages in a richer format than a regular search result listing.

Activating AMP will automatically make AMP versions of your content available. You can choose which content types to generate AMP versions for through the CMS admin and also opt-out on a per-post basis.

By default your standard theme will just work however _the default behavior is to remove any scripts_ so you will need to ensure your site degrades gracefully without JavaScript enabled.

AMP now allows for some JavaScript experience via [amp-script](https://amp.dev/documentation/guides-and-tutorials/develop/custom-javascript/).

The AMP component is provided by the [AMP plugin](https://github.com/humanmade/amp-wp), full [documentation for the plugin can be found here](https://amp-wp.org/documentation/getting-started/).

## Native mode vs Paired mode vs Classic mode

The following modes can be set from the CMS admin under the AMP menu item.

**Native mode** will override your default content URLs with the AMP version of the page. Validation errors are fixed automatically.

In **paired mode** a separate URL is used for the AMP version of your pages and your original page will be the canonical version.

Paired mode is recommended to provide the best user experience to your visitors.

**Classic mode** uses a completely separate set of templates that will require styling and additional testing to match your main theme.
