<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\Pdfs\Resource;
use Aws\S3\S3Client;
use Mockery;

class TestableResource extends Resource
{
    protected function getS3Client()
    {
        return Mockery::mock(S3Client::class);
    }

    public function testableGetS3Client()
    {
        return parent::getS3Client();
    }

    public function testableGetDynamoQueueClient()
    {
        return parent::getDynamoQueueClient();
    }
}