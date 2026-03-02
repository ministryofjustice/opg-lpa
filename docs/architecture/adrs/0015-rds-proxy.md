# Use RDS Proxy as a connection pooling tool

Date: 2025-10-01

## Status

Accepted (replaces ADR 0013)

## Context

In [ADR 0013](0013-connection-pooling.md) we moved to a connection pooling model to reduce overheads in connecting to the database that had performance impacts. However, some time after the move to pgbouncer bitnami changed its support model, meaning that the docker sidecar container we used would no longer be receiving security updates past its final release.

As a result we had to quickly move to an alternative. We chose RDS proxy as it aligned eith our general AWS/Terraform tooling.

## Consequences

We will need to support the network and security group changes for the RDS proxy.

We will need to provide appropriate secrets access in the proxy to allow connectivity.

RDS proxy is slightly slower than the pgbouncer sidecar to become ready, which may have an impact on deploy times.
