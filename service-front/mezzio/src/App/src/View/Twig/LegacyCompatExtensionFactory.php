<?php

declare(strict_types=1);

namespace App\View\Twig;

use App\Form\Error\FormLinkedErrors;
use App\Model\FlashMessagesHolder;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Model\UserDetailsHolder;
use App\Service\AccordionService;
use App\Storage\MezzioSessionStorage;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class LegacyCompatExtensionFactory
{
    public function __invoke(ContainerInterface $container): LegacyCompatExtension
    {
        return new LegacyCompatExtension(
            $container->get('config'),
            new FormLinkedErrors(),
            $container->get(PersistentSessionDetails::class),
            new AccordionService(),
            $container->get(MezzioSessionStorage::class),
            $container->get(UserDetailsHolder::class),
            $container->get(UrlHelper::class),
            $container->get(FlashMessagesHolder::class),
        );
    }
}
