<?php

namespace MakeShared\DataModel\Lpa\Document\Decisions;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

abstract class AbstractDecisions extends AbstractData
{
    const LPA_DECISION_HOW_DEPENDS = 'depends';
    const LPA_DECISION_HOW_JOINTLY = 'jointly';
    const LPA_DECISION_HOW_SINGLE_ATTORNEY = 'single-attorney';
    const LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY = 'jointly-attorney-severally';

    /**
     * @var string Represents how decisions will be made.
     */
    protected $how;

    /**
     * Validators for 'when' should be in concrete classes.
     *
     * @var string Represents when decisions can be made.
     */
    protected $when;

    /**
     * @var string Additional free text details relating to this 'how' decision.
     */
    protected $howDetails;

    /**
     * @return string
     */
    public function getHow()
    {
        return $this->how;
    }

    /**
     * @return string
     */
    public function getWhen()
    {
        return $this->when;
    }
}
