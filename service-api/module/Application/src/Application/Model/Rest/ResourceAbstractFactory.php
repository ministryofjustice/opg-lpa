<?php

namespace Application\Model\Rest;

use Application\DataAccess\Mongo\CollectionFactory;
use Application\DataAccess\UserDal;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Rest\Users\Entity as RouteUser;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Exception;

/**
 * Creates a resource and injects the required dependencies
 *
 * Class ResourceAbstractFactory
 * @package Application\Model\Rest
 */
class ResourceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        Pdfs\Resource::class => [
            'setPdfConfig'         => 'config',
            'setDynamoQueueClient' => 'DynamoQueueClient',
        ],
        Seed\Resource::class => [
            'setApplicationsResource' => 'resource-applications',
        ],
        Users\Resource::class => [
            'setUserDal'              => UserDal::class,
            'setApplicationsResource' => 'resource-applications',
        ],
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return (class_exists($requestedName)
            && is_subclass_of($requestedName, AbstractResource::class));
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new Exception(sprintf('Abstract factory %s can not create the requested service %s', get_class($this), $requestedName));
        }

        $lpaCollection = $container->get(CollectionFactory::class . '-lpa');
        $collection = null;

        if ($requestedName == Stats\Resource::class) {
            $collection = $container->get(CollectionFactory::class . '-stats-lpas');
        } elseif ($requestedName == Users\Resource::class) {
            $collection = $container->get(CollectionFactory::class . '-user');
        } elseif ($requestedName == WhoAreYou\Resource::class) {
            $collection = $container->get(CollectionFactory::class . '-stats-who');
        }

        $resource = new $requestedName($lpaCollection, $collection);

        //  If required load any additional services into the resource
        if (array_key_exists($requestedName, $this->additionalServices) && is_array($this->additionalServices[$requestedName])) {
            foreach ($this->additionalServices[$requestedName] as $setterMethod => $additionalService) {
                if (!method_exists($resource, $setterMethod)) {
                    throw new Exception(sprintf('The setter method %s does not exist on the requested resource %s', $setterMethod, $requestedName));
                }

                $resource->$setterMethod($container->get($additionalService));
            }
        }

        //  If appropriate set the user from the route parameter
        if ($resource instanceof UserConsumerInterface) {
            $userId = $container->get('Application')->getMvcEvent()->getRouteMatch()->getParam('userId');

            if (empty($userId)) {
                throw new ApiProblemException('User identifier missing from URL', 400);
            }

            //  Get the user record using the DAL
            $userDal = $container->get(UserDal::class);
            $user = $userDal->findById($userId);

            if ($user instanceof User) {
                $resource->setRouteUser(new RouteUser($user));

                //  If appropriate set the LPA from the route parameter
                if ($resource instanceof LpaConsumerInterface) {
                    $lpaId = $container->get('Application')->getMvcEvent()->getRouteMatch()->getParam('lpaId');

                    if (!is_numeric($lpaId)) {
                        throw new ApiProblemException('LPA identifier missing from URL', 400);
                    }

                    $lpaData = $lpaCollection->findOne(['_id' => (int) $lpaId, 'user' => $userId]);

                    $lpaData = ['id' => $lpaData['_id']] + $lpaData;

                    $resource->setLpa(new Lpa($lpaData));
                }
            }
        }

        return $resource;
    }
}
