<?php

namespace ApplicationTest\Library\ApiProblem;

use Application\Library\ApiProblem\ValidationApiProblem;

class TestableValidationApiProblem extends ValidationApiProblem
{
    public function getAdditionalDetails()
    {
        return $this->additionalDetails;
    }
}
