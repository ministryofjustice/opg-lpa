<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\InstructionPreference;

use Application\Model\Service\InstructionPreference\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeSharedTest\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTestCase
{
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Service();
        $this->service->setLogger($this->logger);
    }

    public function testUpdateLpa()
    {
        $lpa = FixturesData::getHwLpa();
        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $data = [
            'instruction' => 'This is a test instruction',
            'preference' => 'This is a test preference',
        ];

        [$instruction, $preference] = $this->service->update(strval($lpa->getId()), $data);

        $this->assertEquals($data['instruction'], $instruction->toArray()['instruction']);
        $this->assertEquals($data['preference'], $preference->toArray()['preference']);
    }
}
