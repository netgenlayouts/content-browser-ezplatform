<?php

namespace Netgen\Bundle\ContentBrowserBundle\Tree\EzTags;

use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use DateTime;

class LocationBuilder
{
    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * Constructor.
     *
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     */
    public function __construct(
        TranslationHelper $translationHelper
    ) {
        $this->translationHelper = $translationHelper;
    }

    /**
     * Builds the browser location from tag.
     *
     * @param \Netgen\TagsBundle\API\Repository\Values\Tags\Tag
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Tree\EzTags\Location
     */
    public function buildLocation(Tag $tag)
    {
        $pathString = $tag->pathString;
        if ($tag->id > 0) {
            $pathString = '/0' . $pathString;
        }

        $path = explode('/', trim($pathString, '/'));

        return new Location(
            $tag,
            array(
                'id' => $tag->id,
                'parentId' => $tag->parentTagId,
                'path' => array_map(function ($v) { return (int)$v; }, $path),
                'name' => $this->translationHelper->getTranslatedByMethod(
                    $tag,
                    'getKeyword'
                ),
                'isEnabled' => $tag->id > 0,
                'additionalColumns' => array(
                    'modified' => $tag->modificationDate->format(Datetime::ISO8601),
                    'published' => $tag->modificationDate->format(Datetime::ISO8601),
                )
            )
        );
    }
}
