# 0009. Change DNS for internal service discovery for better support

Date: 31/03/2022

## Status

Proposed (31/03/2022)

## Context

Currently it is hard to filter as the `-internal` suffix does not have it's own "part", and presents issues when you have multiple ephemeral environments in the AWS dev account.

## Proposal

in order to do proper filtering on the DNS Firewall, we need to change the DNS entries used for Service discovery, so that it is easier to support.

We propose that this is changed from the current format of

``` HCL

api.${environment_name}-internal
```

To

``` HCL
api.${environment_name}.internal
```

where `${environment_name}` refers to ephemeral or fixed environment as defined by terraform e.g. `PR1234` or `production`

this means we can filter on all allowed `.internal` egress on DNS firewall, and has the benefit of making the namespace more canonical, as it represents entries used for internal only use.

## Decision

To use this in development account initially, but to follow up with this in non development accounts when we have availability to have downtime, as this change will require the API ECS Service to be recreated.

To review this decision, and ensure alignment with other teams within OPG, and adjust accordingly.

API's:

- That use domains e.g. service discovery in AWS
- that are not exposed to other external services i.e. internal only

Should use `.internal` suffix in the domain name.

## Consequences

We will be able to filter on anything that ends `.internal` in firewall rules.

It will require some down time in non development accounts/environment for our existing set up. This will be mitigated by performing the change in a maintenance window.
