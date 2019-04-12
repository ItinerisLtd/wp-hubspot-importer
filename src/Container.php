<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\BlogPosts;
use SevenShores\Hubspot\Resources\OAuth2 as HubSpotOauth2;
use TypistTech\WPKsesView\Factory as ViewFactory;
use TypistTech\WPOptionStore\Factory as OptionStoreFactory;
use TypistTech\WPOptionStore\OptionStoreInterface;

class Container
{
    protected $oAuth2;
    protected $optionStore;
    protected $settingsPage;
    protected $blogPosts;
    protected $hubSpotFactory;
    protected $userRepo;
    protected $importer;
    protected $blogPostRepo;

    public static function getInstance(): Container
    {
        // TODO: Allow customization, i.e: filters.
        return new static();
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

    public function getOptionStore(): OptionStoreInterface
    {
        if (null === $this->optionStore) {
            $this->optionStore = OptionStoreFactory::build();
        }

        return $this->optionStore;
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

    public function getImporter(): Importer
    {
        if (null === $this->hubSpotFactory) {
            $this->importer = new Importer(
                $this->getBlogPostRepo(),
                $this->getUserRepo()
            );
        }

        return $this->hubSpotFactory;
    }

    protected function getBlogPostRepo(): BlogPostRepo
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

    protected function getUserRepo(): UserRepo
    {
        if (null === $this->userRepo) {
            $this->userRepo = new UserRepo();
        }

        return $this->userRepo;
    }

    public function getBlogPosts(): BlogPosts
    {
        if (null === $this->blogPosts) {
            $hubSpotFactory = $this->getHubSpotFactory();
            $this->blogPosts = $hubSpotFactory->blogPosts();
        }

        return $this->blogPosts;
    }

    protected function getHubSpotFactory(): HubSpotFactory
    {
        // TODO: Review me!
        if (null === $this->hubSpotFactory) {

            $optionStore = $this->getOptionStore();
            $oauth2 = $this->getOAuth2();

            $accessTokenExpireAt = $optionStore->getInt('wp_hubspot_importer_access_token_expire_at');
            if ($accessTokenExpireAt - time() < HOUR_IN_SECONDS) {
                $oauth2->refreshAccessToken();
            }

            // TODO: Check access token valid.
            $this->hubSpotFactory = new HubSpotFactory(
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

        return $this->hubSpotFactory;
    }
}
