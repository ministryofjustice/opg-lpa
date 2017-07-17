<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\Pdfs\Resource;

class TestableResource extends Resource
{
    public function testableGetS3Client()
    {
        return parent::getS3Client();
    }

    public function testableGetDynamoQueueClient()
    {
        return parent::getDynamoQueueClient();
    }
}