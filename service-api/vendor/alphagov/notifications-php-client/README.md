# GOV.UK Notify PHP client

This documentation is for developers interested in using this PHP client to integrate their government service with GOV.UK Notify.

## Table of Contents

* [Installation](#installation)
* [Getting started](#getting-started)
* [Send messages](#send-messages)
* [Get the status of one message](#get-the-status-of-one-message)
* [Get the status of all messages](#get-the-status-of-all-messages)
* [Get a template by ID](#get-a-template-by-id)
* [Get a template by ID and version](#get-a-template-by-id-and-version)
* [Get all templates](#get-all-templates)
* [Generate a preview template](#generate-a-preview-template)
* [Get received text messages](#get-received-text-messages)
* [Development](#development)
* [License](#license)

## Installation

The Notify PHP Client can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require php-http/guzzle6-adapter alphagov/notifications-php-client
```

### PSR-7 HTTP

The Notify PHP Client is based on a PSR-7 HTTP model. You therefore need to pick your preferred HTTP Client library to use.

We will show examples here using the Guzzle v6 Adapter.

Setup instructions are also available for [Curl](docs/curl-client-setup.md) and [Guzzle v5](docs/guzzle5-client-setup.md).

## Getting started

Assuming you’ve installed the package via Composer, the Notify PHP Client will be available via the autoloader.

Create a (Guzzle v6 based) instance of the Client using:

```php
$notifyClient = new \Alphagov\Notifications\Client([
    'apiKey' => '{your api key}',
    'httpClient' => new \Http\Adapter\Guzzle6\Client
]);
```

Generate an API key by logging in to [GOV.UK Notify](https://www.notifications.service.gov.uk) and going to the **API integration** page.

## Send messages

### Text message

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

The method signature is:
```php
sendSms( $phoneNumber, $templateId, array $personalisation = array(), $reference = '', $smsSenderId = NULL  )
```

An example request would look like:

```php
try {

    $response = $notifyClient->sendSms(
        '+447777111222',
        'df10a23e-2c6d-4ea5-87fb-82e520cbf93a', [
            'name' => 'Betty Smith',
            'dob'  => '12 July 1968'
        ],
        'unique_ref123',
        '862bfaaf-9f89-43dd-aafa-2868ce2926a9'
    );

} catch (NotifyException $e){}
```

</details>

#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "id" => "bfb50d92-100d-4b8b-b559-14fa3b091cda",
    "reference" => None,
    "content" => [
        "body" => "Some words",
        "from_number" => "40604"
    ],
    "uri" => "https =>//api.notifications.service.gov.uk/v2/notifications/ceb50d92-100d-4b8b-b559-14fa3b091cd",
    "template" => [
        "id" => "ceb50d92-100d-4b8b-b559-14fa3b091cda",
       "version" => 1,
       "uri" => "https://api.notifications.service.gov.uk/v2/templates/bfb50d92-100d-4b8b-b559-14fa3b091cda"
    ]
]
```
Otherwise the client will raise a ``Alphagov\Notifications\Exception\NotifyException``:

|`exc->getCode()`|`exc->getErrors()`|
|:---|:---|
|`429`|`[{`<br>`"error": "RateLimitError",`<br>`"message": "Exceeded rate limit for key type TEAM of 10 requests per 10 seconds"`<br>`}]`|
|`429`|`[{`<br>`"error": "TooManyRequestsError",`<br>`"message": "Exceeded send limits (50) for today"`<br>`}]`|
|`400`|`[{`<br>`"error": "BadRequestError",`<br>`"message": "Can"t send to this recipient using a team-only API key"`<br>`]}`|
|`400`|`[{`<br>`"error": "BadRequestError",`<br>`"message": "Can"t send to this recipient when service is in trial mode - see https://www.notifications.service.gov.uk/trial-mode"`<br>`}]`|

</details>

#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `$phoneNumber`
The mobile number the SMS notification is sent to.

##### `$templateId`

Find by clicking **API info** for the template you want to send.

##### `$reference`
An optional identifier you generate if you don’t want to use Notify’s `id`. It can be used to identify a single  notification or a batch of notifications.

##### `$personalisation`

If a template has placeholders, you need to provide their values, for example:

```php
personalisation = [
    'name' => 'Betty Smith',
    'dob'  => '12 July 1968'
]
```

Otherwise the parameter can be omitted.

##### `smsSenderId`

Optional. Specifies the identifier of the sms sender to set for the notification. The identifiers are found in your service Settings, when you 'Manage' your 'Text message sender'.

If you omit this argument your default sms sender will be set for the notification.

</details>


### Email

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

The method signature is:
```php
sendEmail( $emailAddress, $templateId, array $personalisation = array(), $reference = '', $emailReplyToId = NULL )
```

An example request would look like:

```php
try {

    $response = $notifyClient->sendEmail(
        'betty@example.com',
        'df10a23e-2c0d-4ea5-87fb-82e520cbf93c', [
            'name' => 'Betty Smith',
            'dob'  => '12 July 1968'
        ],
        'unique_ref123',
        '862bfaaf-9f89-43dd-aafa-2868ce2926a9'
        );

} catch (NotifyException $e){}
```

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "id" => "bfb50d92-100d-4b8b-b559-14fa3b091cda",
    "reference" => None,
    "content" => [
        "subject" => "Licence renewal",
        "body" => "Dear Bill, your licence is due for renewal on 3 January 2016.",
        "from_email" => "the_service@gov.uk"
    ],
    "uri" => "https://api.notifications.service.gov.uk/v2/notifications/ceb50d92-100d-4b8b-b559-14fa3b091cd",
    "template" => [
        "id" => "ceb50d92-100d-4b8b-b559-14fa3b091cda",
        "version" => 1,
        "uri" => "https://api.notificaitons.service.gov.uk/service/your_service_id/templates/bfb50d92-100d-4b8b-b559-14fa3b091cda"
    ]
]
```

Otherwise the client will raise a ``Alphagov\Notifications\Exception\NotifyException``:

|`exc->getCode()`|`exc->getErrors()`|
|:---|:---|
|`429`|`[{`<br>`"error": "RateLimitError",`<br>`"message": "Exceeded rate limit for key type TEAM of 10 requests per 10 seconds"`<br>`}]`|
|`429`|`[{`<br>`"error": "TooManyRequestsError",`<br>`"message": "Exceeded send limits (50) for today"`<br>`}]`|
|`400`|`[{`<br>`"error": "BadRequestError",`<br>`"message": "Can"t send to this recipient using a team-only API key"`<br>`]}`|
|`400`|`[{`<br>`"error": "BadRequestError",`<br>`"message": "Can"t send to this recipient when service is in trial mode - see https://www.notifications.service.gov.uk/trial-mode"`<br>`}]`|


</details>


#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `$emailAddress`
The email address the email notification is sent to.

##### `$templateId`

Find by clicking **API info** for the template you want to send.

##### `$personalisation`

If a template has placeholders you need to provide their values. For example:

```php
personalisation = [
    'name' => 'Betty Smith',
    'dob'  => '12 July 1968'
]
```

Otherwise the parameter can be omitted.

##### `$reference`

An optional identifier you generate if you don’t want to use Notify’s `id`. It can be used to identify a single  notification or a batch of notifications.

##### `$emailReplyToId`

Optional. Specifies the identifier of the email reply-to address to set for the notification. The identifiers are found in your service Settings, when you 'Manage' your 'Email reply to addresses'.

If you omit this argument your default email reply-to address will be set for the notification.

</details>


### Letter

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

The method signature is:
```php
sendLetter( $templateId, array $personalisation = array(), $reference = '' )
```

An example request would look like:

```php
try {

    $response = $notifyClient->sendEmail(
        'df10a23e-2c0d-4ea5-87fb-82e520cbf93c',
        [
            'name'=>'Fred',
            'address_line_1' => 'Foo',
            'address_line_2' => 'Bar',
            'postcode' => 'Baz'
        ],
        'unique_ref123'
    );

} catch (NotifyException $e){}
```

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "id" => "bfb50d92-100d-4b8b-b559-14fa3b091cda",
    "reference" => "unique_ref123",
    "content" => [
        "subject" => "Licence renewal",
        "body" => "Dear Bill, your licence is due for renewal on 3 January 2016.",
    ],
    "uri" => "https://api.notifications.service.gov.uk/v2/notifications/ceb50d92-100d-4b8b-b559-14fa3b091cd",
    "template" => [
        "id" => "ceb50d92-100d-4b8b-b559-14fa3b091cda",
        "version" => 1,
        "uri" => "https://api.notificaitons.service.gov.uk/service/your_service_id/templates/bfb50d92-100d-4b8b-b559-14fa3b091cda"
    ],
    "scheduled_for" => null
]
```

Otherwise the client will raise a ``Alphagov\Notifications\Exception\NotifyException``:

|`exc->getCode()`|`exc->getErrors()`|
|:---|:---|
|`429`|`[{`<br>`"error": "RateLimitError",`<br>`"message": "Exceeded rate limit for key type TEAM of 10 requests per 10 seconds"`<br>`}]`|
|`429`|`[{`<br>`"error": "TooManyRequestsError",`<br>`"message": "Exceeded send limits (50) for today"`<br>`}]`|
|`400`|`[{`<br>`"error": "BadRequestError",`<br>`"message": "Can"t send to this recipient using a team-only API key"`<br>`]}`|
|`400`|`[{`<br>`"error": "BadRequestError",`<br>`"message": "Can"t send to this recipient when service is in trial mode - see https://www.notifications.service.gov.uk/trial-mode"`<br>`}]`|

</details>


#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `templateId`

Find by clicking **API info** for the template you want to send.

##### `personalisation`

If a template has placeholders you need to provide their values. For example:

```php
personalisation = [
    'name' => 'Betty Smith',
    'dob'  => '12 July 1968'
]
```

Otherwise the parameter can be omitted.

##### `reference`

An optional identifier you generate if you don’t want to use Notify’s `id`. It can be used to identify a single  notification or a batch of notifications.


</details>


## Get the status of one message

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

The method signature is:
```php
getNotification( $notificationId )
```

An example request would look like:

```php
try {

    $response = $notifyClient->getNotification( 'c32e9c89-a423-42d2-85b7-a21cd4486a2a' );

} catch (NotifyException $e){}
```

</details>


#### Response

If the request is successful, `response` will be an `array `.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "id" => "notify_id",
    "body" => "Hello Foo",
    "subject" => "null|email_subject",
    "reference" => "client reference",
    "email_address" => "email address",
    "phone_number" => "phone number",
    "line_1" => "full name of a person or company",
    "line_2" => "123 The Street",
    "line_3" => "Some Area",
    "line_4" => "Some Town",
    "line_5" => "Some county",
    "line_6" => "Something else",
    "postcode" => "postcode",
    "type" => "sms|letter|email",
    "status" => "current status",
    "template" => [
        "version" => 1,
        "id" => 1,
        "uri" => "/template/{id}/{version}"
     ],
    "created_at" => "created at",
    "sent_at" => "sent to provider at",
]
```

Otherwise the client will raise a ``Alphagov\Notifications\Exception\NotifyException``:

|`error["status_code"]`|`error["message"]`|
|:---|:---|
|`404`|`[{`<br>`"error": "NoResultFound",`<br>`"message": "No result found"`<br>`}]`|
|`400`|`[{`<br>`"error": "ValidationError",`<br>`"message": "id is not a valid UUID"`<br>`}]`|

</details>

#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `$notificationId`

The ID of the notification.

</details>

## Get the status of all messages

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

The method signature is:
```php
listNotifications( array $filters = array() )
```

An example request would look like:

```php
    $response = $notifyClient->listNotifications([
        'older_than' => 'c32e9c89-a423-42d2-85b7-a21cd4486a2a',
        'reference' => 'weekly-reminders',
        'status' => 'delivered',
        'template_type' => 'sms'
    ]);
```

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "notifications" => [
            "id" => "notify_id",
            "reference" => "client reference",
            "email_address" => "email address",
            "phone_number" => "phone number",
            "line_1" => "full name of a person or company",
            "line_2" => "123 The Street",
            "line_3" => "Some Area",
            "line_4" => "Some Town",
            "line_5" => "Some county",
            "line_6" => "Something else",
            "postcode" => "postcode",
            "type" => "sms | letter | email",
            "status" => sending | delivered | permanent-failure | temporary-failure | technical-failure
            "template" => [
            "version" => 1,
            "id" => 1,
            "uri" => "/template/{id}/{version}"
        ],
        "created_at" => "created at",
        "sent_at" => "sent to provider at",
        ],
        …
  ],
  "links" => [
     "current" => "/notifications?template_type=sms&status=delivered",
     "next" => "/notifications?older_than=last_id_in_list&template_type=sms&status=delivered"
  ]
]
```

Otherwise the client will raise a ``Alphagov\Notifications\Exception\NotifyException``:

|`error["status_code"]`|`error["message"]`|
|:---|:---|
|`400`|`[{`<br>`"error": "ValidationError",`<br>`"message": "bad status is not one of [created, sending, delivered, pending, failed, technical-failure, temporary-failure, permanent-failure]"`<br>`}]`|
|`400`|`[{`<br>`"error": "Apple is not one of [sms, email, letter]"`<br>`}]`|

</details>

#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `older_than`

If omitted 250 of the most recent messages are returned. Otherwise the next 250  messages older than the given notification id are returned.

##### `template_type`

If omitted all messages are returned. Otherwise you can filter by:

* `email`
* `sms`
* `letter`

##### `status`

__email__

You can filter by:

* `sending` - the message is queued to be sent by the provider.
* `delivered` - the message was successfully delivered.
* `failed` - this will return all failure statuses `permanent-failure`, `temporary-failure` and `technical-failure`.
* `permanent-failure` - the provider was unable to deliver message, email does not exist; remove this recipient from your list.
* `temporary-failure` - the provider was unable to deliver message, email box was full; you can try to send the message again.
* `technical-failure` - Notify had a technical failure; you can try to send the message again.

You can omit this argument to ignore this filter.

__text message__

You can filter by:

* `sending` - the message is queued to be sent by the provider.
* `delivered` - the message was successfully delivered.
* `failed` - this will return all failure statuses `permanent-failure`, `temporary-failure` and `technical-failure`.
* `permanent-failure` - the provider was unable to deliver message, phone number does not exist; remove this recipient from your list.
* `temporary-failure` - the provider was unable to deliver message, the phone was turned off; you can try to send the message again.
* `technical-failure` - Notify had a technical failure; you can try to send the message again.

You can omit this argument to ignore this filter.

__letter__

You can filter by:

* `accepted` - Notify is in the process of printing and posting the letter
* `technical-failure` - Notify had an unexpected error while sending to our printing provider

You can omit this argument to ignore this filter.

##### `reference`

This is the `reference` you gave at the time of sending the notification. This can be omitted to ignore the filter.

</details>

## Get a template by ID

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

```php
    $response = $notifyClient->getTemplate( 'templateId' );
```

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>


```php
{
    "id" => "template_id",
    "type" => "sms|email|letter",
    "created_at" => "created at",
    "updated_at" => "updated at",
    "version" => "version",
    "created_by" => "someone@example.com",
    "body" => "body",
    "subject" => "null|email_subject"
}
```

|`error["status_code"]`|`error["errors"]`|
|:---|:---|
|`404`|`[{`<br>`"error" => "NoResultFound",`<br>`"message" => "No result found"`<br>`}]`|

</details>


#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `templateId`

Find by clicking **API info** for the template you want to send.

</details>

## Get a template by ID and version

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

```php
    $response = $notifyClient->getTemplateVersion( 'templateId', 1 );
```

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "id" => "template_id",
    "type" => "sms|email|letter",
    "created_at" => "created at",
    "updated_at" => "updated at",
    "version" => "version",
    "created_by" => "someone@example.com",
    "body" => "body",
    "subject" => "null|email_subject"
]
```

|`error["status_code"]`|`error["errors"]`|
|:---|:---|
|`404`|`[{`<br>`"error" => "NoResultFound",`<br>`"message" => "No result found"`<br>`}]`|

</details>


#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `templateId`

Find by clicking **API info** for the template you want to send.

##### `version`

The version number of the template

</details>

## Get all templates

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

```php
    $this->getAllTemplates(
      $template_type  // optional
    );
```
This will return the latest version for each template

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>

```php
[
    "templates"  => [
        [
            "id" => "template_id",
            "type" => "sms|email|letter",
            "created_at" => "created at",
            "updated_at" => "updated at",
            "version" => "version",
            "created_by" => "someone@example.com",
            "body" => "body",
            "subject" => "null|email_subject"
        ],
        [
            ... another template
        ]
    ]
]
```

If no templates exist for a template type or there no templates for a service, the `response` will be a Dictionary` with an empty `templates` list element:

```php
[
    "templates"  => []
]
```

</details>


#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `$templateType`

If omitted all messages are returned. Otherwise you can filter by:

* `email`
* `sms`
* `letter`

</details>


## Generate a preview template

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

```php
    $personalisation = [ "foo" => "bar" ];
    $this->previewTemplate( $templateId, $personalisation );
```

</details>


#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>


```php
[
    "id" => "notify_id",
    "type" => "sms|email|letter",
    "version" => "version",
    "body" => "Hello bar" // with substitution values,
    "subject" => "null|email_subject"
]
```

|`error["status_code"]`|`error["errors"]`|
|:---|:---|
|`400`|`[{`<br>`"error" => "BadRequestError",`<br>`"message" => "Missing personalisation => [name]"`<br>`}]`|
|`404`|`[{`<br>`"error" => "NoResultFound",`<br>`"message" => "No result found"`<br>`}]`|


</details>


#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `$templateId`

Find by clicking **API info** for the template you want to send.

##### `$personalisation`

If a template has placeholders you need to provide their values. For example:

```php
$personalisation = [
    'first_name' => 'Amala',
    'reference_number' => '300241',
];
```

Otherwise the parameter can be omitted or `null` can be passed in its place.

</details>

## Get received text messages

#### Method

<details>
<summary>
Click here to expand for more information.
</summary>

```php
    $this->listReceivedTexts(
      $older_than  // optional
    );
```

</details>

#### Response

If the request is successful, `response` will be an `array`.

<details>
<summary>
Click here to expand for more information.
</summary>


```php
[
    "received_text_messages" => [
        [
            "id" => "notify_id",
            "user_number" => "user number",
            "notify_number" => "notify number",
            "created_at" => "created at",
            "service_id" => "service id",
            "content" => "text content"
        ],
        [
            ... another received text message
        ]
    ]
  ],
  "links" => [
     "current" => "/received-text-messages",
     "next" => "/received-text-messages?older_than=last_id_in_list"
  ]
]
```

</details>

#### Arguments

<details>
<summary>
Click here to expand for more information.
</summary>

##### `$older_than`

If omitted 250 of the most recently received text messages are returned. Otherwise the next 250 received text messages older than the given id are returned.

</details>
