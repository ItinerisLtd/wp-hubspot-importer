<?php
/**
 * Plugin Name:     WP HubSpot Importer
 * Plugin URI:      https://github.com/ItinerisLtd/wp-hubspot-importer
 * Description:     TODO.
 * Version:         0.0.0
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
