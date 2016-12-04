<?php

namespace Netgen\ContentBrowser\Tests\Config\FieldDefinition;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Repository\Values\ContentType\FieldDefinition;
use Netgen\ContentBrowser\Config\Configuration;
use Netgen\ContentBrowser\Tests\Config\FieldDefinition\Stubs\ConfigProcessor;
use PHPUnit\Framework\TestCase;

class ConfigProcessorTest extends TestCase
{
    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentTypeServiceMock;

    /**
     * @var \Netgen\ContentBrowser\Tests\Config\FieldDefinition\Stubs\ConfigProcessor
     */
    protected $configProcessor;

    public function setUp()
    {
        $this->contentTypeServiceMock = $this->createMock(ContentTypeService::class);

        $this->configProcessor = new ConfigProcessor($this->contentTypeServiceMock);
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::__construct
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::supports
     */
    public function testSupports()
    {
        $this->assertTrue(
            $this->configProcessor->supports(
                'ez-field-definition-field_type-ng_news-relation'
            )
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::__construct
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::supports
     */
    public function testSupportsReturnsFalse()
    {
        $this->assertFalse(
            $this->configProcessor->supports(
                'some-config'
            )
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::__construct
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::getFieldDefinition
     */
    public function testLoadConfig()
    {
        $contentType = new ContentType(
            array(
                'fieldDefinitions' => array(
                    new FieldDefinition(
                        array(
                            'identifier' => 'relation',
                            'fieldTypeIdentifier' => 'field_type',
                        )
                    ),
                ),
            )
        );

        $this->contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentTypeByIdentifier')
            ->with('news')
            ->will($this->returnValue($contentType));

        $config = new Configuration('test');
        $this->configProcessor->processConfig(
            'ez-field-definition-field_type-news-relation',
            $config
        );

        $this->assertTrue($config->hasParameter('test'));
        $this->assertEquals('config', $config->getParameter('test'));
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::getFieldDefinition
     * @expectedException \Netgen\ContentBrowser\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Config name format for field definition is not valid.
     */
    public function testLoadConfigWithInvalidConfigName()
    {
        $this->configProcessor->processConfig(
            'ez-field-definition-invalid',
            new Configuration('ezcontent')
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::getFieldDefinition
     * @expectedException \Netgen\ContentBrowser\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage 'news' content type does not exist.
     */
    public function testLoadConfigWithNonExistingContentType()
    {
        $this->contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentTypeByIdentifier')
            ->with('news')
            ->will($this->throwException(new NotFoundException('content type', 'news')));

        $this->configProcessor->processConfig(
            'ez-field-definition-field_type-news-relation',
            new Configuration('ezcontent')
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::getFieldDefinition
     * @expectedException \Netgen\ContentBrowser\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage 'relation' field definition does not exist.
     */
    public function testLoadConfigWithNonExistingFieldDefinition()
    {
        $contentType = new ContentType(array('fieldDefinitions' => array()));

        $this->contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentTypeByIdentifier')
            ->with('news')
            ->will($this->returnValue($contentType));

        $this->configProcessor->processConfig(
            'ez-field-definition-field_type-news-relation',
            new Configuration('ezcontent')
        );
    }

    /**
     * @covers \Netgen\ContentBrowser\Config\FieldDefinition\ConfigProcessor::getFieldDefinition
     * @expectedException \Netgen\ContentBrowser\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage 'relation' field definition is not of 'field_type' field type.
     */
    public function testLoadConfigWithInvalidFieldTypeIdentifier()
    {
        $contentType = new ContentType(
            array(
                'fieldDefinitions' => array(
                    new FieldDefinition(
                        array(
                            'identifier' => 'relation',
                            'fieldTypeIdentifier' => 'some_other_field_type',
                        )
                    ),
                ),
            )
        );

        $this->contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentTypeByIdentifier')
            ->with('news')
            ->will($this->returnValue($contentType));

        $this->configProcessor->processConfig(
            'ez-field-definition-field_type-news-relation',
            new Configuration('ezcontent')
        );
    }
}