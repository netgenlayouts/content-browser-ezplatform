<?php

declare(strict_types=1);

namespace Netgen\ContentBrowser\Item\ColumnProvider\ColumnValueProvider\EzTags;

use Netgen\ContentBrowser\Item\ColumnProvider\ColumnValueProviderInterface;
use Netgen\ContentBrowser\Item\EzTags\EzTagsInterface;
use Netgen\ContentBrowser\Item\ItemInterface;

final class ParentTagId implements ColumnValueProviderInterface
{
    public function getValue(ItemInterface $item)
    {
        if (!$item instanceof EzTagsInterface) {
            return null;
        }

        return $item->getTag()->parentTagId;
    }
}
