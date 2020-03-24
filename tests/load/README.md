# Refunds Load Testing

Using [Locust](https://github.com/locustio/locust) and [Real Browser Locust](https://github.com/nickboucart/realbrowserlocusts), we can execute a load test on the public front end.

We use [Faker](https://github.com/joke2k/faker) and some random number generation to create unique claims for each time locust runs a complete journey of the claim a refund public front end.

The load test script will refuse to run against protected environments, checking the target url.

### Install

Install python dependencies using pip

``` bash
pip install -r requirements.txt
```

For MacOs, also install the libev dependency using brew

``` bash
brew install libev
```

### Running load test

Start locust from the cli

``` bash
locust
```

Open the web ui in your browser http://localhost:8089

Select the number of total users to simulate, how many users to spawn each second, and provide the base url to target the tests for example `https://96-lpa3420.front.development.lpa.opg.service.justice.gov.uk` .

Clicking `Start Swarming` will start the load test.
