# 0004. Implement load testing

Date: 2021-09-03

## Status

* Accepted (2021-09-01)

## Context

We needed to test parallel login of users to ensure that
session data isn't accidentally shared between users
(see [decision 0002](./0002-custom-save-handler-in-service-front.md)).

Also, Make an LPA currently has no load tests. These are useful
for capacity planning and finding bottlenecks which may be
causing errors for users on the site. While we are auto-scaling,
we don't have a way to verify that it is sufficient to manage
expected load, which load testing can provide.

Load testing can also provide a benchmark we can use to ensure
that any new work we do does not degrade performance of the stack
as a whole.

## Decision

Use [locust.io](https://locust.io/) to test parallel logins.

While there are alternatives to locust, it is implemented in Python
(our chosen language going forward), has an intuitive API, and some
members of the team have experience with it already (and can reuse
previously-written code).

Load tests will be added to the tests/load directory in the project,
as they are not component-specific and apply to the whole stack.

Doing the above has the happy side effect of opening up the possibility of
load testing the whole application stack in future.

Initially, this will only run locally and is not integrated into
the CI pipeline. We will consider extending this testing into CI
in future.

## Consequences

Cons:

* Additional code to maintain.
* Not browser-driven, so can be difficult to properly simulate
  what happens when a user logs in (e.g. this ignores Ajax
  requests by default).

Pros:

* Can test scenarios which are difficult with cypress, which
  tends to focus on a single user journey.
* Can be applied to load test whole stack in future.
