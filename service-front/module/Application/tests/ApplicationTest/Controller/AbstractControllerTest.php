<?php

namespace ApplicationTest\Controller;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Session\SessionManager;
use ApplicationTest\Controller\Authenticated\Lpa\CertificateProviderControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\CorrespondentControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\DonorControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\PeopleToNotifyControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\PrimaryAttorneyControllerTest;
use ApplicationTest\Controller\Authenticated\Lpa\ReplacementAttorneyControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Deprecated;
use Zend\EventManager\EventManager;
use Zend\EventManager\ResponseCollection;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Controller\Plugin\CreateHttpNotFoundModel;
use Zend\Mvc\Controller\Plugin\FlashMessenger;
use Zend\Mvc\Controller\Plugin\Forward;
use Zend\Mvc\Controller\Plugin\Layout;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\Mvc\Controller\Plugin\Url;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\Http\RouteMatch as HttpRouteMatch;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;
use Zend\Session\Storage\StorageInterface;
use Zend\Stdlib\ArrayObject;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Uri\Uri;

abstract class AbstractControllerTest extends TestCase
{
    /**
     * @var MockInterface|ServiceLocatorInterface
     */
    protected $serviceLocator;
    /**
     * @var MockInterface|Logger
     */
    protected $logger;
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
     * @var MockInterface|ResetSessionCloneData
     */
    protected $resetSessionCloneData;
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
     * @var MockInterface|StorageInterface
     */
    protected $storage;
    /**
     * @var MockInterface|SessionManager
     */
    protected $sessionManager;
    /**
     * @var MockInterface|LpaApplicationService
     */
    protected $lpaApplicationService;
    /**
     * @var MockInterface|LpaAuthAdapter
     */
    protected $authenticationAdapter;
    /**
     * @var ArrayObject
     */
    protected $userDetailsSession;
    /**
     * @var User
     */
    protected $user = null;
    /**
     * @var UserIdentity
     */
    protected $userIdentity = null;
    /**
     * @var MockInterface|Request
     */
    protected $request;
    /**
     * @var MockInterface|Client
     */
    protected $apiClient;
    /**
     * @var MockInterface|StorageInterface
     */
    protected $cache;
    /**
     * @var MockInterface|Metadata
     */
    protected $metadata;
    /**
     * @var MockInterface
     */
    protected $router;

    /**
     * @param AbstractController $controller
     */
    public function controllerSetUp($controller)
    {
        //Required to suppress the deprecated error received when calling getServiceLocator()
        //Calling and using the service locator directly in code could be considered a IoC/DI anti pattern
        //Ideally we would be injecting dependencies via constructor args or setters via the IoC container
        //This work will be carried out as part of the upgrade to Zend 3
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $this->serviceLocator = Mockery::mock(ServiceLocatorInterface::class);

        $this->logger = Mockery::mock(Logger::class);
        $this->logger->shouldReceive('setWriters')->passthru();
        $this->logger->setWriters(new SplPriorityQueue());
        $this->serviceLocator->shouldReceive('get')->withArgs(['Logger'])->andReturn($this->logger);

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['AuthenticationService'])->andReturn($this->authenticationService);

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

