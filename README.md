# WP HubSpot Importer

[![CircleCI](https://circleci.com/gh/ItinerisLtd/wp-hubspot-importer.svg?style=svg)](https://circleci.com/gh/ItinerisLtd/wp-hubspot-importer)
[![Packagist Version](https://img.shields.io/packagist/v/itinerisltd/wp-hubspot-importer.svg)](https://packagist.org/packages/itinerisltd/wp-hubspot-importer)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/itinerisltd/wp-hubspot-importer.svg)](https://packagist.org/packages/itinerisltd/wp-hubspot-importer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/itinerisltd/wp-hubspot-importer.svg)](https://packagist.org/packages/itinerisltd/wp-hubspot-importer)
[![GitHub License](https://img.shields.io/github/license/itinerisltd/wp-hubspot-importer.svg)](https://github.com/ItinerisLtd/wp-hubspot-importer/blob/master/LICENSE)
[![Hire Itineris](https://img.shields.io/badge/Hire-Itineris-ff69b4.svg)](https://www.itineris.co.uk/contact/)


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Goal

## Minimum Requirements

- PHP v7.3
- WordPress v5.1

## Installation

### Composer (Recommended)

```sh-session
$ composer require itinerisltd/wp-hubspot-importer
```

### Classic

Download `wp-hubspot-importer.zip` from [GitHub releases](https://github.com/itinerisltd/wp-hubspot-importer/releases)
Then, [install as usual](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins)

## Usage

### OAuth2 Authorization

1. Head to WP Dashboard > Tools > WP HubSpot Importer
1. Authenticate `WP HubSpot Importer` to use HubSpot API on your behalf 

### Importing from HubSpot to WordPress

```sh-session
$ wp hubspot import
Importing from HubSpot...
Fetching HubSpot blog topics...
Success: Fetched Blog Topic: Blog (1111111111)
Success: Fetched Blog Topic: News (2222222222)
Fetching HubSpot blog posts updated since Tue, 23 Apr 2019 12:19:15 +0000...
Success: Imported Blog Post: I am the blog post title (3333333333)
Success: Imported Blog Post: Hello World (4444444444)
Success: Finished at Tue, 23 Apr 2019 12:20:38 +0000
``` 

## Data Structure

By default, [WP HubSpot Importer](https://github.com/ItinerisLtd/wp-hubspot-importer) imports:

- [HubSpot blog posts](https://developers.hubspot.com/docs/methods/blogv2/get_blog_posts) as [`post`](https://codex.wordpress.org/Post_Types#Post)
- [HubSpot blog topics](https://developers.hubspot.com/docs/methods/blog/v3/list-blog-topics) as [`post_tag`](https://codex.wordpress.org/Taxonomies#Tag)

See: [`BlogPostRepo::upsert`](./src/BlogPostRepo.php)

These can be customized by defining your own container via the [`wp_hubspot_importer_container_init`](./src/Container.php) filter.

### HubSpot Blog Post ID

```php
$wpPostId = 999;

// Returns '1234567890'
get_post_meta($wpPostId, Container::HUBSPOT_BLOG_POST_ID_META_KEY, true);
```

### Featured Image URL

Featured images are not imported to WordPress media library, but the URLs are stored as custom post meta.

```php
$wpPostId = 999;

// Returns 'https://cdn2.hubspot.net/hubfs/1234566/xxx.jpeg'
get_post_meta($wpPostId, Container::HUBSPOT_FEATURED_IMAGE_URL_META_KEY, true);
```

## FAQ

### Will you add support for older PHP versions?

Never! This plugin will only works on [actively supported PHP versions](https://secure.php.net/supported-versions.php).

Don't use it on **end of life** or **security fixes only** PHP versions.

### It looks awesome. Where can I find some more goodies like this?

- Articles on [Itineris' blog](https://www.itineris.co.uk/blog/)
- More projects on [Itineris' GitHub profile](https://github.com/itinerisltd)
- More plugins on [Itineris](https://profiles.wordpress.org/itinerisltd/#content-plugins) and [TangRufus](https://profiles.wordpress.org/tangrufus/#content-plugins) wp.org profiles
- Follow [@itineris_ltd](https://twitter.com/itineris_ltd) and [@TangRufus](https://twitter.com/tangrufus) on Twitter
- Hire [Itineris](https://www.itineris.co.uk/services/) to build your next awesome site

### Besides wp.org, where can I give a :star::star::star::star::star: review?

Thanks! Glad you like it. It's important to let my boss knows somebody is using this project. Please consider:

- give :star::star::star::star::star: reviews on [wp.org](https://wordpress.org/support/plugin/wp-hubspot-importer/reviews/#new-post)
- tweet something good with mentioning [@itineris_ltd](https://twitter.com/itineris_ltd) and [@TangRufus](https://twitter.com/tangrufus)
- ️️:star: star this [Github repo](https://github.com/ItinerisLtd/wp-hubspot-importer)
- watch this [Github repo](https://github.com/ItinerisLtd/wp-hubspot-importer)
- write blog posts
- submit [pull requests](https://github.com/ItinerisLtd/wp-hubspot-importer)
- [hire Itineris](https://www.itineris.co.uk/services/)

## Testing

```sh-session
$ composer phpstan:analyse
$ composer style:check
```

Pull requests without tests will not be accepted!

## Feedback

**Please provide feedback!** We want to make this library useful in as many projects as possible.
Please submit an [issue](https://github.com/ItinerisLtd/wp-hubspot-importer/issues/new) and point out what you do and don't like, or fork the project and make suggestions.
**No issue is too small.**

## Change Log

Please see [CHANGELOG](./CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email [hello@itineris.co.uk](hello@itineris.co.uk) instead of using the issue tracker.

## Credits

[WP HubSpot Importer](https://github.com/ItinerisLtd/wp-hubspot-importer) is a [Itineris Limited](https://www.itineris.co.uk/) project created by [Tang Rufus](https://typist.tech).

Full list of contributors can be found [here](https://github.com/ItinerisLtd/wp-hubspot-importer/graphs/contributors).

## License

[WP HubSpot Importer](https://github.com/ItinerisLtd/wp-hubspot-importer) is released under the [MIT License](https://opensource.org/licenses/MIT).
