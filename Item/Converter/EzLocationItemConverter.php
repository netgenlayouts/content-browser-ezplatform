<?php

namespace Netgen\Bundle\ContentBrowserBundle\Item\Converter;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Helper\TranslationHelper;
use DateTime;

class EzLocationItemConverter implements ConverterInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var array
     */
    protected $config = array();

    /**
     * Constructor.
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param array $config
     */
    public function __construct(
        Repository $repository,
        TranslationHelper $translationHelper,
        array $config
    ) {
        $this->repository = $repository;
        $this->translationHelper = $translationHelper;
        $this->config = $config;
    }

    /**
     * Returns the ID of the value object.
     *
     * @param mixed $valueObject
     *
     * @return int|string
     */
    public function getId($valueObject)
    {
        return $valueObject->id;
    }

    /**
     * Returns the parent ID of the value object.
     *
     * @param mixed $valueObject
     *
     * @return int|string
     */
    public function getParentId($valueObject)
    {
        return $valueObject->parentLocationId != 1 ? $valueObject->parentLocationId : null;
    }

    /**
     * Returns the value of the value object.
     *
     * @param mixed $valueObject
     *
     * @return int|string
     */
    public function getValue($valueObject)
    {
        return $valueObject->id;
    }

    /**
     * Returns the name of the value object.
     *
     * @param mixed $valueObject
     *
     * @return string
     */
    public function getName($valueObject)
    {
        return $this->translationHelper->getTranslatedContentNameByContentInfo(
            $valueObject->contentInfo
        );
    }

    /**
     * Returns the selectable flag of the value object.
     *
     * @param mixed $valueObject
     *
     * @return bool
     */
    public function getIsSelectable($valueObject)
    {
        if (empty($this->config['types'])) {
            return true;
        }

        $contentType = $this->repository->getContentTypeService()->loadContentType(
            $valueObject->contentInfo->contentTypeId
        );

        return in_array($contentType->identifier, $this->config['types']);
    }

    /**
     * Returns the template variables of the value object.
     *
     * @param mixed $valueObject
     *
     * @return array
     */
    public function getTemplateVariables($valueObject)
    {
        return array(
            'content' => $this->repository->getContentService()->loadContentByContentInfo(
                $valueObject->contentInfo
            ),
            'location' => $valueObject,
        );
    }

    /**
     * Returns the columns of the value object.
     *
     * @param mixed $valueObject
     *
     * @return array
     */
    public function getColumns($valueObject)
    {
        $ownerContentInfo = $this->repository->sudo(
            function (Repository $repository) use ($valueObject) {
                return $repository->getContentService()->loadContentInfo(
                    $valueObject->contentInfo->ownerId
                );
            }
        );

        return array(
            'location_id' => $valueObject->id,
            'content_id' => $valueObject->contentId,
            'type' => $this->translationHelper->getTranslatedByMethod(
                $this->repository->getContentTypeService()->loadContentType(
                    $valueObject->contentInfo->contentTypeId
                ),
                'getName'
            ),
            'visible' => !$valueObject->invisible,
            'owner' => $this->translationHelper->getTranslatedContentNameByContentInfo(
                $ownerContentInfo
            ),
            'modified' => $valueObject->contentInfo->modificationDate->format(Datetime::ISO8601),
            'published' => $valueObject->contentInfo->publishedDate->format(Datetime::ISO8601),
            'priority' => $valueObject->priority,
            'section' => $this->repository->getSectionService()->loadSection(
                $valueObject->contentInfo->sectionId
            )->name,
        );
    }
}