<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use stdClass;

class BlogPost
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

    public function getPostContent(): string
    {
        return wp_kses_post($this->original->post_body ?? '');
    }

    public function getPostExcerpt(): string
    {
        return sanitize_text_field($this->original->post_summary ?? '');
    }

    public function getPostTitle(): string
    {
        return sanitize_text_field($this->original->page_title ?? '');
    }

    public function getPostDateGmt(): string
    {
        $timestampMilliseconds = (int) ($this->original->publish_date ?? 0);
        $timestamp = (int) floor($timestampMilliseconds / 1000);

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    public function getFeaturedImageUrl(): string
    {
        return esc_url_raw(
            $this->original->featured_image ?? '',
            [
                'http',
                'https',
            ]
        );
    }

    public function getTopics(): string
    {
        $topicIds = array_map(function ($topicId): string {
            return 'topic_' . sanitize_text_field($topicId);
        }, $this->original->topic_ids);

        return implode($topicIds, ', ');
    }

    public function getPostModifiedGmt(): string
    {
        $timestampMilliseconds = (int) ($this->original->updated ?? 0);
        $timestamp = (int) floor($timestampMilliseconds / 1000);

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    public function isPublished(): bool
    {
        return 'PUBLISHED' === sanitize_text_field($this->original->state);
    }

    public function getAuthorUsername(): string
    {
        $authorOriginal = $this->original->blog_author ?? new stdClass();
        $id = $authorOriginal->id ?? 'unknown_id';

        return sanitize_user("hubspot_${id}", true);
    }

    public function getAuthorDisplayName(): string
    {
        $authorOriginal = $this->original->blog_author ?? new stdClass();

        return sanitize_text_field($authorOriginal->display_name ?? 'Unknown Author');
    }
}