        $this->config = [
            'version' => [
                'tag' => '1.2.3.4-test',
            ],
            'terms' => [
                'lastUpdated' => '2015-02-17 14:00 UTC',
            ],
            'admin' => [
                'accounts' => ['admin@test.com'],
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
                'sendgrid' => [
                    'webhook' => [
                        'token' => 'ValidToken',
                    ],
                ],
                'sender' => [
                    'default' => [
                        'name' => 'Unit Tests',
                        'address' => 'unit@test.com',
                    ]
                ],
            ]
        ];
        $this->serviceLocator->shouldReceive('get')->withArgs(['config'])->andReturn($this->config);
        $this->serviceLocator->shouldReceive('get')->withArgs(['Config'])->andReturn($this->config);

        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['FormElementManager'])->andReturn($this->formElementManager);

        $this->storage = Mockery::mock(StorageInterface::class);

        $this->sessionManager = Mockery::mock(SessionManager::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['SessionManager'])->andReturn($this->sessionManager);
        $this->sessionManager->shouldReceive('getStorage')->andReturn($this->storage);

        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['LpaApplicationService'])->andReturn($this->lpaApplicationService);

        $this->authenticationAdapter = Mockery::mock(LpaAuthAdapter::class);
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['AuthenticationAdapter'])->andReturn($this->authenticationAdapter);

        $this->userDetailsSession = new ArrayObject();
        $this->userDetailsSession->user = $this->user;
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['UserDetailsSession'])->andReturn($this->userDetailsSession);

        $controller->setServiceLocator($this->serviceLocator);
        $controller->setPluginManager($this->pluginManager);
        $controller->setEventManager($this->eventManager);

        $this->request = Mockery::mock(Request::class);

        $this->responseCollection->shouldReceive('stopped')->andReturn(false);
        $controller->dispatch($this->request);

        $this->apiClient = Mockery::mock(Client::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['ApiClient'])->andReturn($this->apiClient);

        $this->cache = Mockery::mock(StorageInterface::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['Cache'])->andReturn($this->cache);

        $this->metadata = Mockery::mock(Metadata::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['Metadata'])->andReturn($this->metadata);

        $this->router = Mockery::mock(ArrayObject::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['Router'])->andReturn($this->router);
    }

    /**
     * @param AbstractController $controller
     * @return MockInterface|RouteMatch
     */
    public function getRouteMatch($controller)
    {
        $event = new MvcEvent();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->setRouteMatch($routeMatch);
        $controller->setEvent($event);
        return $routeMatch;
    }

    /**
     * @param AbstractController $controller
     * @return MockInterface|RouteMatch
     */
    public function getHttpRouteMatch($controller)
    {
        $event = new MvcEvent();
        $routeMatch = Mockery::mock(HttpRouteMatch::class);
        $event->setRouteMatch($routeMatch);
        $controller->setEvent($event);
        return $routeMatch;
    }

    /**
     * @param AbstractController$controller
     * @param string $routeName
     */
    public function setMatchedRouteName($controller, $routeName, $routeMatch = null)
    {
        $routeMatch = $routeMatch ?: $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($routeName)->once();
        return $routeMatch;
    }

    /**
     * @param AbstractController$controller
     * @param string $routeName
     */
    public function setMatchedRouteNameHttp($controller, $routeName, $expectedMatchedRouteNameTimes = 1)
    {
        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($routeName)->times($expectedMatchedRouteNameTimes);
        return $routeMatch;
    }

    public function setSeedLpa($lpa, $seedLpa)
    {
        $lpa->seed = $seedLpa->id;
        $this->lpaApplicationService->shouldReceive('getSeedDetails')
            ->withArgs([$lpa->id])->andReturn($this->getSeedData($seedLpa))->once();

        //Make sure the container hasn't cached the seed lpa
        $seedId = $lpa->seed;
        $cloneContainer = new Container('clone');
        $cloneContainer->$seedId = null;
    }

    /**
     * @param Lpa $seedLpa
     * @return array
     */
    public function getSeedData($seedLpa)
    {
        $result = array('seed' => $seedLpa->id);

        if ($seedLpa->document == null) {
            return $result;
        }

        $document = $seedLpa->document->toArray();

        $result = $result + array_intersect_key($document, array_flip([
                'donor',
                'correspondent',
                'certificateProvider',
                'primaryAttorneys',
                'replacementAttorneys',
                'peopleToNotify'
            ]));

        $result = array_filter($result, function ($v) {
            return !empty($v);
        });

        return $result;
    }

    public function setPostInvalid($form, array $postData = [], $dataToSet = null, $expectedPostTimes = 1)
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

    public function setPostValid(
        $form,
        array $postData = [],
        $dataToSet = null,
        $expectedPostTimes = 1,
        $expectedGetPostTimes = 1
    ) {
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

    public function setRedirectToRoute($route, $lpa, $response)
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
    public function setRedirectToReuseDetails($user, $lpa, $lpaRoute, $response)
    {
        $this->userDetailsSession->user = $user;

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $url = str_replace('lpa/', "http://localhost/lpa/{$lpa->id}/", $lpaRoute);
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

        $reuseDetailsUrl = "lpa/{$lpa->id}/reuse-details?" . implode('&', array_map(function ($value, $key) {
            $valueString = is_bool($value) ? $value === true ? '1' : '0' : $value;
            return "$key=$valueString";
        }, $queryParams, array_keys($queryParams)));

        $this->url->shouldReceive('fromRoute')
            ->withArgs([
                'lpa/reuse-details',
                ['lpa-id' => $lpa->id],
                ['query' => $queryParams]
            ])->andReturn($reuseDetailsUrl)->once();

        $this->redirect->shouldReceive('toUrl')->withArgs([$reuseDetailsUrl])->andReturn($response);
    }

    public function setReuseDetails($controller, $form, $user, $who)
    {
        $this->userDetailsSession->user = $user;

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

    public function setFormAction($form, $lpa, $route, $expectedFromRouteTimes = 1)
    {
        $url = $this->getLpaUrl($lpa, $route);
        $this->url->shouldReceive('fromRoute')->withArgs([$route, ['lpa-id' => $lpa->id]])
            ->andReturn($url)->times($expectedFromRouteTimes);
        $form->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        return $url;
    }

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
            $dob = $attorney->dob->date;
            $flattenAttorneyData['dob-date'] = [
                'day'   => $dob->format('d'),
                'month' => $dob->format('m'),
                'year'  => $dob->format('Y'),
            ];
        }

        return $flattenAttorneyData;
    }

    public function tearDown()
    {
        //Clear out Zend containers
        $preAuthRequest = new Container('PreAuthRequest');
        $preAuthRequest->url = null;
        Mockery::close();
    }

    /**
     * @param $lpa
     * @param $route
     * @return mixed
     */
    private function getLpaUrl($lpa, $route, $queryParameters = null, $fragment = null)
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
            $url .= '?' . implode('&', array_map(function ($value, $key) {
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
}
