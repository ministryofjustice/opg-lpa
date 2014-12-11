<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use mikehaertl\pdftk\Pdf;
use ZendPdf\PdfDocument;

class PdfProcessor
{
    static $pdfdk = null;
    static $zendPdf = null;
    
    static public function getPdftkInstance($path=null)
    {
        if(self::$pdfdk === null) {
            return new Pdf($path);
        }
        else {
            if($path) {
                self::$pdfdk->addFile($path);
            }
        
            return self::$pdfdk;
        }
    }
    
    static public function load($tmpFilePath)
    {
        if(self::$zendPdf === null) {
            return PdfDocument::load($tmpFilePath);        
        }
        else {
            self::$zendPdf->load($tmpFilePath);
        }
    }
    
    public function setPdfdkProcessor($pdf)
    {
        self::$pdfdk = $pdf;
    }
    
    public function setZendPdfProcessor($pdf)
    {
        self::$zendPdf = $pdf;
    }
}
?>