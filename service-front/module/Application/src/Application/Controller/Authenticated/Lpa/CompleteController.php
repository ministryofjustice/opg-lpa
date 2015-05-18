<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Elements\Name;

class CompleteController extends AbstractLpaController
{
    
    protected $contentHeader = 'complete-partial.phtml';
    
    public function indexAction()
    {
        $viewParams = $this->getViewParams();
        
        $viewModel = new ViewModel($viewParams);
        
        $payment = $this->getLpa()->payment;
        if($payment->reducedFeeUniversalCredit) {
            $viewModel->setTemplate('application/complete/payment-pending-universal-credit.phtml');
        }
        elseif($payment->amount == 0) {
            $viewModel->setTemplate('application/complete/no-need-to-pay.phtml');
        }
        elseif($payment->method == Payment::PAYMENT_TYPE_CARD) {
            $viewModel->setTemplate('application/complete/pay-by-card.phtml');
        }
        else {
            $viewModel->setTemplate('application/complete/pay-by-cheque.phtml');
        }
        
        return $viewModel;
    }
    
    public function viewDocsAction()
    {
        return new ViewModel($this->getViewParams());
    }
    
    private function getViewParams()
    {
        $lpa = $this->getLpa();
        
        $viewParams = [
                'completedAt'        => $lpa->completedAt->format('d/m/Y'),
                'donorName'          => $lpa->document->donor->name,
                'lp1Url'             => $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpa->id, 'pdf-type'=>'lp1']),
                'cloneUrl'           => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id'=>$lpa->id]),
                'formName'           => (($lpa->document->type==Document::LPA_TYPE_PF)?'LP1F':'LP1H'),
                'correspondentName'  => (($lpa->document->correspondent->name instanceof Name)?$lpa->document->correspondent->name:$lpa->document->correspondent->company),
                'paymentAmount'      => $lpa->payment->amount,
                'paymentReferenceNo' => $lpa->payment->reference,
                'hasRemission'       => ($this->getFlowChecker()->isEligibleForFeeReduction()),
        ];
        
        if(count($lpa->document->peopleToNotify) > 0) {
            $viewParams['lp3Url'] = $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpa->id, 'pdf-type'=>'lp3']);
            $viewParams['peopleToNotify'] = $lpa->document->peopleToNotify;
        }
        
        if($this->getFlowChecker()->isEligibleForFeeReduction()) {
            $viewParams['lpa120Url'] = $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpa->id, 'pdf-type'=>'lpa120']);
        }
        
        
        return $viewParams;
    }
}
