<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\Pdfs\Resource;
use Mockery\MockInterface;

class TestableResource extends Resource
{
    private $s3Client = null;

    /**
     * @param MockInterface $s3Client
     */
    public function setS3Client($s3Client)
    {
        $this->s3Client = $s3Client;
    }

    protected function getS3Client()
    {
        if ($this->s3Client === null) {
            return parent::getS3Client();
        }

        return $this->s3Client;
    }

    public function testableGetS3Client()
    {
        return parent::getS3Client();
    }
}