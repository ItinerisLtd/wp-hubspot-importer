<?php
declare(strict_types=1);

namespace Itineris\WPHubSpotImporter\Commands;

use Itineris\WPHubSpotImporter\Factory;
use Itineris\WPHubSpotImporter\OAuth2;
use TypistTech\WPOptionStore\OptionStoreInterface;
use WP_CLI;

/**
 * TODO: Re-think this command.
 */
class Verify
{
    public function __invoke(): void
    {
        [
            'oauth2' => $oauth2,
            'optionStore' => $optionStore,
        ] = Factory::build();

        WP_CLI::log("==> Verifying 'WP_HUBSPOT_IMPORTER_CLIENT_ID' is defined...");
        $result = $this->verifyStringOptionNotEmpty('wp_hubspot_importer_client_id', $optionStore);
        $isSuccessful = $result;

        WP_CLI::log("==> Verifying 'WP_HUBSPOT_IMPORTER_CLIENT_SECRET' is defined...");
        $result = $this->verifyStringOptionNotEmpty('wp_hubspot_importer_client_secret', $optionStore);
        $isSuccessful = $isSuccessful && $result;

        WP_CLI::log("==> Verifying 'refresh token' is set...");
        $result = $this->verifyStringOptionNotEmpty('wp_hubspot_importer_refresh_token', $optionStore);
        $isSuccessful = $isSuccessful && $result;

        WP_CLI::log("==> Verifying 'refresh token' info...");
        $result = $this->verifyRefreshTokenInfo($oauth2);
        $isSuccessful = $isSuccessful && $result;

        WP_CLI::log("==> Verifying 'access token' refreshing...");
        $result = $this->verifyAccessTokenRefreshing($oauth2, $optionStore);
        $isSuccessful = $isSuccessful && $result;

        if ($isSuccessful) {
            WP_CLI::success('All good');
        } else {
            WP_CLI::error("Something's wrong");
        }
    }

    protected function verifyStringOptionNotEmpty(string $key, OptionStoreInterface $optionStore): bool
    {
        $displayKey = strtoupper($key);
        $value = $optionStore->getString($key);

        if ('' === $value) {
            WP_CLI::error("'${displayKey}' not found", false);
            return false;
        }

        WP_CLI::success("'${displayKey}' is found");
        return true;
    }

    protected function verifyRefreshTokenInfo(OAuth2 $oauth2): bool
    {
        $info = $oauth2->getRefreshTokenInfo();

        if (200 !== $info->getStatusCode()) {
            WP_CLI::error("'refresh token' is not valid", false);
            return false;
        }

        $data = $info->getData();

        $user = $data->user ?? '';
        if (is_string($user) && '' !== $user) {
            WP_CLI::success("'refresh token' belongs to user - '${user}''");
        } else {
            WP_CLI::error("'refresh token' belongs to unknown 'user'", false);
            return false;
        }

        $hubDomain = $data->hub_domain ?? '';
        if (is_string($hubDomain) && '' !== $hubDomain) {
            WP_CLI::success("'refresh token' belongs to hubDomain - '${hubDomain}'");
        } else {
            WP_CLI::error("'refresh token' belongs to unknown 'hubDomain'", false);
            return false;
        }

        $scopes = $data->scopes ?? [];
        $intersect = array_intersect(OAuth2::SCOPES, $scopes);
        if (OAuth2::SCOPES !== $intersect) {
            WP_CLI::error("'refresh token' has insufficient scopes", false);
            return false;
        }

        return true;
    }

    protected function verifyAccessTokenRefreshing(OAuth2 $oauth2, OptionStoreInterface $optionStore): bool
    {
        $oauth2->refreshAccessToken();

        $accessTokenExpireAt = $optionStore->getInt('wp_hubspot_importer_access_token_expire_at');
        $accessTokenExpireIn = $accessTokenExpireAt - time();

        if ($accessTokenExpireIn < 18000) { // 5 * 3600 = 18000 = 5 hours.
            WP_CLI::error("'Access token' expires in ${accessTokenExpireIn} seconds", false);
            return false;
        }

        WP_CLI::success("'Access token' expires in ${accessTokenExpireIn} seconds");
        return true;
    }
}
