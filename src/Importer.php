<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

class Importer
{
    public const DO_NOTHING = 0;
    public const CREATE_POST = 1;
    public const UPDATE_POST = 2;
    public const DELETE_POST = 3;

    /** @var BlogPostRepo */
    protected $blogPostRepo;
    /** @var AuthorRepo */
    protected $authorRepo;
    /** @var BlogTopicRepo */
    protected $blogTopicRepo;

    public function __construct(BlogPostRepo $blogPostRepo, AuthorRepo $authorRepo, BlogTopicRepo $blogTopicRepo)
    {
        $this->blogPostRepo = $blogPostRepo;
        $this->authorRepo = $authorRepo;
        $this->blogTopicRepo = $blogTopicRepo;
    }

    public function import(BlogPost $blogPost): void
    {
        $this->blogPostRepo->upsert(
            $blogPost,
            $this->authorRepo->upsertFrom($blogPost),
            ...$this->blogTopicRepo->find(...$blogPost->getTopicsIds())
        );
    }
}
