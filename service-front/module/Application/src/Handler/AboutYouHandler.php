<?php

namespace Application\Handler;

use Application\Model\Service\User\Details;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessagesInterface;

/**
 * @psalm-suppress UndefinedClass
 */

final class AboutYouHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly FormElementManager $formElementManager,
        private readonly TemplateRendererInterface $renderer,
        private readonly UrlHelper $urlHelper,
        private readonly Details $details,
        private readonly FlashMessagesInterface $flashMessages
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var SessionInterface|null $session */
        $session = $request->getAttribute(SessionInterface::class);

        /** @var User|null $userDetails */
        $userDetails = $request->getAttribute(User::class);

        $routeParams = $request->getAttribute('Mezzio\Router\RouteResult')?->getMatchedParams() ?? [];
        $isNew = array_key_exists('new', $routeParams) && $routeParams['new'] !== null;

        /** @var \Laminas\Form\FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\AboutYou');

        $actionTarget = $this->urlHelper->generate(
            'user/about-you',
            $isNew ? ['new' => 'new'] : []
        );

        $form->setAttribute('action', $actionTarget);

        $userDetailsArr = $userDetails->flatten();

        if (strtoupper($request->getMethod()) === 'POST') {
            $postData = $request->getParsedBody() ?? [];

            if (!is_array($postData)) {
                $postData = [];
            }

            $existingData = array_intersect_key(
                $userDetailsArr,
                array_flip(['id', 'createdAt', 'updatedAt'])
            );

            $form->setData(array_merge($postData, $existingData));

            if ($form->isValid()) {
                $this->details->updateAllDetails($form->getData());

                $userDetailsSession = $session->get('UserDetails', []);
                unset($userDetailsSession['user']);
                $session->set('UserDetails', $userDetailsSession);

                if (!$isNew) {
                    $this->flashMessages->flash('success', 'Your details have been updated.');
                }

                $dashboardUrl = $this->urlHelper->generate('user/dashboard');
                return new RedirectResponse($dashboardUrl);
            }
        } else {
            if (!$isNew && is_null($userDetails->name)) {
                $newUrl = $this->urlHelper->generate('user/about-you', ['new' => 'new']);
                return new RedirectResponse($newUrl);
            }

            if (!is_null($userDetails->dob)) {
                $dob = $userDetails->dob->date;

                $userDetailsArr['dob-date'] = [
                    'day'   => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year'  => $dob->format('Y'),
                ];
            }

            $form->setData($userDetailsArr);
        }

        $cancelUrl = '/user/dashboard';

        $html = $this->renderer->render(
            'authenticated/about-you/index',
            [
                'form'                       => $form,
                'isNew'                      => $isNew,
                'cancelUrl'                  => $cancelUrl,
                'signedInUser'               => $userDetails,
                'secondsUntilSessionExpires' => $request->getAttribute('secondsUntilSessionExpires'),
                'flash'                      => $this->flashMessages->getFlashes(),
            ]
        );

        return new HtmlResponse($html);
    }
}
