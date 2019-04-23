<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\Admin;

use Itineris\WPHubSpotImporter\OAuth2;
use TypistTech\WPKsesView\ViewInterface;
use TypistTech\WPOptionStore\OptionStoreInterface;

class SettingsPage
{
    protected const SLUG = 'wp-hubspot-importer';

    /** @var OAuth2 */
    protected $oAuth2;
    /** @var OptionStoreInterface */
    protected $optionStore;
    /** @var ViewInterface */
    protected $view;

    public function __construct(ViewInterface $view, OptionStoreInterface $optionStore, OAuth2 $oAuth2)
    {
        $this->view = $view;
        $this->optionStore = $optionStore;
        $this->oAuth2 = $oAuth2;
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
            $this->view->getRenderClosure((object) [
                'authenticationUrl' => $this->oAuth2->getAuthenticationUrl(),
                'hasRefreshToken' => '' !== $this->optionStore->getString('wp_hubspot_importer_refresh_token'),
            ])
        );
    }
}
