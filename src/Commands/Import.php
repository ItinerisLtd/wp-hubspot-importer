<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\Commands;

use Itineris\WPHubSpotImporter\BlogPost;
use Itineris\WPHubSpotImporter\Container;
use Itineris\WPHubSpotImporter\Importer;
use Itineris\WPHubSpotImporter\OAuth2;
use SevenShores\Hubspot\Resources\BlogPosts;
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

    public function __invoke(): void
    {
        WP_CLI::log('Importing from HubSpot...');

        $container = Container::getInstance();
        $this->optionStore = $container->getOptionStore();
        $this->blogPosts = $container->getBlogPosts();
        $this->importer = $container->getImporter();

        $lastImportedAt = $this->optionStore->getInt('wp_hubspot_importer_last_imported_at');
        WP_CLI::log('Fetching HubSpot blog posts updated since ' . date(DATE_RFC2822, $lastImportedAt));

        $batchIndex = 0;
        $imported = 0;

        do {
            [
                'total' => $total,
                'batchSize' => $batchSize,
            ] = $this->importSingleBatch($lastImportedAt, $batchIndex++);

            $imported += $batchSize;
        } while ($imported < $total);

        // TODO: update timestamp.
        // TODO: update tags info.
    }

    protected function importSingleBatch(int $lastImportedAt, int $batchIndex): array
    {
        $response = $this->blogPosts->all([
            'limit' => static::LIMIT,
            'offset' => static::LIMIT * $batchIndex,
            'updated__gt' => $lastImportedAt * 1000,
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

            WP_CLI::success('Imported: ' . $blogPost->getHubSpotId());
        }, $data->objects);

        return [
            'total' => absint($data->total_count ?? 0),
            'batchSize' => count($data->objects),
        ];
    }
}
