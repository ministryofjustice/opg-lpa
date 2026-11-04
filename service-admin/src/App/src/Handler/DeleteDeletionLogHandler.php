<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\DeleteDeletionLog;
use App\RequestAttributes;
use App\Service\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress UnusedClass
 */
class DeleteDeletionLogHandler extends AbstractHandler
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new DeleteDeletionLog([
            'csrf' => $request->getAttribute(RequestAttributes::CSRF_TOKEN),
        ]);

        $error = null;

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $form->setData(is_array($parsedBody) ? $parsedBody : []);

            if ($form->isValid()) {
                $email = (string) $form->getInputFilter()->getValue('email');
                $adminEmail = $request->getAttribute(RequestAttributes::USER_EMAIL);

                if ($this->userService->deleteDeletionLog($email)) {
                    $this->auditLog(
                        $adminEmail,
                        'admin.deletion_log.deleted',
                        'Admin deleted deletion log',
                    );

                    $this->setFlashMessage($request, 'success', 'Deletion log deleted for ' . $email);

                    return $this->redirectToRoute('search');
                }

                $error = 'The deletion log could not be deleted. Please try again.';
            } elseif (empty($form->get('email')->getValue())) {
                return $this->redirectToRoute('search');
            }
        } else {
            $email = trim((string) ($request->getQueryParams()['email'] ?? ''));

            if ($email === '') {
                return $this->redirectToRoute('search');
            }

            $form->setData(['email' => $email]);
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::delete-deletion-log', [
            'form'  => $form,
            'email' => $form->get('email')->getValue(),
            'error' => $error,
        ]));
    }
}
