# opg-lpa
The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.


## Local Development Setup

The first time you bring up the environment:

```
git clone git@github.com:ministryofjustice/opg-lpa.git
cd opg-lpa

docker-compose run front-composer
docker-compose run admin-composer
docker-compose run api-composer
docker-compose run pdf-composer

docker-compose up
```

After the first time, you bring up the environment with:
```
docker-compose up
```

