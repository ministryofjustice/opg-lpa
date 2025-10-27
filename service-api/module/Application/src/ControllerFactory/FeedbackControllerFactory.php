<?php

namespace Application\ControllerFactory;

use Application\Controller\FeedbackController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use LmcRbacMvc\Service\AuthorizationService;
use Application\Model\Service\Feedback\Service as FeedbackService;

class FeedbackControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $_requestedName
     * @param array|null $_options
     * @return FeedbackController
     */
    public function __invoke(ContainerInterface $container, $_requestedName, array|null $_options = null)
    {
        return new FeedbackController(
            $container->get(FeedbackService::class),
            $container->get(AuthorizationService::class)
        );
    }
}
