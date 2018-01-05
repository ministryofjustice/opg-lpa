<?php

namespace Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('how', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Choice([
                'choices' => [
                    self::LPA_DECISION_HOW_DEPENDS,
                    self::LPA_DECISION_HOW_JOINTLY,
                    self::LPA_DECISION_HOW_SINGLE_ATTORNEY,
                    self::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                ]
            ]),
        ]);

        $metadata->addPropertyConstraints('howDetails', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => 1,
                'max' => (1000 * 1024)
            ]),
        ]);
    }

    /**
     * @return string
     */
    public function getHow()
    {
        return $this->how;
    }

    /**
     * @param string $how
     * @return $this
     */
    public function setHow($how): AbstractDecisions
    {
        $this->how = $how;

        return $this;
    }

    /**
     * @return string
     */
    public function getWhen()
    {
        return $this->when;
    }

    /**
     * @param string $when
     * @return $this
     */
    public function setWhen($when): AbstractDecisions
    {
        $this->when = $when;

        return $this;
    }

    /**
     * @return string
     */
    public function getHowDetails()
    {
        return $this->howDetails;
    }

    /**
     * @param string $howDetails
     * @return $this
     */
    public function setHowDetails($howDetails): AbstractDecisions
    {
        $this->howDetails = $howDetails;

        return $this;
    }
}
