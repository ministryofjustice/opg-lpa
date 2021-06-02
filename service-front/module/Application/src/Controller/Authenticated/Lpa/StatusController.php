<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Laminas\View\Model\ViewModel;
use DateTime;

/**
 * Class StatusController
 * @package Application\Controller\Authenticated\Lpa
 */
class StatusController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $lpaStatus = null;

        if ($lpa->getCompletedAt() instanceof DateTime) {
            $lpaStatus = 'completed';

            $trackFromDate = new DateTime($this->config()['processing-status']['track-from-date']);

            if ($trackFromDate <= new DateTime('now') && $trackFromDate <= $lpa->getCompletedAt()) {
                $lpaStatus = 'waiting';

                $lpaStatusDetails = $this->getLpaApplicationService()->getStatuses($lpa->getId());

                if ($lpaStatusDetails[$lpa->getId()]['found'] == true) {
                    $lpaStatus = strtolower($lpaStatusDetails[$lpa->getId()]['status']);
                }
            }
        }

        //  Keep these statues in workflow order
        $statuses = ['completed', 'waiting', 'received', 'checking', 'returned'];
        if (!in_array($lpaStatus, $statuses)) {
            return $this->redirect()->toRoute('user/dashboard');
        }

        //  Determine what statuses should trigger the current status to display as 'done'
        $doneStatuses = array_slice($statuses, 0, array_search($lpaStatus, $statuses));

        // Return either the applicationRejectedDate or the applicationRegistrationDate based on what's received from Sirius
        $metadata = $lpa->getMetadata();

        if (isset($metadata['application-rejected-date']))
            $returnDate = $metadata['application-rejected-date'];
        else if (isset($metadata['application-registration-date']))
            $returnDate = $metadata['application-registration-date'];
        else
            $returnDate = null;

        return new ViewModel([
            'returnDate'   => $returnDate,
            'lpa'          => $lpa,
            'status'       => $lpaStatus,
            'doneStatuses' => $doneStatuses,
            'canGenerateLPA120' => $lpa->canGenerateLPA120(),
        ]);
    }
}
