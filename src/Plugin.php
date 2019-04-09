<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use Itineris\WPHubSpotImporter\API\OAuth2;

class Plugin
{
    public static function run(): void
    {
        [
            'oauth2' => $oauth2,
            'settingPage' => $settingPage,
        ] = Factory::build();

        // TODO: Throw error when constants not set!

        /** @var SettingsPage $settingPage */
        add_action('admin_menu', [$settingPage, 'addManagementPage']);

        /** @var OAuth2 $oauth2 */
        add_action('wp', function () use ($oauth2): void {
            $action = null;
            if (isset($_GET['wp-hubspot-importer-action'])) {
                $action = sanitize_text_field(wp_unslash($_GET['wp-hubspot-importer-action']));
            }

            if ('authentication-callback' === $action) {
                $oauth2->handleAuthenticationCallback();
            }
        });
    }
}
