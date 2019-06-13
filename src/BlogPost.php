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
        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_hubspot_id',
            sanitize_text_field($this->original->id),
            $this
        );
    }

    public function getPostContent(): string
    {
        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_post_content',
            wp_kses_post($this->original->post_body ?? ''),
            $this
        );
    }

    public function getPostExcerpt(): string
    {
        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_post_excerpt',
            sanitize_text_field($this->original->post_summary ?? ''),
            $this
        );
    }

    public function getPostTitle(): string
    {
        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_post_title',
            sanitize_text_field($this->original->page_title ?? ''),
            $this
        );
    }

    public function getPostDateGmt(): string
    {
        $timestampMilliseconds = (int) ($this->original->publish_date ?? 0);
        $timestamp = (int) floor($timestampMilliseconds / 1000);

        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_post_date_gmt',
            gmdate('Y-m-d H:i:s', $timestamp),
            $this
        );
    }

    public function getFeaturedImageUrl(): string
    {
        $url = esc_url_raw(
            $this->original->featured_image ?? '',
            [
                'http',
                'https',
            ]
        );

        return (string) apply_filters('wp_hubspot_importer_blog_post_featured_image_url', $url, $this);
    }

    public function getTopicsIds(): array
    {
        return (array) apply_filters(
            'wp_hubspot_importer_blog_post_topics_ids',
            array_map('sanitize_text_field', $this->original->topic_ids),
            $this
        );
    }

    public function getPostModifiedGmt(): string
    {
        $timestampMilliseconds = (int) ($this->original->updated ?? 0);
        $timestamp = (int) floor($timestampMilliseconds / 1000);

        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_post_modified_gmt',
            gmdate('Y-m-d H:i:s', $timestamp),
            $this
        );
    }

    public function getAuthorUsername(): string
    {
        $authorOriginal = $this->original->blog_author ?? new stdClass();
        $id = $authorOriginal->id ?? 'unknown_id';

        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_author_username',
            sanitize_user("hubspot_${id}", true),
            $this
        );
    }

    public function getAuthorDisplayName(): string
    {
        $authorOriginal = $this->original->blog_author ?? new stdClass();

        return (string) apply_filters(
            'wp_hubspot_importer_blog_post_author_display_name',
            sanitize_text_field($authorOriginal->display_name ?? 'Unknown Author'),
            $this
        );
    }

    public function getPostStatus(): string
    {
        $state = sanitize_text_field($this->original->state);
        switch ($state) {
            case 'PUBLISHED':
                $status = 'publish';
                break;
            case 'SCHEDULED':
                $status = 'future';
                break;
            default:
                $status = 'draft';
        }

        $deletedAt = absint($this->original->deleted_at);
        if ($deletedAt > 0) {
            $status = 'trash';
        }

        return (string) apply_filters('wp_hubspot_importer_blog_post_status', $status, $this);
    }
}
