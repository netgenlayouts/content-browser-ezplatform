<?php

namespace Netgen\Bundle\ContentBrowserBundle\Tests\Repository\EzPublish\Stubs;

use eZ\Publish\API\Repository\Values\Content\Section;

class SectionServiceStub
{
    public function loadSection($sectionId)
    {
        return new Section(
            array(
                'name' => 'Section',
            )
        );
    }
}