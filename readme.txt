=== Smart Sitemap Generator ===
Contributors: optimisthub, fatih-toprak
Tags: sitemap, xml sitemap, google sitemap, bing sitemap, yandex sitemap, seo
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate XML sitemaps and a sitemap index for your posts, pages and custom post types. Search-engine ready and fast.

== Description ==

Smart Sitemap Generator creates XML sitemaps for your content and a sitemap index that points to all of them, so you can submit a single URL to Google, Bing and Yandex.

Sitemaps are written as static files, so serving them costs almost nothing and they are generated without slowing down your visitors.

**Features**

* Generates a sitemap for each post type you select
* Builds a sitemap index (`sitemap-index.xml`) linking them all
* Includes `lastmod`, `changefreq` and `priority` for every URL
* Splits large sites into multiple sitemap files of 2,000 URLs each
* Rebuilds automatically when you publish or update content
* Rebuilds on a schedule you choose: 24 hours, 1 week, 15 days or 30 days
* Regenerates immediately when you save the settings
* Output validates against the official sitemaps.org schema
* Sitemaps are stored in `wp-content/uploads/sitemaps/`, which is writable on normal hosting
* Adds `Options -Indexes` to the sitemap directory to prevent directory listing
* Removes all generated files when you uninstall the plugin

**Where are my sitemaps?**

After activation the index is available at:

`
https://example.com/wp-content/uploads/sitemaps/sitemap-index.xml
`

Submit that URL to Google Search Console, Bing Webmaster Tools and Yandex Webmaster.

== Installation ==

### INSTALL "Smart Sitemap Generator" FROM WITHIN WORDPRESS

1. Visit the plugins page within your dashboard and select 'Add New';
2. Search for 'Smart Sitemap Generator';
3. Activate Smart Sitemap Generator from your Plugins page;
4. Go to Settings > Smart Sitemap to choose your post types and regeneration interval.

### INSTALL "Smart Sitemap Generator" MANUALLY

1. Upload the 'smart-sitemap-generator' folder to the /wp-content/plugins/ directory;
2. Activate the Smart Sitemap Generator through the 'Plugins' menu in WordPress;
3. Go to Settings > Smart Sitemap to configure it.

== Frequently Asked Questions ==

= Where do I find the sitemap URL? =

It is shown at the top of the plugin settings page under Settings > Smart Sitemap.

= Can I use this with Yoast SEO, Rank Math or All in One SEO? =

Yes. If another plugin already generates sitemaps, simply do not submit this one, or turn this plugin's generation off. Running both is harmless but redundant.

= Which post types are included? =

Only the public post types you tick in the settings. Attachments are excluded on purpose, since media files are not addressable content pages.

= How do I customise priority or changefreq? =

Use the `smartsitemap_priority` and `smartsitemap_changefreq` filters:

`
add_filter( 'smartsitemap_priority', function ( $priority, $post ) {
    return 'page' === $post->post_type ? '1.0' : $priority;
}, 10, 2 );
`

= The sitemaps are not regenerating. What should I check? =

Make sure WP-Cron is working. On low-traffic sites or sites with `DISABLE_WP_CRON` set to true, call `wp-cron.php` from a real server cron job. You can also save the settings page to force an immediate rebuild.

= Does it work on multisite? =

Yes. Each site generates its own sitemaps inside its own uploads directory.

== Changelog ==

= 2.0.0 =

**Fixed**

* Fixed a fatal error that made the plugin completely unusable. The `data_get()` helper came from `rappasoft/laravel-helpers`, which requires Laravel's `illuminate/support` package. That package was never bundled, so `data_get()` was undefined. The plugin now has no Laravel dependency.
* Fixed a fatal `TypeError` on every post save: the `save_post` callback was registered with three accepted arguments but declared none.
* Fixed `strtotime()` being called with a string as its second argument (the timezone offset), which throws a `TypeError` on PHP 8.
* Fixed the `settings_fields()` call using a settings group that was never registered, which broke saving the settings page.
* Fixed the post type checkbox field throwing a warning when no post types had been saved yet.
* Fixed sitemap URLs being built by slicing file paths, which produced wrong URLs. They are now built from the uploads base URL.
* Fixed `lastmod` being taken from `post_date` in local time; it now uses the correct GMT timestamp in ISO 8601 format.
* Fixed the sitemap index pointing at itself.

**Changed**

* Sitemaps are now written to `wp-content/uploads/sitemaps/` instead of `ABSPATH/sitemaps/`. The web root is not writable on many hosts.
* Rewritten as a namespaced, PSR-4 autoloaded plugin (`OptimistHub\SmartSitemap`). The old `includes/` classes are gone.
* The plugin classes are now actually loaded. The previous `index.php` only required the Composer autoloader, and the main class was never instantiated by any code path.
* Rebuilding is now debounced. A bulk edit regenerates the sitemaps once at the end of the request instead of once per post.
* Sitemaps are split into files of 2,000 URLs, matching the documented `setMaxUrls` behaviour, instead of silently generating one file while configured for 1,000.
* Added a real `uninstall.php` that removes options, the cron event and all generated files.
* Added `composer.json`, which was previously excluded by `.gitignore` and caused the broken dependency tree.
* Removed the unused XSL stylesheet assets that no code referenced.
* Regeneration now runs on a real daily cron event plus the configurable interval check, rather than on every single page load in `init`, which regenerated all sitemaps on every request.
* Requires WordPress 6.0 and PHP 7.4 or newer.

= 1.0.01 =

* Plugin Github url changed.

= 1.0.0 =

* Stable version released

== Upgrade Notice ==

= 2.0.0 =

Critical fix. Version 1.0.0 throws a fatal error on activation and could not generate sitemaps at all. Upgrading is strongly recommended.
