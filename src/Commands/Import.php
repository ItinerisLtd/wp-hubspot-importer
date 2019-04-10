<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\Commands;

use Itineris\WPHubSpotImporter\API\OAuth2;
use Itineris\WPHubSpotImporter\BlogPost;
use Itineris\WPHubSpotImporter\Factory;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use SevenShores\Hubspot\Resources\BlogPosts;
use stdClass;
use TypistTech\WPOptionStore\OptionStoreInterface;
use WP_CLI;

class Import
{
    public function __invoke(): void
    {
        WP_CLI::log('Importing from HubSpot...');

        [
            'oauth2' => $oauth2,
            'optionStore' => $optionStore,
        ] = Factory::build();

        /** @var OptionStoreInterface $optionStore */
        $accessTokenExpireAt = $optionStore->getInt('wp_hubspot_importer_access_token_expire_at');
        if ($accessTokenExpireAt - time() < HOUR_IN_SECONDS) {
            WP_CLI::log('Refreshing access token...');

            /** @var OAuth2 $oauth2 */
            $oauth2->refreshAccessToken();
        }

        // TODO: Check access token valid.
        $factory = new HubSpotFactory(
            [
                'key' => $optionStore->getString('wp_hubspot_importer_access_token'),
                'oauth2' => true,
            ],
            null,
            [
                'http_errors' => false,
            ]
        );

        $lastImportedAt = $optionStore->getInt('wp_hubspot_importer_last_imported_at');

        WP_CLI::log('Fetching HubSpot blog posts updated since ' . date(DATE_RFC2822, $lastImportedAt));

        $blogPosts = $factory->blogPosts();

        $batchIndex = 0;
        $imported = 0;

        do {
            [
                'total' => $total,
                'batchSize' => $batchSize,
            ] = $this->importSingleBatch($blogPosts, $lastImportedAt, $batchIndex++);

            $imported += $batchSize;
        } while ($imported < $total);

        // TODO: update timestamp.

        // TODO: update tags info.
    }

    protected const LIMIT = 1;

    protected function importSingleBatch(BlogPosts $blogPosts, int $lastImportedAt, int $batchIndex): array
    {
        WP_CLI::success('$batchIndex' . $batchIndex);

        $response = $blogPosts->all([
            'limit' => static::LIMIT,
            'offset' => static::LIMIT * $batchIndex,
            'updated__gt' => $lastImportedAt * 1000,
        ]);

        if (200 !== $response->getStatusCode()) {
            WP_CLI::error('Failed to fetch HubSpot blog posts', false);
            WP_CLI::error($response->errorType . ': ' . $response->message);
        }

        $data = $response->getData();

        array_map(function (stdClass $original): void {
            $blogPost = new BlogPost($original);
            $blogPost->import();
        }, $data->objects);

        return [
            'total' => absint($data->total_count ?? 0),
            'batchSize' => count($data->objects),
        ];
    }
}
