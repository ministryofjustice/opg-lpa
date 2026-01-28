<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use Application\Model\Service\Session\ContainerNamespace;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\User\User;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class AboutYouControllerTest extends AbstractControllerTestCase
{
    private MockInterface|AboutYou $form;
    private array $postData = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\AboutYou'])->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        //  Set up helpers and services
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();

        //  Set up the form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();

        $expectedUserData = $this->user->flatten();

        // date is split into constituent parts when DOB is parsed, so modify the expectation;
        // see AbstractControllerTest->getUser() where the user fixture is created
        $expectedUserData['dob-date'] = [
            'day' => 17,
            'month' => 12,
            'year' => 1957
        ];

        $this->form->shouldReceive('bind')
            ->with(Mockery::on(function ($userData) use ($expectedUserData): true {
                MatcherAssert::assertThat($expectedUserData, Matchers::equalTo($userData));
                return true;
            }))
            ->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();

        //  Set up form
        $this->setPostInvalid($this->form, $this->postData, $this->getExpectedDataToSet($this->user));
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostValid(): void
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();
        $this->userDetails->shouldReceive('updateAllDetails')->withArgs([$this->postData])->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')
            ->withArgs(['Your details have been updated.'])->once();
        //  Set up form
        $this->setPostValid($this->form, $this->postData, $this->getExpectedDataToSet($this->user));
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('user/dashboard', $result->getHeaders()->get('Location')->getUri());
    }

    public function testNewActionGet(): void
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn('new')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/about-you', ['new' => 'new']])->andReturn('/user/about-you/new')->once();

        //  Set up form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you/new'])->once();

        $expectedUserData = $this->user->flatten();

        // date is split into constituent parts when DOB is parsed, so modify the expectation;
        // see AbstractControllerTest->getUser() where the user fixture is created
        $expectedUserData['dob-date'] = [
            'day' => 17,
            'month' => 12,
            'year' => 1957
        ];

        $this->form->shouldReceive('bind')
            ->with(Mockery::on(function ($userData) use ($expectedUserData): true {
                MatcherAssert::assertThat($expectedUserData, Matchers::equalTo($userData));
                return true;
            }))
            ->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testNewActionPostValid(): void
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn('new')->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/about-you', ['new' => 'new']])->andReturn('/user/about-you/new')->once();
        $this->userDetails->shouldReceive('updateAllDetails')->withArgs([$this->postData])->once();
        //  Set up form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you/new'])->once();
        $this->setPostValid($this->form, $this->postData, $this->getExpectedDataToSet($this->user));
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('user/dashboard', $result->getHeaders()->get('Location')->getUri());
    }

    /**
     * The data expected to set in the form will be different to the form data
     */
    private function getExpectedDataToSet(User $user): array
    {
        //  Get the filtered user data in the same way a controller would
        $userDetails = $user->flatten();
        $existingSetData = array_intersect_key($userDetails, array_flip(['id', 'createdAt', 'updatedAt']));

        return array_merge($this->postData, $existingSetData);
    }
}
