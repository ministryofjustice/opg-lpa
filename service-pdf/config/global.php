<?php

return [

    'service' => [
        'assets' => [
            'source_template_path' => __DIR__ . '/../assets/v2',
            'template_path_on_ram_disk' => '/tmp/pdf_cache/assets/v2',
            'intermediate_file_path' => '/tmp/pdf_cache'
        ],
    ],

    'worker' => [
        'testResponse' => [
            'path' => __DIR__ . '/../test-data/output/',
        ],
        's3Response' => [
            'client' => [
                'version' => '2006-03-01',
                'region' => 'eu-west-1',
            ],
            'settings' => [
                'ACL' => 'private',
                'Bucket' => getenv('OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET') ?: null,
            ],
        ],
    ],

    'log' => [
        'path' => getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-pdf2/application.log',
        'sentry-uri' => getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: '',
    ],

    'pdf' => [
        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'queue'     => getenv('OPG_LPA_PDF_ENCRYPTION_KEY_QUEUE') ?: null,
                'document'  => getenv('OPG_LPA_PDF_ENCRYPTION_KEY_DOCUMENT') ?: null,
            ],
            'options' => [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ],
        ],
        'password' => getenv('OPG_LPA_PDF_OWNER_PASSWORD') ?: 'default-password',
        'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
    ],

    'strike_throughs' => [
        'primaryAttorney-1-hw' =>               ['bx' => 313, 'by' => 243, 'tx' => 550, 'ty' => 546],
        'primaryAttorney-1-pf' =>               ['bx' => 313, 'by' => 178, 'tx' => 550, 'ty' => 471],
        'primaryAttorney-2' =>                  ['bx' => 45,  'by' => 375, 'tx' => 282, 'ty' => 679],
        'primaryAttorney-3' =>                  ['bx' => 313, 'by' => 375, 'tx' => 550, 'ty' => 679],
        'replacementAttorney-0-hw' =>           ['bx' => 45,  'by' => 317, 'tx' => 283, 'ty' => 538],
        'replacementAttorney-1-hw' =>           ['bx' => 313, 'by' => 317, 'tx' => 551, 'ty' => 538],
        'replacementAttorney-0-pf' =>           ['bx' => 45,  'by' => 308, 'tx' => 283, 'ty' => 530],
        'replacementAttorney-1-pf' =>           ['bx' => 313, 'by' => 308, 'tx' => 551, 'ty' => 530],
        'life-sustain-A' =>                     ['bx' => 44,  'by' => 265, 'tx' => 283, 'ty' => 478],
        'life-sustain-B' =>                     ['bx' => 307, 'by' => 265, 'tx' => 550, 'ty' => 478],
        'people-to-notify-0' =>                 ['bx' => 44,  'by' => 335, 'tx' => 283, 'ty' => 501],
        'people-to-notify-1' =>                 ['bx' => 312, 'by' => 335, 'tx' => 552, 'ty' => 501],
        'people-to-notify-2' =>                 ['bx' => 44,  'by' => 127, 'tx' => 283, 'ty' => 294],
        'people-to-notify-3' =>                 ['bx' => 312, 'by' => 127, 'tx' => 552, 'ty' => 294],
        'preference' =>                         ['bx' => 41,  'by' => 423, 'tx' => 554, 'ty' => 532],
        'instruction' =>                        ['bx' => 41,  'by' => 122, 'tx' => 554, 'ty' => 231],
        'attorney-signature-hw' =>              ['bx' => 42,  'by' => 143, 'tx' => 553, 'ty' => 317],
        'attorney-signature-pf' =>              ['bx' => 42,  'by' => 131, 'tx' => 553, 'ty' => 306],
        'applicant-0-hw' =>                     ['bx' => 42,  'by' => 315, 'tx' => 283, 'ty' => 413],
        'applicant-1-hw' =>                     ['bx' => 308, 'by' => 315, 'tx' => 549, 'ty' => 413],
        'applicant-2-hw' =>                     ['bx' => 42,  'by' => 147, 'tx' => 283, 'ty' => 245],
        'applicant-3-hw' =>                     ['bx' => 308, 'by' => 147, 'tx' => 549, 'ty' => 245],
        'applicant-0-pf' =>                     ['bx' => 42,  'by' => 319, 'tx' => 283, 'ty' => 417],
        'applicant-1-pf' =>                     ['bx' => 308, 'by' => 319, 'tx' => 549, 'ty' => 417],
        'applicant-2-pf' =>                     ['bx' => 42,  'by' => 155, 'tx' => 283, 'ty' => 253],
        'applicant-3-pf' =>                     ['bx' => 308, 'by' => 155, 'tx' => 549, 'ty' => 253],
        'applicant-signature-1' =>              ['bx' => 308, 'by' => 395, 'tx' => 549, 'ty' => 493],
        'applicant-signature-2' =>              ['bx' => 42,  'by' => 262, 'tx' => 283, 'ty' => 360],
        'applicant-signature-3' =>              ['bx' => 308, 'by' => 262, 'tx' => 549, 'ty' => 360],
        'additional-applicant-1-hw' =>          ['bx' => 308, 'by' => 315, 'tx' => 549, 'ty' => 413],
        'additional-applicant-2-hw' =>          ['bx' => 42,  'by' => 147, 'tx' => 283, 'ty' => 245],
        'additional-applicant-3-hw' =>          ['bx' => 308, 'by' => 147, 'tx' => 549, 'ty' => 245],
        'additional-applicant-1-pf' =>          ['bx' => 308, 'by' => 319, 'tx' => 549, 'ty' => 417],
        'additional-applicant-2-pf' =>          ['bx' => 42,  'by' => 155, 'tx' => 283, 'ty' => 253],
        'additional-applicant-3-pf' =>          ['bx' => 308, 'by' => 155, 'tx' => 549, 'ty' => 253],
        'correspondent-empty-address' =>        ['bx' => 42,  'by' => 362, 'tx' => 284, 'ty' => 433],
        'correspondent-empty-name-address' =>   ['bx' => 42,  'by' => 362, 'tx' => 413, 'ty' => 565],
        'cs1' =>                                ['bx' => 313, 'by' => 262, 'tx' => 558, 'ty' => 645],
        'lp3-primaryAttorney-1' =>              ['bx' => 312, 'by' => 458, 'tx' => 552, 'ty' => 602],
        'lp3-primaryAttorney-2' =>              ['bx' => 43,  'by' => 242, 'tx' => 283, 'ty' => 386],
        'lp3-primaryAttorney-3' =>              ['bx' => 312, 'by' => 242, 'tx' => 552, 'ty' => 386]
    ],

    'blanks' => [
        'applicant-signature-1-hw' => ['x1' => 297, 'y1' => 500, 'x2' => 560, 'y2' => 371],
        'applicant-signature-2-hw' => ['x1' => 31,  'y1' => 370, 'x2' => 295, 'y2' => 241],
        'applicant-signature-3-hw' => ['x1' => 297, 'y1' => 370, 'x2' => 560, 'y2' => 241],
        'applicant-signature-1-pf' => ['x1' => 297, 'y1' => 511, 'x2' => 560, 'y2' => 382],
        'applicant-signature-2-pf' => ['x1' => 31,  'y1' => 381, 'x2' => 295, 'y2' => 252],
        'applicant-signature-3-pf' => ['x1' => 297, 'y1' => 381, 'x2' => 560, 'y2' => 252]
    ],

    'footer' => [
        'lp1f'       => 'LP1F Property and financial affairs (07.15)',
        'lp1f-draft' => 'LP1F Register your LPA (07.15)',
        'lp1h'       => 'LP1H Health and welfare (07.15)',
        'lp1h-draft' => 'LP1H Register your LPA (07.15)',
        'lp3'        => 'LP3 People to notify (07.15)',
        'cs1'        => 'LPC Continuation sheet 1 (07.15)',
        'cs2'        => 'LPC Continuation sheet 2 (07.15)',
        'cs3'        => 'LPC Continuation sheet 3 (07.15)',
        'cs4'        => 'LPC Continuation sheet 4 (07.15)',
    ],

];
