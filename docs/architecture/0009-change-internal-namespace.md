# 0009. Change DNS for internal service discovery for better support

Date: 31/03/2022

## Status

Accepted (19/04/2022)

## Context

Currently it is hard to filter the `-internal` suffix for service discovery namespaces in Route 53 Resolver DNS firewall. the Domain name used does not have it's own "parts", and presents issues when you have multiple ephemeral environments in the AWS dev account, when setting up rules.

For details of the route 53 Reolver DNS Firewall see: [https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/resolver-dns-firewall.html](https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/resolver-dns-firewall.html)

For details of the service discovery private namespaces see: [https://docs.aws.amazon.com/AmazonECS/latest/developerguide/service-discovery.html](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/service-discovery.html)

## Proposal

In order to do proper filtering on the DNS Firewall, we need to change the DNS entries used for Service discovery, so that it is easier to support.

We propose that this is changed from the current format of

``` bash
api.${environment_name}-internal
```

This will now be in the format:

``` regex
({ephemeralNameInDevOnly}\.)?{accountName}.opg.{serviceName}.{instanceName}.{serviceType}.internal
```

e.g:

``` bash
production.opg.lpa.api.ecs.internal #production
400abcd1203.development.opg.lpa.api.ecs.internal #dev only
```

This should give better tracking of where things are within the stack, and allow us to use this for things like DNS egress filtering in a more granular fashion.

Component parts of domain are as follows:

- `({ephemeralNameInDevOnly}\.)?` is optional, refers to ephemeral environment name as defined by terraform e.g. `PR1234`.
  - This prefixing is only present if `${accountName}` = `development`.
- `${accountName}` refers to the designated account e.g. `production`, `preproduction` or `development`.
- `${serviceName}` refers to the service name e.g. `lpa`.
- `${instanceName}` refers to the instance name inside of the service e.g. `api`.
- `${serviceType}` refers to the AWS service type e.g. `ecs`.

this means we can filter on all allowed `{accountName}.opg.{serviceName}.{instanceName}.{serviceType}.internal` egress on DNS firewall, and has the benefit of making the namespace more canonical, as it represents entries used for internal only use, also identifying which AWS account alias it comes from.

## Decision

- To use this in development account initially, and run until proven ok.
- To follow up with this in non development accounts when we have availability to have downtime, as this change will require the API ECS Service to be recreated.

This Decision has been reviewed and adjusted based on  consultation with the webops Community Of Practice members at OPG.

To document in OPG Technical Guidance in this [ADR](https://docs.opg.service.justice.gov.uk/documentation/adrs/adr-002.html#adr-002-application-domain-names) accordingly with final decision.

API's:

- That use domains e.g. service discovery in AWS
- that are not exposed to other external services i.e. internal only

Should use `({ephemeralNameInDevOnly}\.)?{accountName}.opg.{serviceName}.{instanceName}.{serviceType}.internal` suffix in the domain name.

## Consequences

We will be able to filter on anything that ends `{accountName}.opg.{serviceName}.{instanceName}.{serviceType}.internal.` in dns firewall rules.

It will require some down time in non development accounts/environment for our existing set up. This will be mitigated by performing the change in a maintenance window.
