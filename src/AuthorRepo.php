<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use RuntimeException;
use WP_Error;
use WP_User;

class AuthorRepo
{
    /** @var WP_User[] */
    protected $mapping = [];

    public function upsertFrom(BlogPost $blogPost): WP_User
    {
        $username = $blogPost->getAuthorUsername();

        if (! array_key_exists($username, $this->mapping)) {
            $author = $this->upsert(
                $username,
                $blogPost->getAuthorDisplayName()
            );

            $this->mapping[$username] = $author;
        }

        return $this->mapping[$username];
    }

    protected function upsert(string $username, string $displayName): WP_User
    {
        $username = wp_slash($username);
        $displayName = wp_slash($displayName);

        $args = [
            'user_login' => $username,
            'user_nicename' => $displayName,
            'display_name' => $displayName,
            'nickname' => $displayName,
        ];

        $wpUserId = username_exists($username);
        if (is_int($wpUserId)) {
            // Update user info.
            $args['ID'] = $wpUserId;
        } else {
            // Create new user.
            $args['user_pass'] = wp_generate_password(64);
        }

        $wpUserId = wp_insert_user($args);

        if ($wpUserId instanceof WP_Error) {
            throw new RuntimeException(
                $wpUserId->get_error_message()
            );
        }

        return new WP_User($wpUserId);
    }
}
