# Use shared network firewall

Date: 2026-01-01

## Status

Accepted

## Context

As part of our ongoing security updates we are moving all our services to use the shared network firewall. This allows us to restrict egress from the network at a fine grained level, only allowing known TLS based endpoints to be accessed from within the service (where the component has egress allowed by existing security groups).

When initially trialled it was found that a network firewall per service account was a costly option, so a shared network firewall within the  shared AWS account dev and prod accounts was created to keep the cost to a minimum.


## Consequences

The update to a shared network firewall meant we had to adjust internal network IPs across our services to avoid unexpected collisions and update to the new firewalled network module.

The egress management is in a shared location, so we need to balance the needs of the various products to ensure that restrictions in one product do not impact or create security concerns in another.

Coordination of the shared firewall configuration is part of the WebOps Community's ongoing alignment work.


