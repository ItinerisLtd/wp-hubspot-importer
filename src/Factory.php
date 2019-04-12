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

class Factory
{
    protected $oAuth2;
    protected $optionStore;
    protected $settingsPage;
    protected $blogPosts;
    protected $hubSpotFactory;
    protected $userRepo;

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

    public function getSettingsPage(): SettingsPage
    {
        if (null === $this->settingsPage) {
            $this->settingsPage = new SettingsPage(
                ViewFactory::build(__DIR__ . '/Admin/view/settings-page.php'),
                $this->getOptionStore(),
                $this->getOAuth2()
            );
        }

        return $this->settingsPage;
    }

    public function getOAuth2(): OAuth2
    {
        if (null === $this->oAuth2) {
            $optionStore = $this->getOptionStore();

            $client = new Client(
                [
                    'key' => $optionStore->getString('wp_hubspot_importer_client_secret'),
                ],
                null,
                [
                    'http_errors' => false,
                ]
            );

            return new OAuth2(
                $optionStore,
                new HubSpotOauth2($client)
            );
        }

        return $this->oAuth2;
    }

    public function getOptionStore(): OptionStoreInterface
    {
        if (null === $this->optionStore) {
            $this->optionStore = OptionStoreFactory::build();
        }

        return $this->optionStore;
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

    public function getImporter(): Importer
    {
        if (null === $this->blogPostRepo) {
            $this->importer = new Importer(
                $this->getBlogPostRepo(),
                $this->getUserRepo()
            );
        }

        return $this->importer;
    }

    protected $importer;
    protected $blogPostRepo;

    public function getBlogPostRepo(): BlogPostRepo
    {
        if (null === $this->blogPostRepo) {
            // TODO: Allow customization, i.e: filters.
            $this->blogPostRepo = new BlogPostRepo(
                'post',
                '_hubspot_blog_post_id',
                'hubspot_featured_image_url',
                'post_tag'
            );
        }

        return $this->blogPostRepo;
    }

    public function getUserRepo(): UserRepo
    {
        if (null === $this->userRepo) {
            $this->userRepo = new UserRepo();
        }

        return $this->userRepo;
    }
}
