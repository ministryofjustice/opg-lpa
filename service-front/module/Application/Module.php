<?php

namespace Application;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\Model\Service\Admin\Admin as AdminService;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\System\DynamoCronLock;
use Alphagov\Pay\Client as GovPayClient;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\ViewModel;

class Module
{
    use LoggerTrait;

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // Register error handler for dispatch and render errors
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleError'));
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'handleError'));
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER, array($this, 'preRender'));

        register_shutdown_function(function () {
            $error = error_get_last();

            if ($error['type'] === E_ERROR) {
                // This is a fatal error, we have no exception and no nice view to render
                // The fatal error will have been logged already prior to writing this message
                echo 'An unknown server error has occurred.';
            }
        });

        //---

        $request = $e->getApplication()->getServiceManager()->get('Request');

        if( !($request instanceof \Zend\Console\Request) ){

            // Only bootstrap the session if it's *not* PHPUnit AND is not an excluded url.
            if(
                !strstr( $request->getServer('SCRIPT_NAME'), 'phpunit' ) &&
                !in_array( $request->getUri()->getPath(), [
                    // URLs excluded from creating a session
                    '/ping/elb',
                    '/ping/json',
                    '/notifications/expiry-notice',
                ]))
            {
                $this->bootstrapSession($e);
                $this->bootstrapIdentity($e);
            }

        }

    } // function

    /**
     * Sets up and starts global sessions.
     *
     * @param MvcEvent $e
     */
    private function bootstrapSession(MvcEvent $e){

        $session = $e->getApplication()->getServiceManager()->get('SessionManager');

        // Always starts the session.
        $session->start();

        // Ensures this SessionManager is used for all Session Containers.
        Container::setDefaultManager($session);

        //---

        $session->initialise();

    } // function

    /**
     *
     * This now checks the token on every request otherwise we have no method of knowing if the user has
     * logged in on another browser.
     *
     * We don't deal with forcing the user to re-authenticate here as they
     * may be accessing a page that does not require authentication.
     *
     * @param MvcEvent $e
     */
    private function bootstrapIdentity(MvcEvent $e){

        $sm = $e->getApplication()->getServiceManager();

        $auth = $sm->get('AuthenticationService');

        // If we have an identity...
        if ( ($identity = $auth->getIdentity()) != null ) {

            // Get the tokens details...
            $info = $sm->get('ApiClient')->getTokenInfo( $identity->token() );


            if( is_array($info) && isset($info['expiresIn']) ){

                // update the time the token expires in the session
                $identity->tokenExpiresIn( $info['expiresIn'] );

            } else {

                // else the user will need to re-login, so remove the current identity.
                $auth->clearIdentity();

            }

        } // if we have an identity

    } // function

    //-------------------------------------------

    public function getServiceConfig(){

        return [
            'shared' => [
                'HttpClient' => false,
            ],
            'aliases' => [
                'MailTransport' => 'SendGridTransport',
                'AddressLookupMoj' => 'PostcodeInfo',
                'AuthenticationAdapter' => 'LpaAuthAdapter',
                'Zend\Authentication\AuthenticationService' => 'AuthenticationService',
            ],
            'invokables' => [
                'AuthenticationService'                 => 'Application\Model\Service\Authentication\AuthenticationService',
                'PasswordReset'                         => 'Application\Model\Service\User\PasswordReset',
                'Register'                              => 'Application\Model\Service\User\Register',
                'AboutYouDetails'                       => 'Application\Model\Service\User\Details',
                'DeleteUser'                            => 'Application\Model\Service\User\Delete',
                'Payment'                               => 'Application\Model\Service\Payment\Payment',
                'Feedback'                              => 'Application\Model\Service\Feedback\Feedback',
                'Signatures'                            => 'Application\Model\Service\Feedback\Signatures',
                'Guidance'                              => 'Application\Model\Service\Guidance\Guidance',
                'ApplicationList'                       => 'Application\Model\Service\Lpa\ApplicationList',
                'Metadata'                              => 'Application\Model\Service\Lpa\Metadata',
                'Communication'                         => 'Application\Model\Service\Lpa\Communication',
                'PostcodeInfo'                          => 'Application\Model\Service\AddressLookup\PostcodeInfo',
                'SiteStatus'                            => 'Application\Model\Service\System\Status',
                'ReplacementAttorneyCleanup'            => 'Application\Model\Service\Lpa\ReplacementAttorneyCleanup',
                'ApplicantCleanup'                      => 'Application\Model\Service\Lpa\ApplicantCleanup',
            ],
            'factories' => [
                'SessionManager'        => 'Application\Model\Service\Session\SessionFactory',
                'ApiClient'             => 'Application\Model\Service\ApiClient\ApiClientFactory',
                'PostcodeInfoClient'    => 'Application\Model\Service\AddressLookup\PostcodeInfoClientFactory',

                // Access via 'MailTransport'
                'SendGridTransport' => 'Application\Model\Service\Mail\Transport\SendGridFactory',

                // LPA access service
                'LpaApplicationService' => function( ServiceLocatorInterface $sm ){
                    return new LpaApplicationService( $sm->get('ApiClient') );
                },

                // Authentication Adapter. Access via 'AuthenticationAdapter'
                'LpaAuthAdapter' => function( ServiceLocatorInterface $sm ){
                    return new LpaAuthAdapter( $sm->get('ApiClient') );
                },

                // Admin service
                'AdminService' => function (ServiceLocatorInterface $sm) {
                    return new AdminService($sm->get('ApiClient'));
                },

                // Generate the session container for a user's personal details
                'UserDetailsSession' => function(){
                    return new Container('UserDetails');
                },

                // PSR-7 HTTP Client
                'HttpClient' => function(){
                    return new \Http\Adapter\Guzzle5\Client(
                        new \GuzzleHttp\Client,
                        new \Http\Message\MessageFactory\GuzzleMessageFactory
                    );
                },

                'Cache' => function ( ServiceLocatorInterface $sm ) {

                    $config = $sm->get('config')['admin']['dynamodb'];

                    $config['keyPrefix'] = $sm->get('config')['stack']['name'];

                    $dynamoDbAdapter = new DynamoDbKeyValueStore($config);

                    return $dynamoDbAdapter;
                },

                'DynamoCronLock' => function ( ServiceLocatorInterface $sm ) {

                    $config = $sm->get('config')['cron']['lock']['dynamodb'];

                    $config['keyPrefix'] = $sm->get('config')['stack']['name'];

                    $dynamoDbAdapter = new DynamoCronLock($config);

                    return $dynamoDbAdapter;
                },

                'GovPayClient' => function( ServiceLocatorInterface $sm ){

                    $config = $sm->get('config')['alphagov']['pay'];

                    return new GovPayClient([
                        'apiKey'        => $config['key'],
                        'httpClient'    => $sm->get('HttpClient'),
                    ]);

                },

                'TwigEmailRenderer' => function ( ServiceLocatorInterface $sm ) {

                    $loader = new \Twig_Loader_Filesystem('module/Application/view/email');

                    $env = new \Twig_Environment($loader);

                    $viewHelperManager = $sm->get('ViewHelperManager');
                    $renderer = new \Zend\View\Renderer\PhpRenderer();
                    $renderer->setHelperPluginManager($viewHelperManager);

                    $env->registerUndefinedFunctionCallback(function ($name) use ($viewHelperManager, $renderer) {
                        if (!$viewHelperManager->has($name)) {
                            return false;
                        }

                        $callable = [$renderer->plugin($name), '__invoke'];
                        $options  = ['is_safe' => ['html']];
                        return new \Twig_SimpleFunction(null, $callable, $options);
                    });

                    return $env;

                },

                'TwigViewRenderer' => function ( ServiceLocatorInterface $sm ) {

                    $loader = new \Twig_Loader_Filesystem('module/Application/view/application');

                    $env = new \Twig_Environment($loader);

                    return $env;

                }

            ], // factories
        ];

    } // function

    //-------------------------------------------

    public function getViewHelperConfig(){

        return array(
            'factories' => array(
                'staticAssetPath' => function( $sm ){
                    $config = $sm->getServiceLocator()->get('Config');
                    return new \Application\View\Helper\StaticAssetPath( $config['version']['cache'] );
                },
            ),
        );

    } // function

    //-------------------------------------------

    public function getAutoloaderConfig(){
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig(){

        $configFiles = [
            __DIR__ . '/config/module.config.php',
            __DIR__ . '/config/module.routes.php',
        ];

        //---

        $config = array();

        // Merge all module config options
        foreach($configFiles as $configFile) {
            $config = ArrayUtils::merge( $config, include($configFile) );
        }

        return $config;

    }

    /**
     * Look at the child view of the layout. If we detect that there is
     * a ".twig" file that will be picked up by the Twig module for rendering,
     * then change the current layout to be the ".twig" layout.
     *
     * @param MvcEvent $e
     */
    public function preRender(MvcEvent $e)
    {
        $viewModel = $e->getViewModel();

        if ($viewModel->hasChildren()) {
            // This view has a layout (i.e. it's not a popup window)
            $children = $viewModel->getChildren();

            // $children is an array but we only really expect one child
            $targetTemplateName = $children[0]->getTemplate();

            $potentialTwigTemplate = 'module/Application/view/' . $targetTemplateName . '.twig';

            // if there is a .phtml extension inside the name (abc.phtml.twig), then remove it
            $potentialTwigTemplate = str_replace('.phtml', '', $potentialTwigTemplate);

            // if there is a double .twig extension inside the name (abc.twig.twig), then remove one
            $potentialTwigTemplate = str_replace('.twig.twig', '.twig', $potentialTwigTemplate);

            // the template name will be something like 'application/about-you/index' - with
            // no suffix. We look in the directory where we know the .phtml file will be
            // located and see if there is a .twig file (which would take precedence over it)

            if (file_exists($potentialTwigTemplate)) {
                // Use the Twig layout
                $viewModel->setTemplate('layout/twig/layout');
            }

        }

    }

    /**
     * Use our logger to send this exception to its various destinations
     *
     * @param MvcEvent $e
     */
    public function handleError(MvcEvent $e)
    {

        $exception = $e->getResult()->exception;

        if ($exception) {
            $this->getLogger()->err( $exception->getMessage().' in '.$exception->getFile().' on line '.$exception->getLine().' - '.$exception->getTraceAsString());

            $viewModel = new ViewModel();
            $viewModel->setTemplate('error/500');

            $e->getViewModel()->addChild($viewModel);
            $e->stopPropagation();

            $e->getResponse()->setStatusCode(500);

            return $viewModel;
        }

    }

} // class
