<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Laminas\Http\Request as HttpRequest;
use Laminas\Router\RouteStackInterface;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class ReuseDetailsController extends AbstractLpaActorController
{
    use LoggerTrait;

    /** @var RouteStackInterface */
    private $router;

    public function indexAction()
    {
        $viewModel = new ViewModel();

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Check that the required query params have been provided
        $queryParams = $this->params()->fromQuery() ?? [];
        $callingUrl = $queryParams['calling-url'] ?? null;
        $includeTrusts = $queryParams['include-trusts'] ?? null;
        $actorName = $queryParams['actor-name'] ?? null;

        if (is_null($callingUrl) || is_null($includeTrusts) || is_null($actorName)) {
            throw new \RuntimeException(
                'Required data missing when attempting to load the reuse details screen'
            );
        }

        //  Generate the reuse details form
        $forCorrespondent = (strpos($callingUrl, 'correspondent') !== false);

        $actorReuseDetails = $this->getActorReuseDetails((bool) $includeTrusts, $forCorrespondent);

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\ReuseDetailsForm', [
                         'actorReuseDetails' => $actorReuseDetails,
                     ]);

        $form->setAttribute('action', $this->getReuseDetailsUrl($queryParams));

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();
                $reuseDetailsIndex = $data['reuse-details'];

                // By default the calling URI will be used as a target to return to (via a forward)
                // But if the trust option was selected then adapt the return URL accordingly
                $returnURL = $callingUrl . ($reuseDetailsIndex == 't' ? '-trust' : '');

                // Get the controller and action for the calling route so we can forward the details back there
                $controllerName = $actionName = null;

                // Match the route using a request object
                $requestMatcher = new HttpRequest();
                $requestMatcher->setUri($returnURL);
                $routeMatch = $this->router->match($requestMatcher);

                if ($routeMatch !== null) {
                    $controllerName = $routeMatch->getParam('controller');
                    $actionName = $routeMatch->getParam('action');
                }

                //  Confirm that the controller and action name have been determined
                if (is_null($controllerName) || is_null($actionName)) {
                    throw new \RuntimeException(
                        'Calling controller or action could not be determined ' .
                        'for processing reuse details request'
                    );
                }

                return $this->forward()->dispatch($controllerName, [
                    'action'            => $actionName,
                    'reuseDetailsIndex' => $reuseDetailsIndex,
                    'callingUrl'        => $callingUrl,
                ]);
            }
        }

        $viewModel->form = $form;

        //  Determine the cancel URL from the calling URL and set it in the view
        $viewModel->cancelUrl = substr($callingUrl, 0, strrpos($callingUrl, '/'));

        //  Set the actor name in the view
        $viewModel->actorName = $actorName;

        return $viewModel;
    }

    public function setRouter(RouteStackInterface $router)
    {
        $this->router = $router;
    }
}
