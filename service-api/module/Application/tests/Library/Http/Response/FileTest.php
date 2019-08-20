<?php

namespace ApplicationTest\Library\Http\Response;

use Application\Library\Http\Response\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testConstructor()
    {
        $result = new File('data', 'content type');
        $this->assertEquals('data', $result->getContent());
    }

    public function testGetHeaders()
    {
        $file = new File('data', 'content type');

        $result = $file->getHeaders();

        $this->assertEquals(['Content-Type' => 'content type',
            'Content-Disposition' => 'attachment',
            'Content-Length' => '4'], $result->toArray());
    }
}
