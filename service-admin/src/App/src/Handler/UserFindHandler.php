<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\UserFind;
use App\Service\User\UserService;
use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class UserFindHandler extends AbstractHandler
{
    public static $LIMIT = 10;

    use JwtTrait;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * UserSearchHandler constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new UserFind();

        $limit = self::$LIMIT;

        // to be set from GET
        $query = null;
        $defaultOffset = 0;

        // next/previous params
        $nextOffset = null;
        $previousOffset = null;

        $users = [];

        // we fetch one more record than we need so we can tell whether we
        // should display a "next" link
        $fakeLimit = $limit + 1;

        if ($request->getMethod() == 'GET') {
            $params = $request->getQueryParams();

            if (array_key_exists('query', $params)) {
                $form->setData($params);

                if ($form->isValid()) {
                    $inputFilter = $form->getInputFilter();
                    $query = $inputFilter->get('query')->getValue();
                    $offset = $inputFilter->get('offset')->getValue();
                }
            }
            else {
                // reset this to empty string for display as form element value
                $params['query'] = '';

                $params['offset'] = $defaultOffset;

                $form->setData($params);
            }
        }

        if (!is_null($query)) {
            $options = ['query' => $query, 'offset' => $offset, 'limit' => $fakeLimit];

            $result = $this->userService->match($options);
            $numResults = count($result);

            // there are more records to come after these...
            if ($numResults === $fakeLimit) {
                $nextOffset = $offset + $limit;
            }

            // we are on page 2+
            if ($offset > 0) {
                $previousOffset = $offset - $limit;
            }

            if ($numResults === 0) {
                // Set error message
                $messages = array_merge($form->getMessages(), [
                    'query' => [
                        'No users match your query'
                    ]
                ]);

                $form->setMessages($messages);
            } else {
                // we want the actual number of users asked for in the query,
                // so remove the one retrieved for the purposes of determining
                // if there are more results to come
                $users = array_slice($result, 0, $limit);
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-find', [
            'form'  => $form,
            'users' => $users,
            'query' => $form->get('query')->getValue(),
            'nextOffset' => $nextOffset,
            'previousOffset' => $previousOffset,
        ]));
    }
}
