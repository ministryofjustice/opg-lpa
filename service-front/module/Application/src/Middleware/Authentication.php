<?php

namespace Application\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use DateTime;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Authentication implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
        private readonly SessionManagerSupport $sessionManagerSupport,
        private readonly UrlHelper $urlHelper,
        private readonly array $config
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SessionInterface|null $session */
        $session = $request->getAttribute(SessionInterface::class);

        if ($response = $this->redirectIfNotAuthenticated($request, $session)) {
            return $response;
        }

        /** @var Identity $identity */
        $identity = $this->authenticationService->getIdentity();

        if ($response = $this->redirectIfTermsNotSeen($identity, $session)) {
            return $response;
        }

        $user = $this->getOrLoadUser($session);

        if ($response = $this->logoutIfUserInvalid($user)) {
            return $response;
        }

        $secondsUntilSessionExpires =
            $identity->tokenExpiresAt()->getTimestamp() - (new DateTime())->getTimestamp();

        $request = $request
            ->withAttribute(Identity::class, $identity)
            ->withAttribute(User::class, $user)
            ->withAttribute('secondsUntilSessionExpires', $secondsUntilSessionExpires);

        return $handler->handle($request);
    }

    private function redirectIfNotAuthenticated(
        ServerRequestInterface $request,
        SessionInterface $session
    ): ?ResponseInterface {
        $identity = $this->authenticationService->getIdentity();

        if ($identity instanceof Identity) {
            return null;
        }

        $preAuth = $session->get('PreAuthRequest', []);
        $preAuth['url'] = (string) $request->getUri();
        $session->set('PreAuthRequest', $preAuth);

        $authFailure = $session->get('AuthFailureReason', []);
        $code = $authFailure['code'] ?? null;

        $state = $code === null ? 'timeout' : 'internalSystemError';

        $loginUrl = $this->urlHelper->generate('login', [], [
            'state' => $state,
        ]);

        return new RedirectResponse($loginUrl);
    }

    private function redirectIfTermsNotSeen(
        Identity $identity,
        SessionInterface $session
    ): ?ResponseInterface {
        $termsUpdated = new DateTime($this->config['terms']['lastUpdated']);

        if ($identity->lastLogin() >= $termsUpdated) {
            return null;
        }

        $terms = $session->get('TermsAndConditionsCheck', []);

        if (!empty($terms['seen'])) {
            return null;
        }

        $terms['seen'] = true;
        $session->set('TermsAndConditionsCheck', $terms);

        $termsUrl = $this->urlHelper->generate('user/dashboard/terms-changed');

        return new RedirectResponse($termsUrl);
    }

    private function getOrLoadUser(SessionInterface $session): User
    {
        $userDetailsContainer = $session->get('UserDetails', []);
        $user = $userDetailsContainer['user'] ?? null;

        if (!$user instanceof User) {
            $user = $this->userService->getUserDetails();
            $userDetailsContainer['user'] = $user;
            $session->set('UserDetails', $userDetailsContainer);
        }

        return $user;
    }

    private function logoutIfUserInvalid(User $user): ?ResponseInterface
    {
        try {
            $userDataArr = $user->toArray();
            new User($userDataArr);
        } catch (\Throwable $e) {
            $this->authenticationService->clearIdentity();

            $this->sessionManagerSupport
                ->getSessionManager()
                ->destroy(['clear_storage' => true]);

            $loginUrl = $this->urlHelper->generate('login', [], [
                'state' => 'timeout',
            ]);

            return new RedirectResponse($loginUrl);
        }

        return null;
    }
}
