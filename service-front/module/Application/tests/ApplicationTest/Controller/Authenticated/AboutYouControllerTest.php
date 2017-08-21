<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use Zend\View\Model\ViewModel;

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
    /**
     * @var MockInterface|Details
     */
    private $aboutYouDetails;

    public function setUp()
    {
        $this->controller = new AboutYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\AboutYou')->andReturn($this->form);

        $this->aboutYouDetails = Mockery::mock(Details::class);
        $this->serviceLocator->shouldReceive('get')->with('AboutYouDetails')->andReturn($this->aboutYouDetails);
    }

    public function testIndexAction()
    {
        $user = new User();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->with('user/about-you')->andReturn('user/about-you')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/about-you')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }
}