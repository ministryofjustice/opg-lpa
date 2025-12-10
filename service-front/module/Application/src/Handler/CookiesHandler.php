<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\Element\Radio;
use Laminas\Form\FormElementManager;
use Laminas\Http\Request as HttpRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Form\FormInterface;

class CookiesHandler implements RequestHandlerInterface
{
    public const COOKIE_POLICY_NAME = 'cookie_policy';

    public function __construct(
        private TemplateRendererInterface $renderer,
        private FormElementManager $formElementManager,
        private HttpRequest $httpRequest,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\General\CookieConsentForm');

        $form->setAttribute('action', '/cookies');

        $cookiePolicy = $this->fetchPolicyCookie($this->httpRequest);

        if ($cookiePolicy !== null && array_key_exists('usage', $cookiePolicy)) {
            /** @var Radio $ucElement */
            $ucElement = $form->get('usageCookies');
            $ucElement->setValue($cookiePolicy['usage'] ? 'yes' : 'no');
        }

        $html = $this->renderer->render(
            'application/general/cookies/index.twig',
            ['form' => $form]
        );

        return new HtmlResponse($html);
    }

    private function fetchPolicyCookie(HttpRequest $request): ?array
    {
        $cookies = $request->getCookie();
        if ($cookies !== false && $cookies->offsetExists(self::COOKIE_POLICY_NAME)) {
            $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);

            return is_array($cookiePolicy) ? $cookiePolicy : null;
        }

        return null;
    }
}
