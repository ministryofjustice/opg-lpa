<?php

namespace Application\Handler;

use Application\Model\Service\User\Details;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\User\User;
use Mezzio\Flash\FlashMessages;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Twig\Environment as TwigEnvironment;

/**
 * @psalm-suppress UndefinedClass
 */
final class AboutYouHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly FormElementManager $formElementManager,
        private readonly TwigEnvironment $renderer,
        private readonly Details $details,
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

        $actionTarget = $isNew ? '/user/about-you/new' : '/user/about-you';
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
                    $flash = FlashMessages::createFromSession($session);
                    $flash->flash('success', 'Your details have been updated.');
                }

                $dashboardUrl = '/user/dashboard';
                return new RedirectResponse($dashboardUrl);
            }
        } else {
            if (!$isNew && is_null($userDetails->name)) {
                $newUrl = '/user/about-you/new';
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

        $flashMessages = $session
            ? FlashMessages::createFromSession($session)->getFlashes()
            : [];

        $html = $this->renderer->render(
            'authenticated/about-you/index',
            [
                'form'                       => $form,
                'isNew'                      => $isNew,
                'cancelUrl'                  => $cancelUrl,
                'signedInUser'               => $userDetails,
                'secondsUntilSessionExpires' => $request->getAttribute('secondsUntilSessionExpires'),
                'flash'                      => $flashMessages,
            ]
        );

        return new HtmlResponse($html);
    }
}
