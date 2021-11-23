<?php
/**
 * SEO module functions.
 *
 * @package altis/seo
 */

namespace Altis\SEO;

use Altis;
use Altis\Module;
use WPSEO_Menu;
use WPSEO_Network_Admin_Menu;
use WP_CLI;
use WP_Query;
use Yoast_Network_Admin;

const YOAST_PLUGINS = [
	'wordpress-seo/wp-seo.php',
	'wordpress-seo-premium/wp-seo-premium.php',
];

/**
 * Bootstrap SEO Module.
 *
 * @param Module $module The SEO Module object.
 * @return void
 */
function bootstrap( Module $module ) {
	$settings = $module->get_settings();

	if ( $settings['redirects'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_redirects', 0 );
	}

	if ( $settings['metadata'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_metadata', 0 );

		if ( should_override_metadata_options() ) {
			add_filter( 'pre_option_wpseo_social', __NAMESPACE__ . '\\override_yoast_social_options' );

			// Remove the Yoast SEO Social page.
			add_action( 'admin_menu', function() {
				remove_submenu_page( 'wpseo_dashboard', 'wpseo_social' );
			} );

			add_action( 'admin_notices', __NAMESPACE__ . '\\social_options_overridden_notice' );
		}
	}

	if ( $settings['site-verification'] ) {
		add_action( 'muplugins_loaded', __NAMESPACE__ . '\\Site_Verification\\bootstrap' );
	}

	// Load Yoast SEO.
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_wpseo', 9 );
	if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'admin.php?page=wpseo_' ) !== false ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\add_yoast_plugins', 5 );
		add_filter( 'site_option_active_sitewide_plugins', __NAMESPACE__ . '\\active_yoast_plugins' );
	}

	// Patch network activated plugin bootstrapping manually.
	add_action( 'wpseo_loaded', __NAMESPACE__ . '\\enable_yoast_network_admin' );

	// Remove Yoast SEO dashboard widget.
	add_action( 'admin_init', __NAMESPACE__ . '\\remove_yoast_dashboard_widget' );

	// Remove the Yoast Premium submenu page.
	add_action( 'admin_init', __NAMESPACE__ . '\\remove_yoast_submenu_page' );

	// Remove Helpscout.
	add_filter( 'wpseo_helpscout_show_beacon', '__return_false' );

	// Hide the HUGE SEO ISSUE warning and disable admin bar menu.
	add_filter( 'option_wpseo', __NAMESPACE__ . '\\override_yoast_seo_options', 20 );

	// Read config/robots.txt file into robots.txt route handled by WP.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 10 );

	// Add sitemap to robots.txt.
	add_filter( 'robots_txt', __NAMESPACE__ . '\\add_sitemap_index_to_robots', 11, 2 );

	// CSS overrides.
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_yoast_css_overrides', 11 );
	add_action( 'wpseo_configuration_wizard_head', __NAMESPACE__ . '\\override_wizard_styles' );
	add_action( 'admin_head', __NAMESPACE__ . '\\hide_yoast_premium_social_previews' );

	// Intend to save indexables.
	add_filter( 'wpseo_should_save_indexable', '__return_true' );

	// Migrations.
	add_action( 'altis.migrate', __NAMESPACE__ . '\\suggest_migrations' );
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'altis migrate-seo', __NAMESPACE__ . '\\do_migrations' );
	}
}

/**
 * Get a corresponding callable for a boolean value.
 *
 * @param boolean $condition Condition to check.
 * @return callable
 */
function get_bool_callback( bool $condition ) : callable {
	return $condition ? '__return_true' : '__return_false';
}

/**
 * Load the redirects plugin.
 *
 * @return void
 */
function load_redirects() {
	require_once Altis\ROOT_DIR . '/vendor/humanmade/hm-redirects/hm-redirects.php';
}

/**
 * Checks if Yoast SEO Premium is installed.
 *
 * @return bool
 */
function is_yoast_premium() : bool {
	return class_exists( 'WPSEO_Premium' );
}

