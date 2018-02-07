<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\User\User;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;
use DateTime;

class AboutYouControllerTest extends AbstractControllerTest
{
    /**
     * @var AboutYouController
     */
    private $controller;
    /**
     * @var MockInterface|AboutYou
     */
    private $form;
    private $postData = [

    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(AboutYouController::class);

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\AboutYou'])->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        $user = $this->getUserDetails();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        //  Set up helpers and services
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();

        //  Set up the form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();
        $this->form->shouldReceive('bind')->withArgs([$user->flatten()])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        $user = $this->getUserDetails();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();

        //  Set up form
        $this->setPostInvalid($this->form, $this->postData, $this->getExpectedDataToSet($user));
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostValid()
    {
        $response = new Response();
        $user = $this->getUserDetails();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->withArgs(['user/about-you', []])->andReturn('/user/about-you')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->aboutYouDetails->shouldReceive('updateAllDetails')->withArgs([$this->form])->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')
            ->withArgs(['Your details have been updated.'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        //  Set up form
        $this->setPostValid($this->form, $this->postData, $this->getExpectedDataToSet($user));
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you'])->once();

        $result = $this->controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testNewActionGet()
    {
        $user = $this->getUserDetails(true);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn('new')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/about-you', ['new' => 'new']])->andReturn('/user/about-you/new')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();

        //  Set up form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you/new'])->once();
        $this->form->shouldReceive('bind')->withArgs([$user->flatten()])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testNewActionPostValid()
    {
        $response = new Response();
        $user = $this->getUserDetails(true);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->withArgs(['new', null])->andReturn('new')->once();


        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/about-you', ['new' => 'new']])->andReturn('/user/about-you/new')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->aboutYouDetails->shouldReceive('updateAllDetails')->withArgs([$this->form])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        //  Set up form
        $this->form->shouldReceive('setAttribute')->withArgs(['action', '/user/about-you/new'])->once();
        $this->setPostValid($this->form, $this->postData, $this->getExpectedDataToSet($user));

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * Get sample user details
     *
     * @param bool $newDetails
     * @return User
     */
    private function getUserDetails($newDetails = false)
    {
        $user = new User();

        if (!$newDetails) {
            //  Just set a name for the user details to be considered existing
            $user->id = 123;

            $user->createdAt = new DateTime();

            $user->updatedAt = new DateTime();

            $user->name = new Name([
                'title' => 'Mrs',
                'first' => 'New',
                'last'  => 'User',
            ]);
        }

        return $user;
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
