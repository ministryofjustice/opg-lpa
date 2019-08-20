<?php

namespace ApplicationTest\Library\View\Model;

use Application\Library\View\Model\JsonModel;
use PHPUnit\Framework\TestCase;

class JsonModelTest extends TestCase
{
    public function testSerialize() : void
    {
        // Note: Arrays do not implement Traversable, despite working in a foreach
        $jsonModel = new JsonModel(['some' => ['nested' => 'data']]);

        $result = $jsonModel->serialize();

        $this->assertEquals(
            "{\n"
            . "    \"some\": {\n"
            . "        \"nested\": \"data\"\n"
            . "    }\n"
            . "}", $result);
    }

    public function testSerializeWithCallback() : void
    {
        $jsonModel = new JsonModel(['some' => 'data']);
        $jsonModel->setJsonpCallback('testCallback');

        $result = $jsonModel->serialize();

        $this->assertEquals('testCallback({"some":"data"});', $result);
    }

    public function testSerializeTraversable() : void
    {
        $arrayIterator = new \ArrayIterator(['some' => ['nested' => 'data']]);

        $jsonModel = new JsonModel($arrayIterator);

        $result = $jsonModel->serialize();

        $this->assertEquals(
            "{\n"
            . "    \"some\": {\n"
            . "        \"nested\": \"data\"\n"
            . "    }\n"
            . "}", $result);
    }
}
