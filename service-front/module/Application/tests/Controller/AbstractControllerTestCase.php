<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Exception;
use Application\Controller\AbstractAuthenticatedController;
use Application\Controller\AbstractBaseController;
use Application\Controller\AbstractLpaController;
use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\Authenticated\Lpa\CertificateProviderControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\CorrespondentControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\DonorControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\PeopleToNotifyControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\PrimaryAttorneyControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\ReplacementAttorneyControllerTest;
use Laminas\Session\SessionManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use MakeSharedTest\DataModel\FixturesData;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\ResponseCollection;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\CreateHttpNotFoundModel;
use Laminas\Mvc\Controller\Plugin\FlashMessenger;
use Laminas\Mvc\Controller\Plugin\Forward;
use Laminas\Mvc\Controller\Plugin\Layout;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\Controller\Plugin\Url;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Router\Http\RouteMatch as HttpRouteMatch;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\Session\Container;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Stdlib\Parameters;
use Laminas\Uri\Uri;
use DateTime;
use Psr\Log\LoggerInterface;

abstract class AbstractControllerTestCase extends MockeryTestCase
{
    /**
     * @var MockInterface|LoggerInterface
     */
    protected $logger;
    /**
     * @var Lpa
     */
    protected $lpa;
    /**
     * @var MockInterface|AuthenticationService
     */
    protected $authenticationService;
    /**
     * @var MockInterface|PluginManager
     */
    protected $pluginManager;
    /**
     * @var MockInterface|Redirect
     */
    protected $redirect;
    /**
     * @var MockInterface|Params
     */
    protected $params;
    /**
     * @var MockInterface|Url
     */
    protected $url;
    /**
     * @var MockInterface|FlashMessenger
     */
    protected $flashMessenger;
    /**
     * @var MockInterface|CreateHttpNotFoundModel
     */
    protected $createHttpNotFoundModel;
    /**
     * @var MockInterface|Layout
     */
    protected $layout;
    /**
     * @var MockInterface|Forward
     */
    protected $forward;
    /**
     * @var MockInterface|EventManager
     */
    protected $eventManager;
    /**
     * @var MockInterface|ResponseCollection
     */
    protected $responseCollection;
    /**
     * @var array
     */
    protected $config;
    /**
     * @var MockInterface|AbstractPluginManager
     */
    protected $formElementManager;
    /**
     * @var StorageInterface|ArrayStorage
     */
    protected $storage;
    /**
     * @var MockInterface|SessionManager
     */
    protected $sessionManager;
    /**
     * @var MockInterface|SessionManagerSupport
     */
    protected $sessionManagerSupport;
    /**
     * @var MockInterface|LpaApplicationService
     */
    protected $lpaApplicationService;
    /**
     * @var MockInterface|LpaAuthAdapter
     */
    protected $authenticationAdapter;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var UserIdentity
     */
    protected $userIdentity;
    /**
     * @var MockInterface|ReplacementAttorneyCleanup
     */
    protected $replacementAttorneyCleanup;
    /**
     * @var MockInterface|Request
     */
    protected $request;
    /**
     * @var MockInterface|Client
     */
    protected $apiClient;
    /**
     * @var MockInterface|Metadata
     */
    protected $metadata;
    /**
     * @var MockInterface|RouteStackInterface
     */
    protected $router;
    /**
     * @var MockInterface|Details
     */
    protected $userDetails;

    /** @var MockInterface|RouteMatch */
    protected $routeMatch;

    /** @var MockInterface|SessionUtility */
    protected $sessionUtility;

