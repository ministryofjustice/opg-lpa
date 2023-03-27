<?php

namespace Application\Library\View\Model;

use Traversable;
use Laminas\Json\Json;
use Laminas\Stdlib\ArrayUtils;
use Laminas\View\Model\JsonModel as LaminasJsonModel;

/**
 * Using in replace of Laminas's JsonModel in order to control $options passed to json_encode.
 *
 * Class JsonModel
 * @package Application\Library\View\Model
 */
class JsonModel extends LaminasJsonModel
{
    /**
     * Serialize to JSON
     *
     * @return false|string
     */
    public function serialize()
    {
        $variables = $this->getVariables();
        if ($variables instanceof Traversable) {
            $variables = ArrayUtils::iteratorToArray($variables);
        }
        if (null !== $this->jsonpCallback) {
            // Leave jsonpCallback as default. i.e. Json::encode
            return $this->jsonpCallback . '(' . Json::encode($variables) . ');';
        }

        // Using PHP's inbuilt function. Always return pretty.
        return json_encode($variables, JSON_PRETTY_PRINT);
    }
} // class
