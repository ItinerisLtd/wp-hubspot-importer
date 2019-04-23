<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use RuntimeException;
use WP_Error;
use WP_Query;
use WP_User;

class BlogPostRepo
{
    /** @var string */
    public $topicTaxonomy;
    /** @var string */
    protected $postType;
    /** @var string */
    protected $hubspotBlogPostIdMetaKey;
    /** @var string */
    protected $hubspotFeaturedImageUrlMetaKey;
    /** @var int[] */
    protected $mapping = [];

    public function __construct(
        string $postType,
        string $hubspotBlogPostIdMetaKey,
        string $hubspotFeaturedImageUrlMetaKey,
        string $topicTaxonomy
    ) {
        $this->postType = $postType;
        $this->hubspotBlogPostIdMetaKey = $hubspotBlogPostIdMetaKey;
        $this->hubspotFeaturedImageUrlMetaKey = $hubspotFeaturedImageUrlMetaKey;
        $this->topicTaxonomy = $topicTaxonomy;
    }

    public function upsert(BlogPost $blogPost, WP_User $author, BlogTopic ...$blogTopics): void
    {
        $postId = wp_insert_post([
            'ID' => $this->getPostId($blogPost),
            'post_author' => $author->ID,
            'post_content' => $blogPost->getPostContent(),
            'post_excerpt' => $blogPost->getPostExcerpt(),
            'post_status' => 'publish',
            'post_title' => $blogPost->getPostTitle(),
            'post_type' => $this->postType,
            'post_date_gmt' => $blogPost->getPostDateGmt(),
            'post_modified_gmt' => $blogPost->getPostDateGmt(),
            'meta_input' => [
                $this->hubspotBlogPostIdMetaKey => $blogPost->getHubSpotId(),
                $this->hubspotFeaturedImageUrlMetaKey => $blogPost->getFeaturedImageUrl(),
            ],
        ]);

        if ($postId instanceof WP_Error) {
            throw new RuntimeException(
                $postId->get_error_message()
            );
        }

        $topics = array_map(function (BlogTopic $blogTopic): string {
            // TODO: Handle commas.
            return $blogTopic->getName();
        }, $blogTopics);

        wp_set_post_terms(
            $postId,
            // TODO: Handle commas.
            implode(', ', $topics),
            $this->topicTaxonomy
        );
    }

    protected function getPostId(BlogPost $blogPost): int
    {
        $hubSpotBlogPostId = $blogPost->getHubSpotId();

        if (! array_key_exists($hubSpotBlogPostId, $this->mapping)) {
            $query = new WP_Query([
                'fields' => 'ids',
                'post_type' => $this->postType,
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => $this->hubspotBlogPostIdMetaKey,
                        'value' => $hubSpotBlogPostId,
                    ],
                ],
            ]); // WPCS: slow query ok.

            $postIds = $query->get_posts();

            $postId = is_int($postIds[0])
                ? $postIds[0]
                : 0;

            $this->mapping[$hubSpotBlogPostId] = $postId;
        }

        return $this->mapping[$hubSpotBlogPostId];
    }

    public function delete(BlogPost $blogPost): void
    {
        wp_delete_post(
            $this->getPostId($blogPost),
            true
        );
    }

    public function isPreviouslyImported(BlogPost $blogPost): bool
    {
        $postId = $this->getPostId($blogPost);

        return $postId > 0;
    }
}
