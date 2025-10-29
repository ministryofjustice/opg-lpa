<?php

namespace MakeShared\Factories;

use Application\Library\Authentication\AuthenticationListener;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use MakeShared\Logging\LoggerTrait;
use Psr\Container\ContainerInterface;

class ListenerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Psalm doesn't understand the context that this code is called bty code that
     * knowns where AuthenticationListener is defined
     * @psalm-suppress UndefinedClass
     */
    private $createableListeners = [
        AuthenticationListener::class,
    ];

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName) && in_array($requestedName, $this->createableListeners);
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        $listener = new $requestedName();
        $traitsUsed = class_uses($listener);

        if (in_array(LoggerTrait::class, $traitsUsed)) {
            $listener->setLogger($container->get('Logger'));
        }
        return $listener;
    }
}
