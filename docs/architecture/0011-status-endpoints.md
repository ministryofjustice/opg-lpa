# 0011. Status endpoints and healthchecks

Date: 2022-11-15

## Status

* Proposed (2022-11-15)

## Context

We have various endpoints for monitoring the health of the main site and its dependencies. Some of these don't appear to be used at all, while some are used (and useful). This section attempts to gather together everything we know about these endpoints, how they are currently used, and where they have gaps in their coverage.

### Current endpoints

The Make an LPA service has these public endpoints which describe the state of the service:

* Make frontend /ping - https://lastingpowerofattorney.gov.uk/ping - returns HTML showing the status of dependencies; at the time of writing: Redis (for session data), our own Make API (see below), and DynamoDB (for the system message); **if any dependency is not OK, the status for the whole Make service is marked as not OK**. Note that the data shown for the Make API comes from the /ping endpoint on that API.
  Used for manual checks.
* Make frontend /ping/json - https://lastingpowerofattorney.gov.uk/ping/json - returns a JSON equivalent of the /ping endpoint.
  Used for manual checks.
* Make frontend /ping/elb - https://lastingpowerofattorney.gov.uk/ping/elb - returns a 200 code and a short piece of text
  Not used.
* Make front end /ping/pingdom - https://lastingpowerofattorney.gov.uk/ping/pingdom - returns an XML equivalent (but simplified) of the /ping endpoint, in [the pingdom custom HTTP check XML format](https://www.pingdom.com/blog/new-pingdom-feature-custom-monitoring-type/).
  Used by pingdom.
* Make frontend nginx /robots.txt - http://`<front>`/robots.txt - not a status page as such, but used as one.
  Used by AWS ELBs.

We also have the following status pages which are only accessible internally:

* Make API /ping - http://`api`:7001/ping - returns JSON showing the status of dependencies; at the time of writing: database, Sirius gateway (for tracking LPA statuses), and SQS queue (for PDF generation jobs); **if any dependency is not OK, the status for the whole API is marked as not OK**.
  Used by the Make frontend /ping; the results are merged with the other status checks made by the front end.
* Make API /ping/elb - http://`api`:7001/ping/elb - returns a 200 code and a short piece of text.
  Not used.
* Make front php (runs the front end application PHP code) - fcgi://front-php/health (php-fpm socket).
  Used by the ELBs.
* Make API php (runs the API application PHP code) - fcgi://api-php/health (php-fpm socket).
  Used by the ELBs.

(`front` and `api` are placeholders for the full AWS domain names.)

For reference, these endpoints are accessible locally in dev:

* frontend endpoints are at https://localhost:7002/ping etc.
* api endpoints are at http://localhost:7001/ping etc.

### Current healthchecks

The following healthchecks monitor the health of the service:

* AWS Elastic Load Balancer *checks* service-front Nginx container (front-web)
  * Type: HTTP GET
  * Endpoint: /robots.txt (uses a resource on the front end site rather than any of the dedicated endpoints)
  * Interval: 30 seconds
  * Timeout: 5 seconds
  * Healthy: 3 consecutive HTTP 200 responses
  * Unhealthy: 3 consecutive failures
  * Failure conditions: Network failure, non-200 response
  * Failure action: Traffic no longer routed to container unlesss all containers are failing health checks.
  * Implemntation details: robots.txt is hosted on the nginx container and is called by the ELB.
* AWS Elastic Container Service *checks* PHP-FPM containers (front-app, api-app)
  * Type: FCGI Connect
  * Endpoint: /health (Make front php, Make API php)
  * Interval: 10 seconds
  * Timeout: 15 seconds
  * Healthy: 1 pass
  * Unhealthy: 3 Failures
  * Failure conditions: Unexpected response from :9000 or timeout
  * Failure action: Restart containers
  * Implementation details: ECS calls `/usr/local/bin/health-check.sh` within the container to perform this check. In effect, a script on the container checks that
    the fcgi endpoint is up and running
* Pingdom *checks* http://www.lastingpowerofattorney.service.gov.uk/
  * Type: HTTP GET
  * Endpoint: / (uses the home page rather than a status endpoint)
  * Interval: 1 minute
  * Timeout: 30 seconds
  * Healthy: 1 pass
  * Unhealthy: 1 pass
  * Failure conditions: Timeout / non-200 response
  * Failure action: Generate alert
  * Implementation details: Hits the HTTP endpoint and gets redirected to HTTPS by rewrite rule on ELB.
* Pingdom *checks* https://www.lastingpowerofattorney.service.gov.uk/ping/pingdom
  * Type: HTTP GET
  * Endpoint: /ping/pingdom (the only healthcheck which uses any of the ping pages)
  * Interval: 1 minute
  * Timeout: 30 seconds
  * Healthy: 1 pass
  * Unhealthy: 1 pass
  * Failure conditions: Timeout / non-200 response
  * Failure action: Generate alert
  * Implementation details: Expects a 200 response. 200 will only be returned if API, Redis, database, and Sirius gateway are all working.
* Make team members *manually check* https://www.lastingpowerofattorney.service.gov.uk/ping (and /ping/json)
  * Type: HTTP GET (through a browser, possibly when an issue is detected on the live site)
  * Ad hoc. The human-readable /ping endpoint can be used to triage issues with the live site. However, it doesn't give much useful information about what might be happening. The /ping/json endpoint could be slightly more useful in some situations, but doesn't lend itself to being read by people.

