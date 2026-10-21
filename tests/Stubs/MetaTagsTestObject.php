<?php

namespace Chrometoaster\SEO\Tests\Stubs;

use Chrometoaster\SEO\DataExtensions\MetaDescriptionFallbackExtension;
use Chrometoaster\SEO\Traits\MetadataTagsTrait;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * A data object output as a page: it carries the meta tags trait and the extension.
 */
class MetaTagsTestObject extends DataObject implements TestOnly
{
    use MetadataTagsTrait;

    private static $table_name = 'MetaDescriptionFallback_MetaTagsTestObject';

    private static $db = [
        'Title'           => 'Varchar',
        'MetaDescription' => 'Text',
        'Description'     => 'Text',
    ];

    private static $extensions = [
        MetaDescriptionFallbackExtension::class,
    ];

    private static $meta_generator = 'Metadescription fallback tests';
}
