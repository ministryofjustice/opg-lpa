# Performance platform

## Purpose

The performance platform collates performance
data for publication on data.gov.uk.

There are three chunks of data it collates:

1. Completion rate: The percentage of sessions which result in
a user successfully creating an LPA application. This is defined as
a session in which a user completes at least one journey from
the "create a new LPA" page through to being "created", i.e.
reaching the stage where the user specified "who is applying".
This data is currently derived from Google Analytics.
2. User satisfaction: At the end of a session, a user is shown
a form asking them how satisfied they were with the service.
The form for gathering this data is currently being
migrated to the Make an LPA tool from the central GDS feedback
system. Once the form has moved, this data will be stored in an
RDS database.
3. Digital take-up: This is based on the ratio of LPA applications
made electronically from a form printed by the Make an LPA tool
vs. forms acquired by other means. This data is stored in Sirius,
the back-end application which manages processing of LPA
applications (signed paper forms).

## Architecture

The Python code for this component is in service-perfplat/.

The logical containers within it are as follows:

![Performance platform logical containers](../images/perfplat_logical_containers.png)

This diagram is generated from [this Structurizr file](perfplat_v3_embedded.structurizr).

Additional information about the logical containers:

* The queue is a FIFO SQS queue named `perfplat-queue.fifo`. This is
created in dev by the local-config/start.sh script.

The code is organised as follows:

* bin/ contains scripts; these will become available without specifying
the path after you have installed the project with pip (see **Setup for dev**).
* perfplatcommon/ contains common code which is shared between logical
containers.
* perfplatworker/ contains code specific to the worker lambda.
* dev-proxy/ contains code specifically for running the proxy (see below).
* Dockerfile-worker is the dockerfile for the worker lambda, which is
deployed as a lambda from a docker image.
* Dockerfile-config is the dockerfile which sets up components required
in dev, such as the proxy.

### Proxy (dev only)

In local development, due to limitations of localstack (it's not possible
to deploy containers as lambdas or use SQS queues as event sources for
such lambdas), we have an additional proxy component deployed as an
AWS Lambda (see dev-proxy). This is hooked up to the SQS queue and forwards
events received from the queue to the worker lambda. In production, the
queue can be directly hooked up to the worker lambda and the proxy is not
required.

## Running perfplat

The performance platform components are run as part of the usual
docker-compose run:

```
# run with all 3rd party integrations (requires MoJ AWS config)
make dc-up

# run with limited 3rd party integrations (does not require MoJ AWS config)
make dc-up-out
```

Note that the Makefile creates a network specifically for handling
connections from the proxy to the worker lambda. This is because
the proxy is otherwise unable to "see" the worker in the DNS:
localstack starts fresh docker containers to run lambdas, isolated
from the infrastructure set up by docker-compose, and therefore
without access to the hostnames specified in docker-compose.yml.

## Setup for dev

Follow these steps to set up an environment to run the perfplat code:

1. `virtualenv -p python3 ~/.perfplat-venv`
2. `source ~/.perfplat-venv/bin/active`
3. `cd service-perfplat`
4. `python setup.py -e.[dev]` (note that if you're using zsh, you may need
to do `python setup.py -e.\[dev\]`)

This should install all the dependencies into your virtualenv, making
it possible to run the scripts in the project, including the **Manual testing**
scripts (see below).

## Manual testing

Testing is currently manual until more pieces of the component are in
place.

At present, it is possible to manually check that putting an event onto
the SQS queue triggers the worker lambda as follows:

1. Start the stack with `make dc-up` etc.
2. Open a separate console.
3. Use the provided script to send an event to the local
perfplat-queue.fifo SQS queue: `queuecli perfplat-queue.fifo send`
4. Check the docker-compose logs in the console where you started the
stack. You should see something like the following:

