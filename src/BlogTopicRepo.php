<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use WP_Term;
use WP_Term_Query;

class BlogTopicRepo
{
    /** @var string */
    public $taxonomy;
    /** @var string */
    protected $hubSpotBlogTopicIdMetaKey;
    /** @var int[] */
    protected $mapping;

    public function __construct(
        string $taxonomy,
        string $hubSpotBlogTopicIdMetaKey
    ) {
        $this->taxonomy = $taxonomy;
        $this->hubSpotBlogTopicIdMetaKey = $hubSpotBlogTopicIdMetaKey;
    }

    public function getMapping(): array
    {
        if (null === $this->mapping) {
            // TODO: Get IDs only.
            $terms = get_terms([
                'taxonomy' => $this->hubSpotBlogTopicIdMetaKey,
                'hide_empty' => false,
            ]);

            if (! is_array($terms)) {
                // TODO: Throw exception! Handle exception!
                wp_die('Unknown error');
            }

            /** @var WP_Term[] $terms */
            foreach ($terms as $term) {
                $term->term_taxonomy_id;
                $termId = $term->term_id;
                $hubSpotBlogTopicId = (string) get_term_meta($termId, $this->hubSpotBlogTopicIdMetaKey, true);

                if ('' === $hubSpotBlogTopicId) {
                    continue;
                }

                $this->mapping[$hubSpotBlogTopicId] = $termId;
            }
        }

        return $this->mapping;
    }

    public function upsert(BlogTopic $blogTopic): void
    {
        $hubSpotBlogTopicId = $blogTopic->getHubSpotId();

        $termId = $this->mapping[$hubSpotBlogTopicId] ?? null;
        if (null === $termId) {
            // create
            wp_insert_term(
                $blogTopic->getName(),
                $this->taxonomy,
                [
                    'description'=> $blogTopic->getDescription(),
                    'slug' => $blogTopic->getSlug(),
                ]
            );
        }


        add_term_meta($term_id, 'feature-group', $group, true);
    }
}