/**
 * Load Yoast SEO.
 */
function load_wpseo() {
	// Define Yoast Premium constants prior to loading the free/base version.
	if ( is_yoast_premium() ) {
		if ( ! defined( 'WPSEO_PREMIUM_FILE' ) ) {
			define( 'WPSEO_PREMIUM_FILE', Altis\ROOT_DIR . '/vendor/yoast/wordpress-seo-premium/wp-seo-premium.php' );
		}

		if ( ! defined( 'WPSEO_PREMIUM_PATH' ) ) {
			define( 'WPSEO_PREMIUM_PATH', Altis\ROOT_DIR . '/vendor/yoast/wordpress-seo-premium/' );
		}

		if ( ! defined( 'WPSEO_PREMIUM_BASENAME' ) ) {
			define( 'WPSEO_PREMIUM_BASENAME', 'wordpress-seo-premium/wp-seo-premium.php' );
		}

		// Ensure we have the plugin data function, sometimes not loaded outside of admin contexts.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once Altis\ROOT_DIR . '/wordpress/wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( WPSEO_PREMIUM_FILE );

		define( 'WPSEO_PREMIUM_VERSION', $data['Version'] );
	}

	// Load base version.
	require_once Altis\ROOT_DIR . '/vendor/yoast/wordpress-seo/wp-seo.php';

	// Bootstrap Yoast Premium if available.
	if ( is_yoast_premium() ) {
		require_once Altis\ROOT_DIR . '/vendor/yoast/wordpress-seo-premium/src/functions.php';
		YoastSEOPremium();
	}
}

/**
 * Allow Yoast to validate subscriptions by faking available plugins list.
 *
 * @return void
 */
function add_yoast_plugins() {
	$plugins = get_plugins();
	$updated_plugins = $plugins;
	$available = array_keys( $plugins );

	foreach ( YOAST_PLUGINS as $plugin_file ) {
		$plugin_path = Altis\ROOT_DIR . '/vendor/yoast/' . $plugin_file;
		if ( is_readable( $plugin_path ) && ! in_array( $plugin_file, $available, true ) ) {
			$updated_plugins[ $plugin_file ] = get_plugin_data( $plugin_path, false, false );
		}
	}

	// Append to the cached value.
	if ( count( $plugins ) < count( $updated_plugins ) ) {
		wp_cache_set( 'plugins', [ '' => $updated_plugins ], 'plugins' );
	}
}

/**
 * Filter Yoast plugins to appear active.
 *
 * @param array $active_plugins List of activated plugins.
 * @return array
 */
function active_yoast_plugins( $active_plugins ) {
	if ( ! is_array( $active_plugins ) ) {
		return $active_plugins;
	}

	foreach ( YOAST_PLUGINS as $plugin_file ) {
		$plugin_path = Altis\ROOT_DIR . '/vendor/yoast/' . $plugin_file;
		if ( is_readable( $plugin_path ) ) {
			$active_plugins[] = $plugin_file;
		}
	}

	return $active_plugins;
}

/**
 * Bootstrap network admin features of Yoast SEO.
 *
 * This is done because the plugin's built in check for whether it is network
 * activated relies on `wp_get_active_network_plugins()` which does not
 * work for plugins loaded from the vendor directory.
 *
 * @return void
 */
function enable_yoast_network_admin() {
	$network_admin = new Yoast_Network_Admin();
	$network_admin->register_hooks();
	$admin_menu = new WPSEO_Menu();
	$network_admin_menu = new WPSEO_Network_Admin_Menu( $admin_menu );
	$network_admin_menu->register_hooks();
}

/**
 * Remove the Yoast SEO dashboard widget.
 */
function remove_yoast_dashboard_widget() {
	remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );

	// This script & style are enqueued by Yoast.
	wp_dequeue_script( 'dashboard-widget' );
	wp_dequeue_style( 'wp-dashboard' );
}

/**
 * Remove the Premium submenu.
 */