```
perfplatworker         | START RequestId: 38331752-2f8a-4529-9541-abe0f94fab95 Version: $LATEST
perfplatworker         | [DEBUG] 2021-07-15T15:46:41.504Z        38331752-2f8a-4529-9541-abe0f94fab95    {'Records': [{'body': '{"year": 2021, "month": 7}', 'receiptHandle': 'xsiujrzblmyqjvktkvsocacbzuhoqtgkhijyfresgrybywoqexgfauqhnjzsulvvlomtwelghipcpikksyylljubhixqkmrgoourfkhadyfmpvxlgmxjlygfmujsxsotoymgkfxrdfzsmnhuizemhryfnrtiuxbnyldahlztinhmfbyhvksldeqlc', 'md5OfBody': '66ee22256fdea01163339803112a3e7b', 'eventSourceARN': 'arn:aws:sqs:eu-west-1:000000000000:perfplat-queue.fifo', 'eventSource': 'aws:sqs', 'awsRegion': 'eu-west-1', 'messageId': '019c7ee2-9b10-9665-fdf8-81b0db951e69', 'attributes': {}, 'messageAttributes': {}, 'md5OfMessageAttributes': None, 'sqs': True}]}
perfplatworker         | END RequestId: 38331752-2f8a-4529-9541-abe0f94fab95
perfplatworker         | REPORT RequestId: 38331752-2f8a-4529-9541-abe0f94fab95  Duration: 16.25 ms      Billed Duration: 100 ms Memory Size: 3008 MB    Max Memory Used: 3008 MB
```

Note that the body of the message contains:

```
{"year": 2021, "month": 7}
```

This demonstrates that the message sent from our queuecli script
has reached the perfplat-queue.fifo queue. This has then triggered
the proxy lambda (perfplatworkerproxy). Finally, the proxy has
forwarded the event to the worker lambda (perfplatworker).

You should also see some logging from the proxy, which looks like this:

```
localstack_1           | 2021-07-15T15:46:41:DEBUG:localstack.services.awslambda.lambda_executors: Lambda arn:aws:lambda:eu-west-1:000000000000:function:perfplatworkerproxy result / log output:
localstack_1           | "GOT A RESPONSE"
localstack_1           | > START RequestId: 41fb1083-b893-19e8-aa7e-98d22be5c10d Version: $LATEST
localstack_1           | > [DEBUG]     2021-07-15T15:46:41.475Z        41fb1083-b893-19e8-aa7e-98d22be5c10d    http://perfplatworker:8080/2015-03-31/functions/function/invocations
localstack_1           | > [DEBUG]     2021-07-15T15:46:41.484Z        41fb1083-b893-19e8-aa7e-98d22be5c10d    Starting new HTTP connection (1): perfplatworker:8080
localstack_1           | > [DEBUG]     2021-07-15T15:46:41.511Z        41fb1083-b893-19e8-aa7e-98d22be5c10d    http://perfplatworker:8080 "POST /2015-03-31/functions/function/invocations HTTP/1.1" 200 42
localstack_1           | > [DEBUG]     2021-07-15T15:46:41.513Z        41fb1083-b893-19e8-aa7e-98d22be5c10d    "MESSAGE RECEIVED WITH DB CONNECTION MADE"
localstack_1           | > END RequestId: 41fb1083-b893-19e8-aa7e-98d22be5c10d
localstack_1           | > REPORT RequestId: 41fb1083-b893-19e8-aa7e-98d22be5c10d  Init Duration: 467.22 ms        Duration: 52.31 ms      Billed Duration: 53 ms  Memory Size: 1536 MB    Max Memory Used: 30 MB
```

The exact content will change over time as we refine the responses.
However, the key is that we see log output from perfplatworkerproxy,
and a report of an HTTP request going from it to perfplatworker:8080,
indicating that forwarding to the worker is functional.

It is possible to customise the payload sent in messages to the
queue, e.g.

```
queuecli perfplat-queue.fifo send --payload '{"month": 1, "year": 2020}'
```

This should be useful when testing this data flow in future.