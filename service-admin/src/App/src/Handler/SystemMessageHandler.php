<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SystemMessage;
use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class SystemMessageHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @var StorageInterface
     */
    private $cache;

    /**
     * SystemMessageHandler constructor.
     * @param StorageInterface $cache
     */
    public function __construct(StorageInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new SystemMessage([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        if ($request->getMethod() == 'POST') {
            $form->setData($request->getParsedBody());
            $newMessage = $form->get('message')->getValue();

            $confirmMessage = "No system message has been set";

            if (empty($newMessage) && !is_null($this->cache->getItem('system-message'))) {
                $this->cache->removeItem('system-message');
                $confirmMessage = 'System message removed';

            } elseif ($form->isValid() && !empty($newMessage)) {
                $this->cache->setItem('system-message', $newMessage);
                $confirmMessage = 'System message set';
            }

            $this->setFlashInfoMessage($request, $confirmMessage);

            return $this->redirectToRoute('system.message');
        } else {
            $currentMessage = $this->cache->getItem('system-message');

            $form->get('message')->setValue($currentMessage);
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::system-message', [
            'form'     => $form,
            'messages' => $this->getFlashMessages($request)
        ]));
    }
}
