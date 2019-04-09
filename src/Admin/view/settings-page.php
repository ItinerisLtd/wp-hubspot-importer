<?php
declare(strict_types=1);

echo '<div class="wrap">';
settings_errors();
echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';


printf(
    '<a href="$1%s">Click Me</a>',
    esc_url($context->authenticationUrl)
);

echo '<h2>hasAccessToken: ' . $context->hasRefreshToken . '</h2>';

echo '</div>';
