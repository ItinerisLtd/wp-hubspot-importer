<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use Itineris\WPHubSpotImporter\API\OAuth2;
use Itineris\WPHubSpotImporter\Commands\Verify;
use WP_CLI;

class Plugin
{
    public static function run(): void
    {
        [
            'oauth2' => $oauth2,
            'settingPage' => $settingPage,
        ] = Factory::build();

        /** @var SettingsPage $settingPage */
        add_action('admin_menu', function () use ($settingPage): void {
            $settingPage->addManagementPage();
        });

        /** @var OAuth2 $oauth2 */
        add_action('wp', function () use ($oauth2): void {
            $action = null;

            if (isset($_GET['wp-hubspot-importer-action'])) { // WPCS: Input var ok.
                $action = sanitize_text_field(
                    wp_unslash($_GET['wp-hubspot-importer-action'])
                ); // WPCS: CSRF, Input var okay.
            }

            if ('authentication-callback' === $action) {
                $oauth2->handleAuthenticationCallback();
            }
        });
    }

    public static function registerCommands(): void
    {
        WP_CLI::add_command('hubspot:verify', [Verify::class, '__invoke']);
    }
}
