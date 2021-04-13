<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

class AboutYouControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|AboutYou
     */
    private $form;
    private $postData = [];

    public function setUp() : void
    {
        parent::setUp();

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\AboutYou'])->andReturn($this->form);
    }

    public function testIndexActionGet()
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
        $this->form->shouldReceive('bind')->withArgs([$this->user->flatten()])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
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

    public function testIndexActionPostValid()
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        $response = new Response();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();
        $this->userDetails->shouldReceive('updateAllDetails')->withArgs([$this->postData])->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')
            ->withArgs(['Your details have been updated.'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        //  Set up form
        $this->setPostValid($this->form, $this->postData, $this->getExpectedDataToSet($this->user));
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testNewActionGet()
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
        $this->form->shouldReceive('bind')->withArgs([$this->user->flatten()])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testNewActionPostValid()
    {
        /** @var AboutYouController $controller */
        $controller = $this->getController(AboutYouController::class);

        $response = new Response();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn('new')->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/about-you', ['new' => 'new']])->andReturn('/user/about-you/new')->once();
        $this->userDetails->shouldReceive('updateAllDetails')->withArgs([$this->postData])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        //  Set up form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you/new'])->once();
        $this->setPostValid($this->form, $this->postData, $this->getExpectedDataToSet($this->user));
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * The data expected to set in the form will be different to the form data
     *
     * @param User $user
     * @return array
     */
    private function getExpectedDataToSet(User $user)
    {
        //  Get the filtered user data in the same way a controller would
        $userDetails = $user->flatten();
        $existingSetData = array_intersect_key($userDetails, array_flip(['id', 'createdAt', 'updatedAt']));

        return array_merge($this->postData, $existingSetData);
    }
}