function remove_yoast_submenu_page() {
	remove_submenu_page( 'wpseo_dashboard', 'wpseo_licenses' );
}

/**
 * Load the SEO metadata plugin.
 *
 * @return void
 */
function load_metadata() {
	$config = Altis\get_config()['modules']['seo']['metadata'] ?? [];
	$options = get_option( 'wpseo_social' );

	// Only add our custom Opengraph presenters if Opengraph is enabled.
	if ( ( isset( $config['opengraph'] ) && $config['opengraph'] === true ) || $options['opengraph'] ) {
		add_filter( 'wpseo_frontend_presenters', __NAMESPACE__ . '\\opengraph_presenters' );
	}
}

/**
 * Add our custom Opengraph presenters to the array of Yoast Opengraph presenters.
 *
 * @param array $presenters The array of presenters.
 *
 * @return array Updated array of presenters.
 */
function opengraph_presenters( array $presenters ) : array {
	$presenters[] = new Opengraph\Author_Presenter();
	$presenters[] = new Opengraph\Section_Presenter();
	$presenters[] = new Opengraph\Tag_Presenter();

	return $presenters;
}

/**
 * Override SEO Social options from config.
 *
 * @param array|bool $options Any options set by pre_option_* filters.
 *
 * @return array|bool The filtered option values.
 */
function override_yoast_social_options( $options ) {
	$config = Altis\get_config()['modules']['seo']['metadata'] ?? [];

	// Get Opengraph and Twitter card settings. These default to true if the config has been set but no value given to these options.
	$options['opengraph'] = $config['opengraph'] ?? true;
	$options['twitter'] = $config['twitter'] ?? true;

	$options['pinterestverify'] = $config['pinterest-verify'] ?? '';
	$options['og_default_image'] = $config['fallback-image'] ?? '';
	$options['facebook_site'] = $config['social-urls']['facebook'] ?? '';
	$options['twitter_site'] = $config['social-urls']['twitter'] ?? '';
	$options['instagram_url'] = $config['social-urls']['instagram'] ?? '';
	$options['linkedin_url'] = $config['social-urls']['linkedin'] ?? '';
	$options['google_url'] = $config['social-urls']['google'] ?? '';
	$options['myspace_url'] = $config['social-urls']['myspace'] ?? '';
	$options['pinterest_url'] = $config['social-urls']['pinterest'] ?? '';
	$options['youtube_url'] = $config['social-urls']['youtube'] ?? '';
	$options['wikipedia_url'] = $config['social-urls']['wikipedia'] ?? '';

	// These options are only used as fallbacks from the default Home and Front Page SEO options, and possibly not even then.
	$options['og_frontpage_title'] = $config['opengraph-fallback']['frontpage-title'] ?? '';
	$options['og_frontpage_desc'] = $config['opengraph-fallback']['frontpage-desc'] ?? '';
	$options['og_frontpage_image'] = $config['opengraph-fallback']['frontpage-image'] ?? $options['og_default_image']; // Fall back to the default image if the frontpage image isn't set.

	return $options;
}

/**
 * Check if we should override the metadata options.
 *
 * Compares the SEO metadata options saved in the config file with the default values. If anything has been changed, returns true.
 *
 * @return bool True if metadata values have been saved.
 */
function should_override_metadata_options() : bool {
	$config = Altis\get_config()['modules']['seo']['metadata'] ?? [];
	$default_config = apply_filters( 'altis.config.default', [] )['modules']['seo']['metadata'];

	// If the config matches the default, we aren't overriding metadata options.
	if ( empty( config_diff( $config, $default_config ) ) ) {
		return false;
	}

	// If any changes have been made to the metadata config, they will override the default options.
	return true;
}

/**
 * Determine the difference between two associative arrays by serializing the arrays and comparing them.
 *
 * @link https://stackoverflow.com/a/22355153/11710741
 *
 * @param array $config The array to compare, e.g. a config value.
 * @param array $default_config The array to compare against, e.g. the default config.
 *
 * @return array The difference between the two arrays.
 */
