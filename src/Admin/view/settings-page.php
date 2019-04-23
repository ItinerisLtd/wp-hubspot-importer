<?php
declare(strict_types=1);

echo '<div class="wrap">';
settings_errors();
echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

if ($context->hasRefreshToken) {
    echo 'You have authenticated <code>WP HubSpot Importer</code> to use HubSpot API on your behalf.';
    echo '<br />';
    printf(
        "<a href='$1%s'>Click here</a> to re-authenticate.",
        esc_url($context->authenticationUrl)
    );
} else {
    echo "You haven't authenticated <code>WP HubSpot Importer</code> yet.";
    echo '<br />';
    printf(
        "<a href='$1%s'>Click here</a> to allow <code>WP HubSpot Importer</code> to use HubSpot API on your behalf.",
        esc_url($context->authenticationUrl)
    );
}
echo '</div>';
