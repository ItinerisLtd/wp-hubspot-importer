<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use RuntimeException;
use stdClass;
use WP_CLI;
use WP_Error;
use WP_Query;
use WP_User;

/**
 * TODO: Extract import class.
 */
class BlogPost
{
    protected const POST_TYPE = 'post';
    protected const TOPIC_TAXONOMY = 'post_tag';
    protected const HUBSPOT_BLOG_POST_ID_META_KEY = 'hubspot_blog_post_id';
    protected const HUBSPOT_FEATURED_IMAGE_URL_META_KEY = 'hubspot_blog_post_featured_image_url';
    protected const DO_NOTHING = 0;
    protected const CREATE_POST = 1;
    protected const UPDATE_POST = 2;
    protected const DELETE_POST = 3;

    /** @var stdClass */
    protected $original;
    /** @var int */
    protected $postId;

    public function __construct(stdClass $original)
    {
        $this->original = $original;
    }

    public function import(): void
    {
        switch ($this->whatToDo()) {
            case static::CREATE_POST:
                $this->createPost();
                break;
            case static::UPDATE_POST:
                $this->updatePost();
                break;
            case static::DELETE_POST:
                $this->deletePost();
                break;
        }

        // TODO.
        WP_CLI::success('Imported: ' . $this->getHubSpotBlogPostId());
    }

    protected function whatToDo(): int
    {
        $isPublished = 'PUBLISHED' === sanitize_text_field($this->original->state);

        if ($isPublished) {
            return $this->isPreviouslyImported()
                ? static::UPDATE_POST
                : static::CREATE_POST;
        }

        return $this->isPreviouslyImported()
            ? static::DELETE_POST
            : static::DO_NOTHING;
    }

    protected function isPreviouslyImported(): bool
    {
        $postId = $this->getPostId();
        return $postId > 0;
    }

    protected function getPostId(): int
    {
        if (null === $this->postId) {
            $query = new WP_Query([
                'fields' => 'ids',
                'post_type' => static::POST_TYPE,
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => static::HUBSPOT_BLOG_POST_ID_META_KEY,
                        'value' => $this->getHubSpotBlogPostId(),
                    ],
                ],
            ]); // WPCS: slow query ok.

            $postIds = $query->get_posts();

            $this->postId = is_int($postIds[0])
                ? $postIds[0]
                : 0;
        }

        return $this->postId;
    }

    protected function getHubSpotBlogPostId(): string
    {
        return sanitize_text_field($this->original->id);
    }

    /**
     * TODO: Should this be refactor as `createOrUpdatePost()`?
     */
    protected function createPost(): void
    {
        // This is because `$postId === 0` triggers `wp_insert_post` to create new posts.
        $this->updatePost();
    }

    protected function updatePost(): void
    {
        $author = $this->createOrUpdateAuthor();

        $postId = wp_insert_post([
            'ID' => $this->getPostId(),
            'post_author' => $author->ID,
            'post_content' => $this->getPostContent(),
            'post_excerpt' => $this->getPostExcerpt(),
            'post_status' => 'publish',
            'post_title' => $this->getPostTitle(),
            'post_type' => static::POST_TYPE,
            'post_date_gmt' => $this->getPostDateGmt(),
            'post_modified_gmt' => $this->getPostDateGmt(),
            'meta_input' => [
                static::HUBSPOT_BLOG_POST_ID_META_KEY => $this->getHubSpotBlogPostId(),
                static::HUBSPOT_FEATURED_IMAGE_URL_META_KEY => $this->getFeaturedImageUrl(),
            ],
        ]);

        if ($postId instanceof WP_Error) {
            throw new RuntimeException(
                $postId->get_error_message()
            );
        }

        wp_set_post_terms(
            $postId,
            $this->getTopics(),
            static::TOPIC_TAXONOMY
        );
    }

    protected function createOrUpdateAuthor(): WP_User
    {
        // TODO: Extract to its own class.
        $authorOriginal = $this->original->blog_author;
        $username = sanitize_user('hubspot_' . $authorOriginal->id, true);
        $displayName = sanitize_text_field($authorOriginal->display_name);

        $args = [
            'user_login' => wp_slash($username),
            'user_nicename' => wp_slash($displayName),
            'display_name' => wp_slash($displayName),
            'nickname' => wp_slash($displayName),
        ];

        $user = get_user_by('login', $username);
        if ($user instanceof WP_User) {
            // Update user info.
            $args['ID'] = $user->ID;
        } else {
            // Create new user.
            $args['user_pass'] = wp_generate_password(64);
        }

        // TODO: Skip unnecessary SQL query.
        $userId = wp_insert_user($args);

        if ($userId instanceof WP_Error) {
            throw new RuntimeException(
                $userId->get_error_message()
            );
        }

        return new WP_User($userId);
    }

    protected function getPostContent(): string
    {
        return wp_kses_post($this->original->post_body ?? '');
    }

    protected function getPostExcerpt(): string
    {
        return sanitize_text_field($this->original->post_summary ?? '');
    }

    protected function getPostTitle(): string
    {
        return sanitize_text_field($this->original->page_title ?? '');
    }

    protected function getPostDateGmt(): string
    {
        $timestampMilliseconds = (int) ($this->original->publish_date ?? 0);
        $timestamp = (int) floor($timestampMilliseconds / 1000);

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    protected function getFeaturedImageUrl(): string
    {
        return esc_url_raw(
            $this->original->featured_image ?? '',
            [
                'http',
                'https',
            ]
        );
    }

    protected function getTopics(): string
    {
        $topicIds = array_map(function ($topicId): string {
            return 'topic_' . sanitize_text_field($topicId);
        }, $this->original->topic_ids);

        return implode($topicIds, ', ');
    }

    protected function deletePost(): void
    {
        wp_delete_post(
            $this->getPostId(),
            true
        );
    }

    protected function getPostModifiedGmt(): string
    {
        $timestampMilliseconds = (int) ($this->original->updated ?? 0);
        $timestamp = (int) floor($timestampMilliseconds / 1000);

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