function config_diff( array $config, array $default_config ) : array {
	return array_map( 'unserialize',
		array_diff(
			array_map( 'serialize', $config ),
			array_map( 'serialize', $default_config )
		)
	);
}

/**
 * Override the Yoast SEO options.
 *
 * Disables the Search Engines Discouraged warning on non-production environments and the admin bar menu.
 *
 * @param mixed $options The option to retrieve.
 *
 * @return array|false The updated WPSEO options.
 */
function override_yoast_seo_options( $options ) {
	if ( ! is_array( $options ) ) {
		return $options;
	}

	$options['enable_admin_bar_menu'] = false;

	if ( Altis\get_environment_type() === 'production' ) {
		return $options;
	}

	$options['ignore_search_engines_discouraged_notice'] = true;

	return $options;
}

/**
 * Render a notice on the Yoast SEO Dashboard page that social settings have been configured in the Altis config.
 */
function social_options_overridden_notice() {
	$screen = get_current_screen();

	if ( $screen->base !== 'toplevel_page_wpseo_dashboard' ) {
		return;
	}

	// Bail if we've seen this message once already.
	if ( wp_cache_get( 'has_displayed_social_notice', 'altis.seo' ) ) {
		return;
	}

	$classes = 'notice notice-warning';
	$message = __( 'Social metadata has been set in the Altis configuration file and cannot be modified in the WordPress admin.', 'altis-seo' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $classes ), esc_html( $message ) );

	// Store a value in the cache to say that we've seen this message once.
	wp_cache_set( 'has_displayed_social_notice', true, 'altis.seo' );
}

/**
 * Add robots.txt content if file is present.
 *
 * @param string $output robots.txt file content generated by WP.
 *
 * @return string robots.txt file content including custom configuration if any.
 */
