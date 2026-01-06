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

The product covers the web application available at [https://www.lastingpowerofattorney.service.gov.uk/](https://www.lastingpowerofattorney.service.gov.uk/)<sup>1</sup> for making an LPA document.

Make an LPA provides a convenient digital way to make the paper form that is printed and sent to OPG. In failure scenarios users can fall back to the paper forms or downloaded fillable PDF forms from gov.uk.

The products source code is available as open source via GitHub [https://github.com/ministryofjustice/opg-lpa](https://github.com/ministryofjustice/opg-lpa)

See [https://docs.opg.service.justice.gov.uk/documentation/support/lpa.html](https://docs.opg.service.justice.gov.uk/documentation/support/lpa.html) for more information.

**1**: This root page is operated by GDS.

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
- Application code built with `PHP >= 8.4`
- Docker containers based on `Alpine Linux >= 3.21`.
    - [Admin area](https://github.com/ministryofjustice/opg-lpa/blob/main/service-admin/docker/)
    - [API](https://github.com/ministryofjustice/opg-lpa/blob/main/service-api/docker/)
    - [Front](https://github.com/ministryofjustice/opg-lpa/tree/main/service-front/docker)
    - [PDF service](https://github.com/ministryofjustice/opg-lpa/tree/main/service-pdf/docker/)


## Architecture (HLD/LLD)

Make is a multi-tier app split across several containers. Requests are handled from the Internet via an AWS Application load balancer. End user requests are then passed to `Front` which renders the UI and talks to the `API` layer for data access as well as external services. The `API` layer talks to the Aurora database via a connection pooling tool for increased stability. `PDF` generation, which can be slow, is handled via a queuing system with outputs temporarily stored in S3.

Digital team users (mostly product managers) have access to the admin tool, which is limited to known authenticated users and IP restricted to MOJ networks.

C4 model diagram containers and network overview:

<img src="https://raw.githubusercontent.com/ministryofjustice/opg-lpa/refs/heads/main/docs/images/c4-container-level.svg">

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

Make An LPA is hosted in AWS. It has `development`, `pre-production` and `production` environments, each ring-fenced in its own AWS account to reduce blast radius of any incidents. Only production contains real user data, non prod environment access is limited to MOJ networks. Deployment is via automated promotion of releases through the `development` > `pre-production` > `production` pipeline. New work is tested on isolated ephemeral environments in `development` before merge to main.

The application tiers run on [AWS Elastic Container Service](https://aws.amazon.com/ecs/). This is designed to handle traffic spikes and containers will scale up or down based on system usage (between 2 and 20 containers for primary system elements). Containers are balanced across multiple availability zones within the `eu-west-1` region to provide resilience.

All application data is hosted in highly available services provided by AWS. The main data store is an [AWS Aurora](https://aws.amazon.com/rds/aurora/) Postgres cluster. [AWS Elasticache](https://aws.amazon.com/elasticache/) is used for session storage. System status messages are stored in [DynamoDB](https://aws.amazon.com/dynamodb/)  to be unaffected by Aurora issues.

[AWS Web Application Firewall](https://aws.amazon.com/waf/) is configured on the service to block known PHP issues, known bad inputs and common attacks (ie. CSRF, XSS and SQL injection attempts).

The service uses AWS application load balancers that only accept HTTPS/TLS connections, with their standard DDOS prevention. AWS Advanced Shield is also configured for additional DDOS protection and out of hours remediation.

Make An LPA has common massive traffic spikes triggered by being mentioned on popular consumer-related television programmes. This has tested its resilience on multiple occasions. Auto-scaling is configured to mitigate this.

All infrastructure is managed and provisioned by Terraform Infrastructure as Code (IAC) for reproducibility, environments differ only in service scaling and data content.

The service runs an integration and minimal load test during its deployment process.

The service has a [maintenance mode](https://github.com/ministryofjustice/opg-lpa/tree/main/docs/runbooks/maintenance_mode) to disable publich facing elements during prolonged issues.

## Disaster Recovery Plans (Procedures / Runbooks)

In the unlikely event that the main AWS region becomes unavailable, the Make An LPA application can be stood up in the `eu-west-2` region after some IAC configuration changes.

IAC model allows us to stand up a new version of the service in another AWS account with relative ease as long as backups as preserved.

### Recovery Time and Recovery Point Objectives

Currently TBC.

## Backup & Restore Plans (configuration and Testing)

Application data in the Aurora Postgres cluster is backed up nightly. Backups are stored and synced to the second region (`eu-west-2`). Backups are also stored as immutable objects via the AWS Backup service.

The team can manually restore from a backup database if the active database is deleted or becomes corrupted.

The Infrastructure as Code model allows us to stand up a new version of the service easily (this is the process we use for ephemeral development environments).

**Restore process was last tested fully on 25/11/2025.**

## Supporting Information (Risk & Test Tracker Links)

Make An LPA has a comprehensive automated end to end test suite covering the user journeys within the service. These use [cypress and are within the code base](https://github.com/ministryofjustice/opg-lpa/tree/main/cypress/e2e) and are run on every change as part of the continuous integration workflows for new changes. Merging of new change is only possible if the existing test suite continues to pass against the change and if reviewed by another team member.

Risks are tracked in a local team risk tracker and by the [OPG Digital Tech Leadership team at an OPG Digital Level](https://justiceuk.sharepoint.com/:x:/r/sites/OPGDigital/Shared%20Documents/OPG%20Digital/Security/OPG%20Digital%20Tech%20Risks%20-%20March%202025%20Update.xlsx?d=wc85a4873d3df4bfbb86378688c1e6d24&csf=1&web=1&e=GFerX7). Risks are fed back via delivery and service owner to portfolio.

## Releases

Releases are handled via GitHub Actions and use semantic versioning. [All releases and note are available within GitHub](https://github.com/ministryofjustice/opg-lpa/releases).
