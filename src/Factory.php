<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use Itineris\WPHubSpotImporter\API\OAuth2;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\OAuth2 as HubSpotOauth2;
use TypistTech\WPKsesView\Factory as ViewFactory;
use TypistTech\WPOptionStore\Factory as OptionStoreFactory;
use TypistTech\WPOptionStore\OptionStoreInterface;

class Factory
{
    public static function build(): array
    {
        $optionStore = OptionStoreFactory::build();

        $oauth2 = static::buildOAuth2($optionStore);
        $settingPage = static::buildSettingsPage($optionStore, $oauth2);

        return [
            'oauth2' => $oauth2,
            'settingPage' => $settingPage,
            'optionStore' => $optionStore,
        ];
    }

    protected static function buildOAuth2(OptionStoreInterface $optionStore): OAuth2
    {
        $client = new Client([
            'key' => $optionStore->getString('wp_hubspot_importer_client_secret'),
        ]);
        $oauth2 = new HubSpotOauth2($client);

        return new OAuth2($optionStore, $oauth2);
    }

    protected static function buildSettingsPage(OptionStoreInterface $optionStore, OAuth2 $oauth2): SettingsPage
    {
        $view = ViewFactory::build(__DIR__ . '/Admin/view/settings-page.php');

        return new SettingsPage($view, $optionStore, $oauth2);
    }
}
