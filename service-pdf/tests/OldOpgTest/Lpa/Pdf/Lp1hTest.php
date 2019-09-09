<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Lp1h;
use Exception;

class Lp1hTest extends AbstractPdfTestClass
{
    public function testConstructorThrowsExceptionNotEnoughData()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate Opg\Lpa\Pdf\Lp1h');

        new Lp1h(new Lpa());
    }

    public function testGenerate()
    {
        $lpa = $this->getLpa(false);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateDraft()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Remove some details so the LPA is determined to be in a draft state
        $lpa->payment = null;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetInstrument.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateSimpleLpaWithNoContinuationSheets()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Cut down the actors to the maximum number
        array_splice($lpa->document->primaryAttorneys, 4);
        array_splice($lpa->document->whoIsRegistering, 4);
        array_splice($lpa->document->replacementAttorneys, 2);
        array_splice($lpa->document->peopleToNotify, 4);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "jointly-attorney-severally",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateCanSustainLifeFalse()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change when the life sustaining treatment questions can be made
        $lpa->document->primaryAttorneyDecisions->canSustainLife = false;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-A",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateDonorRegistering()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            16 => [
                "applicant-0-hw",
                "applicant-1-hw",
                "applicant-2-hw",
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-1-hw',
                'applicant-signature-2-hw',
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "donor",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateSinglePrimaryAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            1 => [
                "primaryAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            2 => [
                "primaryAttorney-2",
                "primaryAttorney-3",
            ],
            16 => [
                "applicant-1-hw",
                "applicant-2-hw",
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-1-hw',
                'applicant-signature-2-hw',
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Ms",
            'signature-attorney-1-name-first' => "Isobel",
            'signature-attorney-1-name-last' => "Ward",
            'signature-attorney-2-name-title' => "Mr",
            'signature-attorney-2-name-first' => "Ewan",
            'signature-attorney-2-name-last' => "Adams",
            'signature-attorney-3-name-title' => "Ms",
            'signature-attorney-3-name-first' => "Erica",
            'signature-attorney-3-name-last' => "Schmidt",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateSingleReplacementAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of replacement attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            4 => [
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateZeroReplacementAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            4 => [
                "replacementAttorney-0-hw",
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateThreeAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys
        array_splice($lpa->document->primaryAttorneys, 3);
        array_splice($lpa->document->whoIsRegistering, 3);

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            2 => [
                "primaryAttorney-3",
            ],
            4 => [
                "replacementAttorney-0-hw",
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            14 => [
                "attorney-signature-hw",
            ],
            16 => [
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateTwoAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys
        array_splice($lpa->document->primaryAttorneys, 2);
        array_splice($lpa->document->whoIsRegistering, 2);

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            2 => [
                "primaryAttorney-2",
                "primaryAttorney-3",
            ],
            4 => [
                "replacementAttorney-0-hw",
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            13 => [
                "attorney-signature-hw",
            ],
            14 => [
                "attorney-signature-hw",
            ],
            16 => [
                "applicant-2-hw",
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-2-hw',
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateOneAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            1 => [
                "primaryAttorney-1-hw",
            ],
            2 => [
                "primaryAttorney-2",
                "primaryAttorney-3",
            ],
            4 => [
                "replacementAttorney-0-hw",
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            12 => [
                "attorney-signature-hw",
            ],
            13 => [
                "attorney-signature-hw",
            ],
            14 => [
                "attorney-signature-hw",
            ],
            16 => [
                "applicant-1-hw",
                "applicant-2-hw",
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-1-hw',
                'applicant-signature-2-hw',
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGeneratePrimaryAttorneysActJointly()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the primary attorneys act to jointly
        $lpa->document->primaryAttorneyDecisions->how = 'jointly';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateLessThanSixPeopleToNotify()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of people to notify to 3
        array_splice($lpa->document->peopleToNotify, 3);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            6 => [
                "people-to-notify-3",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateEmptyInstructionsAndPreferences()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Blank the instructions and preferences
        $lpa->document->instruction = '';
        $lpa->document->preference = '';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            7 => [
                "preference",
                "instruction",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateContactDetailsEnteredManuallyFalse()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set contact details entered manually to false
        $lpa->document->correspondent->contactDetailsEnteredManually = false;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateCorrespondentIsAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set the first attorney as the correspondent
        $firstAttorney = $lpa->document->primaryAttorneys[0];

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_ATTORNEY,
            'name'                          => $firstAttorney->name->toArray(),
            'address'                       => $firstAttorney->address,
            'contactDetailsEnteredManually' => false,
        ]);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "attorney",
            'lpa-document-correspondent-name-title' => "Mrs",
            'lpa-document-correspondent-name-first' => "Amy",
            'lpa-document-correspondent-name-last' => "Wheeler",
            'lpa-document-correspondent-company' => "",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateCorrespondentIsAttorneyEnteredManually()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set the first attorney as the correspondent
        $firstAttorney = $lpa->document->primaryAttorneys[0];

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_ATTORNEY,
            'name'                          => $firstAttorney->name->toArray(),
            'address'                       => $firstAttorney->address,
            'contactDetailsEnteredManually' => true,
        ]);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "attorney",
            'lpa-document-correspondent-name-title' => "Mrs",
            'lpa-document-correspondent-name-first' => "Amy",
            'lpa-document-correspondent-name-last' => "Wheeler",
            'lpa-document-correspondent-company' => "",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateCorrespondentIsCertificateProvider()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set the certificate provider as the correspondent
        $certificateProvider = $lpa->document->certificateProvider;

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_CERTIFICATE_PROVIDER,
            'name'                          => $certificateProvider->name->toArray(),
            'address'                       => $certificateProvider->address,
            'contactDetailsEnteredManually' => true,
        ]);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "other",
            'lpa-document-correspondent-name-title' => "Mr",
            'lpa-document-correspondent-name-first' => "Reece",
            'lpa-document-correspondent-name-last' => "Richards",
            'lpa-document-correspondent-company' => "",
            'lpa-document-correspondent-address-address1' => "11 Brookside",
            'lpa-document-correspondent-address-address2' => "Cholsey",
            'lpa-document-correspondent-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-correspondent-address-postcode' => "OX10 9NN",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateCorrespondentIsOther()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        $otherName = new LongName([
            'title' => 'Sir',
            'first' => 'Ronald',
            'last'  => 'McDonald',
        ]);

        $otherAddress = new Address([
            'address1' => '123 Road Street',
            'address2' => 'Some town',
            'address3' => 'Some county',
            'postcode' => 'AB1 2CD',
        ]);

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_OTHER,
            'name'                          => $otherName,
            'company'                       => 'Some Company Ltd',
            'address'                       => $otherAddress,
            'contactDetailsEnteredManually' => true,
        ]);

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "other",
            'lpa-document-correspondent-name-title' => "Sir",
            'lpa-document-correspondent-name-first' => "Ronald",
            'lpa-document-correspondent-name-last' => "McDonald",
            'lpa-document-correspondent-address-address1' => "123 Road Street",
            'lpa-document-correspondent-address-address2' => "Some town",
            'lpa-document-correspondent-address-address3' => "Some county",
            'lpa-document-correspondent-address-postcode' => "AB1 2CD",
            'lpa-document-correspondent-company' => "Some Company Ltd",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesPrimaryAttorneysActDepends()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the primary attorneys act to depends
        $lpa->document->primaryAttorneyDecisions->how = 'depends';
        $lpa->document->primaryAttorneyDecisions->howDetails = 'Some information about how they act here';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "depends",
            'has-more-than-2-replacement-attorneys' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesReplacementAttorneysActJointlyAndSeverally()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        //  Change how the replacement attorneys act to jointly and severally
        $lpa->document->replacementAttorneyDecisions->how = 'jointly-attorney-severally';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            1 => [
                "primaryAttorney-1-hw",
            ],
            2 => [
                "primaryAttorney-2",
                "primaryAttorney-3",
            ],
            5 => [
                "life-sustain-B",
            ],
            16 => [
                "applicant-1-hw",
                "applicant-2-hw",
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-1-hw',
                'applicant-signature-2-hw',
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Ms",
            'signature-attorney-1-name-first' => "Isobel",
            'signature-attorney-1-name-last' => "Ward",
            'signature-attorney-2-name-title' => "Mr",
            'signature-attorney-2-name-first' => "Ewan",
            'signature-attorney-2-name-last' => "Adams",
            'signature-attorney-3-name-title' => "Ms",
            'signature-attorney-3-name-first' => "Erica",
            'signature-attorney-3-name-last' => "Schmidt",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesReplacementAttorneysActJointly()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        //  Change how the replacement attorneys act to jointly
        $lpa->document->replacementAttorneyDecisions->how = 'jointly';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            1 => [
                "primaryAttorney-1-hw",
            ],
            2 => [
                "primaryAttorney-2",
                "primaryAttorney-3",
            ],
            5 => [
                "life-sustain-B",
            ],
            16 => [
                "applicant-1-hw",
                "applicant-2-hw",
                "applicant-3-hw",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [
            19 => [
                'applicant-signature-1-hw',
                'applicant-signature-2-hw',
                'applicant-signature-3-hw',
            ],
        ];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'how-attorneys-act' => "only-one-attorney-appointed",
            'has-more-than-2-replacement-attorneys' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Ms",
            'signature-attorney-1-name-first' => "Isobel",
            'signature-attorney-1-name-last' => "Ward",
            'signature-attorney-2-name-title' => "Mr",
            'signature-attorney-2-name-first' => "Ewan",
            'signature-attorney-2-name-last' => "Adams",
            'signature-attorney-3-name-title' => "Ms",
            'signature-attorney-3-name-first' => "Erica",
            'signature-attorney-3-name-last' => "Schmidt",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesSingleReplacementAttorneyStepsInWhenFirst()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of replacement attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        //  Change when replacement attorneys step in to first and how they act to null (because there is only one)
        $lpa->document->replacementAttorneyDecisions->when = 'first';
        $lpa->document->replacementAttorneyDecisions->how = null;

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            4 => [
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesSingleReplacementAttorneyStepsInDepends()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of replacement attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        //  Change when the replacement attorneys step in to depends
        $lpa->document->replacementAttorneyDecisions->when = 'depends';
        $lpa->document->replacementAttorneyDecisions->whenDetails = 'Some information about how they step in here';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            4 => [
                "replacementAttorney-1-hw",
            ],
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesMultiReplacementAttorneysActJointlyAndSeverally()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the replacement attorneys act to jointly and severally
        $lpa->document->replacementAttorneyDecisions->how = 'jointly-attorney-severally';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesMultiReplacementAttorneysActJointly()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the replacement attorneys act to jointly
        $lpa->document->replacementAttorneyDecisions->how = 'jointly';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesMultiReplacementAttorneysStepsInDepends()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change when the replacement attorneys step in to depends
        $lpa->document->replacementAttorneyDecisions->when = 'depends';
        $lpa->document->replacementAttorneyDecisions->whenDetails = 'Some information about how they step in here';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.                            ",
            'lpa-document-instruction' => "\r\nMaecenas posuere augue sed purus malesuada dapibus.                                 ",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }

    public function testGenerateAdditionalPagesLongInstructionsAndPreferences()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Update the instructions and preferences details to be very long
        $lpa->document->instruction = 'Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here';
        $lpa->document->preference = 'Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here';

        $pdf = new Lp1h($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LP1H.pdf';

        $strikeThroughTargets = [
            5 => [
                "life-sustain-B",
            ],
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('LP1H_CoversheetRegistration.pdf'),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1H.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1H Health and welfare (07.15)",
                        'footer-registration-right' => "LP1H Register your LPA (07.15)",
                    ],
                ],
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                null,   //TODO - To be changed after we fix the aggregator checking
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_3.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs3-donor-full-name' => "Mrs Nancy Garrison",
                        'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-1-hw',
                            'applicant-2-hw',
                            'applicant-3-hw',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-title' => "Mr",
                        'applicant-0-name-first' => "Elliot",
                        'applicant-0-name-last' => "Sanders",
                        'applicant-0-dob-date-day' => "10",
                        'applicant-0-dob-date-month' => "10",
                        'applicant-0-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1H.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-1-hw',
                            'applicant-signature-2-hw',
                            'applicant-signature-3-hw',
                        ],
                    ],
                ],
            ],
        ];

        $data = [
            'lpa-document-donor-name-title' => "Mrs",
            'lpa-document-donor-name-first' => "Nancy",
            'lpa-document-donor-name-last' => "Garrison",
            'lpa-document-donor-otherNames' => "",
            'lpa-document-donor-dob-date-day' => "11",
            'lpa-document-donor-dob-date-month' => "01",
            'lpa-document-donor-dob-date-year' => "1948",
            'lpa-document-donor-address-address1' => "Bank End Farm House",
            'lpa-document-donor-address-address2' => "Undercliff Drive",
            'lpa-document-donor-address-address3' => "Ventnor, Isle of Wight",
            'lpa-document-donor-address-postcode' => "PO38 1UL",
            'lpa-document-donor-email-address' => "opglpademo+LouiseJames@gmail.com",
            'has-more-than-4-attorneys' => "On",
            'how-attorneys-act' => "jointly-attorney-severally",
            'has-more-than-2-replacement-attorneys' => "On",
            'change-how-replacement-attorneys-step-in' => "On",
            'lpa-document-peopleToNotify-0-name-title' => "Mr",
            'lpa-document-peopleToNotify-0-name-first' => "Anthony",
            'lpa-document-peopleToNotify-0-name-last' => "Webb",
            'lpa-document-peopleToNotify-0-address-address1' => "Brickhill Cottage",
            'lpa-document-peopleToNotify-0-address-address2' => "Birch Cross",
            'lpa-document-peopleToNotify-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-peopleToNotify-0-address-postcode' => "BS18 6PL",
            'lpa-document-peopleToNotify-1-name-title' => "Miss",
            'lpa-document-peopleToNotify-1-name-first' => "Louie",
            'lpa-document-peopleToNotify-1-name-last' => "Wade",
            'lpa-document-peopleToNotify-1-address-address1' => "33 Lincoln Green Lane",
            'lpa-document-peopleToNotify-1-address-address2' => "",
            'lpa-document-peopleToNotify-1-address-address3' => "Cholderton, Oxfordshire",
            'lpa-document-peopleToNotify-1-address-postcode' => "SP4 4DY",
            'lpa-document-peopleToNotify-2-name-title' => "Mr",
            'lpa-document-peopleToNotify-2-name-first' => "Stern",
            'lpa-document-peopleToNotify-2-name-last' => "Hamlet",
            'lpa-document-peopleToNotify-2-address-address1' => "33 Junction road",
            'lpa-document-peopleToNotify-2-address-address2' => "Brighton",
            'lpa-document-peopleToNotify-2-address-address3' => "Sussex",
            'lpa-document-peopleToNotify-2-address-postcode' => "JL7 8AK",
            'lpa-document-peopleToNotify-3-name-title' => "Mr",
            'lpa-document-peopleToNotify-3-name-first' => "Jayden",
            'lpa-document-peopleToNotify-3-name-last' => "Rodriguez",
            'lpa-document-peopleToNotify-3-address-address1' => "42 York Road",
            'lpa-document-peopleToNotify-3-address-address2' => "Canterbury",
            'lpa-document-peopleToNotify-3-address-address3' => "Kent",
            'lpa-document-peopleToNotify-3-address-postcode' => "YL4 5DL",
            'has-more-than-4-notified-people' => "On",
            'has-more-than-5-notified-people' => "On",
            'lpa-document-preference' => "\r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\n",
            'lpa-document-instruction' => "\r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\n",
            'see_continuation_sheet_3' => "see continuation sheet 3",
            'lpa-document-certificateProvider-name-title' => "Mr",
            'lpa-document-certificateProvider-name-first' => "Reece",
            'lpa-document-certificateProvider-name-last' => "Richards",
            'lpa-document-certificateProvider-address-address1' => "11 Brookside",
            'lpa-document-certificateProvider-address-address2' => "Cholsey",
            'lpa-document-certificateProvider-address-address3' => "Wallingford, Oxfordshire",
            'lpa-document-certificateProvider-address-postcode' => "OX10 9NN",
            'who-is-applicant' => "attorney",
            'applicant-0-name-title' => "Mrs",
            'applicant-0-name-first' => "Amy",
            'applicant-0-name-last' => "Wheeler",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "05",
            'applicant-0-dob-date-year' => "1975",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "David",
            'applicant-1-name-last' => "Wheeler",
            'applicant-1-dob-date-day' => "12",
            'applicant-1-dob-date-month' => "03",
            'applicant-1-dob-date-year' => "1972",
            'applicant-2-name-title' => "Dr",
            'applicant-2-name-first' => "Wellington",
            'applicant-2-name-last' => "Gastri",
            'applicant-2-dob-date-day' => "02",
            'applicant-2-dob-date-month' => "09",
            'applicant-2-dob-date-year' => "1982",
            'applicant-3-name-title' => "Dr",
            'applicant-3-name-first' => "Henry",
            'applicant-3-name-last' => "Taylor",
            'applicant-3-dob-date-day' => "10",
            'applicant-3-dob-date-month' => "09",
            'applicant-3-dob-date-year' => "1973",
            'who-is-correspondent' => "donor",
            'correspondent-contact-by-post' => "On",
            'correspondent-contact-by-phone' => "On",
            'lpa-document-correspondent-phone-number' => "01234123456",
            'correspondent-contact-by-email' => "On",
            'lpa-document-correspondent-email-address' => "opglpademo+LouiseJames@gmail.com",
            'correspondent-contact-in-welsh' => "On",
            'is-repeat-application' => "On",
            'repeat-application-case-number' => 12345678,
            'pay-by' => "card",
            'lpa-payment-phone-number' => "NOT REQUIRED.",
            'apply-for-fee-reduction' => "On",
            'lpa-payment-reference' => "ABCD-1234",
            'lpa-payment-amount' => "£0.00",
            'lpa-payment-date-day' => "26",
            'lpa-payment-date-month' => "07",
            'lpa-payment-date-year' => "2017",
            'lpa-document-primaryAttorneys-0-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-0-name-first' => "Amy",
            'lpa-document-primaryAttorneys-0-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-0-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-0-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-0-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-0-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-0-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-0-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "David",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Dr",
            'lpa-document-primaryAttorneys-2-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-2-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-2-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-2-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-2-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Henry",
            'lpa-document-primaryAttorneys-3-name-last' => "Taylor",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1973",
            'lpa-document-primaryAttorneys-3-address-address1' => "Lark Meadow Drive",
            'lpa-document-primaryAttorneys-3-address-address2' => "Solihull",
            'lpa-document-primaryAttorneys-3-address-address3' => "Birmingham",
            'lpa-document-primaryAttorneys-3-address-postcode' => "B37 6NA",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'lpa-document-replacementAttorneys-0-name-title' => "Ms",
            'lpa-document-replacementAttorneys-0-name-first' => "Isobel",
            'lpa-document-replacementAttorneys-0-name-last' => "Ward",
            'lpa-document-replacementAttorneys-0-dob-date-day' => "01",
            'lpa-document-replacementAttorneys-0-dob-date-month' => "02",
            'lpa-document-replacementAttorneys-0-dob-date-year' => "1937",
            'lpa-document-replacementAttorneys-0-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-0-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-0-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-0-address-postcode' => "TA3 7HF",
            'lpa-document-replacementAttorneys-1-name-title' => "Mr",
            'lpa-document-replacementAttorneys-1-name-first' => "Ewan",
            'lpa-document-replacementAttorneys-1-name-last' => "Adams",
            'lpa-document-replacementAttorneys-1-dob-date-day' => "12",
            'lpa-document-replacementAttorneys-1-dob-date-month' => "03",
            'lpa-document-replacementAttorneys-1-dob-date-year' => "1972",
            'lpa-document-replacementAttorneys-1-address-address1' => "2 Westview",
            'lpa-document-replacementAttorneys-1-address-address2' => "Staplehay",
            'lpa-document-replacementAttorneys-1-address-address3' => "Trull, Taunton, Somerset",
            'lpa-document-replacementAttorneys-1-address-postcode' => "TA3 7HF",
            'signature-attorney-0-name-title' => "Mrs",
            'signature-attorney-0-name-first' => "Amy",
            'signature-attorney-0-name-last' => "Wheeler",
            'signature-attorney-1-name-title' => "Mr",
            'signature-attorney-1-name-first' => "David",
            'signature-attorney-1-name-last' => "Wheeler",
            'signature-attorney-2-name-title' => "Dr",
            'signature-attorney-2-name-first' => "Wellington",
            'signature-attorney-2-name-last' => "Gastri",
            'signature-attorney-3-name-title' => "Dr",
            'signature-attorney-3-name-first' => "Henry",
            'signature-attorney-3-name-last' => "Taylor",
            'footer-instrument-right' => "LP1H Health and welfare (07.15)",
            'footer-registration-right' => "LP1H Register your LPA (07.15)",
            'has-more-preferences' => "On",
            'has-more-instructions' => "On",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1h.pdf');
    }
}
