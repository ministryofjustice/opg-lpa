<?php
namespace Application\Library\View\Model;

use Traversable;
use Zend\Json\Json;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\JsonModel as ZFJsonModel;

/**
 * Using in replace of ZF2's JsonModel in order to control $options passed to json_encode.
 *
 * Class JsonModel
 * @package Application\Library\View\Model
 */
class JsonModel extends ZFJsonModel {

    /**
     * Serialize to JSON
     *
     * @return string
     */
    public function serialize()
    {
        $variables = $this->getVariables();
        if ($variables instanceof Traversable) {
            $variables = ArrayUtils::iteratorToArray($variables);
        }
        if (null !== $this->jsonpCallback) {
            // Leave jsonpCallback as default. i.e. Json::encode
            return $this->jsonpCallback.'('.Json::encode($variables).');';
        }

        // Using PHP's inbuilt function. Always return pretty.
        return json_encode( $variables, JSON_PRETTY_PRINT );
    }

} // class