### Dependencies

Our service has the following dependencies between components:

```
front-app
  dynamodb (used for system messages)
  Redis (session storage)
  * Ordnance Survey (used for postcode lookups)
  * Notify (used to send emails to users)
  api (through api-web; read/write data for users, LPAs etc.)
    RDS/Postgres (database)
    Sirius API gateway (for showing statuses of LPA applications)
    * S3 (for storing generated PDFs)
    SQS (for posting PDF generation jobs)

* admin-app (has no status page)
  dynamodb (read/write system message)
  api (through api-web; read feedback and user data)
```

`*` = currently not included in status checks

The existing status points don't cover all of the dependencies of our service: those marked with `*` don't appear on any status pages.

The admin UI doesn't have a status page, though this is less critical to citizens.

### Other notes

* There is an [OPG status page](https://theofficeofthepublicguardian.statuspage.io/). The uptime shown here is based only on incidents reported for a site and is not based on automated checks.
* pingdom has a [status page for OPG service uptime](http://stats.pingdom.com/k0oru282ot2o). This is based on calls to / (listed as "Lasting Power of Attorney - Homepage") and /ping/pingdom (listed as "Lasting Power of Attorney - Ping"). Note that the main check to the Make site ("Homepage") may have higher uptime than the check to the /ping/pingdom endpoint ("Ping"), as the latter is considered "down" whenever Sirius is not available.

## Proposal

The proposal is to increase coverage of these endpoints, remove unused ones, reduce the maintenance of the remaining ones, and provide finer-grained status checks. This should help us diagnose problems more quickly, as well as give us a better idea of when our site is actually unusable, rather than when its dependencies (especially Sirius gateway) are down.

1.  These endpoints aren't used and should be removed:
    * **Make API /ping/elb**
2.  We should merge these multiple endpoints which have different paths. Instead, we'd use a single path and vary the response content type depending on the client's Accept header:
    * **Make frontend /ping**, **Make frontend /ping/json**, **Make frontend /ping/pingdom** *should be merged into* **Make frontend /ping**
    This would leave us with the following status endpoints:
    * **Make frontend /ping**
    * **Make API /ping**
    * **Make frontend nginx /robots.txt** (requires no changes)
    * **Make front php** (requires no changes)
    * **Make API php** (requires no changes)
3.  We should extend the remaining endpoints to cover dependencies which aren't checked at the moment:
    * **Make frontend /ping** *should also check* (1) Ordnance Survey; (2) Notify
    * **Make API /ping** *should also check* S3
    As these are 3rd party services, we need to be careful about how often we ping them. Terms of service etc. need to be considered. We may also need to investigate which type of request we should make.
4.  As we are now querying more endpoints, we should attempt to *parallelise the queries* to dependencies. This should enable the endpoints to respond in a more timely fashion.
5.  We should modify the response format from the /ping endpoints so that it is easier to read and conveys finer-grained detail about the state of the system. [This RFC for health check response formats](https://datatracker.ietf.org/doc/html/draft-inadarei-api-health-check-02) could be used as a basis for this rework.
    One key aspect of this is being able to discriminate between a completely healthy system (all dependencies are fine); a degraded system (some dependencies like Sirius Gateway are unavailable, but the service is still usable as we cache Sirius responses); and an unhealthy system (critical dependencies like the API are unavailable). This is not possible with the current `"ok": true|false` response.
6.  We should modify the status codes returned by the **Make frontend /ping** endpoint, to enable it to be used by pingdom for healthchecks:
    * 200: for a healthy or degraded response
    * 503: for an unhealthy response; note that it is perfectly acceptable to return an HTML page explaining the problem alongside a 503 code
    (Pingdom will treat anything other than a 200 or 300 response as a failure.)
7.  Once we've done the above, we should *change the pingdom healthchecks* to point at the new endpoints:
    * Pingdom *checks* http://www.lastingpowerofattorney.service.gov.uk/ - requires no changes
    * Pingdom *checks* https://www.lastingpowerofattorney.service.gov.uk/ping/pingdom *should now make a GET request to*
      https://www.lastingpowerofattorney.service.gov.uk/ping. This page will return a 200 if the service is healthy or degraded, or a 503 if the service is unhealthy.
8.  We should *add a status endpoint to service-admin*, as it doesn't currently have one. This will need to check connectivity to our API. This is low priority, as the admin UI is only used internally, and only depends on our own API. It becomes clear quite quickly if this is not available.

## Decision

We should improve the status endpoints so they are easier to maintain, less redundant, more comprehensive, and therefore more useful.

## Consequences

Possible disruption as we modify the endpoints. However, because we are only really rationalising one endpoint which is used externally (by Pingdom), this should be minimal. It will also not impact users, as the healthchecks we carry out do not affect the service to the public, only our uptime statistics.

We will be better able to understand when our service is down because of our own components.

We will open up the possibility of being able to monitor how often our service is degraded but still usable, which we can't do at the moment.

In future, status checks could be made more comprehensive by checking the format of the response returned by each dependency. For example, we could confirm that the OpenAPI specification provided by the Sirius gateway matches the response format we are expecting; or we that the response from the Ordnance Survey postcode lookup matches our expectations.
