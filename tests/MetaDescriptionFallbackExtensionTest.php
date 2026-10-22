<?php

namespace Chrometoaster\SEO\Tests;

use Chrometoaster\SEO\DataExtensions\MetaDescriptionFallbackExtension;
use Chrometoaster\SEO\Tests\Stubs\MetaTagsTestObject;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Class MetaDescriptionFallbackExtensionTest
 *
 * The meta description a page or a data object outputs: its own
 * MetaDescription first, then the configured fallback fields in order.
 */
class MetaDescriptionFallbackExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        MetaTagsTestObject::class,
    ];

    /**
     * @var Controller
     */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(MetaDescriptionFallbackExtension::class, 'fallback_fields', [
            'Description',
            'Content',
        ]);

        // The extension looks at the current controller for a data object acting as a page
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $this->controller = Controller::create();
        $this->controller->setRequest($request);
        $this->controller->pushCurrent();
    }

    protected function tearDown(): void
    {
        $this->controller->popCurrent();

        parent::tearDown();
    }

    public function testExtensionIsAppliedToPages()
    {
        $this->assertTrue(SiteTree::has_extension(MetaDescriptionFallbackExtension::class));
    }

    public function testPageMetaDescriptionComesFirst()
    {
        $page = SiteTree::create([
            'Title'           => 'Page',
            'MetaDescription' => 'Own description',
            'Content'         => '<p>Content of the page.</p>',
        ]);

        $tags = $page->MetaTags(false);

        $this->assertStringContainsString('<meta name="description" content="Own description">', $tags);
        $this->assertSame(1, substr_count($tags, 'name="description"'));
    }

    public function testPageFallsBackToContent()
    {
        $page = SiteTree::create([
            'Title'   => 'Page',
            'Content' => "<p>First paragraph.</p><p>Second\n   paragraph.</p>",
        ]);

        $tags = $page->MetaTags(false);

        $this->assertStringContainsString('<meta name="description" content="First paragraph. Second paragraph.">', $tags);
        $this->assertSame(1, substr_count($tags, 'name="description"'));
    }

    public function testPageFallbackFollowsADotNotationMethod()
    {
        Config::modify()->set(MetaDescriptionFallbackExtension::class, 'fallback_fields', [
            'Content.Summary',
        ]);

        $page = SiteTree::create([
            'Title'   => 'Page',
            'Content' => '<p>Short content.</p>',
        ]);

        $this->assertSame('Short content.', $page->getGeneralMetaDescription());
    }

    public function testPageWithoutAnyDescriptionHasNoDescriptionTag()
    {
        $page = SiteTree::create(['Title' => 'Page']);

        $this->assertStringNotContainsString('name="description"', $page->MetaTags(false));
    }

    public function testGeneralMetaDescriptionIsEscapedForAnAttribute()
    {
        $page = SiteTree::create([
            'Title'           => 'Page',
            'MetaDescription' => 'Fish "and" chips',
        ]);

        $this->assertSame('Fish &quot;and&quot; chips', $page->getGeneralMetaDescription());
    }

    public function testDataObjectWithTheTraitFallsBackToItsFields()
    {
        $object = MetaTagsTestObject::create([
            'Title'       => 'Object',
            'Description' => '<p>Description of the object.</p>',
        ]);

        $tags = $object->MetaTags();

        $this->assertStringContainsString('<title>Object</title>', $tags);
        $this->assertStringContainsString('<meta name="generator" content="Metadescription fallback tests"', $tags);
        $this->assertStringContainsString('<meta name="description" content="Description of the object.">', $tags);
        $this->assertSame(1, substr_count($tags, 'name="description"'));
    }
}
