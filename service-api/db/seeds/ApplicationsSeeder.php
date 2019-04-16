q<?php


use Phinx\Seed\AbstractSeed;

class ApplicationsSeeder extends AbstractSeed
{
    public function getDependencies()
    {
        return [
            'UsersSeeder',
        ];
    }

    private function createApplication($id, $user, $email, $title, $firstName, $lastName)
    {
        $application = [
            'id' => $id,
            'user' => $user,
            'updatedAt' => '2019-03-08 11:59:20.804744+00',
            'startedAt' => '2019-03-08 11:59:20.804744+00',
            'createdAt' => '2019-03-08 11:59:20.804744+00',
            'completedAt' => '2019-03-08 11:59:20.804744+00',
            'lockedAt' => '2019-03-08 11:59:20.804744+00',
            'locked' => true,
            'whoAreYouAnswered' => true,
            'seed' => null,
            'repeatCaseNumber' => null,
            'document' => '{
                "type": "property-and-financial",
                "donor": {"dob": {
                    "date": "1980-01-01T00:00:00.000000+0000"},
                    "name": {"last": "' . $lastName . '", "first": "' . $firstName . '", "title": "' . $title . '"},
                    "email": {"address": "' . $email . '"},
                    "address": {"address1": "34 A Road", "address2": "", "address3": "", "postcode": "T56 6TY"},
                    "canSign": true,
                    "otherNames": ""
                },
                "preference": "",
                "instruction": "",
                "correspondent": {
                    "who": "donor",
                    "name": {"last": "' . $lastName . '", "first": "' . $firstName . '", "title": "' . $title . '"},
                    "email": {"address": "' . $email . '"},
                    "phone": null,
                    "address": {"address1": "34 A Road", "address2": "", "address3": "", "postcode": "T56 6TY"},
                    "company": null,
                    "contactByPost": false,
                    "contactInWelsh": false,
                    "contactDetailsEnteredManually": null
                },
                "peopleToNotify": [],
                "primaryAttorneys": [{
                    "id": 1,
                    "dob": {"date": "1980-02-01T00:00:00.000000+0000"},
                    "name": {
                        "last": "Billson",
                        "first": "Bob",
                        "title": "Mr"
                    },
                    "type": "human",
                    "email": null,
                    "address": {"address1": "34 A Road", "address2": "", "address3": "A town", "postcode": "B68 0NZ"}
                }],
                "whoIsRegistering": "donor",
                "certificateProvider": {"name": {"last": "Provider", "first": "A", "title": "Mrs"},
                "address": {"address1": "34 A Road", "address2": "", "address3": "", "postcode": "T56 7YU"}},
                "replacementAttorneys": [],
                "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null},
                "replacementAttorneyDecisions": null
            }',
            'payment' => '{
                "date": null,
                "email": null,
                "amount": 0,
                "method": null,
                "reference": null,
                "gatewayReference": null,
                "reducedFeeLowIncome": null,
                "reducedFeeAwardedDamages": true,
                "reducedFeeUniversalCredit": null,
                "reducedFeeReceivesBenefits": true
            }',
            'metadata' => null,
            'search' => $title . ' ' . $firstName . ' ' . $lastName,
        ];

        return $application;
    }

    public function run()
    {
        $data = [
            $this->createApplication('1','90e60becf3d5f385a9c07691109701f6', 'opgcasper@gmail.com',
                'Mrs', 'Anne', 'Anneson'),
            $this->createApplication('2','90e60becf3d5f385a9c07691109701f6', 'opgcasper@gmail.com',
                'Mr', 'Bob', 'Bobson'),
            $this->createApplication('3','90e60becf3d5f385a9c07691109701f6', 'opgcasper@gmail.com',
                'Mr', 'Carl', 'Carlson'),
        ];

        $users = $this->table('applications');

        foreach ($data as $application) {
            if (!$this->fetchRow("SELECT * FROM applications a WHERE a.id='" . $application['id'] . "'")) {
                $users->insert($application)->save();

                print('Seeded application ' . $application['id'] . "\n");
            } else {
                print('Application already exists with ID ' . $application['id'] . "\n");
            }
        }
    }
}
