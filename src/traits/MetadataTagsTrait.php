<?php

namespace Chrometoaster\SEO\Traits;

use SilverStripe\View\HTML;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Permission;

/**
 * Trait MetadataTagsTrait
 *
 * Contains a MetaTags function similar to that found in SiteTree.
 * Apply this trait to dataobjects when they need to output MetaTags like pages do.
 */
trait MetadataTagsTrait
{
    private static $casting = array(
        'MetaTags' => 'HTMLFragment',
    );

    /**
     * @param bool $includeTitle
     * @return string
     */
    public function MetaTags($includeTitle = true)
    {
        $tags = [];
        if ($includeTitle && mb_strtolower($includeTitle) != 'false') {
            $tags[] = HTML::createTag('title', [], $this->obj('Title')->forTemplate());
        }

        $generator = trim(Config::inst()->get(self::class, 'meta_generator'));
        if (!empty($generator)) {
            $tags[] = HTML::createTag('meta', [
                'name'    => 'generator',
                'content' => $generator,
            ]);
        }

        $charset = ContentNegotiator::config()->uninherited('encoding');
        $tags[]  = HTML::createTag('meta', [
            'http-equiv' => 'Content-Type',
            'content'    => 'text/html; charset=' . $charset,
        ]);
        if ($this->MetaDescription) {
            $tags[] = HTML::createTag('meta', [
                'name'    => 'description',
                'content' => $this->MetaDescription,
            ]);
        }

        $tagString = implode("\n", $tags);
        if ($this->ExtraMeta) {
            $tagString .= $this->obj('ExtraMeta')->forTemplate();
        }

        $this->extend('updateMetaTags', $tagString);

        return $tagString;
    }
}