function robots_txt( string $output ) : string {
	$robots_file = Altis\ROOT_DIR . '/.config/robots.txt';

	// Legacy file will be in the `/config` dir instead of `/.config`.
	$legacy_file = Altis\ROOT_DIR . '/config/robots.txt';

	if ( file_exists( $robots_file ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$output .= "\n" . file_get_contents( $robots_file ) . "\n";
	} elseif ( file_exists( $legacy_file ) ) {
		// If the legacy-style file exists, load it, but warn.
		trigger_error( 'The "config/robots.txt" file is deprecated as of Altis 2.0. Use ".config/robots.txt" instead.', E_USER_DEPRECATED );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$output .= "\n" . file_get_contents( $legacy_file ) . "\n";
	}

	return $output;
}

/**
 * Add the Yoast SEO sitemap index to the robots.txt file.
 *
 * @param string $output The original robots.txt content.
 * @param bool $public Whether the site is public.
 *
 * @return string The filtered robots.txt content.
 */
function add_sitemap_index_to_robots( string $output, bool $public ) : string {
	if ( $public ) {
		$output .= sprintf( "Sitemap: %s\n", site_url( '/sitemap_index.xml' ) );
	}

	return $output;
}

/**
 * Check if the current admin color scheme is the Altis default.
 *
 * @return boolean
 */
function is_altis_admin_color_scheme() : bool {
	$color_scheme = get_user_option( 'admin_color' );
	if ( ! empty( $color_scheme ) && $color_scheme !== 'altis' ) {
		return false;
	}
	return true;
}

/**
 * Enqueue CSS.
 */
function enqueue_yoast_css_overrides() {
	if ( ! is_altis_admin_color_scheme() ) {
		return;
	}

	wp_enqueue_style( 'altis-seo', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/altis-seo.css', [], '2021-06-04-5' );
}

/**
 * Override the Yoast wizard styles.
 *
 * The Yoast setup wizard bails early, before our styles are loaded, but we can
 * hook into their action to load in our style overrides.
 */
function override_wizard_styles() {
	if ( ! is_altis_admin_color_scheme() ) {
		return;
	}

	wp_register_style( 'altis-seo', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/global-styles.css', [], '2021-06-04-5' );
	wp_print_styles( 'altis-seo' );
}

/**
 * Hide the social previews if Yoast Premium is not active.
 */
function hide_yoast_premium_social_previews() {
	$screen = get_current_screen();

	// Bail early if Yoast Premium is active or if we aren't on a post edit screen.
	if ( is_yoast_premium() || $screen->base !== 'post' ) {
		return;
	}

	/**
	 * This targets the 6th and 7th components panel in the Yoast
	 * sidebar, which corresponds to the Facebook and Twitter social
	 * preview buttons. If Yoast ever adds more panels to this sidebar,
	 * this will need to be updated.
	 */
	$styles = 'div.components-panel div:nth-child(6n) div.yoast.components-panel__body, div.components-panel div:nth-child(7n) div.yoast.components-panel__body {
		display: none;
	}';

	/**
	 * Hide the Social tab in the Yoast Metabox.
	 *
	 * The Google preview is in the basic SEO tab and social previews
	 * are only available for Yoast SEO Premium.
	 */
	$styles .= '.wpseo-metabox-menu .yoast-aria-tabs li:last-of-type {
		display:none;
	}';

	echo "<style>$styles</style>"; // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Suggestion migration command.
 *
 * @return void
 */
function suggest_migrations() : void {
	WP_CLI::line( WP_CLI::colorize( '%yTo migrate SEO data for Altis v7 or earlier to Yoast SEO run `wp altis migrate-seo`%n' ) );
}

/**
 * Run migration functions.
 *
 * @return void
 */
function do_migrations() : void {
	migrate_wpseo_to_yoast();
}

/**
 * Copy WPSEO settings to Yoast.
 *
 * Migration for Altis 7 or lower to 8+.
 *
 * @return void
 */
function migrate_wpseo_to_yoast() : void {
	// WP SEO to Yoast data mappings.
	$title_tag_mapping = [
		'#site_name#' => '%%sitename%%',
		'#site_description#' => '%%sitedesc%%',
		'#date_published#' => '%%date%%',
		'#date_modified#' => '%%modified%%',
		'#author#' => '%%name%%',
		'#categories#' => '%%category%%',
		'#tags#' => '%%tag%%',
		'#term_name#' => '%%term_title%%',
		'#post_type_singular_name#' => '%%pt_single%%',
		'#post_type_plural_name#' => '%%pt_plural%%',
		'#archive_date#' => '%%archive_title%%',
		'#search_term#' => '%%searchphrase%%',
		'#thumbnail_url#' => '',
	];

	$options_mapping = [
		'home_title' => 'title-home-wpseo',
		'home_description' => 'metadesc-home-wpseo',
		'archive_author_title' => 'title-author-wpseo',
		'archive_author_description' => 'metadesc-author-wpseo',
		'archive_date_title' => 'title-archive-wpseo',
		'archive_date_description' => 'metadesc-archive-wpseo',
		'search_title' => 'title-search-wpseo',
		'404_title' => 'title-404-wpseo',
	];

	$meta_mapping = [
		'_meta_title' => '_yoast_wpseo_title',
		'_meta_description' => '_yoast_wpseo_metadesc',
		'_meta_keywords' => '_yoast_wpseo_focuskw',
	];

	// Begin processing.
	$sites_count = get_sites( [
		'count' => true,
		'number' => -1,
	] );
	$sites_processed = 0;
	$sites_page = 0;

	while ( $sites_processed < $sites_count ) {

		// Get sites collection.
		$sites = get_sites( [
			'number' => 100,
			'offset' => $sites_page * 100,
			'fields' => 'ids',
		] );

		// Set next page.
		$sites_page++;

		foreach ( $sites as $site_id ) {

			switch_to_blog( $site_id );

			WP_CLI::log( sprintf( 'Migrating legacy SEO settings for site %d...', $site_id ) );

			// Handle options.
			$options = get_option( 'wp-seo' );
			if ( ! empty( $options ) && is_array( $options ) ) {
				$yoast_options = get_option( 'wpseo_titles' );
				if ( empty( $yoast_options ) || ! is_array( $yoast_options ) ) {
					$yoast_options = [];
				}

				foreach ( $options as $name => $value ) {
					// Ignore if empty.
					if ( empty( $value ) ) {
						continue;
					}

					// Replace variables with Yoast equivalents.
					$value = str_replace(
						array_keys( $title_tag_mapping ),
						array_values( $title_tag_mapping ),
						$value
					);

					// Replace remaining hash delimited words with double percents (tag names are the same).
					$value = preg_replace( '/(?:#([^#\s]+)#)/', '%%$1%%', $value );

					// Check how to map this value.
					if ( isset( $options_mapping[ $name ] ) ) {
						$yoast_options[ $options_mapping[ $name ] ] = $value;
					} elseif ( preg_match( '/^(single|archive)_([a-z_-]+)_(title|description)$/', $name, $matches ) ) {
						// Dynamic item.
						$prefix = $matches[3] === 'title' ? 'title' : 'metadesc';
						if ( $matches[1] === 'single' ) {
							$yoast_options[ $prefix . '-' . $matches[2] ] = $value;
						} else {
							if ( taxonomy_exists( $matches[2] ) ) {
								$yoast_options[ $prefix . '-tax-' . $matches[2] ] = $value;
							}
							if ( post_type_exists( $matches[2] ) ) {
								$yoast_options[ $prefix . '-ptarchive-' . $matches[2] ] = $value;
							}
						}
					}
				}

				// Update Yoast options.
				update_option( 'wpseo_titles', $yoast_options );
				delete_option( 'wp-seo' );
			}

			// Ensure queries are not modified and go via the db, prevents ElasticPress integration etc...
			remove_all_actions( 'pre_get_posts' );

			// Handle post meta.
			foreach ( array_keys( $meta_mapping ) as $meta_key ) {
				$posts_query_args = [
					// Get all public post types, even those with exclude_from_search (so "any" is not appropriate https://core.trac.wordpress.org/ticket/17592).
					'post_type' => get_post_types( [ 'public' => true ] ),
					'post_status' => 'any',
					'posts_per_page' => 1,
					'fields' => 'ids',
					'suppress_filters' => true,
					'meta_query' => [
						[
							'key' => $meta_key,
							'compare' => 'EXISTS',
						],
					],
				];
				$posts = new WP_Query( $posts_query_args );

				if ( $posts->found_posts > 0 ) {
					$posts_query_args['posts_per_page'] = 100;
					$posts_query_args['paged'] = 1;

					$progress = WP_CLI\Utils\make_progress_bar( sprintf( 'Copying %s data', $meta_key ), $posts->found_posts );

					while ( $posts_query_args['paged'] <= $posts->max_num_pages ) {

						$posts = new WP_Query( $posts_query_args );
						$posts_query_args['paged']++;

						foreach ( $posts->posts as $post_id ) {
							foreach ( $meta_mapping as $old => $new ) {
								// Copy post meta data over.
								$value = get_post_meta( $post_id, $old, true );
								// Handle keywords, Yoast only accepts 1 focus keyword by default so use the 1st.
								if ( $old === '_meta_keywords' ) {
									$value = explode( ',', $value );
									$value = array_map( 'trim', $value );
									$value = array_shift( $value );
								}
								// Update meta data if Yoast data not present.
								if ( ! empty( $value ) && empty( get_post_meta( $post_id, $new, true ) ) ) {
									update_post_meta( $post_id, $new, $value );
								}
								// Remove old meta data to prevent reprocessing.
								delete_post_meta( $post_id, $old );
							}

							$progress->tick();
						}

						// Prevent object cache consuming too much memory.
						WP_CLI\Utils\wp_clear_object_cache();
					}

					$progress->finish();
				}
			}

			restore_current_blog();

			// Site processed.
			$sites_processed++;
		}
	}
}
