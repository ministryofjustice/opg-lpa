<?php

namespace OpgTest\Lpa\Pdf;

use MakeShared\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Lp1f;
use Exception;

class Lp1fTest extends AbstractPdfTestCase
{
    public function testConstructorThrowsExceptionNotEnoughData()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate Opg\Lpa\Pdf\Lp1f');

        new Lp1f(new Lpa());
    }

    public function testGenerate()
    {
        $lpa = $this->getLpa();

        $pdf = new Lp1f($lpa, [], $this->factory);

        //  Set up the expected data for verification
        $templateFileName = 'LP1F.pdf';

        $strikeThroughs = [
            17 => [
                'correspondent-empty-name-address',
            ],
        ];

        $blankTargets = [];

        $coversheetFileName = 'LP1F_CoversheetRegistration2025fee.pdf';

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath($coversheetFileName),
            ],
            15 => [
                [
                    'templateFileName' => 'LP1F.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Elliot",
                        'signature-attorney-3-name-last' => "Sanders",
                        'footer-instrument-right' => "LP1F Property and financial affairs (07.15)",
                        'footer-registration-right' => "LP1F Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1F.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Isobel",
                        'signature-attorney-3-name-last' => "Ward",
                        'footer-instrument-right' => "LP1F Property and financial affairs (07.15)",
                        'footer-registration-right' => "LP1F Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1F.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Mr",
                        'signature-attorney-3-name-first' => "Ewan",
                        'signature-attorney-3-name-last' => "Adams",
                        'footer-instrument-right' => "LP1F Property and financial affairs (07.15)",
                        'footer-registration-right' => "LP1F Register your LPA (07.15)",
                    ],
                ],
                [
                    'templateFileName' => 'LP1F.pdf',
                    'data' => [
                        'signature-attorney-3-name-title' => "Ms",
                        'signature-attorney-3-name-first' => "Erica",
                        'signature-attorney-3-name-last' => "Schmidt",
                        'footer-instrument-right' => "LP1F Property and financial affairs (07.15)",
                        'footer-registration-right' => "LP1F Register your LPA (07.15)",
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
                [
                    'templateFileName' => 'LPC_Continuation_Sheet_4.pdf',
                    'constituentPdfs' => [
                        'start' => [
                            $this->getFullTemplatePath('blank.pdf'),
                        ],
                    ],
                    'data' => [
                        'cs4-trust-corporation-company-registration-number' => "678437685",
                        'cs4-footer-right' => "LPC Continuation sheet 4 (07.15)",
                    ],
                ],
                $this->getFullTemplatePath('blank.pdf'),
            ],
            17 => [
                [
                    'templateFileName' => 'LP1F.pdf',
                    'strikeThroughTargets' => [
                        16 => [
                            'applicant-2-pf',
                            'applicant-3-pf',
                        ],
                    ],
                    'data' => [
                        'applicant-0-name-last' => "Standard Trust",
                        'applicant-1-name-title' => "Mr",
                        'applicant-1-name-first' => "Elliot",
                        'applicant-1-name-last' => "Sanders",
                        'applicant-1-dob-date-day' => "10",
                        'applicant-1-dob-date-month' => "10",
                        'applicant-1-dob-date-year' => "1987",
                        'who-is-applicant' => "attorney",
                    ],
                ],
            ],
            'end' => [
                [
                    'templateFileName' => 'LP1F.pdf',
                    'blankTargets' => [
                        19 => [
                            'applicant-signature-2-pf',
                            'applicant-signature-3-pf',
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
            'attorney-0-is-trust-corporation' => "On",
            'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
            'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
            'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
            'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
            'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
            'lpa-document-primaryAttorneys-0-email-address' => "\nopglpademo+trustcorp@gmail.com",
            'lpa-document-primaryAttorneys-1-name-title' => "Mrs",
            'lpa-document-primaryAttorneys-1-name-first' => "Amy",
            'lpa-document-primaryAttorneys-1-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-1-dob-date-day' => "10",
            'lpa-document-primaryAttorneys-1-dob-date-month' => "05",
            'lpa-document-primaryAttorneys-1-dob-date-year' => "1975",
            'lpa-document-primaryAttorneys-1-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-1-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-1-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-1-email-address' => "\nopglpademo+AmyWheeler@gmail.com",
            'lpa-document-primaryAttorneys-2-name-title' => "Mr",
            'lpa-document-primaryAttorneys-2-name-first' => "David",
            'lpa-document-primaryAttorneys-2-name-last' => "Wheeler",
            'lpa-document-primaryAttorneys-2-dob-date-day' => "12",
            'lpa-document-primaryAttorneys-2-dob-date-month' => "03",
            'lpa-document-primaryAttorneys-2-dob-date-year' => "1972",
            'lpa-document-primaryAttorneys-2-address-address1' => "Brickhill Cottage",
            'lpa-document-primaryAttorneys-2-address-address2' => "Birch Cross",
            'lpa-document-primaryAttorneys-2-address-address3' => "Marchington, Uttoxeter, Staffordshire",
            'lpa-document-primaryAttorneys-2-address-postcode' => "ST14 8NX",
            'lpa-document-primaryAttorneys-2-email-address' => "\nopglpademo+DavidWheeler@gmail.com",
            'lpa-document-primaryAttorneys-3-name-title' => "Dr",
            'lpa-document-primaryAttorneys-3-name-first' => "Wellington",
            'lpa-document-primaryAttorneys-3-name-last' => "Gastri",
            'lpa-document-primaryAttorneys-3-dob-date-day' => "02",
            'lpa-document-primaryAttorneys-3-dob-date-month' => "09",
            'lpa-document-primaryAttorneys-3-dob-date-year' => "1982",
            'lpa-document-primaryAttorneys-3-address-address1' => "Severington Lane",
            'lpa-document-primaryAttorneys-3-address-address2' => "Kingston",
            'lpa-document-primaryAttorneys-3-address-address3' => "Burlingtop, Hertfordshire",
            'lpa-document-primaryAttorneys-3-address-postcode' => "PL1 9NE",
            'lpa-document-primaryAttorneys-3-email-address' => "\nopglpademo+WellingtonGastri@gmail.com",
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
            'when-attorneys-may-make-decisions' => "when-donor-lost-mental-capacity",
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
            'footer-instrument-right' => "LP1F Property and financial affairs (07.15)",
            'footer-registration-right' => "LP1F Register your LPA (07.15)",
            'lpa-a-reference-number' => $this->formattedLpaRef,
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $blankTargets, $constituentPdfs, $data, $pageShift, $this->formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'Lp1f.pdf');

        $this->visualDiffCheck($pdf, 'tests/visualdiffpdfs/1762447614.1819-A510-7295-5715-Lp1f.pdf');
    }
}
