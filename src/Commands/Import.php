<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\Commands;

use Itineris\WPHubSpotImporter\BlogPost;
use Itineris\WPHubSpotImporter\BlogTopic;
use Itineris\WPHubSpotImporter\BlogTopicRepo;
use Itineris\WPHubSpotImporter\Container;
use Itineris\WPHubSpotImporter\Importer;
use SevenShores\Hubspot\Resources\BlogPosts;
use SevenShores\Hubspot\Resources\BlogTopics;
use stdClass;
use TypistTech\WPOptionStore\OptionStoreInterface;
use WP_CLI;

class Import
{
    protected const LIMIT = 20;

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

    public function __invoke(): void
    {
        WP_CLI::log('Importing from HubSpot...');

        $container = Container::getInstance();
        $this->optionStore = $container->getOptionStore();
        $this->blogPosts = $container->getBlogPosts();
        $this->importer = $container->getImporter();

        $this->blogTopics = $container->getBlogTopics();
        $this->blogTopicRepo = $container->getBlogTopicRepo();

        WP_CLI::log('Fetching HubSpot blog topics...');

        // TODO: Save topics into database. Currently, we are expecting small numbers of topics only.
        $blogTopicBatchIndex = 0;
        do {
            $remaining = $this->fetchSingleBlogTopicBatch($blogTopicBatchIndex++);
        } while ($remaining > 0);

        $lastImportedAt = $this->optionStore->getInt(static::LAST_IMPORTED_AT_OPTION_NAME);
        WP_CLI::log('Fetching HubSpot blog posts updated since ' . date(DATE_RFC2822, $lastImportedAt) . '...');

        $blogPostBatchIndex = 0;
        do {
            $remaining = $this->importSingleBlogPostBatch($lastImportedAt, $blogPostBatchIndex++);
        } while ($remaining > 0);

        $time = time();
        update_option(static::LAST_IMPORTED_AT_OPTION_NAME, time());
        WP_CLI::success('Finished at ' . date(DATE_RFC2822, $time));
    }

    protected const LAST_IMPORTED_AT_OPTION_NAME = 'wp_hubspot_importer_last_imported_at';

    protected function fetchSingleBlogTopicBatch(int $batchIndex): int
    {
        $response = $this->blogTopics->all([
            'limit' => static::LIMIT,
            'offset' => static::LIMIT * $batchIndex,
        ]);

        $data = $response->getData();

        if (200 !== $response->getStatusCode()) {
            WP_CLI::error('Failed to fetch HubSpot blog topics', false);
            WP_CLI::error(
                ($data->errorType ?? 'Unknown error type') . ': ' . ($data->message ?? 'Unknown error message')
            );
        }

        array_map(function (stdClass $original): void {
            $blogTopic = new BlogTopic($original);
            $this->blogTopicRepo->add($blogTopic);

            WP_CLI::success(
                sprintf(
                    'Fetched Blog Topic: %1$s (%2$s)',
                    $blogTopic->getName(),
                    $blogTopic->getHubSpotId()
                )
            );
        }, $data->objects);

        $total = absint($data->total ?? 0);
        $imported = static::LIMIT * ($batchIndex + 1);

        return (int) ($total - $imported);
    }

    protected function importSingleBlogPostBatch(int $lastImportedAt, int $batchIndex): int
    {
        $response = $this->blogPosts->all([
            'limit' => static::LIMIT,
            'offset' => static::LIMIT * $batchIndex,
            'updated__gt' => $lastImportedAt * 1000,
            'deleted_at__lt' => PHP_INT_MAX,
        ]);

        $data = $response->getData();

        if (200 !== $response->getStatusCode()) {
            WP_CLI::error('Failed to fetch HubSpot blog posts', false);
            WP_CLI::error(
                ($data->errorType ?? 'Unknown error type') . ': ' . ($data->message ?? 'Unknown error message')
            );
        }

        array_map(function (stdClass $original): void {
            $blogPost = new BlogPost($original);
            $this->importer->import($blogPost);

            WP_CLI::success(
                sprintf(
                    'Imported Blog Post: %1$s (%2$s)',
                    $blogPost->getPostTitle(),
                    $blogPost->getHubSpotId()
                )
            );
        }, $data->objects);

        $total = absint($data->total ?? 0);
        $imported = static::LIMIT * ($batchIndex + 1);

        return (int) ($total - $imported);
    }
}
