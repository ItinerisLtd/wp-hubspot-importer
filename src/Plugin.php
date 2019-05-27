<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Commands\Import;
use Itineris\WPHubSpotImporter\Commands\Verify;
use WP_CLI;
use WP_Post;

class Plugin
{
    public static function run(): void
    {
        add_action('admin_menu', function (): void {
            $container = Container::getInstance();
            $settingPage = $container->getSettingsPage();
            $settingPage->addManagementPage();
        });

        // TODO: Refactor!
        // TODO: Find a way not to run it on every page load.
        add_action('wp', function (): void {
            $action = null;

            if (isset($_GET['wp-hubspot-importer-action'])) { // WPCS: Input var ok.
                $action = sanitize_text_field(
                    wp_unslash($_GET['wp-hubspot-importer-action'])
                ); // WPCS: CSRF, Input var okay.
            }

            if ('authentication-callback' === $action) {
                $container = Container::getInstance();
                $oAuth2 = $container->getOAuth2();
                $oAuth2->handleAuthenticationCallback();
            }
        });

        // TODO: Refactor!
        add_filter('post_row_actions', function (array $actions, WP_Post $post): array {
            if (Container::BLOG_POST_POST_TYPE !== $post->post_type) {
                return $actions;
            }

            $postTypeObject = get_post_type_object(Container::BLOG_POST_POST_TYPE);
            if (null === $postTypeObject) {
                return $actions;
            }

            $actionUrl = wp_nonce_url(
                admin_url(
                    sprintf($postTypeObject->_edit_link . '&amp;action=pull-hubspot', $post->ID)
                ),
                'pull-hubspot_' . $post->ID
            );
            $actions['pull-hubspot'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                $actionUrl,
                // translators: %s: post title.
                esc_attr(sprintf(__('Pull &#8220;%s&#8221; from HubSpot', 'wp-hubspot-importer'), $post->post_title)),
                __('Pull from HubSpot', 'wp-hubspot-importer')
            );

            return $actions;
        }, 10, 2);

        add_action('post_action_pull-hubspot', function (int $postId) {
            check_admin_referer('pull-hubspot_' . $postId);

            $container = new Container();

            $blogPosts = $container->getBlogPosts();
            $importer = $container->getImporter();

            $response = $blogPosts->getById(
                get_post_meta($postId, Container::HUBSPOT_BLOG_POST_ID_META_KEY, true)
            );
            $data = $response->getData();

            if (200 !== $response->getStatusCode()) {
                $message = 'Failed to fetch HubSpot blog posts - '
                    . ($data->errorType ?? 'Unknown error type') . ': '
                    . ($data->message ?? 'Unknown error message');

                wp_die(
                    esc_html($message)
                );
            }

            $blogPost = new BlogPost($data);
            $importer->import($blogPost);

            $message = sprintf(
                'Successfully pulled Blog Post from HubSpot - %1$s (%2$s)',
                $blogPost->getPostTitle(),
                $blogPost->getHubSpotId()
            );

            wp_die(
                esc_html($message),
                esc_html($message),
                [
                    'response' => 200,
                    'back_link' => true,
                ]
            );
        });
    }

    public static function registerCommands(): void
    {
        WP_CLI::add_command('hubspot verify', Verify::class);
        WP_CLI::add_command('hubspot import', Import::class);
    }
}
