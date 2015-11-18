# OPG LPA Logger

## SNS Example

    $logger = new Logger();
    
    $logger->setSnsCredentials(
        'endpoints' => [
                Sns::SNS_MINOR => 'arn:aws:sns:eu-west-1:923426666275:EXAMPLE',
                Sns::SNS_MAJOR => 'arn:aws:sns:eu-west-1:923426666275:EXAMPLE',
        ],
        [
            'credentials' => [
                    'key' => '',
                    'secret' => '',
            ],
            'version' => '2010-03-31',
            'region' => 'eu-west-1',
        ]
    );
    
    $logger->alert($message1);
    $logger->err($message2);
    $logger->warn($message3);

## Testing

    cd tests
    cp sentry.key.example sentry.key
    cp sns.credentials.example sns.credentials

Replace the contents of the files with valid values

    ../bin/phpunit

