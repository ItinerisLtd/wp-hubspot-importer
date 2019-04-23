<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

class BlogTopicRepo
{
    /**
     * In-memory store.
     *
     * @var BlogTopic[]
     */
    protected $store = [];

    public function add(BlogTopic $blogTopic): void
    {
        // TODO: Save to database.
        $this->store[$blogTopic->getHubSpotId()] = $blogTopic;
    }

    /**
     * @param string ...$hubSpotId
     *
     * @return BlogTopic[]
     */
    public function find(string ...$hubSpotId): array
    {
        $blogTopics = array_map(function (string $hubSpotId): ?BlogTopic {
            return $this->store[$hubSpotId] ?? null;
        }, $hubSpotId);

        return array_filter($blogTopics);
    }
}
