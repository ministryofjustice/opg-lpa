<?php
namespace Application\Model\Rest\Pdfs;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface {

    protected $lpa;
    protected $details;

    //---

    const STATUS_NOT_AVAILABLE   = 'not-available';  // PDF cannot be generated as we do not have all teh data.
    const STATUS_NOT_QUEUED      = 'not-in-queue';   // The LPA is not in the PDF queue.
    const STATUS_IN_QUEUE        = 'in-queue';       // The LPA is in the PDF queue.
    const STATUS_READY           = 'ready';          // THe PDF is available for immediate download.

    //---

    public function __construct( Array $details, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->details = $details;

    }

    public function userId(){
        return $this->lpa->user;
    }

    public function lpaId(){
        return $this->lpa->id;
    }

    public function resourceId(){
        return $this->details['type'];
    }

    public function toArray(){
        return $this->details;
    }

} // class
