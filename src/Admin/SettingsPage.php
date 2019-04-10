<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\Admin;

use Itineris\WPHubSpotImporter\API\OAuth2;
use TypistTech\WPKsesView\ViewInterface;
use TypistTech\WPOptionStore\OptionStoreInterface;

class SettingsPage
{
    protected const SLUG = 'wp-hubspot-importer';

    /** @var OAuth2 */
    protected $oauth2;
    /** @var OptionStoreInterface */
    protected $optionStore;
    /** @var ViewInterface */
    protected $view;

    public function __construct(ViewInterface $view, OptionStoreInterface $optionStore, OAuth2 $oauth2)
    {
        $this->view = $view;
        $this->optionStore = $optionStore;
        $this->oauth2 = $oauth2;
    }

    public static function getUrl(): string
    {
        return admin_url('/tools.php?page=' . static::SLUG);
    }

    public function addManagementPage(): void
    {
        add_management_page(
            __('WP HubSpot Importer', 'wp-hubspot-importer'),
            __('WP HubSpot Importer', 'wp-hubspot-importer'),
            'manage_options',
            static::SLUG,
            // TODO: Update wp-kses-view to return closure.
            function (): void {
                // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UndefinedVariable
                $this->view->render((object) [
                    // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UndefinedVariable
                    'authenticationUrl' => $this->oauth2->getAuthenticationUrl(),
                    // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UndefinedVariable
                    'hasRefreshToken' => '' !== $this->optionStore->getString('wp_hubspot_importer_refresh_token'),
                ]);
            }
        );
    }
}
