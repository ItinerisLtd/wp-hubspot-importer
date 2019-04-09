<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\API;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use SevenShores\Hubspot\Resources\OAuth2 as HubSpotOauth2;
use TypistTech\WPOptionStore\OptionStoreInterface;

class OAuth2
{
    protected const SCOPES = [
        'content',
    ];

    /** @var OptionStoreInterface */
    protected $optionStore;
    /** @var HubSpotOauth2 */
    protected $oauth2;

    public function __construct(OptionStoreInterface $optionStore, HubSpotOauth2 $oauth2)
    {
        $this->optionStore = $optionStore;
        $this->oauth2 = $oauth2;
    }

    public function getAuthenticationUrl(): string
    {
        return (string) $this->oauth2->getAuthUrl(
            $this->optionStore->getString('wp_hubspot_importer_client_id'),
            $this->getAuthenticationCallbackUrl(),
            static::SCOPES
        );
    }

    protected function getAuthenticationCallbackUrl(): string
    {
        return add_query_arg(
            'wp-hubspot-importer-action',
            'authentication-callback',
            site_url()
        );
    }

    public function handleAuthenticationCallback(): void
    {
        $code = null;
        // TODO: Handle else!
        if (isset($_GET['code'])) {
            $code = sanitize_text_field(wp_unslash($_GET['code']));
        }

        $tokens = $this->oauth2->getTokensByCode(
            $this->optionStore->getString('wp_hubspot_importer_client_id'),
            $this->optionStore->getString('wp_hubspot_importer_client_secret'),
            $this->getAuthenticationCallbackUrl(),
            $code
        );

        update_option(
            'wp_hubspot_importer_refresh_token',
            sanitize_text_field($tokens->data->refresh_token)
        );
        update_option(
            'wp_hubspot_importer_access_token',
            sanitize_text_field($tokens->data->access_token)
        );
        update_option(
            'wp_hubspot_importer_access_token_expire_at',
            time() + absint($tokens->data->expires_in)
        );

        wp_safe_redirect(
            SettingsPage::getUrl()
        );
        exit;
    }
}
