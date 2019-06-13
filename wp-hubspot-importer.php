<?php
/**
 * Plugin Name:     WP HubSpot Importer
 * Plugin URI:      https://github.com/ItinerisLtd/wp-hubspot-importer
 * Description:     Import HubSpot blog posts into WordPress.
 * Version:         0.2.2
 * Author:          Itineris Limited
 * Author URI:      https://itineris.co.uk
 * License:         MIT
 * License URI:     https://opensource.org/licenses/MIT
 * Text Domain:     wp-hubspot-importer
 */

declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

Plugin::run();

if (defined('WP_CLI') && WP_CLI) {
    Plugin::registerCommands();
}
