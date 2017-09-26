<?php

namespace Netgen\ContentBrowser\Item\ColumnProvider\ColumnValueProvider\EzPublish;

use Netgen\ContentBrowser\Item\ColumnProvider\ColumnValueProviderInterface;
use Netgen\ContentBrowser\Item\EzPublish\EzPublishInterface;
use Netgen\ContentBrowser\Item\ItemInterface;

class Modified implements ColumnValueProviderInterface
{
    /**
     * @var string
     */
    private $dateFormat;

    /**
     * Constructor.
     *
     * @param string $dateFormat
     */
    public function __construct($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    public function getValue(ItemInterface $item)
    {
        if (!$item instanceof EzPublishInterface) {
            return null;
        }

        return $item->getContent()->contentInfo->modificationDate->format(
            $this->dateFormat
        );
    }
}