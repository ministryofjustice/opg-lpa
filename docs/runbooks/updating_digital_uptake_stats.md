# Updating the Digital Uptake stats

For the moment this is a manual process.

Clone the repo https://github.com/ministryofjustice/opg-performance-data or pull to update from main if you already have it.

Make a new branch.

Edit the file src/_data/make_a_lasting_power_of_attorney_service/data.json

In this json file can be found elements of this form :

```
 {
    "_timestamp": "2026-04-01T00:00:00+00:00",
    "service": "make-a-lpa",
    "channel": "digital",
    "count": "41",
    "dataType": "digital-take-up",
    "period": "month"
 },
```

You probably have been given 2 or 3 figures by the Product Manager.
The first is likely an update to an existing monthly figure .
The others likely require new entries in the json.

Push and make a PR in the repo , get someone to review it, then it will auto-merge.

If you make a typo, the CI build for the PR will fail quickly and obviously , enabling you to correct it
