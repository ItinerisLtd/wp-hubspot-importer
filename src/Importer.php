<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

class Importer
{
    protected const DO_NOTHING = 0;
    protected const CREATE_POST = 1;
    protected const UPDATE_POST = 2;
    protected const DELETE_POST = 3;

    /** @var BlogPostRepo */
    protected $blogPostRepo;
    /** @var AuthorRepo */
    protected $authorRepo;

    public function __construct(BlogPostRepo $blogPostRepo, AuthorRepo $authorRepo)
    {
        $this->blogPostRepo = $blogPostRepo;
        $this->authorRepo = $authorRepo;
    }

    public function import(BlogPost $blogPost): void
    {
        switch ($this->whatToDo($blogPost)) {
            case static::CREATE_POST:
            case static::UPDATE_POST:
                $author = $this->authorRepo->upsertFrom($blogPost);
                $this->blogPostRepo->upsert($blogPost, $author);
                break;
            case static::DELETE_POST:
                $this->blogPostRepo->delete($blogPost);
                break;
        }
    }

    protected function whatToDo(BlogPost $blogPost): int
    {
        if ($blogPost->isPublished()) {
            return $this->blogPostRepo->isPreviouslyImported($blogPost)
                ? static::UPDATE_POST
                : static::CREATE_POST;
        }

        return $this->blogPostRepo->isPreviouslyImported($blogPost)
            ? static::DELETE_POST
            : static::DO_NOTHING;
    }
}
