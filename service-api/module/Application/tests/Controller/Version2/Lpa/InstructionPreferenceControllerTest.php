<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\InstructionPreferenceController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\InstructionPreference\Service;
use Application\Library\Authorization\UnauthorizedException;
use MakeShared\DataModel\Validator\ValidatorResponse;
use Mockery;
use Mockery\MockInterface;

class InstructionPreferenceControllerTest extends AbstractControllerTestCase
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(array $parameters = []): InstructionPreferenceController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new InstructionPreferenceController($this->authenticationService, $this->service);
        $this->callDispatch($controller, $parameters);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testUpdateSuccess()
    {
        $controller = $this->getController();

        $this->service
            ->shouldReceive('update')
            ->withArgs([$this->lpaId, ['some' => 'data']])
            ->andReturn([
                $this->createEntity(['instruction' => 'an instruction']),
                $this->createEntity(['preference' => 'a preference']),
            ])->once();

        $response = $controller->update(98765, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('[{"instruction":"an instruction"},{"preference":"a preference"}]', $response->getContent());
    }

    public function testUpdateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service
            ->shouldReceive('update')
            ->withArgs([$this->lpaId, ['some' => 'data']])
            ->andReturn(new ValidationApiProblem(new ValidatorResponse([])))->once();

        $response = $controller->update(10, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
            'title' => 'Bad Request',
            'status' => 400,
            'detail' => 'Your request could not be processed due to validation error',
            'validation' => []
        ], $response->toArray());
    }

    public function testUpdateUnauthorised()
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->update(10, []);
    }
}
