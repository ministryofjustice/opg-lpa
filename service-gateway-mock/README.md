# Gateway mock

Mock server which mimics the Sirius gateway API.

Runs using [Prism](https://stoplight.io/open-source/prism), delivering
the API specified in the latest version of the
[Sirius Swagger doc](https://github.com/ministryofjustice/opg-sirius-api-gateway/blob/master/docs/swagger.v1.yaml).

An instance is spun up as part of the docker-compose script in dev to enable
lightweight testing of the Make an LPA tool without a full Sirius stack.
