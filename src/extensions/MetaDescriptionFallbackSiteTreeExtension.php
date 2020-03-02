<?php

namespace Chrometoaster\SEO\DataExtensions;

use Config;
use Controller;
use DataExtension;
use DataObject;

/**
 * Class MetaDescriptionFallbackSiteTreeExtension
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
class MetaDescriptionFallbackSiteTreeExtension extends DataExtension
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
            $fallbackFields = Config::inst()->get(get_class(), 'fallback_fields');

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

        return strip_tags($metaDescription);
    }


    /**
     * Update page meta tags
     *
     * @param $tags
     */
    public function MetaTags(&$tags)
    {
        $metaDescription = $this->getGeneralMetaDescription();

        if (!empty($metaDescription)) {
            $tag = sprintf('<meta name="description" content="%s" />', preg_replace('/\$/', '\\\$', $metaDescription));
            $replacePattern = '/<meta.*?name="description".*?\/>/';

            // replace if present, append otherwise
            if (preg_match($replacePattern, $tags)) {
                $tags = preg_replace($replacePattern, $tag, $tags, 1);
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
