# Service Continuity for Make an LPA

A service for members of the public to make a Lasting Power of Attorney. Users fill out online forms, pay a fee and can download the completed forms as a PDF which they can then sign and post to the Office of the Public Guardian to be registered.

When the user creates an LPA in Make an LPA, details of the LPA are only stored in the Make an LPA Postgres database. These details are not entered into Sirius until the printed form is received by OPG and the LPA is entered into Sirius by a caseworker.

The service is developed and operated by OPG Digital; see [the overview in our technical docs](https://docs.opg.service.justice.gov.uk/documentation/support/lpa.html)


## Service Accountability (Accountable / Responsible)

A product team within OPG Digital owns the delivery and maintenance of the service. The multi-disciplinary team is responsible for the service. We follow an agile software development lifecycle as mandated by the GDS Service Standard. Make an LPA Online has passed its Live assessment stage as part of the Service Standard.

OPG Digital uses an embedded WebOps, which means infrastructure engineers are embedded within the team to support the product's pipelines and infrastructure, as well as developers who maintain the application code. OPG Digital follows a continuous delivery model, where we constantly deploy small chunks of change as soon as they are merged into the codebase. Comprehensive test automation ensures this is possible.

### Contacts
- [Service contact details](https://docs.opg.service.justice.gov.uk/documentation/support/lpa.html#service-team-contact)

### Notes
- The service will be replaced when the Modernised Digital LPA becomes publicly available.
- Since October 2025 a seperate squad has been created to address some technical debt and ensure product support until decommissioned.

## Service Scope

The product covers the web application available at [https://www.lastingpowerofattorney.service.gov.uk/home](https://www.lastingpowerofattorney.service.gov.uk/home) for making an LPA document.

Make an LPA provides a convenient digital way to make the paper form that is printed and sent to OPG. In failure scenarios users can fall back to the paper forms or downloaded fillable PDF forms from gov.uk.

The products source code is available as open source via GitHub [https://github.com/ministryofjustice/opg-lpa](https://github.com/ministryofjustice/opg-lpa)

See [https://docs.opg.service.justice.gov.uk/documentation/support/lpa.html](https://docs.opg.service.justice.gov.uk/documentation/support/lpa.html) for more information.


## Interfaces and Dependencies (Internal and External)

Make an LPA has a dependency on OPG Digital's internal Sirius service (via an API gateway) to return LPA status details to customers (this is a non-essential feature that fails gracefully).

### External Dependencies

| Dependency | Purpose |
| ---------- | ------- |
| Amazon Web Services (AWS) | Cloud hosting platform |
| GitHub | Source control and build system |
| Renovate | Automated dependency updates |
| Trivy | Security scanning of containers |
| Slack | Alerting |
| PagerDuty | Alerting |
| GOV.UK Pay | Payment handling |
| GOV.UK Notify | Email handling |
| Ordinance Survey Place API | Postcode to address lookup |
| Google Analytics | Site statistics |

### Software

- [Code dependency list (SBOM)](https://github.com/ministryofjustice/opg-lpa/network/dependencies)
- Application code built with `PHP >= 8.3`
- Docker containers based on Alpine Linux
    - [Admin area](https://github.com/ministryofjustice/opg-lpa/blob/main/service-admin/docker/)
    - [API](https://github.com/ministryofjustice/opg-lpa/blob/main/service-api/docker/)
    - [Front](https://github.com/ministryofjustice/opg-lpa/tree/main/service-front/docker)
    - [PDF service](https://github.com/ministryofjustice/opg-lpa/tree/main/service-pdf/docker/)


## Architecture (HLD/LLD)

Make is a multi-tier app split across several containers. Requests are handled from the Internet via an AWS Application load balancer. End user requests are then passed to `Front` which renders the UI and talks to the `API` layer for data access as well as external services. The `API` layer talks to the Aurora database via a connection pooling tool for increased stability. `PDF` generation, which can be slow, is handled via a queuing system with outputs temporarily stored in S3.

Digital team users (mostly product managers) have access to the admin tool, this is IP restricted to MOJ networks.

C4 model diagram containers and network overview:

<DIAGRAM>

**Note**: AWS Network firewall is currently being rolled out.

## Incident Response Plans (Call Tree, Including Roles and Responsibilities)

OPG Digital has a standard incident response process across all its teams; detailed in our [technical guidance](https://docs.opg.service.justice.gov.uk/documentation/incidents/process.html)

Incidents are handled by a product's team following the "you build it you run it" approach common in Agile delivery teams. Teams are expected to pause any feature work and help the incident team swarm on solutions to live issues.

The following roles are part of the process:

Any team member can call an incident using the incident management applications [automated Slack tooling](https://docs.opg.service.justice.gov.uk/documentation/incidents/process.html#declare-an-incident). The OPG incident tool records timelines and actions from the incident channel automatically and produces a report for reference purposes. They are given the reporter role by default.

[Incident leads](https://docs.opg.service.justice.gov.uk/documentation/incidents/process.html#incident-lead) are a rotating list of Technical Architects, Lead Webops and Senior Webops members of OPG Digital. Managed in pagerduty. They are looped in to coordinate responses, this can be done via pagerduty via the incident app or via Slack.

Developers and Webops from the team will be brought into the incident channel as the team to solve the issue.

Where communication with the wider OPG is needed, Product or Delivery managers from the product in question take up the [Communications Lead](https://docs.opg.service.justice.gov.uk/documentation/incidents/process.html#communications-lead) role and hook into wider OPG business continuity processes.

Where the incident in question is security-related wider MOJ security colleagues will be brought into the incident channel or contacted via Report a Cyber Security Incident Form.

Our incident tooling automatically logs actions from incident slack channels to compile reports - these are all accessible on a dedicated [incident website](https://incident.opg.service.justice.gov.uk/).

After an incident a Root Cause Analysis is run so that lessons learned can be picked up by the team and wider OPG Digital staff. These are stored in the [OPG Digital confluence space](https://opgtransform.atlassian.net/wiki/spaces/RCAS/overview).

**Note**: The incident website requires GitHub SSO to the MOJ organisation.


## IT Continuity Plans (Resilience)

## Disaster Recovery Plans (Procedures / Runbooks)

## Backup & Restore Plans (configuration and Testing)

## Supporting Information (Risk & Test Tracker Links)




