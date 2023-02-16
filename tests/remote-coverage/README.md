Most of this was worked out from:

https://alejandrocelaya.blog/2022/02/12/capturing-remote-code-coverage-in-api-tests-with-phpunit/

To get remote code coverage working for service-front:

1. Set front-app.build.args.ENABLE_XDEBUG=1 in docker-compose.yml
2. Copy tests/remote-coverage/index.php to service-front/public/index.php (**DO NOT COMMIT THIS FILE!**)
3. Run the application as normal. For example, you could run cypress tests against it. Coverage data is automatically written into service-front/build/remote-coverage-php/merged.cov, in the PHP coverage format.
4. This isn't human readable, so to make it look nice do: `docker exec -it lpa-front-app php /app/tests/remote-coverage/coveragetohtml.php`. The resulting report is accessible via service-front/build/remote-coverage-html/index.html.
