<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\UserSearch;
use App\RequestAttributes;
use App\Service\Paginator;
use App\Service\SharedSpaceService;
use App\Service\UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress UnusedClass
 */
class SearchHandler extends AbstractHandler
{
    public function __construct(
        private readonly UserService $userService,
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly Paginator $paginator,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new UserSearch([
            'csrf' => $request->getAttribute(RequestAttributes::CSRF_TOKEN),
        ]);

        $this->paginator->setPerPage(20);
        $this->paginator->setPage(1);

        // to be set from GET
        $searchTerm = null;
        $searchType = null;
        $results = null;

        if ($request->getMethod() == RequestMethodInterface::METHOD_GET) {
            $params = $request->getQueryParams();

            if (array_key_exists('searchTerm', $params)) {
                $form->setData($params);

                if ($form->isValid()) {
                    $inputFilter = $form->getInputFilter();

                    $searchTerm = $inputFilter->getValue('searchTerm');
                    $searchType = $inputFilter->getValue('searchType');

                    $this->paginator->setPage((int) $inputFilter->getValue('page'));
                }
            } else {
                // reset this to empty string for display as form element value
                $params['searchTerm'] = '';
                $params['page'] = $this->paginator->getPage();

                $form->setData($params);
            }
        }

        if (!is_null($searchTerm)) {
            $input = trim($searchTerm);

            $paginated = !in_array($searchType, ['userId', 'aReference'], true);

            $result = match ($searchType) {
                'userId'          => $this->userService->searchById($input),
                'aReference'      => $this->userService->searchByAReference($input),
                'sharedSpaceName' => $this->sharedSpaceService->matchSharedSpaces(
                    $input,
                    $this->paginator->getPage(),
                    $this->paginator->getPerPage()
                ),
                default           => $this->userService->match(
                    $input,
                    $this->paginator->getPage(),
                    $this->paginator->getPerPage()
                ),
            };

            // Deleted users have no row in the live users table, so they can
            // never be found via the partial-match search above.
            if (
                $searchType !== 'userId' && $searchType !== 'aReference' && $searchType !== 'sharedSpaceName'
                && (($result === false) || ($result['total'] === 0))
            ) {
                $exactResult = $this->userService->search($input);

                if ($exactResult !== false) {
                    $result = $exactResult;
                    $paginated = false;
                }
            }

            if ($result === false) {
                $formMessages = $form->getMessages();

                $notFoundMessage = match ($searchType) {
                    'userId'          => 'No user found for user ID',
                    'aReference'      => 'No user found for A Reference',
                    'sharedSpaceName' => 'No shared space found for shared space name',
                    default           => 'No user found for email address',
                };

                // Set error message
                $messages = array_merge($formMessages, [
                    'searchTerm' => [
                        $notFoundMessage
                    ]
                ]);

                $form->setMessages($messages);
            } else {
                if ($paginated) {
                    $results = $result['results'];
                    $this->paginator->setTotal($result['total']);
                } else {
                    $results = [$result];
                }

                $this->auditLog(
                    $request->getAttribute(RequestAttributes::USER_EMAIL),
                    'admin.user.search',
                    'Admin viewed user data',
                    ['searched_for' => $input],
                );
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::search', [
            'form'  => $form,
            'results'  => $results,
            'searchType' => $form->get('searchType')->getValue(),
            'queryParams' => [
                'searchTerm' => $form->get('searchTerm')->getValue(),
                'searchType' => $form->get('searchType')->getValue(),
                'secret' => $form->get('secret')->getValue(),
            ],
            'secret' => $form->get('secret')->getValue(),
            'paginator' => $this->paginator,
        ]));
    }
}
