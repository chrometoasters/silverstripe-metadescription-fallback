<?php

namespace Chrometoaster\SEO\DataExtensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBText;

/**
 * Class MetaDescriptionFallbackExtension
 *
 * Provide a mechanism to define fallback fields to get relevant content
 * for meta description header.
 *
 * A list of fields can be defined and it's followed in that order until some content is found.
 *
 * Fallback fields can be defined as names of fields, names of methods
 * or using the dot notation referencing relations or methods.
 *
 * Examples:
 *  - Description
 *  - Introduction
 *  - Content.Summary
 *  - RelatedPages.First.MetaDescription
 *
 *
 * @package Chrometoaster\SEO\DataExtensions
 */
class MetaDescriptionFallbackExtension extends Extension
{
    /**
     * Fallback fields for meta description header
     *
     * @config
     * @var array
     */
    private static $fallback_fields = [];


    /**
     * Get MetaDescription for the page/dataobject
     *
     * Look at the MetaDescription field or method first and if nothing is found there,
     * follow configured set of fields/field callbacks/relations to determine the content.
     *
     * @param null $dataObject
     * @return mixed
     */
    public function getGeneralMetaDescription($dataObject = null)
    {
        $metaDescription = '';

        if (!$dataObject instanceof DataObject) {
            $dataObject = $this->getCurrentDataObject();
        }

        // default to MetaDescription first
        if ($dataObject->hasField('MetaDescription')) {
            $metaDescription = $dataObject->getField('MetaDescription');
        } elseif ($dataObject->hasMethod('getMetaDescription')) {
            $metaDescription = $dataObject->getMetaDescription();
        }

        // do we need to look for a fallback?
        if (empty($metaDescription)) {

            // configured fallback fields and/or methods
            $fallbackFields = Config::inst()->get(static::class, 'fallback_fields');

            if ($fallbackFields && is_array($fallbackFields) && count($fallbackFields)) {
                foreach ($fallbackFields as $fb) {
                    if (strpos($fb, '.') !== false) {
                        // Extract field name in case this is a method called on a field (e.g. "Content.Summary")
                        list($name, $cb) = explode('.', $fb, 2);

                        if ($dataObject->hasDatabaseField($name)) {
                            $metaDescription = $dataObject->dbObject($name)->$cb();
                        } else {
                            $metaDescription = $dataObject->relObject($fb);
                        }
                    } elseif ($dataObject->hasField($fb)) {
                        $metaDescription = $dataObject->getField($fb);
                    } elseif ($dataObject->hasMethod($fb)) {
                        $metaDescription = $dataObject->$fb();
                    }

                    // only iterate until first non-empty value is found
                    if (!empty($metaDescription)) {
                        break;
                    }
                }
            }
        }

        // add a space to closing </p> to prevent bunching, strip all tags and replace multiple spaces with a single one
        $metaDescription = preg_replace('/\s+/', ' ', strip_tags(trim(str_replace('</p>', '</p> ', (string) $metaDescription))));

        // call ATT() on an instance of DBText
        return DBText::create()->setValue($metaDescription)->ATT();
    }


    /**
     * Update page meta tags
     *
     * @param $tags
     */
    public function updateMetaTags(&$tags)
    {
        $metaDescription = $this->getGeneralMetaDescription();

        if (!empty($metaDescription)) {
            $tag = sprintf('<meta name="description" content="%s">', $metaDescription);
            $replacePattern = '/<meta.*?name="description".*?>/U';

            // replace if present, append otherwise
            if (preg_match($replacePattern, $tags)) {
                $tags = preg_replace($replacePattern, preg_replace('/\$/', '\\\$', $tag), $tags, 1);
            } else {
                $tags .= $tag;
            }
        }
    }


    /**
     * Get current data object
     *
     * This can be a page type or dataobject acting as a page.
     *
     * @return DataObject
     */
    protected function getCurrentDataObject()
    {
        $controller = Controller::curr();
        $dataObject = $this->owner;

        // Get data object from a controller method
        if (method_exists($controller, 'getDataObjectAsPage')) {
            $dataObjectAsPage = $controller->getDataObjectAsPage();
            if ($dataObjectAsPage) {
                $dataObject = $dataObjectAsPage;
            }
        }

        return $dataObject;
    }
}
