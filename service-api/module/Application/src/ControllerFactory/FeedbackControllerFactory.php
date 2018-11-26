<?php
namespace Application\ControllerFactory;

use Application\Controller\FeedbackController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZfcRbac\Service\AuthorizationService;
use Application\Model\Service\Feedback\Service as FeedbackService;

class FeedbackControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return FeedbackController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new FeedbackController(
            $container->get(FeedbackService::class),
            $container->get(AuthorizationService::class)
        );
    }
}
