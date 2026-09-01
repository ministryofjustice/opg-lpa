<?php

namespace Application\Model\Service\InstructionPreference;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Instruction\Entity as InstructionEntity;
use Application\Model\Service\Preference\Entity as PreferenceEntity;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @return ValidationApiProblem|array<InstructionEntity|PreferenceEntity>
     */
    public function update(string $lpaId, array $data): ValidationApiProblem|array
    {
        $instruction = ($data['instruction'] ?? null);
        $preference = ($data['preference'] ?? null);

        $lpa = $this->getLpa($lpaId);
        $lpa->getDocument()->setInstruction($instruction);
        $lpa->getDocument()->setPreference($preference);

        $validation = $lpa->getDocument()->validate(['instruction', 'preference']);

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return [new InstructionEntity($instruction), new PreferenceEntity($preference)];
    }
}
