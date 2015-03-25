<?php
namespace Opg\Lpa\Pdf\Service;

use RuntimeException;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\Forms\Lp1f;
use Opg\Lpa\Pdf\Service\Forms\Lp1h;
use Opg\Lpa\Pdf\Service\Forms\Lp3;
use Opg\Lpa\Pdf\Service\Forms\Lpa120;
use Opg\Lpa\DataModel\Lpa\Document\Document;

use Opg\Lpa\DataModel\Lpa\StateChecker;


class Generator implements GeneratorInterface {

    const TYPE_FORM_LP1    = 'LP1';
    const TYPE_FORM_LP3     = 'LP3';
    const TYPE_FORM_LPA120  = 'LPA120';
    
    protected $config;
    protected $formType;
    protected $lpa;
    protected $response;
    
    public function __construct( $formType, Lpa $lpa, ResponseInterface $response ){

        # CHECK $TYPE IS VALID. THROW AN EXCEPTION IF NOT.

        $this->config = Config::getInstance( );
        $this->formType = $formType;
        $this->lpa = $lpa;
        $this->response = $response;
        
        // copy pdf template files to ram if they haven't.
        $this->copyPdfSourceToRam();
        
    }

    /**
     * Returns bool true iff the document was successfully generated and passed to $response->save().
     * Otherwise returns a string describing the error is returned.
     *
     * @return bool|string
     */
    public function generate(){

        if( $this->lpa->validate()->hasErrors() ){
            // The LPA is invalid.
            throw new RuntimeException('LPA failed validation');
        }
        
        //---

        $state = new StateChecker( $this->lpa );
        
        # GENERATE THE PDF, STORING IN A LOCAL TMP FILE UNDER /tmp
        switch($this->formType) {
            case self::TYPE_FORM_LP1:

                if( !$state->canGenerateLP1() ){
                    throw new RuntimeException('LPA does not contain all the required data to generate a LP1');
                }

                switch($this->lpa->document->type) {
                    case Document::LPA_TYPE_PF:
                        $pdf = new Lp1f($this->lpa);
                        break;
                    case Document::LPA_TYPE_HW:
                        $pdf = new Lp1h($this->lpa);
                        break;
                }

                break;
            case self::TYPE_FORM_LP3:

                if( !$state->canGenerateLP3() ){
                    throw new RuntimeException('LPA does not contain all the required data to generate a LP3');
                }

                $pdf = new Lp3($this->lpa);

                break;
            case self::TYPE_FORM_LPA120:

                if( !$state->canGenerateLPA120() ){
                    throw new RuntimeException('LPA does not contain all the required data to generate a LPA120');
                }

                $pdf = new Lpa120($this->lpa);

                break;
            default:
                throw new \UnexpectedValueException('Invalid form type: '.$this->formType);
                return;
        }
        
        if($pdf->generate()) {
            $filePath = $pdf->getPdfFilePath();
        }
        else {
            return false;
        }
        
        //---

        # PASS THE GENERATED FILE TO $this->response->save( new SplFileInfo( $filePath ) );
        
        $this->response->save( new \SplFileInfo( $filePath ) );

        //---

        # DELETE THE LOCAL TEMP FILE
        $pdf->cleanup();
        
        //--- temp files deleted at the end of $pdf's life cycle - in it's destructor.

        return true;

    } // function
    
    /**
     * Copy LPA PDF template files into ram disk if they are not found on the ram disk.
     */
    private function copyPdfSourceToRam()
    {
        // check if 
        if(!\file_exists($this->config['service']['assets']['template_path_on_ram_disk'])) {
            \mkdir($this->config['service']['assets']['template_path_on_ram_disk'], 0777, true);
        }
        
        foreach(glob($this->config['service']['assets']['source_template_path'].'/*.pdf') as $pdf_source) {
            $pathInfo = pathinfo($pdf_source);
            
            if(!\file_exists($this->config['service']['assets']['template_path_on_ram_disk'].'/'.$pathInfo['basename'])) {
                copy($pdf_source, $this->config['service']['assets']['template_path_on_ram_disk'].'/'.$pathInfo['basename']);
            }
        }
    }

} // class
