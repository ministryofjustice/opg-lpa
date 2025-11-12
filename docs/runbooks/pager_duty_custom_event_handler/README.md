# Setup custom event handlers

## Audience

This is to be used in advanced scenarios e.g. DR or during an incident, or to reduce noise. Guidance from webops engineers is recommended.

## Context

On rare occasions, for example when running DR, Pager Duty will need to be manually setup to interpret events coming from the DB Events SNS topic properly, as they are unusable in thier raw format. We cannot do this via terraform easily, so we will have to do the following:

- Step 1: Confirm event subscription manually on PagerDuty upon initial creation
- Step 2: Update the custom event transform code manually into the relevant integration.

Generally, this is a one off process, only used when setting up a new region. Note: each account has differing code to filter out noise, dev having the most noise. Production also has some filtering too, e.g. for backups.

However, follow step 2 any time we need to alter some of the snippets for example to improve filtering, refactor it etc. after initial creation.

## Prerequisites

- Access to PagerDuty for LPA team. if you do not have this, please ask operations engineering in the relevant channel. If in doubt ask a WebOps engineer to assist.

## Step 1. Initial setup of DB alerts

upon first setup i.e. `terraform apply` at `terraform/region` level to an empty region e.g. London:

1. Log in to Pager Duty
2. Within pagerduty ,and as soon as possible (less than a minute) under  `open incidents` locate the last alert named `Custom Event Transform`.
3. Click on its `title`.
4. In the alert there will be a link to click in the `rawBody` portion of the incident. *note sensitive details are marked redacted*:

    ```json
    {
    "Type" : "SubscriptionConfirmation",
    "MessageId" : "redacted",
    "Token" : "redacted",
    "TopicArn" : "arn:aws:sns:eu-west-2:redacted:preproduction-eu-west-2-rds-events",
    "Message" : "You have chosen to subscribe to the topic arn:aws:sns:eu-west-2:redacted:preproduction-eu-west-2-rds-events.\nTo confirm the subscription, visit the SubscribeURL included in this message.",
    "SubscribeURL" : "https://sns.eu-west-2.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:eu-west-2:redacted:preproduction-eu-west-2-rds-events&Token=redacted",
    "Timestamp" : "2022-04-26T10:36:59.239Z",
    "SignatureVersion" : "1",
    "Signature" : "redacted",
    "SigningCertURL" : "https://sns.eu-west-2.amazonaws.com/SimpleNotificationService-redacted.pem"
    }
    ```

5. Click on the link next to `SubscribeUrl`. This will open an xml page confirming subscription.
   1. If this doesn't get done in time i.e. more than 1 minute, the `terraform apply` in that region will fail with a timeout, and  reapplying the terraform will be required.

## Step 2: Update the custom event transform code

1. Locate the service in the service Directory:
   1. Production - Production Make a Lasting Power of Attorney Database Alerts.
   2. Dev or Preproduction - Non-Production Make a Lasting Power of Attorney Database Alerts.
2. Click on the integrations tab
3. Locate the region alerts of interest e.g. for dev in london its `development eu-west-2 Region DB Alerts`
4. Click on the cog icon
5. Locate the edit integration button.
6. In the `The code you want to execute` text area, replace the relevant js content, from the code snippet e.g. `development_custom_event.js` for dev. these snippets are also in the same folder as this ReadMe.
7. Click `Save changes`.
