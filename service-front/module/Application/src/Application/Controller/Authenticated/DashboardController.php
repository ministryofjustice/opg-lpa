<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $query = $this->params()->fromQuery('search', null);
        $page = $this->params()->fromRoute('page', 1);

        //  Get the LPA list using a query if provided
        $lpas = $this->getServiceLocator()->get('ApplicationList')->getLpaSummaries($query);

        //  If there are no LPAs and this is NOT a query, redirect them to create one...
        if (is_null($query) && count($lpas) == 0) {
            return $this->createAction();
        }

        // Sort by updatedAt into descending order
        // Once we remove #v1Code, perhaps we can assume they're pre-sorted from the API/DB?
        usort($lpas, function ($a, $b) {
            if ($a->updatedAt == $b->updatedAt) {
                return 0;
            }
            return ($a->updatedAt > $b->updatedAt) ? -1 : 1;
        });

        //  Set up the paginator and add the LPA data
        $paginator = new Paginator(new PaginatorArrayAdapter($lpas));

        $paginator->setPageRange(5)
                  ->setItemCountPerPage(50)
                  ->setCurrentPageNumber($page);

        return new ViewModel([
            'lpas' => $paginator,
            'freeText' => $query,
            'isSearch' => (is_string($query) && !empty($query)),
            'user' => [
                'lastLogin' => $this->getUser()->lastLogin(),
            ],
        ]);
    }

    /**
     * Creates a new LPA
     *
     * If 'lpa-id' is set, use the passed ID to seed the new LPA.
     */
    public function createAction()
    {
        $seedId = $this->params()->fromRoute('lpa-id');

        //-------------------------------------
        // If we're seeding the new LPA...

        if ($seedId != null) {
            //-------------------------------------
            // Create a new LPA...

            $lpa = $this->getLpaApplicationService()->createApplication();

            if (!$lpa instanceof Lpa) {
                $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');
                return $this->redirect()->toRoute('user/dashboard');
            }

            $result = $this->getLpaApplicationService()->setSeed($lpa->id, (int) $seedId);

            $this->resetSessionCloneData($seedId);

            if ($result !== true) {
                $this->flashMessenger()->addWarningMessage('LPA created but could not set seed');
            }

            // Redirect them to the first page...
            return $this->redirect()->toRoute('lpa/form-type', [ 'lpa-id'=>$lpa->id ]);
        }

        //---

        // Redirect them to the first page, no LPA created
        return $this->redirect()->toRoute('lpa-type-no-id');
    }

    public function deleteLpaAction()
    {
        $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');
        if ($this->getLpaApplicationService()->deleteApplication($lpaId) !== true) {
            throw new \RuntimeException('API client failed to delete LPA for id: '.$lpaId);
        }

        return $this->redirect()->toRoute('user/dashboard');
    }

    //---

    /**
     * Displayed when the Terms and Conditions have changed since the user last logged in.
     */
    public function termsAction()
    {
        return new ViewModel();
    }

    //------------------------------------------------------------------

    /**
     * This is overridden to prevent people being (accidently?) directed to this controller post-auth.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated($allowRedirect = true)
    {
        return parent::checkAuthenticated(false);
    }
}
