# Lemonade CMS Auction

[![PHPStan](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/phpstan.yml/badge.svg)](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/phpstan.yml)
[![Tests](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/phpunit.yml/badge.svg)](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/phpunit.yml)
[![Coding Standards](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/johnnyxlemonade/admin-cms-auction/actions/workflows/coding-standards.yml)
[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](composer.json)

`johnnyxlemonade/admin-cms-auction` is an optional CMS module with the
`cms.auction` code. When present in the Composer installation, it provides
auction management at `<admin-base>/content/auction` (for the standard `/admin`
base path, `/admin/content/auction`).

An auction root stores its author name, owner, starting price in CZK, auction
date, auction state, external URL, and shared display settings. The author name
is text metadata; the owner is a separate audit identity. Each localized
translation stores its location, title, summary, content, SEO metadata, tags,
and publication state and time. Display settings are root metadata shared
across translations: recommendation, author, sharing, publication date, main
image, reading time, and tags.

The module uses the shared CMS content lifecycle for drafts, audited saves,
soft deletion, restoration, and canonical URL reservations. Images, galleries,
and attachments use Admin Platform file collections. The package does not
provide public routes, templates, or auction rendering; the host application
decides how to use the stored display metadata.

## Installation

The package requires PHP `>=8.3 <8.6`, `johnnyxlemonade/framework`,
`johnnyxlemonade/admin-platform`, and `johnnyxlemonade/cms-core`.

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
- The host owns public routes, rendering, and publication checks.

## Development and QA

From the package root, run:

```bash
composer cs:check
composer stan
composer test
composer qa
```
