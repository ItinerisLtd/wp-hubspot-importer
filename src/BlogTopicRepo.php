<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

class BlogTopicRepo
{
    /** @var BlogTopic[] */
    protected $store = [];

    public function add(BlogTopic $blogTopic): void
    {
        $this->store[$blogTopic->getHubSpotId()] = $blogTopic;
    }
}
