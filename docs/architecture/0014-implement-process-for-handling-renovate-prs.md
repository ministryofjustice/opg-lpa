# 0014. Implement process for handling Renovate PRs

Date: 2025-10-14

## Status

Accepted

## Context

We need a clear process for handling Renovate PRs. Our goals are to:

- Reduce duplicative effort
- Have no undue friction to merging changes
- Clearly document which upgrades are blocked and why

## Decision

We will implement the following workflow:

```mermaid
graph TD
    START(PR created) --> ASSIGN[Assign yourself to the PR]
    ASSIGN --> BUILDS{Is the PR building correctly?}
    BUILDS -->|Yes| MERGE[Merge PR]
        MERGE --> END(Done)
    BUILDS -->|No| TRIVIAL{Is it a trivial fix?}
    TRIVIAL --> |Yes| FIX[Fix it]
        FIX --> MERGE
    TRIVIAL --> |No| CANFIX{Is it possible to fix?}
    CANFIX --> |No| IGNORE[Ignore in renovate.json with explanation and expiry date]
        IGNORE --> END
    CANFIX --> |Yes| JIRA[Create ticket in Jira]
        JIRA --> PR_LINK[Add link to Jira ticket on PR]
        PR_LINK --> PR_LABEL[Add 'stop-updating' label to PR]
        PR_LABEL --> END
```

## Consequences

This workflow ensures everyone can see who has picked up the work, and introduces a clear workflow for documenting and tracking complicated updates in Jira. Simple updates be merged with minimal effort.

Expiry dates in renovate.json will need to be checked to avoid temporary ignores being forgotten about and made permanent.
