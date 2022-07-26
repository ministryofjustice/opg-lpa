# Sirius gateway mock

Mock servers which together mimic the Sirius gateway API. This is a tool
for local testing of functionality which is otherwise difficult to test,
e.g. you want to fetch data from the gateway then simulate it going offline,
to check whether caching is working correctly.

It runs using [Prism](https://stoplight.io/open-source/prism), delivering
the API specified in the latest version of the
[Sirius Swagger doc](https://github.com/ministryofjustice/opg-sirius-api-gateway/blob/master/docs/swagger.v1.yaml).

A Prism instance (mocksirius) and an nginx instance (gateway) are spun up as
part of the docker-compose script in dev to enable lightweight testing of the
Make an LPA tool without a full Sirius stack.

This mock uses data which matches
[the data set up in the pre-prod Sirius instance](https://opgtransform.atlassian.net/wiki/spaces/LDS/pages/1289191456/Testing+Track+my+LPA+Status).
In turn, the Sirius pre-prod data corresponds with the seeding data used in
Make (see `seeding` directory in the root of this project).

Specifically, the IDs of X LPAs are mapped to examples added to the Sirius
Swagger YAML: see `swagger-examples.yaml` for the examples themselves. The
mapping goes from an LPA ID to an example name (see `nginx.conf`, which
contains the mapping). The example name is then used in the gateway nginx proxy
to add a `Prefer: example=<name>` header to requests, which are then forwarded
to the Prism proxy. This `Prefer` header
[instructs Prism to a return a particular Swagger example as a response](https://github.com/stoplightio/prism/blob/master/docs/guides/01-mocking.md#Response-Generation),
allowing us to predictably show a status for an LPA. Without this intermediate proxy,
Prism can only return a single baked response, or dynamic responses with
random data which varies for every request.

## Setup

The Sirius gateway mock is not enabled by default, but can be temporarily
switched on as follows:

1. Copy the gateway and mocksirius sections from the docker-compose.fragments.yml
file inside this directory into the docker-compose.yml file in the project root
directory.
2. Set the `OPG_LPA_PROCESSING_STATUS_ENDPOINT` environment variable for api-app in the
docker-compose.yml file in the project root to the value in docker-compose.fragments.yml,
so that the API points at the gateway server.
3. Run `make dc-up` as per usual.

## Viewing the examples

It's possible to invoke the mock endpoints using curl or similar as follows:

```
# Received
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A47629358836

# Rejected
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A88668805824

# Registered
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A68582508781

# Invalid
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A93348314693

# Withdrawn
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A43476377885

# Deleted 410 response returned which sets LPA as Waiting
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A97998888883

# Waiting (returns a 404 which Make front-end interprets as Waiting status, i.e.
# the LPA application has not yet been recorded on Sirius)
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/lpa-online-tool/lpas/A91155453023
```

The URLs above cause Prism to return examples with specific statuses, as shown.
Any other LPA ID (the last part of the path) will return a "Received" status.

## Working on the scripts

We use a script to add our own modifications to the Sirius Swagger file
(for now, at least). These require python3 plus some dependencies. If you're
working on these scripts (in the `scripts/` directory), follow these
instructions to set up your environment:

```
virtualenv -p python3 ~/venv/gatewaymock
source ~/venv/gatewaymock/bin/activate
pip install -r requirements.txt
```

You should now be able to work on the scripts in the `scripts/` directory.