    /**
     * Set up the services in default configuration - these can be adapted
     * in the subclasses before getting the controller to test
     */
    public function setUp(): void
    {
        $this->lpa = FixturesData::getPfLpa();

        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->pluginManager = Mockery::mock(PluginManager::class);
        $this->pluginManager->shouldReceive('setController');

        $this->redirect = Mockery::mock(Redirect::class);
        $this->pluginManager->shouldReceive('get')->withArgs(['redirect', null])->andReturn($this->redirect);

        $this->params = Mockery::mock(Params::class);
        $this->params->shouldReceive('__invoke')->andReturn($this->params);
        $this->pluginManager->shouldReceive('get')->withArgs(['params', null])->andReturn($this->params);

        $this->url = Mockery::mock(Url::class);
        $this->pluginManager->shouldReceive('get')->withArgs(['url', null])->andReturn($this->url);

        $this->flashMessenger = Mockery::mock(FlashMessenger::class);
        $this->pluginManager->shouldReceive('get')
            ->withArgs(['flashMessenger', null])->andReturn($this->flashMessenger);

        $this->createHttpNotFoundModel = new CreateHttpNotFoundModel();
        $this->pluginManager->shouldReceive('get')
            ->withArgs(['createHttpNotFoundModel', null])->andReturn($this->createHttpNotFoundModel);

        $this->layout = Mockery::mock(Layout::class);
        $this->pluginManager->shouldReceive('get')->withArgs(['layout', null])->andReturn($this->layout);

        $this->forward = Mockery::mock(Forward::class);
        $this->pluginManager->shouldReceive('get')->withArgs(['forward', null])->andReturn($this->forward);

        $this->eventManager = Mockery::mock(EventManager::class);
        $this->eventManager->shouldReceive('setIdentifiers');
        $this->eventManager->shouldReceive('attach');

        $this->responseCollection = Mockery::mock(ResponseCollection::class);
        $this->eventManager->shouldReceive('triggerEventUntil')->andReturn($this->responseCollection);

        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);

        $this->storage = new ArrayStorage();

        $this->sessionManager = Mockery::mock(SessionManager::class);
        $this->sessionManager->shouldReceive('getStorage')->andReturn($this->storage)->byDefault();
        $this->sessionManager->shouldReceive('start')->andReturnNull()->byDefault();
        $this->sessionManager->shouldReceive('regenerateId')->with(true)->andReturnNull()->byDefault();

        $this->sessionUtility = Mockery::mock(SessionUtility::class);

        $this->sessionManagerSupport = new SessionManagerSupport($this->sessionManager, $this->sessionUtility);
        $this->user = $this->getUserDetails();

        $this->setIdentity(
            new UserIdentity($this->user->id, 'token', 60 * 60, new DateTime('today midnight'))
        );

