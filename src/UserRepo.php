<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;


use RuntimeException;
use WP_Error;
use WP_User;

class UserRepo
{
    /** @var WP_User[] */
    protected $mapping = [];

    public function upsertFrom(BlogPost $blogPost): WP_User
    {
        $username = $blogPost->getAuthorUsername();

        if (! array_key_exists($username, $this->mapping)) {
            $user = $this->upsert(
                $username,
                $blogPost->getAuthorDisplayName()
            );

            $this->mapping[$username] = $user;
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

        $userId = username_exists($username);
        if (is_int($userId)) {
            // Update user info.
            $args['ID'] = $userId;
        } else {
            // Create new user.
            $args['user_pass'] = wp_generate_password(64);
        }

        $userId = wp_insert_user($args);

        if ($userId instanceof WP_Error) {
            throw new RuntimeException(
                $userId->get_error_message()
            );
        }

        return new WP_User($userId);
    }
}
