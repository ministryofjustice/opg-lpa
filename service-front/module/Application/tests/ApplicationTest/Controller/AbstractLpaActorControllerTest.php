<?php

namespace ApplicationTest\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use DateTime;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;

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

    public function testGetActorReuseDetailsCorrespondentOther()
    {
        $seedLpa = FixturesData::getHwLpa();
        $seedLpa->document->correspondent->who = Correspondence::WHO_OTHER;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $result = $this->controller->testGetActorReuseDetails();
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, '(was the correspondent)');

        $this->assertNotNull($reuseDetails);
    }

    public function testGetActorReuseDetailsTrustNotIncluded()
    {
        $seedLpa = FixturesData::getHwLpa();
        $trust = FixturesData::getAttorneyTrust();
        $seedLpa->document->primaryAttorneys[] = $trust;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $result = $this->controller->testGetActorReuseDetails(false);
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, $trust->name);

        $this->assertNull($reuseDetails);
    }

    public function testGetActorReuseDetailsTrustIncluded()
    {
        $seedLpa = FixturesData::getHwLpa();
        $trust = FixturesData::getAttorneyTrust();
        $seedLpa->document->primaryAttorneys[] = $trust;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $result = $this->controller->testGetActorReuseDetails(true);
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, $trust->name);
        $reuseDetailsTrust = $result['t'];

        $this->assertNotNull($reuseDetails);
        $this->assertNotNull($reuseDetailsTrust);
        $this->assertEquals($reuseDetails, $reuseDetailsTrust);
    }

    public function testGetActorReuseDetailsUserDetailsAlreadyUsedAsDonor()
    {
        $seedLpa = FixturesData::getHwLpa();
        $this->lpa->document->donor->name->first = $this->user->name->first;
        $this->lpa->document->donor->name->last = $this->user->name->last;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $result = $this->controller->testGetActorReuseDetails();
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, $this->user->name->first . ' ' . $this->user->name->last);

        $this->assertNull($reuseDetails);
    }

    public function testUpdateCorrespondentDataTrust()
    {
        $this->lpa->document->correspondent->who = Correspondence::WHO_ATTORNEY;
        $this->controller->setLpa($this->lpa);

        $this->lpaApplicationService->shouldReceive('setCorrespondent')->withArgs(function ($lpaId, $correspondent) {
            return $lpaId === $this->lpa->id && $correspondent->company === FixturesData::getAttorneyTrust()->name;
        })->andReturn(true)->once();

        $trust = FixturesData::getAttorneyTrust();
        $result = $this->controller->testUpdateCorrespondentData($trust);

        $this->assertNull($result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update correspondent for id: 91333263035
     */
    public function testUpdateCorrespondentDataFailed()
    {
        $this->lpa->document->correspondent->who = Correspondence::WHO_ATTORNEY;
        $this->controller->setLpa($this->lpa);

        $this->lpaApplicationService->shouldReceive('setCorrespondent')->andReturn(false)->once();

        $trust = FixturesData::getAttorneyTrust();
        $this->controller->testUpdateCorrespondentData($trust);
    }

    private function getReuseDetailsByLabelContains($actorReuseDetails, $label)
    {
        $index = null;
        foreach ($actorReuseDetails as $key => $value) {
            if (strpos($value['label'], $label) !== false) {
                $index = $key;
                break;
            }
        }

        if ($index === null) {
            return null;
        }

        return $actorReuseDetails[$index];
    }
}
