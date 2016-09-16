<?php
namespace Opg\Lpa\Api\Client\Response;

interface ErrorInterface {

    /**
     * @return null|string
     */
    public function getDetail();

}
