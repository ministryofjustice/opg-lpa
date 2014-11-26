<?php

namespace Opg\Lpa\Pdf\Service;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\Forms\Lp1f;
use Opg\Lpa\Pdf\Service\Forms\Lp1h;
use Opg\Lpa\Pdf\Service\Forms\Lp3;


class Generator implements GeneratorInterface {

    const TYPE_FORM_LP1H = 'LP1H';
    const TYPE_FORM_LP1F = 'LP1F';
    const TYPE_FORM_LP3 = 'LP3';
    
    protected $config;
    protected $type;
    protected $lpa;
    protected $response;

    public function __construct( Config $config, $type, Lpa $lpa, ResponseInterface $response ){

        # CHECK $TYPE IS VALID. THROW AN EXCEPTION IF NOT.

        $this->config = $config;
        $this->type = $type;
        $this->lpa = $lpa;
        $this->response = $response;
    }

    /**
     * Returns bool true iff the document was successfully generated and passed to $response->save().
     * Otherwise returns a string describing the error is returned.
     *
     * @return bool|string
     */
    public function generate(){

        if( $this->lpa->validate()->hasErrors() ){
            // The LPA is invalid, return an error.
        }

        if( $this->lpa->isComplete() !== true ){
            // The LPA is not complete, return an error.
        }

        //---

        # GENERATE THE PDF, STORING IN A LOCAL TMP FILE UNDER /tmp
        switch($this->type) {
            case self::TYPE_FORM_LP1F:
                $pdf = new Lp1f($this->lpa);
                break;
            case self::TYPE_FORM_LP1H:
                $pdf = new Lp1h($this->lpa);
                break;
            case self::TYPE_FORM_LP3:
                $pdf = new Lp3($this->lpa);
                break;
            default:
                throw new \UnexpectedValueException('Invalid form type: '.$this->type);
                return;
        }
        
        $filePath = $pdf->generate()
                        ->getPdfFilePath();
        
        //---

        # PASS THE GENERATED FILE TO $this->response->save( new SplFileInfo( $filePath ) );
        
        $this->response->save( new \SplFileInfo( $filePath ) );

        //---

        # DELETE THE LOCAL TEMP FILE

        //---

        return true;

    } // function

} // class
