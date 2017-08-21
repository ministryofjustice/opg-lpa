<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\ForgotPasswordController;
use Application\Form\User\ResetPasswordEmail;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Response;

class ForgotPasswordControllerTest extends AbstractControllerTest
{
    /**
     * @var ForgotPasswordController
     */
    private $controller;
    /**
     * @var MockInterface|ResetPasswordEmail
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ForgotPasswordController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ResetPasswordEmail::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\ResetPasswordEmail')->andReturn($this->form);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());
    }

    public function testIndexActionAlreadyLoggedIn()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}