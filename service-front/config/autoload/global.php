<?php

$DYNAMO_DB_CONFIG = [
    'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
    'version' => '2012-08-10',
    'region' => getenv('AWS_REGION') ?: 'eu-west-1',
];

return [

    'version' => [
        'cache' => (getenv('OPG_DOCKER_TAG') !== false) ? abs(crc32(getenv('OPG_DOCKER_TAG'))) : time(),
        'tag' => getenv('OPG_DOCKER_TAG'),
    ],

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    'terms' => [
        // The date and time the terms were last updated.
        // Users who have not logged in since this date will see the 'T&Cs updated' page.
        'lastUpdated' => '2015-02-17 14:00 UTC',
    ],

    'redirects' => [
        'index' => 'https://www.gov.uk/power-of-attorney/make-lasting-power',
        'logout' => 'https://www.gov.uk/done/lasting-power-of-attorney',
        // Once feedback form is live, this will become:
        //'logout' => getenv('FRONT_DOMAIN').'/completed-feedback',
    ],

    // dynamodb config required to get the system message
    'admin' => [
        'dynamodb' => [
            'client' => $DYNAMO_DB_CONFIG,
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: 'lpa-properties-shared',
            ],
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],
    ],

    'session' => [
        // ini session.* settings...
        'native_settings' => [
            // The cookie name used in the session
            'name' => 'lpa2',

            // Only allow the cookie to be sent over https, if we're using HTTPS.
            'cookie_secure' => true,

            // Prevent cookie from being accessed from JavaScript
            'cookie_httponly' => true,

            // The probability of GC running is gc_probability/gc_divisor
            'gc_probability' => 0,
        ],
    ], // session

    'redis' => [
        'url' => getenv('OPG_LPA_COMMON_REDIS_CACHE_URL'),

        // TTL for Redis keys in milliseconds
        'ttlMs' => (1000 * 60 * 60 * 3), // 3 hours,

        // config for calls to ordnance survey in PingController
        'ordnance_survey' => [
            // Rate limit status check calls to OS
            'max_call_per_min' => 2, // once every 30 secs
        ],
    ],

    'csrf' => [
        // Salt used for generating csrf tokens
        'salt' => getenv('OPG_LPA_FRONT_CSRF_SALT') ?: null,
    ],


    'api_client' => [
        'api_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: null,
    ],

    'email' => [
        // should reference a key within this array which provides
        // implementation-specific configuration
        'notify' => [
            'key' => getenv('OPG_LPA_FRONT_EMAIL_NOTIFY_API_KEY') ?: null,
            'smokeTestEmailAddress' => 'simulate-delivered@notifications.service.gov.uk',
        ],

        'sender' => [
                'default' => [
                        'name' => 'Office of the Public Guardian',
                        'address' => 'opg@lastingpowerofattorney.service.gov.uk',
                ],

                'feedback' => [
                        'name' => 'User Feedback',
                        'address' => 'opg@lastingpowerofattorney.service.gov.uk',
                ],
        ], // opg email sender
    ], // email


    'address' => [

        'ordnancesurvey' => [
            'key' => getenv('OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY') ?: null,
            'endpoint' => getenv('OPG_LPA_OS_PLACES_HUB_ENDPOINT') ?: null,
        ],

    ], // address

    'alphagov' => [
        'pay' => [
            'key' => getenv('OPG_LPA_FRONT_GOV_PAY_KEY') ?: null,
            'url' => getenv('OPG_LPA_FRONT_GOV_PAY_URL') ?: null,
        ],
    ],

    'log' => [
        'path' => getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-front2/application.log',
        'sentry-uri' => getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: null,
    ], // log

    'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gov.uk',

    'processing-status' => [
        'track-from-date' => getenv('OPG_LPA_FRONT_TRACK_FROM_DATE') ?: '2019-04-01',

        // Number of working days after an LPA is processed before we expect it
        // to be received by the user
        'expected-working-days-before-receipt' => 15,
    ],

    'telemetry' => [
        // fraction of requests which will be sampled, e.g. 0.05
        'requestsSampledFraction' => getenv('OPG_LPA_TELEMETRY_REQUESTS_SAMPLED_FRACTION') ?: null,

        'exporter' => [
            'serviceName' => 'service-front',

            // if this value is null, a console exporter will be used;
            // for a standard XRay (over UDP) exporter, use host='localhost' and port=2000
            'host' => getenv('OPG_LPA_TELEMETRY_HOST') ?: null,
            'port' => getenv('OPG_LPA_TELEMETRY_PORT') ?: null,
        ],
    ],

    'twig' => [
        'cache_dir' => getenv('OPG_LPA_FRONT_TWIG_CACHE_DIR') ?: '/tmp/twig_cache',
    ],

    'zfctwig' => [
        'environment_options' => ['cache' => getenv('OPG_LPA_FRONT_TWIG_CACHE_DIR') ?: '/tmp/twig_cache'],
        'extensions' => [
            Application\View\Twig\AppFiltersExtension::class,
            Application\View\Twig\AppFunctionsExtension::class,
        ],
    ],

    'accordion' => [
        'bars' => [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/life-sustaining',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ],
    ],
];
