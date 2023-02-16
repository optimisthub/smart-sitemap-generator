<?php

/**
 * Plugin Name:     Smart Sitemap Generator
 * Plugin URI:      https://github.com/optimisthub/smart-sitemap-generator
 * Description:     Generate Sitemaps for Google, Yandex, Bing and other search engines.
 * Author:          optimisthub
 * Author URI:      https://optimisthub.com
 * Text Domain:     smart-sitemap-generator
 * Version:         1.0.01
 * Requires at least: 5.0
 * Tested up to: 6.1.1
 * Requires PHP: 7.1
 * License: GPLv2
 */
 
defined( 'ABSPATH' ) || exit;

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

 