<?php

namespace Application\View\Helper;

use Application\Adapter\DynamoDbKeyValueStore;
use Laminas\View\Helper\AbstractHelper;

class SystemMessage extends AbstractHelper
{
    /**
     * @var DynamoDbKeyValueStore
     */
    private $cache;

    public function __construct(DynamoDbKeyValueStore $cache)
    {
        $this->cache = $cache;
    }

    public function __invoke()
    {
        $message = $this->cache->getItem('system-message');

        if ($message !== NULL) {
            $message = trim($message);
        }

        if ($message != '') {
            echo <<<SYSMESS
            <div class="notice">
              <i class="icon icon-important"></i>
              <p>
                <strong class="bold-small text">$message</strong>
              </p>
            </div>
SYSMESS;
        }
    }
}
