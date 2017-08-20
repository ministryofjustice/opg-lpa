<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CertificateProviderController;
use Application\Form\Lpa\CertificateProviderForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class CertificateProviderControllerTest extends AbstractControllerTest
{
    /**
     * @var CertificateProviderController
     */
    private $controller;
    /**
     * @var MockInterface|CertificateProviderForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new CertificateProviderController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(CertificateProviderForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\CertificateProviderForm')->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }
}