<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\OAuth2 as HubSpotOauth2;
use TypistTech\WPKsesView\Factory as ViewFactory;
use TypistTech\WPOptionStore\Factory as OptionStoreFactory;
use TypistTech\WPOptionStore\OptionStoreInterface;

/**
 * TODO: Refactor this class!
 * TODO: Refactor to singleton!
 */
class Factory
{
    /**
     * TODO: Refactor this class!
     */
    public static function build(): array
    {
        $optionStore = OptionStoreFactory::build();

        $oauth2 = static::buildOAuth2($optionStore);
        $settingPage = static::buildSettingsPage($optionStore, $oauth2);

        return [
            'oauth2' => $oauth2,
            'optionStore' => $optionStore,
            'settingPage' => $settingPage,
        ];
    }

    /**
     * TODO: Refactor this class!
     */
    public static function buildWithRefreshingAccessToken(): array
    {
        [
            'oauth2' => $oauth2,
            'optionStore' => $optionStore,
            'settingPage' => $settingPage,
        ] = static::build();

        $hubSpotFactory = static::buildHubSpotFactory($optionStore, $oauth2);
        $blogPosts = $hubSpotFactory->blogPosts();

        return [
            'oauth2' => $oauth2,
            'optionStore' => $optionStore,
            'settingPage' => $settingPage,
            'blogPosts' => $blogPosts,
            'hubSpotFactory' => $hubSpotFactory,
        ];
    }

    protected static function buildHubSpotFactory(OptionStoreInterface $optionStore, OAuth2 $oauth2): HubSpotFactory
    {
        $accessTokenExpireAt = $optionStore->getInt('wp_hubspot_importer_access_token_expire_at');
        if ($accessTokenExpireAt - time() < HOUR_IN_SECONDS) {
            $oauth2->refreshAccessToken();
        }
        // TODO: Check access token valid.

        return new HubSpotFactory(
            [
                'key' => $optionStore->getString('wp_hubspot_importer_access_token'),
                'oauth2' => true,
            ],
            null,
            [
                'http_errors' => false,
            ]
        );
    }

    protected static function buildOAuth2(OptionStoreInterface $optionStore): OAuth2
    {
        $client = new Client(
            [
                'key' => $optionStore->getString('wp_hubspot_importer_client_secret'),
            ],
            null,
            [
                'http_errors' => false,
            ]
        );
        $oauth2 = new HubSpotOauth2($client);

        return new OAuth2($optionStore, $oauth2);
    }

    protected static function buildSettingsPage(OptionStoreInterface $optionStore, OAuth2 $oauth2): SettingsPage
    {
        $view = ViewFactory::build(__DIR__ . '/Admin/view/settings-page.php');

        return new SettingsPage($view, $optionStore, $oauth2);
    }

    /**
     * TODO: Refactor this class!
     */
    public static function buildImporter(): Importer
    {
        // TODO: Allow customization, i.e: filters.
        $blogPostRepo = new BlogPostRepo(
            'post',
            '_hubspot_blog_post_id',
            'hubspot_featured_image_url',
            'post_tag'
        );

        return new Importer(
            $blogPostRepo,
            new UserRepo()
        );
    }
}
