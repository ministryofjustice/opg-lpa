# 0010. Restructure Ops Infrastructure to support Multi Region, improving Disaster Recovery capabilities

Date: 29/04/2022

## Status

Accepted (retrospectively) :29/04/2022

## Context

This restructure is part of of work to support the disaster recovery aims of the project, and to improve the RTO and RPO capability.

By reworking the Ops infrastructure, this will decrease the time to recover significantly, as we will have in place a solution that can be deployed to another region as needed.

Recovery point objectives are met by an automated snapshot which is cross region and we would use this upon restoration of the service. the RPO is currently 15 minutes, based on the current understanding of how the cross region snapshot replication works.

The service needs to at least be capable of being brought up in a secondary region, should the primary region fail due to:

- Data centre outages.
- Natural disaster.
- Other uncontrollable events.

Thus meeting the requirements of DR capability.

We also wanted to ensure the structure is future proof in order to allow for changes in the DR strategy e.g. warm standby.

Note that whilst it is not a requirement to have a warm standby,  having the capability to provision on an as need basis is useful. A back up and restore with a turn-key provision via terraform is acceptable, but a small amount of other manual steps will have to be taken, which are unavoidable due to not being available via that route. We will look to minimize these as much as possible.

We estimate that this will allow us to provision in a matter of a couple of hours or less, well within the agreed times given to the business of 24 hours.

In any case, we currently have the paper version available to download on the .gov website as a fall back, but this change gives the service  an extra layer of resilience.

## Final Proposal

The service's infrastructure was structured to be a single region as follows:

```sh
terraform
 ┣ account  #shared account level resources
 ┣ environment #environment level resources, particularly to support ephemeral environments
```

Whilst this supports the multi environment nature of the dev account i.e. ephemeral environments, this did not at all support the service going into another region, as some resources are present at the account level - e.g. networking and caching, that are simply not cross region.

instead we add an additional level called `region` which supported shared infrastructure in a region, whilst keeping the other levels such:

```sh
terraform
 ┣ account #account level global resources only e.g. IAM roles
 ┣ region #resources shared that are required per region
 ┣ environment #environment level resources, particularly to support ephemeral environments
```

Whilst doing this we also restructure the code to be modularized, so that it could be called per region at the region and environment levels introducing the potential to have 2 regions at once. This requires some significant refactoring, and moving of state within terraform.

This work is to be based on the structure of one of our internal services, which needed to support multi region in the same way.

## Decision

- To implement the restructure, with associated refactoring
- To do this in an incremental fashion
- To ensure the live service is minimally impacted, by using scheduled down times whenever needed
- To carry out investigative works to see if we needed to change the DB infrastructure or upgrade to support this, weighing the cost and benefits of options.
- Once completed to a satisfactory level, incrementally test the approach and iterate as required to fix, script out and document approach in Development account *- to be completed*.
- Perform DR testing in pre-production with agreement with the team and business stakeholders *- to be done*.
- Perform a full DR failover test upon satisfactory testing and iteration of approach in previous steps. This will need agreement and downtime out of hours to ensure the approach works *- to be done*.

## Consequences

- We will need to ensure we have a fail-back strategy in place, in order to recover the service. this could mean we make the new region primary and old region secondary on fail over for longer. the implications of this need to be considered carefully.
- The business will need to be aware that the recovery will still take some time. As this service is not mission critical, but a tool used to aid applicants which can be done in paper form anyway, this shouldn't be a major issue.
- Complexity of build is increased as we have to change the CI process to cater for the additional level to be deployed, and modularization of the configuration.
- It will slow down the build process since the terraform deployment will have the additional `region` element to apply, which is in between the `account` and `environment` dependencies.
- We need to consider how to deal with one region being down, and unavailable, and how this affects the state file.
- There are some DR Scenarios we need to consider, where dependencies are also being recovered or made available in different region.
