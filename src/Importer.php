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
        switch ($this->whatToDo($blogPost)) {
            case static::CREATE_POST:
            case static::UPDATE_POST:
                $this->blogPostRepo->upsert(
                    $blogPost,
                    $this->authorRepo->upsertFrom($blogPost),
                    ...$this->blogTopicRepo->find(...$blogPost->getTopicsIds())
                );
                break;
            case static::DELETE_POST:
                $this->blogPostRepo->delete($blogPost);
                break;
        }
    }

    protected function whatToDo(BlogPost $blogPost): int
    {
        if ($blogPost->isPublished()) {
            $whatToDo = $this->blogPostRepo->isPreviouslyImported($blogPost)
                ? static::UPDATE_POST
                : static::CREATE_POST;
        } else {
            $whatToDo = $this->blogPostRepo->isPreviouslyImported($blogPost)
                ? static::DELETE_POST
                : static::DO_NOTHING;
        }

        return (int) apply_filters('wp_hubspot_importer_importer_what_to_do', $whatToDo, $blogPost);
    }
}