        //  Config array merged so it can be updated in calling test class if required
        $this->config = [
            'version' => [
                'tag' => '1.2.3.4-test',
            ],
            'terms' => [
                'lastUpdated' => '2015-02-17 14:00 UTC',
            ],
            'session' => [
                'native_settings' => [
                    'name' => 'lpa'
                ]
            ],
            'redirects' => [
                'index' => 'https://www.gov.uk/power-of-attorney/make-lasting-power',
                'logout' => 'https://www.gov.uk/done/lasting-power-of-attorney',
            ],
            'account-cleanup' => [
                'notification' => [
                    'token' => 'validAccountCleanupToken',
                ],
            ],
            'email' => [
                'sender' => [
                    'default' => [
                        'name' => 'Unit Tests',
                        'address' => 'unit@test.com',
                    ]
                ],
            ],
            'processing-status' => [
                'track-from-date' => '2017-01-01',
                'expected-working-days-before-receipt' => 15,
            ]
        ];

        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);

        $this->userDetails = Mockery::mock(Details::class);

        $this->request = Mockery::mock(Request::class);

        $this->responseCollection->shouldReceive('stopped')->andReturn(false);

        $this->apiClient = Mockery::mock(Client::class);

        $this->router = Mockery::mock(RouteStackInterface::class);

        $this->replacementAttorneyCleanup = Mockery::mock(ReplacementAttorneyCleanup::class);

        $this->metadata = Mockery::mock(Metadata::class);

        $this->routeMatch = Mockery::mock(RouteMatch::class);
    }

    /**
     * Set up the identity and the authentication service responses
     */
    protected function setIdentity(?UserIdentity $identity = null)
    {
        $this->userIdentity = $identity;

        //  Mock or remock the authentication service
        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(!is_null($this->userIdentity));
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity);
    }

    /**
     * @return AbstractBaseController
     */
    protected function getController(string $controllerName)
    {
        /** @var AbstractBaseController $controller */
        if (is_subclass_of($controllerName, AbstractAuthenticatedController::class)) {
            $this->sessionUtility->shouldReceive('getFromMvc')
                ->withArgs(['UserDetails', 'user'])
                ->andReturn($this->user)
                ->byDefault();

            if (is_subclass_of($controllerName, AbstractLpaController::class)) {
                $lpaId = ($this->lpa instanceof Lpa ? $this->lpa->id : null);

                //  If there is no identity then the getApplication call will not be made in the abstract contructor
                if (!is_null($this->userIdentity)) {
                    if ($this->lpa instanceof Lpa) {
                        $this->lpaApplicationService->shouldReceive('getApplication')
                            ->withArgs([$lpaId])
                            ->andReturn($this->lpa)
                            ->once();
                    } else {
                        $this->lpaApplicationService->shouldReceive('getApplication')
                            ->withArgs([$lpaId])
                            ->andReturn(false)
                            ->once();
                    }
                }

                $controller = new $controllerName(
                    $lpaId,
                    $this->formElementManager,
                    $this->sessionManagerSupport,
                    $this->authenticationService,
                    $this->config,
                    $this->lpaApplicationService,
                    $this->userDetails,
                    $this->replacementAttorneyCleanup,
                    $this->metadata,
                    $this->sessionUtility,
                );
            } else {
                $controller = new $controllerName(
                    $this->formElementManager,
                    $this->sessionManagerSupport,
                    $this->authenticationService,
                    $this->config,
                    $this->lpaApplicationService,
                    $this->userDetails,
                    $this->sessionUtility,
                );
            }
        } else {
            $controller = new $controllerName(
                $this->formElementManager,
                $this->sessionManagerSupport,
                $this->authenticationService,
                $this->config,
                $this->sessionUtility,
            );
        }

        $controller->setLogger($this->logger);

        $controller->setPluginManager($this->pluginManager);
        $controller->setEventManager($this->eventManager);

        $controller->setEvent(new MvcEvent());

        $controller->dispatch($this->request);

        return $controller;
    }

    /**
     * @param AbstractController $controller
     * @return MockInterface|RouteMatch
     */
    public function getRouteMatch($controller)
    {
        $controller->getEvent()->setRouteMatch($this->routeMatch);
        return $this->routeMatch;
    }

    /**
     * @param AbstractController $controller
     * @return MockInterface|RouteMatch
     */
    public function getHttpRouteMatch($controller)
    {
        $routeMatch = Mockery::mock(HttpRouteMatch::class);
        $controller->getEvent()->setRouteMatch($routeMatch);
        return $routeMatch;
    }

    /**
     * @param AbstractController $controller
     * @param string $routeName
     * @return MockInterface|null|RouteMatch
     */
    public function setMatchedRouteName($controller, $routeName, $routeMatch = null)
    {
        $routeMatch = $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($routeName)->once();
        return $routeMatch;
    }

    /**
     * @param AbstractController $controller
     * @param string $routeName
     * @return MockInterface|RouteMatch
     */
    public function setMatchedRouteNameHttp($controller, $routeName, $expectedMatchedRouteNameTimes = 1)
    {
        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getMatchedRouteName')
            ->andReturn($routeName)
            ->times($expectedMatchedRouteNameTimes);
        return $routeMatch;
    }

    public function setSeedLpa($lpa, $seedLpa): void
    {
        $lpa->seed = $seedLpa->id;

        $seedData = $this->getSeedData($seedLpa);

        $this->lpaApplicationService
            ->shouldReceive('getSeedDetails')
            ->withArgs([$lpa->id])
            ->andReturn($seedData);

        //Make sure the container hasn't cached the seed lpa
        $seedId = $lpa->seed;
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('clone', $seedId)
            ->andReturn(null);

        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('clone', $seedId, $seedData);
    }

    /**
     * @param Lpa $seedLpa
     * @return array
     */
    public function getSeedData($seedLpa)
    {
        $result = ['seed' => $seedLpa->getId()];

        if ($seedLpa->getDocument() == null) {
            return $result;
        }

        $document = $seedLpa->getDocument()->toArray();

        $result += array_intersect_key($document, array_flip([
                'donor',
                'correspondent',
                'certificateProvider',
                'primaryAttorneys',
                'replacementAttorneys',
                'peopleToNotify'
            ]));

        return array_filter($result, function ($v): bool {
            return !empty($v);
        });
    }

    /**
     * @param MockInterface $form
     * @param int $expectedPostTimes
     */
    public function setPostInvalid($form, array $postData = [], $dataToSet = null, $expectedPostTimes = 1): void
    {
        //  Post data is got from the form it will be a Parameters object
        $postData = (is_array($postData) ? new Parameters($postData) : $postData);

        //  If the data to set is null then make it equal to the post data in array form
        if (is_null($dataToSet)) {
            $dataToSet = $postData;
        }

        $this->request->shouldReceive('isPost')->andReturn(true)->times($expectedPostTimes);
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $form->shouldReceive('setData')->withArgs([$dataToSet])->once();
        $form->shouldReceive('isValid')->andReturn(false)->once();
    }

    /**
     * @param MockInterface $form
     * @param int $expectedPostTimes
     * @param int $expectedGetPostTimes
     */
    public function setPostValid(
        $form,
        array $postData = [],
        $dataToSet = null,
        $expectedPostTimes = 1,
        $expectedGetPostTimes = 1
    ): void {
        //  Post data is got from the form it will be a Parameters object
        $postData = (is_array($postData) ? new Parameters($postData) : $postData);

        //  If the data to set is null then make it equal to the post data in array form
        if (is_null($dataToSet)) {
            $dataToSet = $postData;
        }

        $this->request->shouldReceive('isPost')->andReturn(true)->times($expectedPostTimes);
        $this->request->shouldReceive('getPost')->andReturn($postData)->times($expectedGetPostTimes);
        $form->shouldReceive('setData')->withArgs([$dataToSet])->once();
        $form->shouldReceive('isValid')->andReturn(true)->once();
    }

    public function setRedirectToRoute($route, $lpa, $response): void
    {
        $args = [$route, ['lpa-id' => $lpa->id]];
        $args[] = $this->getExpectedRouteOptions($route);
        $this->redirect->shouldReceive('toRoute')->withArgs($args)->andReturn($response)->once();
    }

    /**
     * @param User $user
     * @param Lpa $lpa
     * @param string $lpaRoute e.g. lpa/certificate-provider/edit
     * @param Response $response
     */
    public function setRedirectToReuseDetails($user, $lpa, $lpaRoute, $response): void
    {
        $this->sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($user)
            ->byDefault();

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $url = str_replace('lpa/', "http://localhost/lpa/{$lpa->getId()}/", $lpaRoute);
        $uri = new Uri($url);

        $includeTrusts = false;
        $actorName = null;

        if ($this instanceof CertificateProviderControllerTest) {
            $actorName = 'Certificate provider';
        } elseif ($this instanceof CorrespondentControllerTest) {
            $actorName = 'Correspondent';
        } elseif ($this instanceof DonorControllerTest) {
            $actorName = 'Donor';
        } elseif ($this instanceof PeopleToNotifyControllerTest) {
            $actorName = 'Person to notify';
        } elseif ($this instanceof PrimaryAttorneyControllerTest) {
            $includeTrusts = true;
            $actorName = 'Attorney';
        } elseif ($this instanceof ReplacementAttorneyControllerTest) {
            $includeTrusts = true;
            $actorName = 'Replacement attorney';
        }

        $this->request->shouldReceive('getUri')->andReturn($uri)->once();
        $queryParams = [
            'calling-url'    => $uri->getPath(),
            'include-trusts' => $includeTrusts,
            'actor-name'     => $actorName,
        ];

        $reuseDetailsUrl = "lpa/{$lpa->getId()}/reuse-details?" . implode('&', array_map(function ($value, $key): string {
            $valueString = is_bool($value) ? $value === true ? '1' : '0' : $value;
            return "$key=$valueString";
        }, $queryParams, array_keys($queryParams)));

        $this->url->shouldReceive('fromRoute')
            ->withArgs([
                'lpa/reuse-details',
                ['lpa-id' => $lpa->getId()],
                ['query' => $queryParams]
            ])->andReturn($reuseDetailsUrl)->once();

        $this->redirect->shouldReceive('toUrl')->withArgs([$reuseDetailsUrl])->andReturn($response);
    }

    /**
     * @param MockInterface|AbstractController $controller
     * @param MockInterface $form
     * @param $user
     * @param $who
     * @return MockInterface|RouteMatch
     */
    public function setReuseDetails($controller, $form, $user, $who)
    {
        $this->sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($user)
            ->byDefault();

        $routeMatch = $this->getRouteMatch($controller);

        $actorReuseDetails = $controller->testGetActorReuseDetails();

        $index = 0;
        foreach ($actorReuseDetails as $key => $value) {
            if ($value['data']['who'] === $who) {
                $index = $key;
                break;
            }
        }

        $routeMatch->shouldReceive('getParam')->withArgs(['reuseDetailsIndex'])->andReturn($index)->once();

        $form->shouldReceive('bind')->withArgs([$actorReuseDetails[$index]['data']])->once();

        return $routeMatch;
    }

    /**
     * @param MockInterface $form
     * @param $lpa
     * @param $route
     * @param int $expectedFromRouteTimes
     * @return mixed
     */
    public function setFormAction($form, $lpa, $route, $expectedFromRouteTimes = 1)
    {
        $url = $this->getLpaUrl($lpa, $route);
        $this->url->shouldReceive('fromRoute')->withArgs([$route, ['lpa-id' => $lpa->id]])
            ->andReturn($url)->times($expectedFromRouteTimes);
        $form->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        return $url;
    }

    /**
     * @param MockInterface $form
     * @param $lpa
     * @param $route
     * @param $idx
     * @param int $expectedFromRouteTimes
     * @return mixed
     */
    public function setFormActionIndex($form, $lpa, $route, $idx, $expectedFromRouteTimes = 1)
    {
        $url = $this->getLpaUrl($lpa, $route, ['idx' => $idx]);
        $this->url->shouldReceive('fromRoute')->withArgs([$route, ['lpa-id' => $lpa->id, 'idx' => $idx]])
            ->andReturn($url)->times($expectedFromRouteTimes);
        $form->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        return $url;
    }

    public function setUrlFromRoute($lpa, $route, $extraQueryParameters = null, $fragment = null)
    {
        $url = $this->getLpaUrl($lpa, $route, $extraQueryParameters, $fragment);
        $queryParameters = ['lpa-id' => $lpa->id];

        if (is_array($extraQueryParameters)) {
            $queryParameters = array_merge($queryParameters, $extraQueryParameters);
        }

        if (is_array($fragment)) {
            $this->url->shouldReceive('fromRoute')
                ->withArgs([$route, $queryParameters, $fragment])->andReturn($url)->once();
        } else {
            $this->url->shouldReceive('fromRoute')->withArgs([$route, $queryParameters])->andReturn($url)->once();
        }

        return $url;
    }

    /**
     * @param AbstractAttorney $attorney
     * @return mixed
     */
    public function getFlattenedAttorneyData($attorney)
    {
        $flattenAttorneyData = $attorney->flatten();

        if ($attorney instanceof Human) {
            $dob = $attorney->getDob()->getDate();
            $flattenAttorneyData['dob-date'] = [
                'day'   => $dob->format('d'),
                'month' => $dob->format('m'),
                'year'  => $dob->format('Y'),
            ];
        }

        return $flattenAttorneyData;
    }

    public function tearDown(): void
    {
        // We have what are effectively global variables to track status from Module.php into
        // controllers, as Module.php bootstraps identity via the API. Clear out the containers
        // so we can be sure there's nothing being carried between tests.
        $preAuthRequest = new Container('PreAuthRequest');
        $preAuthRequest->url = null;

        $authFailureReason = new Container('AuthFailureReason');
        $authFailureReason->code = null;
        $authFailureReason->reason = null;

        parent::tearDown();
    }

    /**
     * @param $lpa
     * @param $route
     */
    private function getLpaUrl($lpa, $route, $queryParameters = null, $fragment = null): array|string
    {
        $url = str_replace('lpa/', "lpa/{$lpa->id}/", $route);
        $queryParams = [];
        if (is_array($queryParameters)) {
            $queryParams = array_merge($queryParams, $queryParameters);
        }
        if (is_array($fragment)) {
            $queryParams = array_merge($queryParams, $fragment);
        }
        if (count($queryParams) > 0) {
            $url .= '?' . implode('&', array_map(function ($value, $key): string {
                $valueString = is_bool($value) ? $value === true ? '1' : '0' : $value;
                return "$key=$valueString";
            }, $queryParams, array_keys($queryParams)));
        }
        return $url;
    }

    public function getExpectedRouteOptions($route)
    {
        return [];
    }

    /**
     * Get sample user details
     *
     * @param bool $newDetails
     * @throws Exception
     */
    private function getUserDetails($newDetails = false): User
    {
        $user = new User();

        if (!$newDetails) {
            //  Just set a name for the user details to be considered existing
            //  But user the user ID from the LPA fixture data
            $user->id = $this->lpa->user;

            $user->createdAt = new DateTime();

            $user->updatedAt = new DateTime();

            $user->name = new Name([
                'title' => 'Mrs',
                'first' => 'New',
                'last'  => 'User',
            ]);

            $user->email = new EmailAddress([
                'address' => 'unit@test.com',
            ]);

            $user->dob = new Dob(['date' => '1957-12-17T00:00:00.000Z']);
        }

        return $user;
    }
}
