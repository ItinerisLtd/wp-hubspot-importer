<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\BlogPosts;
use SevenShores\Hubspot\Resources\BlogTopics;
use SevenShores\Hubspot\Resources\OAuth2 as HubSpotOauth2;
use TypistTech\WPKsesView\Factory as ViewFactory;
use TypistTech\WPOptionStore\Factory as OptionStoreFactory;
use TypistTech\WPOptionStore\OptionStoreInterface;
use UnexpectedValueException;

/**
 * TODO: Find a better alternative, maybe `typisttech/wp-contained-hook`.
 */
class Container
{
    public const BLOG_POST_POST_TYPE = 'post';
    public const HUBSPOT_BLOG_POST_ID_META_KEY = '_hubspot_blog_post_id';
    public const HUBSPOT_FEATURED_IMAGE_URL_META_KEY = 'hubspot_featured_image_url';
    /**
     * Must be non-hierarchical.
     */
    public const TOPIC_TAXONOMY = 'post_tag';

    /** @var Container */
    protected static $instance;
    /** @var SettingsPage */
    protected $settingsPage;
    /** @var HubSpotFactory */
    protected $hubSpotFactory;
    /** @var AuthorRepo */
    protected $authorRepo;
    /** @var BlogPostRepo */
    protected $blogPostRepo;
    /** @var OAuth2 */
    protected $oAuth2;
    /** @var OptionStoreInterface */
    protected $optionStore;
    /** @var BlogPosts */
    protected $blogPosts;
    /** @var Importer */
    protected $importer;
    /** @var BlogTopicRepo */
    protected $blogTopicRepo;
    /** @var BlogTopics */
    protected $blogTopics;

    public static function getInstance(): self
    {
        if (null === static::$instance) {
            $instance = apply_filters(
                'wp_hubspot_importer_container_init',
                new static()
            );

            if (! $instance instanceof self) {
                $message = sprintf(
                    'Filter "%1$s" should return an instance of "%2$s", instance of "%3$s" given.',
                    'wp_hubspot_importer_container_init',
                    __CLASS__,
                    is_object($instance) ? get_class($instance) : gettype($instance)
                );

                throw new UnexpectedValueException($message);
            }
            static::$instance = $instance;
        }

        return static::$instance;
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
        if (null === $this->importer) {
            $this->importer = new Importer(
                $this->getBlogPostRepo(),
                $this->getAuthorRepo(),
                $this->getBlogTopicRepo()
            );
        }

        return $this->importer;
    }

    protected function getBlogPostRepo(): BlogPostRepo
    {
        if (null === $this->blogPostRepo) {
            $this->blogPostRepo = new BlogPostRepo(
                static::BLOG_POST_POST_TYPE,
                static::HUBSPOT_BLOG_POST_ID_META_KEY,
                static::HUBSPOT_FEATURED_IMAGE_URL_META_KEY,
                static::TOPIC_TAXONOMY
            );
        }

        return $this->blogPostRepo;
    }

    protected function getAuthorRepo(): AuthorRepo
    {
        if (null === $this->authorRepo) {
            $this->authorRepo = new AuthorRepo();
        }

        return $this->authorRepo;
    }

    public function getBlogTopicRepo(): BlogTopicRepo
    {
        if (null === $this->blogTopicRepo) {
            $this->blogTopicRepo = new BlogTopicRepo();
        }

        return $this->blogTopicRepo;
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
            $oAuth2 = $this->getOAuth2();

            $accessTokenExpireAt = $optionStore->getInt('wp_hubspot_importer_access_token_expire_at');
            if ($accessTokenExpireAt - time() < HOUR_IN_SECONDS) {
                $oAuth2->refreshAccessToken();
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

    public function getBlogTopics(): BlogTopics
    {
        if (null === $this->blogTopics) {
            $hubSpotFactory = $this->getHubSpotFactory();
            $this->blogTopics = $hubSpotFactory->blogTopics();
        }

        return $this->blogTopics;
    }
}
