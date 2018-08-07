<?php

declare(strict_types=1);

namespace Netgen\ContentBrowser\Tests\Backend;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentName;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\Repository\Repository;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\SPI\Persistence\Content\Type;
use eZ\Publish\SPI\Persistence\Content\Type\Handler;
use Netgen\ContentBrowser\Backend\EzPublishBackend;
use Netgen\ContentBrowser\Config\Configuration;
use Netgen\ContentBrowser\Item\EzPublish\Item;
use Netgen\ContentBrowser\Item\ItemInterface;
use Netgen\ContentBrowser\Item\LocationInterface;
use Netgen\ContentBrowser\Tests\Stubs\Location as StubLocation;
use PHPUnit\Framework\TestCase;

final class EzPublishBackendTest extends TestCase
{
    /**
     * @var \eZ\Publish\API\Repository\Repository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repositoryMock;

    /**
     * @var \eZ\Publish\API\Repository\SearchService&\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchServiceMock;

    /**
     * @var \eZ\Publish\API\Repository\ContentService&\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentServiceMock;

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Type\Handler&\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentTypeHandlerMock;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper&\PHPUnit\Framework\MockObject\MockObject
     */
    private $translationHelperMock;

    /**
     * @var array
     */
    private $locationContentTypes;

    /**
     * @var array
     */
    private $defaultSections;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var \Netgen\ContentBrowser\Backend\EzPublishBackend
     */
    private $backend;

    public function setUp(): void
    {
        $this->defaultSections = [2, 43, 5];
        $this->locationContentTypes = ['frontpage' => 24, 'category' => 42];

        $this->contentTypeHandlerMock = $this->createMock(Handler::class);
        $this->contentTypeHandlerMock
            ->expects(self::any())
            ->method('loadByIdentifier')
            ->will(
                self::returnCallback(function (string $identifier): Type {
                    return new Type(
                        [
                            'id' => $this->locationContentTypes[$identifier],
                        ]
                    );
                })
            );

        $this->repositoryMock = $this->createPartialMock(
            Repository::class,
            [
                'sudo',
                'getSearchService',
                'getContentService',
            ]
        );

        $this->repositoryMock
            ->expects(self::any())
            ->method('sudo')
            ->with(self::anything())
            ->will(self::returnCallback(
                function (callable $callback) {
                    return $callback($this->repositoryMock);
                }
            ));

        $this->searchServiceMock = $this->createMock(SearchService::class);
        $this->contentServiceMock = $this->createMock(ContentService::class);

        $this->contentServiceMock
            ->expects(self::any())
            ->method('loadContentByContentInfo')
            ->with(self::isInstanceOf(ContentInfo::class))
            ->will(self::returnCallback(
                function (ContentInfo $contentInfo): Content {
                    return new Content(
                        [
                            'versionInfo' => new VersionInfo(
                                [
                                    'contentInfo' => $contentInfo,
                                ]
                            ),
                        ]
                    );
                }
            ));

        $this->repositoryMock
            ->expects(self::any())
            ->method('getSearchService')
            ->will(self::returnValue($this->searchServiceMock));

        $this->repositoryMock
            ->expects(self::any())
            ->method('getContentService')
            ->will(self::returnValue($this->contentServiceMock));

        $this->translationHelperMock = $this->createMock(TranslationHelper::class);

        $this->translationHelperMock
            ->expects(self::any())
            ->method('getTranslatedContentNameByContentInfo')
            ->willReturn('Name');

        $configuration = new Configuration('ezlocation', 'eZ location', []);
        $configuration->setParameter('sections', $this->defaultSections);
        $configuration->setParameter('location_content_types', array_keys($this->locationContentTypes));

        $this->languages = ['eng-GB', 'cro-HR'];

        $this->backend = new EzPublishBackend(
            $this->repositoryMock,
            $this->searchServiceMock,
            $this->contentTypeHandlerMock,
            $this->translationHelperMock,
            $configuration
        );

        $this->backend->setLanguages($this->languages);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::__construct
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getContentTypeIds
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSections
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     */
    public function testGetSections(): void
    {
        $query = new LocationQuery();
        $query->filter = new Criterion\LocationId($this->defaultSections);

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(2)]),
            new SearchHit(['valueObject' => $this->getLocation(43)]),
            new SearchHit(['valueObject' => $this->getLocation(5)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $locations = $this->backend->getSections();

        self::assertCount(3, $locations);

        foreach ($locations as $location) {
            self::assertInstanceOf(Item::class, $location);
            self::assertInstanceOf(LocationInterface::class, $location);
        }

        self::assertSame(2, $locations[0]->getLocationId());
        self::assertSame(43, $locations[1]->getLocationId());
        self::assertSame(5, $locations[2]->getLocationId());
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::loadLocation
     */
    public function testLoadLocation(): void
    {
        $query = new LocationQuery();
        $query->filter = new Criterion\LocationId(2);

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(2)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $location = $this->backend->loadLocation(2);

        self::assertInstanceOf(Item::class, $location);
        self::assertSame(2, $location->getLocationId());
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::loadLocation
     * @expectedException \Netgen\ContentBrowser\Exceptions\NotFoundException
     * @expectedExceptionMessage Location with ID "2" not found.
     */
    public function testLoadLocationThrowsNotFoundException(): void
    {
        $query = new LocationQuery();
        $query->filter = new Criterion\LocationId(2);

        $searchResult = new SearchResult();
        $searchResult->searchHits = [];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $this->backend->loadLocation(2);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::loadItem
     */
    public function testLoadItem(): void
    {
        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\LocationId(2),
            ]
        );

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(2)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $item = $this->backend->loadItem(2);

        self::assertInstanceOf(Item::class, $item);
        self::assertSame(2, $item->getValue());
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::loadItem
     */
    public function testLoadItemWithContent(): void
    {
        $this->backend = new EzPublishBackend(
            $this->repositoryMock,
            $this->searchServiceMock,
            $this->contentTypeHandlerMock,
            $this->translationHelperMock,
            new Configuration('ezcontent', 'eZ content', [])
        );

        $this->backend->setLanguages($this->languages);

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ContentId(2),
                new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN),
            ]
        );

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(null, null, 2)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $item = $this->backend->loadItem(2);

        self::assertInstanceOf(Item::class, $item);
        self::assertSame(2, $item->getValue());
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::loadItem
     * @expectedException \Netgen\ContentBrowser\Exceptions\NotFoundException
     * @expectedExceptionMessage Item with value "2" not found.
     */
    public function testLoadItemThrowsNotFoundException(): void
    {
        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\LocationId(2),
            ]
        );

