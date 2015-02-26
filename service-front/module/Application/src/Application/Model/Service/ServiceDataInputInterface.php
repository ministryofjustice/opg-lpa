<?php
namespace Application\Model\Service;

/**
 * Used to pass complex data types into the Service layer (typically a Zend Form object)
 *
 * Interface ServiceDataInputInterface
 * @package Application\Model\Service
 */
interface ServiceDataInputInterface {

    /**
     * @return array Data from the input object ready for ingestion by a LPA Model.
     */
    public function getDataForModel();

}
