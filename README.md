# SilverStripe MetaDescription Fallback

## Requirements

* Silverstripe CMS 6
* PHP 8.3 or later

For Silverstripe CMS 4 and 5, use the 2.x releases.

## Maintainers

* michal.kleiner@chrometoaster.com
* doug.mckechie@chrometoaster.com

## Description

This module applies a data extension to SiteTree providing a mechanism to populate meta description header from a defined
set of content fields when the default MetaDescription is empty.

The list can use names of fields, names of methods or leverage the the dot notation to reference relations or methods,
as outlined in the configuration example below.

### Use with Dataobjects

The extension this module provides can also be applied to dataobjects, for example if you are outputting them as pages
and need meta tags included in the source of the page.

In this case the MetadataTagsTrait also needs to be used by those dataobjects.

## Installation with [Composer](https://getcomposer.org/)

```
composer require "chrometoaster/silverstripe-metadescription-fallback"
```

### Example configuration

In your `config.yml`, define the list of fields:

```YAML
Chrometoaster\SEO\DataExtensions\MetaDescriptionFallbackExtension:
  fallback_fields:
    - Description
    - Introduction
    - Content.Summary
    - RelatedPages.First.MetaDescription
```

Run dev/build either via opening the url `http://<your-host>/dev/build` in a browser or
by running the dev/build task using CLI.

## Reporting Issues

Please [create an issue](http://github.com/chrometoasters/silverstripe-metadescription-fallback/issues) for any bugs you've found, 
or features you're missing.

## Changelog

For details of updates, bugfixes, and features, please see the [changelog](CHANGELOG.md).

## TODO

* Add unit tests for fallback mechanism.
