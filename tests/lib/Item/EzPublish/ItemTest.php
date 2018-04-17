<?php

namespace Netgen\ContentBrowser\Tests\Item\EzPublish;

use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use Netgen\ContentBrowser\Item\EzPublish\Item;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    private $location;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    private $content;

    /**
     * @var \Netgen\ContentBrowser\Item\EzPublish\Item
     */
    private $item;

    public function setUp()
    {
        $this->content = new Content(
            [
                'versionInfo' => new VersionInfo(
                    [
                        'contentInfo' => new ContentInfo(
                            [
                                'id' => 42,
                            ]
                        ),
                    ]
                ),
            ]
        );

        $this->location = new Location(
            [
                'id' => 22,
                'parentLocationId' => 24,
                'invisible' => true,
            ]
        );

        $this->item = new Item($this->location, $this->content, 42, 'Some name', false);
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getLocationId
     */
    public function testGetLocationId()
    {
        $this->assertEquals(22, $this->item->getLocationId());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::__construct
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getValue
     */
    public function testGetValue()
    {
        $this->assertEquals(42, $this->item->getValue());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getName
     */
    public function testGetName()
    {
        $this->assertEquals('Some name', $this->item->getName());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getParentId
     */
    public function testGetParentId()
    {
        $this->assertEquals(24, $this->item->getParentId());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getParentId
     */
    public function testGetParentIdWithRootLocation()
    {
        $this->location = new Location(
            [
                'parentLocationId' => 1,
            ]
        );

        $this->item = new Item($this->location, $this->content, 42, 'Some name');

        $this->assertNull($this->item->getParentId());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::isVisible
     */
    public function testIsVisible()
    {
        $this->assertFalse($this->item->isVisible());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::isSelectable
     */
    public function testIsSelectable()
    {
        $this->assertFalse($this->item->isSelectable());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getLocation
     */
    public function testGetLocation()
    {
        $this->assertEquals($this->location, $this->item->getLocation());
    }

    /**
     * @covers \Netgen\ContentBrowser\Item\EzPublish\Item::getContent
     */
    public function testGetContent()
    {
        $this->assertEquals($this->content, $this->item->getContent());
    }
}
