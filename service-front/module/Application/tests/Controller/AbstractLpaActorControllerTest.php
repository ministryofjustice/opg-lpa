<?php

namespace ApplicationTest\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;

class AbstractLpaActorControllerTest extends AbstractControllerTest
{
    public function testReuseActorDetailsHttpOneOption()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $this->userDetailsSession->user = $this->user;
        $form = Mockery::mock(AbstractActorForm::class);
        $this->getHttpRouteMatch($controller);
        $this->request->shouldReceive('getPost')->withArgs(['reuse-details'])->andReturn(0)->once();
        $form->shouldReceive('bind')->once();

        $result = $controller->testReuseActorDetails($form);

        $this->assertTrue($result);
    }

    public function testGetActorReuseDetailsCorrespondentOther()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $seedLpa = FixturesData::getHwLpa();
        $seedLpa->document->correspondent->who = Correspondence::WHO_OTHER;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->userDetailsSession->user = $this->user;

        $result = $controller->testGetActorReuseDetails();
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, '(was the correspondent)');

        $this->assertNotNull($reuseDetails);
    }

    // Test that the correspondent from the reused (seed) LPA appears in the
    // reusable actors list when on the correspondent page
    public function testGetActorReuseDetailsWasCorrespondentForCorrespondentPopup()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $seedLpa = FixturesData::getHwLpa();
        $seedLpa->document->correspondent->who = Correspondence::WHO_OTHER;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->userDetailsSession->user = $this->user;

        $result = $controller->testGetActorReuseDetails(true, true);
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, '(was the correspondent)');

        $this->assertNotNull($reuseDetails);
    }

    public function testGetActorReuseDetailsTrustNotIncluded()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $seedLpa = FixturesData::getHwLpa();
        $trust = FixturesData::getAttorneyTrust();
        $seedLpa->document->primaryAttorneys[] = $trust;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->userDetailsSession->user = $this->user;

        $result = $controller->testGetActorReuseDetails(false);
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, $trust->name);

        $this->assertNull($reuseDetails);
    }

    public function testGetActorReuseDetailsTrustIncluded()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $seedLpa = FixturesData::getHwLpa();
        $trust = FixturesData::getAttorneyTrust();
        $seedLpa->document->primaryAttorneys[] = $trust;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->userDetailsSession->user = $this->user;

        $result = $controller->testGetActorReuseDetails(true);
        $reuseDetails = $this->getReuseDetailsByLabelContains($result, $trust->name);
        $reuseDetailsTrust = $result['t'];

        $this->assertNotNull($reuseDetails);
        $this->assertNotNull($reuseDetailsTrust);
        $this->assertEquals($reuseDetails, $reuseDetailsTrust);
    }

    public function testGetActorReuseDetailsUserDetailsAlreadyUsedAsDonor()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $seedLpa = FixturesData::getHwLpa();
        $this->lpa->document->donor->name->first = $this->user->name->first;
        $this->lpa->document->donor->name->last = $this->user->name->last;
        $this->setSeedLpa($this->lpa, $seedLpa);
        $this->userDetailsSession->user = $this->user;

        $result = $controller->testGetActorReuseDetails();
        $reuseDetails = $this->getReuseDetailsByLabelContains(
            $result,
            $this->user->name->first . ' ' . $this->user->name->last
        );

        $this->assertNull($reuseDetails);
    }

    public function testUpdateCorrespondentDataTrust()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $this->lpa->document->correspondent->who = Correspondence::WHO_ATTORNEY;

        $this->lpaApplicationService->shouldReceive('setCorrespondent')->withArgs(function ($lpa, $correspondent) {
            return $lpa->id === $this->lpa->id && $correspondent->company === FixturesData::getAttorneyTrust()->name;
        })->andReturn(true)->once();

        $trust = FixturesData::getAttorneyTrust();
        $result = $controller->testUpdateCorrespondentData($trust);

        $this->assertNull($result);
    }

    public function testUpdateCorrespondentDataFailed()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $this->lpa->document->correspondent->who = Correspondence::WHO_ATTORNEY;

        $this->lpaApplicationService->shouldReceive('setCorrespondent')->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update correspondent for id: 91333263035');

        $trust = FixturesData::getAttorneyTrust();
        $controller->testUpdateCorrespondentData($trust);
    }

    public function testUpdateCorrespondentDataFailedOnDelete()
    {
        $controller = $this->getController(TestableAbstractLpaActorController::class);

        $this->lpa->document->correspondent->who = Correspondence::WHO_ATTORNEY;

        $this->lpaApplicationService->shouldReceive('deleteCorrespondent')
            ->andReturn(false)
            ->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete correspondent for id: 91333263035');

        $trust = FixturesData::getAttorneyTrust();
        $isDelete = true;
        $controller->testUpdateCorrespondentData($trust, $isDelete);
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
