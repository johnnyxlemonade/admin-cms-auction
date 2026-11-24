# Lemonade CMS Auction

[![Tests](https://img.shields.io/github/actions/workflow/status/johnnyxlemonade/admin-cms-auction/phpunit.yml?branch=main&label=Tests&logo=githubactions&logoColor=white&labelColor=0f766e)](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/phpunit.yml)
[![PHP 8.3–8.6](https://img.shields.io/badge/PHP-8.3%20%7C%208.4%20%7C%208.5%20%7C%208.6-777BB4?logo=php&logoColor=white)](#requirements)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/johnnyxlemonade/admin-cms-auction/phpstan.yml?branch=main&label=PHPStan&logo=php&logoColor=white&labelColor=5b4baf)](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/phpstan.yml)
[![License](https://img.shields.io/badge/license-Apache--2.0-green.svg)](LICENSE)

`johnnyxlemonade/admin-cms-auction` is an optional CMS module with the
`cms.auction` code. When present in the Composer installation, it provides
auction management at `<admin-base>/cms/auction` (for the standard `/admin`
base path, `/admin/cms/auction`).

An auction root stores its author name, owner, starting price in CZK, auction
date, auction state, external URL, and shared display settings. The author name
is text metadata; the owner is a separate audit identity. Each localized
translation stores its location, title, summary, content, SEO metadata, tags,
and publication state and time. Display settings are root metadata shared
across translations: recommendation, author, sharing, publication date, main
image, reading time, and tags.

The module uses the shared CMS content lifecycle for drafts, audited saves,
soft deletion, restoration, and canonical URL reservations. Images, galleries,
and attachments use Admin Platform file collections. The host owns public
controllers, templates, HTML, design, presentation-specific calculations and
the framework sitemap provider.

## Public auctions

`CmsAuctionServiceProvider` registers `PublishedAuctionReadModel`. `record()`
returns one visible translation or `null`; `collection()` returns a typed
`PublishedAuctionCollection`. `latest()` returns recent auctions without a count
query. `others()` returns recent auctions except the current record for detail pages.
`recommended()` returns recent records marked as recommended. Lists support a
localized tag; `collection()` also supports auction business state and
recommendation filters. The business state (`preparing`, `active`, `ended`,
`cancelled`) never replaces the separate CMS publication rules.

```php
use Lemonade\Cms\Auction\Public\PublishedAuctionReadModel;

$auction = $auctions->record($id, $locale);
$page = $auctions->collection($locale, page: $pageNumber, perPage: 12, tag: $tag);
$recent = $auctions->others($id, $locale, limit: 2);
$navigation = $paginationComponent->render($page->getPagination(
    basePath: $request->getUri()->getPath(),
    query: ['tag' => $tag],
));

$priceText = $auction?->getStartingPrice();
$currency = $auction?->getCurrency();
$auctionTime = $auction?->getAuctionAt();
$businessState = $auction?->getAuctionState();
$gallery = $auction?->getGallery() ?? [];
$attachments = $auction?->getAttachments() ?? [];
```

The starting price remains stored text, not a numeric amount. `getAuctionAt()`
returns `?DateTimeImmutable`; the host formats the date and interprets the
business state for presentation. `PublishedAuction` also exposes localized
summary, content, SEO, location, author, tags, display settings and canonical
URL through typed getters. It implements `PublishedMediaInterface` with
`getThumbnail()`, ordered `getGallery()` and ordered `getAttachments()` based on
batched `FileReadModel` metadata. The host creates presentation URLs for files.
`isPublicInAnyLocale($id)` lets a host download handler check the public
availability of an attachment owner.

All public queries require an available module, enabled language, published
translation with a reached publication time, nondeleted auction and active
canonical route. `collection()` uses filtered SQL `COUNT` and `LIMIT/OFFSET`;
it never loads the whole result set for pagination. Tags and files are loaded
in batches.

`PublishedAuctionReadModel::sitemapEntries($locale)` yields lightweight
`PublishedAuctionSitemapEntry` candidates with `getPublicUrl()` and
`getUpdatedAt()`. The update time is the latest root, translation or canonical
route change. The host selects locales and candidates in its existing framework
`sitemap.providers` registration and passes them to `SitemapUrl`:

```php
use Lemonade\Framework\Discovery\Sitemap\SitemapUrl;

foreach ($auctions->sitemapEntries($locale) as $entry) {
    yield SitemapUrl::create($entry->getPublicUrl(), $entry->getUpdatedAt());
}
```

## Requirements

- PHP 8.3 or newer, tested on PHP 8.3, 8.4, 8.5 and 8.6.
- `johnnyxlemonade/framework:dev-main`.
- `johnnyxlemonade/admin-platform:dev-main`.
- `johnnyxlemonade/cms-core:dev-main`.

## Installation

```bash
composer require johnnyxlemonade/admin-cms-auction:dev-main
```

Composer package metadata discovers and activates the module; there is no
separate installed or enabled state. Run the host application's controlled
migration and permission-sync deployment flow before serving requests.
Permissions are not granted to roles automatically.

## Package boundaries

- CMS Auction owns the auction aggregate, Admin module contribution,
  publication workflow, and auction-specific permissions.
- Admin Platform owns shared editor components, file collections, upload
  transport, and `system_file` metadata.
- CMS Core owns the shared content lifecycle and canonical route reservations.
- The host owns public routes, rendering, and sitemap selection.

## Development and QA

From the package root, run:

```bash
composer cs:check
composer stan
composer test
composer qa
```
