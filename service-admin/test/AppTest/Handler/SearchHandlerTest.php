<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserSearch;
use App\Handler\SearchHandler;
use App\RequestAttributes;
use App\Service\Paginator;
use App\Service\SharedSpaceService;
use App\Service\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SearchHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private SharedSpaceService|MockObject $mockSharedSpaceService;
    private LoggerInterface|MockObject $mockLogger;
    private SearchHandler $handler;

    protected function setUp(): void
    {
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockSharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->handler = new SearchHandler(
            $this->mockUserService,
            $this->mockSharedSpaceService,
            new Paginator()
        );
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    private function makeRequest(array $queryParams = [], string $adminEmail = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams($queryParams)
            ->withAttribute(RequestAttributes::CSRF_TOKEN, Common::TEST_CSRF_TOKEN);

        if ($adminEmail !== null) {
            $request = $request->withAttribute(RequestAttributes::USER_EMAIL, $adminEmail);
        }

        return $request;
    }

    public function testRendersForm()
    {
        $this->mockTemplateRenderer->expects($this->once())->method('render')
            ->with(
                'app::search',
                $this->callback(fn ($args) => $args['form'] instanceof UserSearch)
            )->willReturn('response');

        $this->handler->handle($this->makeRequest());
    }

    public function testSubmitsSearchByEmail()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with('user@example.com', 1, 20)
            ->willReturn(['results' => [$user], 'total' => 1]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['queryParams']['searchTerm'] === 'user@example.com'
                && $args['results'] === [$user])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testSubmitsSearchByUserId()
    {
        $user = ['userId' => 'abc123', 'isActive' => true];
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchById')
            ->with('abc123')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['results'] === [$user])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'abc123',
            'searchType' => 'userId',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testSubmitsSearchByAReference()
    {
        $user = ['userId' => 'abc123def456', 'isActive' => true];
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchByAReference')
            ->with('A-99998888882')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['results'] === [$user])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'A-99998888882',
            'searchType' => 'aReference',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testSubmitsSearchBySharedSpaceName()
    {
        $sharedSpace = ['sharedSpaceId' => 'ss1', 'sharedSpaceName' => 'Test Space'];
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('matchSharedSpaces')
            ->with('Test', 1, 20)
            ->willReturn(['results' => [$sharedSpace], 'total' => 1]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['results'] === [$sharedSpace])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'Test',
            'searchType' => 'sharedSpaceName',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testPaginatesMatchResultsAndSetsNextPage()
    {
        $users = array_fill(0, 20, ['userId' => 'x', 'username' => 'x@example.com']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with('user', 1, 20)
            ->willReturn(['results' => $users, 'total' => 25]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                count($args['results']) === 20
                && $args['paginator']->getNextPage() === 2
                && $args['paginator']->getPreviousPage() === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user',
            'searchType' => 'email',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testPaginatesSharedSpaceResultsAndSetsPreviousPage()
    {
        $sharedSpaces = array_fill(0, 5, ['sharedSpaceId' => 'ss', 'sharedSpaceName' => 'Test Space']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('matchSharedSpaces')
            ->with('Test', 2, 20)
            ->willReturn(['results' => $sharedSpaces, 'total' => 25]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                count($args['results']) === 5
                && $args['paginator']->getNextPage() === null
                && $args['paginator']->getPreviousPage() === 1)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'Test',
            'searchType' => 'sharedSpaceName',
            'page' => '2',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testAuditLogsSuccessfulSearch()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with('user@example.com', 1, 20)
            ->willReturn(['results' => [new User(['name' => new Name(['first' => 'David'])])], 'total' => 1]);

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin viewed user data',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.search'
                    && $context['admin_email'] === 'admin@example.com'
                    && !array_key_exists('admin_id', $context)
                    && $context['searched_for'] === 'user@example.com')
            );

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testRendersEmptyResultsWhenNoUserFoundByEmail()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with('nobody@example.com', 1, 20)
            ->willReturn(['results' => [], 'total' => 0]);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('nobody@example.com')
            ->willReturn(false);

        $this->mockLogger->expects($this->once())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === []
                && $args['results'] === [])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'nobody@example.com',
            'searchType' => 'email',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testRendersErrorWhenUserNotFoundByEmail()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with('user@example.com', 1, 20)
            ->willReturn(false);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('user@example.com')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No user found for email address']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'page' => '1',
            'secret' => $secret,
        ]));
    }

    public function testFallsBackToExactMatchAndFindsDeletedUserWhenPartialMatchIsEmpty()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with('deleted@example.com', 1, 20)
            ->willReturn(['results' => [], 'total' => 0]);

        $deletedUser = ['isDeleted' => true, 'deletedAt' => '2021-05-05', 'reason' => 'User manually deleted their account'];

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('deleted@example.com')
            ->willReturn($deletedUser);

        $this->mockLogger->expects($this->once())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === []
                && $args['results'] === [$deletedUser])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'deleted@example.com',
            'searchType' => 'email',
            'page' => '1',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testRendersErrorWhenUserNotFoundByUserId()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchById')
            ->with('abc123')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No user found for user ID']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'abc123',
            'searchType' => 'userId',
            'page' => '1',
            'secret' => $secret,
        ]));
    }

    public function testRendersErrorWhenUserNotFoundByAReference()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchByAReference')
            ->with('A-99998888882')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No user found for A Reference']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'A-99998888882',
            'searchType' => 'aReference',
            'page' => '1',
            'secret' => $secret,
        ]));
    }

    public function testRendersErrorWhenSharedSpaceNotFound()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('matchSharedSpaces')
            ->with('Test', 1, 20)
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No shared space found for shared space name']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'Test',
            'searchType' => 'sharedSpaceName',
            'page' => '1',
            'secret' => $secret,
        ]));
    }

    public function testRequiresCsrf()
    {
        $this->mockUserService->expects($this->never())->method('match');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('secret') === [
                    'notSame' => 'The form submitted did not originate from the expected site'
                ]
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'page' => '1',
            'secret' => 'not_the_real_hash', // pragma: allowlist secret
        ]));
    }
}
