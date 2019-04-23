<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use Itineris\WPHubSpotImporter\Commands\Import;
use Itineris\WPHubSpotImporter\Commands\Verify;
use WP_CLI;

class Plugin
{
    public static function run(): void
    {
        add_action('admin_menu', function (): void {
            $container = Container::getInstance();
            $settingPage = $container->getSettingsPage();
            $settingPage->addManagementPage();
        });

        // TODO: Refactor!
        // TODO: Find a way not to run it on every page load.
        add_action('wp', function (): void {
            $action = null;

            if (isset($_GET['wp-hubspot-importer-action'])) { // WPCS: Input var ok.
                $action = sanitize_text_field(
                    wp_unslash($_GET['wp-hubspot-importer-action'])
                ); // WPCS: CSRF, Input var okay.
            }

            if ('authentication-callback' === $action) {
                $container = Container::getInstance();
                $oAuth2 = $container->getOAuth2();
                $oAuth2->handleAuthenticationCallback();
            }
        });
    }

    public static function registerCommands(): void
    {
        WP_CLI::add_command('hubspot verify', Verify::class);
        WP_CLI::add_command('hubspot import', Import::class);
    }
}
