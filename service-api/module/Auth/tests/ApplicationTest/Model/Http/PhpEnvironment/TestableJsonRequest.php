<?php

namespace ApplicationTest\Model\Http\PhpEnvironment;

use Application\Model\Http\PhpEnvironment\JsonRequest;

class TestableJsonRequest extends JsonRequest
{
    public function __construct($contentType, $content, $allowCustomMethods = true)
    {
        if (!empty($contentType)) {
            $this->getHeaders()->addHeaders(['Content-Type' => $contentType]);
        }

        if (!empty($content)) {
            $this->setContent($content);
        }

        parent::__construct($allowCustomMethods);
    }
}