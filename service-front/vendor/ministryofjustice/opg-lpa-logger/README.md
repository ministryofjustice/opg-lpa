# Lasting Power of Attorney Logger

The lasting power of attorney Logger is a set of PHP classes that we use for application logging within our systems.

## Installation with Composer

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ministryofjustice/opg-lpa-logger"
        }
    ],
    "require": {
        "ministryofjustice/opg-lpa-logger": "^1.0.0",
    }
}
```

## Usage

```php
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

```

## Testing

    cd tests
    cp sentry.key.example sentry.key
    cp sns.credentials.example sns.credentials

Replace the contents of the files with valid values

    ../bin/phpunit

License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
