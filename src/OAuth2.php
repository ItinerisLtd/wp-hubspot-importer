<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter;

use Itineris\WPHubSpotImporter\Admin\SettingsPage;
use SevenShores\Hubspot\Http\Response;
use SevenShores\Hubspot\Resources\OAuth2 as HubSpotOauth2;
use stdClass;
use TypistTech\WPOptionStore\OptionStoreInterface;

class OAuth2
{
    public const SCOPES = [
        'content',
    ];
    protected const NONCE_ACTION = 'wp-hubspot-importer-authentication';

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
        $nonceUrl = wp_nonce_url(site_url(), static::NONCE_ACTION);

        return add_query_arg(
            'wp-hubspot-importer-action',
            'authentication-callback',
            $nonceUrl
        );
    }

    public function handleAuthenticationCallback(): void
    {
        if ($this->isNonceValid()) {
            wp_die('This link has been expired.');
        }

        $code = $this->getCodeFromSuperGlobal();
        if ('' === $code) {
            wp_die('Authentication code not found');
        }

        $response = $this->oauth2->getTokensByCode(
            $this->optionStore->getString('wp_hubspot_importer_client_id'),
            $this->optionStore->getString('wp_hubspot_importer_client_secret'),
            $this->getAuthenticationCallbackUrl(),
            $code
        );

        if (200 !== $response->getStatusCode()) {
            wp_die('Unable to get access token');
        }

        $this->saveTokensIntoDatabase(
            $response->getData()
        );

        wp_safe_redirect(
            SettingsPage::getUrl()
        );
        exit;
    }

    protected function isNonceValid(): bool
    {
        $nonce = '';
        if (isset($_GET['_wpnonce'])) { // WPCS: Input var ok.
            $nonce = sanitize_key($_GET['_wpnonce']); // WPCS: Input var ok.
        }

        $nonceVerificationResult = wp_verify_nonce($nonce, static::NONCE_ACTION);

        return is_int($nonceVerificationResult) && $nonceVerificationResult > 0;
    }

    protected function getCodeFromSuperGlobal(): string
    {
        $code = '';
        if (isset($_GET['code'])) { // WPCS: CSRF, Input var ok.
            $code = sanitize_text_field(wp_unslash($_GET['code'])); // WPCS: CSRF, Input var ok.
        }

        return $code;
    }

    protected function saveTokensIntoDatabase(stdClass $data): void
    {
        update_option(
            'wp_hubspot_importer_refresh_token',
            sanitize_text_field($data->refresh_token)
        );
        update_option(
            'wp_hubspot_importer_access_token',
            sanitize_text_field($data->access_token)
        );
        update_option(
            'wp_hubspot_importer_access_token_expire_at',
            time() + absint($data->expires_in)
        );
    }

    public function refreshAccessToken(): void
    {
        $response = $this->oauth2->getTokensByRefresh(
            $this->optionStore->getString('wp_hubspot_importer_client_id'),
            $this->optionStore->getString('wp_hubspot_importer_client_secret'),
            $this->optionStore->getString('wp_hubspot_importer_refresh_token')
        );
        if (200 !== $response->getStatusCode()) {
            wp_die('Unable to refresh access token');
        }

        $this->saveTokensIntoDatabase(
            $response->getData()
        );
    }

    public function getRefreshTokenInfo(): Response
    {
        return $this->oauth2->getRefreshTokenInfo(
            $this->optionStore->getString('wp_hubspot_importer_refresh_token')
        );
    }
}