        $searchResult = new SearchResult();
        $searchResult->searchHits = [];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $this->backend->loadItem(2);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getContentTypeIds
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSortClause
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubLocations
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     */
    public function testGetSubLocations(): void
    {
        $query = new LocationQuery();
        $query->offset = 0;
        $query->limit = 9999;
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId(2),
                new Criterion\ContentTypeId(
                    array_values($this->locationContentTypes)
                ),
            ]
        );

        $query->sortClauses = [new ContentName(LocationQuery::SORT_ASC)];

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(null, 2)]),
            new SearchHit(['valueObject' => $this->getLocation(null, 2)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $locations = $this->backend->getSubLocations(
            new Item($this->getLocation(2), new Content(), 2, 'location')
        );

        self::assertCount(2, $locations);
        foreach ($locations as $location) {
            self::assertInstanceOf(Item::class, $location);
            self::assertInstanceOf(LocationInterface::class, $location);
            self::assertSame(2, $location->getParentId());
        }
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubLocations
     */
    public function testGetSubLocationsWithInvalidItem(): void
    {
        $this->searchServiceMock
            ->expects(self::never())
            ->method('findLocations');

        $locations = $this->backend->getSubLocations(new StubLocation(0));

        self::assertSame([], $locations);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getContentTypeIds
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubLocationsCount
     */
    public function testGetSubLocationsCount(): void
    {
        $query = new LocationQuery();
        $query->limit = 0;
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId(2),
                new Criterion\ContentTypeId(
                    array_values($this->locationContentTypes)
                ),
            ]
        );

        $searchResult = new SearchResult();
        $searchResult->totalCount = 2;

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $count = $this->backend->getSubLocationsCount(
            new Item($this->getLocation(2), new Content(), 2, 'location')
        );

        self::assertSame(2, $count);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSortClause
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     */
    public function testGetSubItems(): void
    {
        $query = new LocationQuery();
        $query->offset = 0;
        $query->limit = 25;
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId(2),
            ]
        );

        $query->sortClauses = [new ContentName(LocationQuery::SORT_ASC)];

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(null, 2)]),
            new SearchHit(['valueObject' => $this->getLocation(null, 2)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $items = $this->backend->getSubItems(
            new Item($this->getLocation(2), new Content(), 2, 'location')
        );

        self::assertCount(2, $items);
        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertInstanceOf(ItemInterface::class, $item);
            self::assertSame(2, $item->getParentId());
        }
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSortClause
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     */
    public function testGetSubItemsWithOffsetAndLimit(): void
    {
        $query = new LocationQuery();
        $query->offset = 5;
        $query->limit = 10;
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId(2),
            ]
        );

        $query->sortClauses = [new ContentName(LocationQuery::SORT_ASC)];

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation(null, 2)]),
            new SearchHit(['valueObject' => $this->getLocation(null, 2)]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $items = $this->backend->getSubItems(
            new Item($this->getLocation(2), new Content(), 2, 'location'),
            5,
            10
        );

        self::assertCount(2, $items);
        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertInstanceOf(ItemInterface::class, $item);
            self::assertSame(2, $item->getParentId());
        }
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubItems
     */
    public function testGetSubItemsWithInvalidItem(): void
    {
        $this->searchServiceMock
            ->expects(self::never())
            ->method('findLocations');

        $items = $this->backend->getSubItems(new StubLocation(0));

        self::assertSame([], $items);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::getSubItemsCount
     */
    public function testGetSubItemsCount(): void
    {
        $query = new LocationQuery();
        $query->limit = 0;
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId(2),
            ]
        );

        $searchResult = new SearchResult();
        $searchResult->totalCount = 2;

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $count = $this->backend->getSubItemsCount(
            new Item($this->getLocation(2), new Content(), 2, 'location')
        );

        self::assertSame(2, $count);
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::search
     */
    public function testSearch(): void
    {
        $query = new LocationQuery();
        $query->offset = 0;
        $query->limit = 25;
        $query->query = new Criterion\FullText('test');
        $query->filter = new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN);

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation()]),
            new SearchHit(['valueObject' => $this->getLocation()]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $items = $this->backend->search('test');

        self::assertCount(2, $items);
        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertInstanceOf(ItemInterface::class, $item);
        }
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItem
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::buildItems
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::isSelectable
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::search
     */
    public function testSearchWithOffsetAndLimit(): void
    {
        $query = new LocationQuery();
        $query->offset = 5;
        $query->limit = 10;
        $query->query = new Criterion\FullText('test');
        $query->filter = new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN);

        $searchResult = new SearchResult();
        $searchResult->searchHits = [
            new SearchHit(['valueObject' => $this->getLocation()]),
            new SearchHit(['valueObject' => $this->getLocation()]),
        ];

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $items = $this->backend->search('test', 5, 10);

        self::assertCount(2, $items);
        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertInstanceOf(ItemInterface::class, $item);
        }
    }

    /**
     * @covers \Netgen\ContentBrowser\Backend\EzPublishBackend::searchCount
     */
    public function testSearchCount(): void
    {
        $query = new LocationQuery();
        $query->limit = 0;
        $query->query = new Criterion\FullText('test');
        $query->filter = new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN);

        $searchResult = new SearchResult();
        $searchResult->totalCount = 2;

        $this->searchServiceMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query), self::identicalTo(['languages' => $this->languages]))
            ->will(self::returnValue($searchResult));

        $count = $this->backend->searchCount('test');

        self::assertSame(2, $count);
    }

    /**
     * Returns the location object used in tests.
     *
     * @param int|string $id
     * @param int|string $parentLocationId
     * @param int|string $contentId
     *
     * @return \eZ\Publish\Core\Repository\Values\Content\Location
     */
    private function getLocation($id = null, $parentLocationId = null, $contentId = null): Location
    {
        return new Location(
            [
                'id' => $id,
                'parentLocationId' => $parentLocationId,
                'contentInfo' => new ContentInfo(
                    [
                        'id' => $contentId,
                    ]
                ),
                'sortField' => Location::SORT_FIELD_NAME,
                'sortOrder' => Location::SORT_ORDER_ASC,
            ]
        );
    }
}
