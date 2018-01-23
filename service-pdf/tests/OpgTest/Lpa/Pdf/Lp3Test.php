<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Lp3;
use Exception;

class Lp3Test extends AbstractPdfTestClass
{
    public function testConstructorThrowsExceptionNotEnoughData()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate Opg\Lpa\Pdf\Lp3');

        new Lp3(new Lpa());
    }

    public function testGeneratePFFirstPersonToNotify()
    {
        $lpa = $this->getLpa();

        $personToNotify = $lpa->document->peopleToNotify[0];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [
            3 => [
                [
                    'templateFileName' => 'LP3.pdf',
                    'strikeThroughTargets' => [
                        2 => [
                            'lp3-primaryAttorney-2',
                            'lp3-primaryAttorney-3',
                        ],
                    ],
                    'data' => [
                        'how-attorneys-act' => "jointly-attorney-severally",
                        'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
                        'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
                        'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
                        'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
                        'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
                        'lpa-document-primaryAttorneys-1-name-title' => "Mr",
                        'lpa-document-primaryAttorneys-1-name-first' => "Elliot",
                        'lpa-document-primaryAttorneys-1-name-last' => "Sanders",
                        'lpa-document-primaryAttorneys-1-address-address1' => "12 Church Lane",
                        'lpa-document-primaryAttorneys-1-address-address2' => "Brierfield",
                        'lpa-document-primaryAttorneys-1-address-address3' => "Lancashire",
                        'lpa-document-primaryAttorneys-1-address-postcode' => "L21 4WL",
                        'footer-right-page-three' => "LP3 People to notify (07.15)",
                    ],
                ],
            ],
            'end' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Mr",
            'lpa-document-peopleToNotify-name-first' => "Anthony",
            'lpa-document-peopleToNotify-name-last' => "Webb",
            'lpa-document-peopleToNotify-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-address-postcode' => "BS18 6PL",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }

    public function testGeneratePFSecondPersonToNotifySinglePrimaryAttorney()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $personToNotify = $lpa->document->peopleToNotify[1];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [
            2 => [
                'lp3-primaryAttorney-1',
                'lp3-primaryAttorney-2',
                'lp3-primaryAttorney-3',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            3 => [
                [
                    'templateFileName' => 'LP3.pdf',
                    'strikeThroughTargets' => [
                        2 => [
                            'lp3-primaryAttorney-2',
                            'lp3-primaryAttorney-3',
                        ],
                    ],
                    'data' => [
                        'how-attorneys-act' => "jointly-attorney-severally",
                        'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
                        'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
                        'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
                        'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
                        'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
                        'lpa-document-primaryAttorneys-1-name-title' => "Mr",
                        'lpa-document-primaryAttorneys-1-name-first' => "Elliot",
                        'lpa-document-primaryAttorneys-1-name-last' => "Sanders",
                        'lpa-document-primaryAttorneys-1-address-address1' => "12 Church Lane",
                        'lpa-document-primaryAttorneys-1-address-address2' => "Brierfield",
                        'lpa-document-primaryAttorneys-1-address-address3' => "Lancashire",
                        'lpa-document-primaryAttorneys-1-address-postcode' => "L21 4WL",
                        'footer-right-page-three' => "LP3 People to notify (07.15)",
                    ],
                ],
            ],
            'end' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Miss",
            'lpa-document-peopleToNotify-name-first' => "Louie",
            'lpa-document-peopleToNotify-name-last' => "Wade",
            'lpa-document-peopleToNotify-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-address-address2' => "",
            'lpa-document-peopleToNotify-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-address-postcode' => "SP4 4DY",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }

    public function testGeneratePFThirdPersonToNotifyDonorRegistering()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $personToNotify = $lpa->document->peopleToNotify[2];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [
            3 => [
                [
                    'templateFileName' => 'LP3.pdf',
                    'strikeThroughTargets' => [
                        2 => [
                            'lp3-primaryAttorney-2',
                            'lp3-primaryAttorney-3',
                        ],
                    ],
                    'data' => [
                        'how-attorneys-act' => "jointly-attorney-severally",
                        'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
                        'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
                        'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
                        'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
                        'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
                        'lpa-document-primaryAttorneys-1-name-title' => "Mr",
                        'lpa-document-primaryAttorneys-1-name-first' => "Elliot",
                        'lpa-document-primaryAttorneys-1-name-last' => "Sanders",
                        'lpa-document-primaryAttorneys-1-address-address1' => "12 Church Lane",
                        'lpa-document-primaryAttorneys-1-address-address2' => "Brierfield",
                        'lpa-document-primaryAttorneys-1-address-address3' => "Lancashire",
                        'lpa-document-primaryAttorneys-1-address-postcode' => "L21 4WL",
                        'footer-right-page-three' => "LP3 People to notify (07.15)",
                    ],
                ],
            ],
            'end' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Mr",
            'lpa-document-peopleToNotify-name-first' => "Stern",
            'lpa-document-peopleToNotify-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-address-postcode' => "JL7 8AK",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "donor",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }

    public function testGeneratePFFourthPersonToNotifyTrustAttorneyOnly()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Ensure that there is only a trust primary attorney
        foreach ($lpa->document->primaryAttorneys as $attorneyKey => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                //  Set the trust as the only attorney registering the LPA
                $lpa->document->whoIsRegistering = [$attorney->id];
            } else {
                //  Remove the attorney
                unset($lpa->document->primaryAttorneys[$attorneyKey]);
            }
        }

        $personToNotify = $lpa->document->peopleToNotify[3];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [
            2 => [
                'lp3-primaryAttorney-1',
                'lp3-primaryAttorney-2',
                'lp3-primaryAttorney-3',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            3 => [
                [
                    'templateFileName' => 'LP3.pdf',
                    'strikeThroughTargets' => [
                        2 => [
                            'lp3-primaryAttorney-2',
                            'lp3-primaryAttorney-3',
                        ],
                    ],
                    'data' => [
                        'how-attorneys-act' => "jointly-attorney-severally",
                        'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
                        'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
                        'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
                        'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
                        'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
                        'lpa-document-primaryAttorneys-1-name-title' => "Mr",
                        'lpa-document-primaryAttorneys-1-name-first' => "Elliot",
                        'lpa-document-primaryAttorneys-1-name-last' => "Sanders",
                        'lpa-document-primaryAttorneys-1-address-address1' => "12 Church Lane",
                        'lpa-document-primaryAttorneys-1-address-address2' => "Brierfield",
                        'lpa-document-primaryAttorneys-1-address-address3' => "Lancashire",
                        'lpa-document-primaryAttorneys-1-address-postcode' => "L21 4WL",
                        'footer-right-page-three' => "LP3 People to notify (07.15)",
                    ],
                ],
            ],
            'end' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Mr",
            'lpa-document-peopleToNotify-name-first' => "Jayden",
            'lpa-document-peopleToNotify-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-address-address3' => "Kent",
            'lpa-document-peopleToNotify-address-postcode' => "YL4 5DL",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "property-and-financial-affairs",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
            'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
            'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
            'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
            'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }

    public function testGenerateHWFirstPersonToNotify()
    {
        $lpa = $this->getLpa(false);

        $personToNotify = $lpa->document->peopleToNotify[0];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [
            3 => [
                [
                    'templateFileName' => 'LP3.pdf',
                    'strikeThroughTargets' => [
                        2 => [
                            'lp3-primaryAttorney-1',
                            'lp3-primaryAttorney-2',
                            'lp3-primaryAttorney-3',
                        ],
                    ],
                    'data' => [
                        'how-attorneys-act' => "jointly-attorney-severally",
                        'lpa-document-primaryAttorneys-0-name-title' => "Mr",
                        'lpa-document-primaryAttorneys-0-name-first' => "Elliot",
                        'lpa-document-primaryAttorneys-0-name-last' => "Sanders",
                        'lpa-document-primaryAttorneys-0-address-address1' => "12 Church Lane",
                        'lpa-document-primaryAttorneys-0-address-address2' => "Brierfield",
                        'lpa-document-primaryAttorneys-0-address-address3' => "Lancashire",
                        'lpa-document-primaryAttorneys-0-address-postcode' => "L21 4WL",
                        'footer-right-page-three' => "LP3 People to notify (07.15)",
                    ],
                ],
            ],
            'end' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Mr",
            'lpa-document-peopleToNotify-name-first' => "Anthony",
            'lpa-document-peopleToNotify-name-last' => "Webb",
            'lpa-document-peopleToNotify-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-address-postcode' => "BS18 6PL",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "health-and-welfare",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }

    public function testGenerateHWSecondPersonToNotifySinglePrimaryAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $personToNotify = $lpa->document->peopleToNotify[1];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [
            2 => [
                'lp3-primaryAttorney-1',
                'lp3-primaryAttorney-2',
                'lp3-primaryAttorney-3',
            ]
        ];

        $blankTargets = [];

        $constituentPdfs = [];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Miss",
            'lpa-document-peopleToNotify-name-first' => "Louie",
            'lpa-document-peopleToNotify-name-last' => "Wade",
            'lpa-document-peopleToNotify-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-address-address2' => "",
            'lpa-document-peopleToNotify-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-address-postcode' => "SP4 4DY",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "attorney",
            'lpa-type' => "health-and-welfare",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }

    public function testGenerateHWThirdPersonToNotifyDonorRegistering()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $personToNotify = $lpa->document->peopleToNotify[2];

        $pdf = new Lp3($lpa, $personToNotify);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP3.pdf';

        $strikeThroughTargets = [];

        $blankTargets = [];

        $constituentPdfs = [
            3 => [
                [
                    'templateFileName' => 'LP3.pdf',
                    'strikeThroughTargets' => [
                        2 => [
                            'lp3-primaryAttorney-1',
                            'lp3-primaryAttorney-2',
                            'lp3-primaryAttorney-3',
                        ],
                    ],
                    'data' => [
                        'how-attorneys-act' => "jointly-attorney-severally",
                        'lpa-document-primaryAttorneys-0-name-title' => "Mr",
                        'lpa-document-primaryAttorneys-0-name-first' => "Elliot",
                        'lpa-document-primaryAttorneys-0-name-last' => "Sanders",
                        'lpa-document-primaryAttorneys-0-address-address1' => "12 Church Lane",
                        'lpa-document-primaryAttorneys-0-address-address2' => "Brierfield",
                        'lpa-document-primaryAttorneys-0-address-address3' => "Lancashire",
                        'lpa-document-primaryAttorneys-0-address-postcode' => "L21 4WL",
                        'footer-right-page-three' => "LP3 People to notify (07.15)",
                    ],
                ],
            ],
            'end' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];

        $data = [
            'lpa-document-peopleToNotify-name-title' => "Mr",
            'lpa-document-peopleToNotify-name-first' => "Stern",
            'lpa-document-peopleToNotify-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-address-postcode' => "JL7 8AK",
            'footer-right-page-one' => "LP3 People to notify (07.15)",
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'who-is-applicant' => "donor",
            'lpa-type' => "health-and-welfare",
            'footer-right-page-two' => "LP3 People to notify (07.15)",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
            'footer-right-page-four' => "LP3 People to notify (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp3.pdf');
    }
}
