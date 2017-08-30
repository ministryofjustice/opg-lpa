<?php

namespace ApplicationTest\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use DateTime;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;

class AbstractLpaActorControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableAbstractLpaActorController
     */
    private $controller;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new TestableAbstractLpaActorController();
        parent::controllerSetUp($this->controller);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new UserIdentity($this->user->id, 'token', 60 * 60, new DateTime());

        $this->lpa = FixturesData::getPfLpa();
    }

    public function testReuseActorDetailsHttpOneOption()
    {
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $form = Mockery::mock(AbstractActorForm::class);
        $this->getHttpRouteMatch($this->controller);
        $this->request->shouldReceive('getPost')->withArgs(['reuse-details'])->andReturn(0)->once();
        $form->shouldReceive('bind')->once();

        $result = $this->controller->testReuseActorDetails($form);

        $this->assertTrue($result);
    }
}