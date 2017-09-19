<?php

namespace Netgen\ContentBrowser\Item\ColumnProvider\ColumnValueProvider\EzContent;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\ContentBrowser\Item\ColumnProvider\ColumnValueProviderInterface;
use Netgen\ContentBrowser\Item\ItemInterface;

class ContentType implements ColumnValueProviderInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    public function __construct(Repository $repository, TranslationHelper $translationHelper)
    {
        $this->repository = $repository;
        $this->translationHelper = $translationHelper;
    }

    public function getValue(ItemInterface $item)
    {
        return $this->repository->sudo(
            function (Repository $repository) use ($item) {
                return $this->translationHelper->getTranslatedByMethod(
                    $repository->getContentTypeService()->loadContentType(
                        $item->getContent()->contentInfo->contentTypeId
                    ),
                    'getName'
                );
            }
        );
    }
}
