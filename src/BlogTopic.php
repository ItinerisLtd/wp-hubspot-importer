<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use stdClass;

class BlogTopic
{
    /** @var stdClass */
    protected $original;

    public function __construct(stdClass $original)
    {
        $this->original = $original;
    }

    public function getHubSpotId(): string
    {
        return sanitize_text_field($this->original->id);
    }

    public function getName(): string
    {
        return sanitize_text_field($this->original->name);
    }
}
